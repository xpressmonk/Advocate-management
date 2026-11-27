<?php
namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Document;
use App\Models\LegalCase;
use App\Services\GoogleDriveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class DocumentController extends Controller
{
    private const MAX_FILE_SIZE_KB = 20480;
    private const ALLOWED_EXTENSIONS = 'pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt';

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'case_id' => ['nullable', 'integer', 'exists:cases,id'],
            'client_id' => ['nullable', 'integer', 'exists:clients,id'],
        ]);

        $documents = Document::with([
            'case:id,case_number,title', 'client:id,name', 'uploader:id,name',
        ])->latest();

        if (isset($filters['case_id'])) $documents->where('case_id', $filters['case_id']);
        if (isset($filters['client_id'])) $documents->where('client_id', $filters['client_id']);

        return view('documents.index', [
            'documents' => $documents->paginate(20)->withQueryString(),
            'cases' => LegalCase::orderBy('case_number')->get(['id', 'case_number', 'title']),
            'clients' => Client::orderBy('name')->get(['id', 'name']),
            'selectedCaseId' => $filters['case_id'] ?? null,
            'selectedClientId' => $filters['client_id'] ?? null,
        ]);
    }

    public function store(Request $request, GoogleDriveService $drive): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'case_id' => ['nullable', 'required_without:client_id', 'integer', 'exists:cases,id'],
            'client_id' => ['nullable', 'required_without:case_id', 'integer', 'exists:clients,id'],
            'file' => ['required', 'file', 'max:'.self::MAX_FILE_SIZE_KB,
                'mimes:'.self::ALLOWED_EXTENSIONS, 'extensions:'.self::ALLOWED_EXTENSIONS],
        ]);
        $validator->after(function ($validator) use ($request): void {
            if ($request->filled('case_id') && $request->filled('client_id')) {
                $message = 'Attach the document to either a case or a client, not both.';
                $validator->errors()->add('case_id', $message);
                $validator->errors()->add('client_id', $message);
            }
        });
        $validated = $validator->validate();
        $file = $request->file('file');

        if (!empty($validated['case_id'])) {
            $case = LegalCase::findOrFail($validated['case_id']);
            try {
                $uploaded = $drive->uploadCaseDocument($case, $file);
                Document::create([
                    'case_id' => $case->id,
                    'uploaded_by' => $request->user()->id,
                    'original_name' => $uploaded['name'],
                    'path' => 'google-drive/'.$uploaded['id'],
                    'google_drive_file_id' => $uploaded['id'],
                    'storage_provider' => 'google_drive',
                    'mime_type' => $uploaded['mime_type'],
                    'size' => $uploaded['size'],
                ]);
            } catch (Throwable $exception) {
                report($exception);
                return back()->withInput()->with('error', 'Google Drive upload failed. Confirm that Drive is connected and try again.');
            }
            return redirect()->route('documents.index', ['case_id' => $case->id])
                ->with('success', 'Case document uploaded to Google Drive.');
        }

        // Client-only documents remain on the existing private local disk.
        $disk = Storage::disk('private_documents');
        $path = $file->store('documents', 'private_documents');
        if (!$path) throw new \RuntimeException('The uploaded document could not be stored.');

        try {
            Document::create([
                'client_id' => $validated['client_id'],
                'uploaded_by' => $request->user()->id,
                'original_name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
                'path' => $path,
                'storage_provider' => 'local',
                'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                'size' => $file->getSize(),
            ]);
        } catch (Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }

        return redirect()->route('documents.index', ['client_id' => $validated['client_id']])
            ->with('success', 'Client document uploaded.');
    }

    public function download(Document $document, GoogleDriveService $drive)
    {
        if ($document->storage_provider === 'google_drive') {
            try {
                $response = $drive->download($document->google_drive_file_id);
                $stream = $response->getBody();
                return response()->streamDownload(
                    static function () use ($stream): void {
                        while (!$stream->eof()) echo $stream->read(1024 * 1024);
                    },
                    $document->original_name,
                    [
                        'Content-Type' => $document->mime_type ?: 'application/octet-stream',
                        'Cache-Control' => 'private, no-store',
                        'X-Content-Type-Options' => 'nosniff',
                    ]
                );
            } catch (Throwable $exception) {
                report($exception);
                abort(502, 'Could not retrieve this document from Google Drive.');
            }
        }

        $disk = Storage::disk('private_documents');
        abort_unless($disk->exists($document->path), 404, 'Document file not found.');
        return $disk->download($document->path, $document->original_name, [
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => 'sandbox',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Document $document, GoogleDriveService $drive): RedirectResponse
    {
        $filters = array_filter([
            'case_id' => $document->case_id,
            'client_id' => $document->client_id,
        ], static fn ($value): bool => $value !== null);

        try {
            if ($document->storage_provider === 'google_drive') {
                $drive->delete($document->google_drive_file_id);
            }
            $document->delete();
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', 'Document could not be deleted. Please retry or contact an administrator.');
        }

        return redirect()->route('documents.index', $filters)->with('success', 'Document deleted.');
    }
}

@extends('layouts.app')

@section('heading', 'Documents')

@section('content')
    <div class="mb-5">
        <h1 class="text-2xl font-bold">Documents</h1>
        <p class="mt-1 text-sm text-slate-600">Case documents are stored in the firm's private Google Drive. Client-only documents remain in private local storage.</p>
    </div>

    <section class="card mb-6">
        <h2 class="mb-2 text-lg font-bold">Upload a document</h2>
        <p class="mb-4 text-sm text-slate-600">Attach each file to exactly one case or client. Maximum size: 20 MB. Case documents go to Google Drive.</p>
        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
            @csrf
            <label class="block text-sm">
                Case
                <select class="field mt-1" name="case_id">
                    <option value="">No case selected</option>
                    @foreach($cases as $case)
                        <option value="{{ $case->id }}" @selected((string) $selectedCaseId === (string) $case->id)>
                            {{ $case->case_number }} - {{ $case->title }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm">
                Client
                <select class="field mt-1" name="client_id">
                    <option value="">No client selected</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" @selected((string) $selectedClientId === (string) $client->id)>
                            {{ $client->name }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label class="block text-sm md:col-span-2">
                File (PDF, Word, Excel, JPG, PNG, or TXT)
                <input class="field mt-1" type="file" name="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.txt" required>
            </label>
            <div class="md:col-span-2">
                <button class="btn primary" type="submit">Upload document</button>
            </div>
        </form>
    </section>

    <section class="card overflow-x-auto">
        <h2 class="mb-3 text-lg font-bold">Stored documents</h2>
        <table class="table w-full text-sm">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Attached to</th>
                    <th>Type</th>
                    <th>Size</th>
                    <th>Uploaded by</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $document)
                    <tr>
                        <td>{{ $document->original_name }}</td>
                        <td>
                            @if($document->case)
                                Case {{ $document->case->case_number }}
                            @else
                                Client {{ $document->client?->name }}
                            @endif
                        </td>
                        <td>{{ strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION)) }}</td>
                        <td>{{ number_format($document->size / 1048576, 2) }} MB</td>
                        <td>{{ $document->uploader?->name ?? 'Unknown' }}</td>
                        <td class="whitespace-nowrap">
                            <a class="text-teal-700" href="{{ route('documents.download', $document) }}">Download</a>
                            @if(\App\Support\Roles::can((string) (auth()->user()->role ?: 'staff'), 'delete'))
                                <span class="px-1 text-slate-400">|</span>
                                <form class="inline" method="POST" action="{{ route('documents.destroy', $document) }}" onsubmit="return confirm('Permanently delete this document?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="text-red-600" type="submit">Delete</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6">No documents uploaded.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="mt-4">{{ $documents->links() }}</div>
    </section>
@endsection

<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Document;
use App\Models\LegalCase;
use App\Models\User;
use App\Services\GoogleDriveService;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('private_documents');

        $drive = \Mockery::mock(GoogleDriveService::class);
        $drive->shouldReceive('isConnected')->andReturn(false);
        $drive->shouldReceive('uploadCaseDocument')->andReturnUsing(function ($case, $file) {
            $this->uploadedCaseFileContents = $file->get();
            return [
                'id' => 'mock-drive-file-id',
                'name' => basename($file->getClientOriginalName()),
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'size' => $file->getSize(),
            ];
        });
        $drive->shouldReceive('download')->andReturnUsing(function () {
            return new Response(200, [], $this->uploadedCaseFileContents ?? '');
        });
        $drive->shouldReceive('delete')->byDefault();
        $this->app->instance(GoogleDriveService::class, $drive);
    }

    public function test_authenticated_user_can_upload_and_download_a_private_case_document(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        $file = UploadedFile::fake()->createWithContent('petition.pdf', "%PDF-1.4\nPrivate case file");

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'case_id' => $case->id,
            'client_id' => '',
            'file' => $file,
        ]);

        $response->assertRedirect(route('documents.index', ['case_id' => $case->id]));
        $this->assertDatabaseCount('documents', 1);

        $document = Document::firstOrFail();
        $this->assertSame($case->id, $document->case_id);
        $this->assertNull($document->client_id);
        $this->assertSame($user->id, $document->uploaded_by);
        $this->assertSame('google_drive', $document->storage_provider);
        $this->assertSame('mock-drive-file-id', $document->google_drive_file_id);

        $this->get(route('documents.index', ['case_id' => $case->id]))
            ->assertOk()
            ->assertSee('Stored documents')
            ->assertSee('petition.pdf');

        $this->get(route('documents.download', $document))
            ->assertOk()
            ->assertHeaderContains('Content-Disposition', 'attachment')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeaderContains('Cache-Control', 'private')
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertStreamedContent("%PDF-1.4\nPrivate case file");

        $otherUser = User::create([
            'name' => 'Another User',
            'email' => 'another@example.com',
            'password' => 'test-password',
            'role' => 'admin',
        ]);
        $this->actingAs($otherUser)
            ->get(route('documents.download', $document))
            ->assertOk();

        $this->get('/storage/'.$document->path)->assertNotFound();
    }

    public function test_documents_can_be_attached_to_a_client(): void
    {
        $user = $this->createUser();
        $client = $this->createClient();

        $this->actingAs($user)->post(route('documents.store'), [
            'case_id' => '',
            'client_id' => $client->id,
            'file' => UploadedFile::fake()->createWithContent('identity.txt', 'Client identity document'),
        ])->assertRedirect(route('documents.index', ['client_id' => $client->id]));

        $document = Document::firstOrFail();
        $this->assertSame($client->id, $document->client_id);
        $this->assertNull($document->case_id);
        Storage::disk('private_documents')->assertExists($document->path);
    }

    public function test_case_and_client_detail_pages_link_to_their_documents(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        $client = Client::findOrFail($case->client_id);

        $this->actingAs($user)
            ->get(route('cases.show', $case))
            ->assertOk()
            ->assertSee('Manage documents')
            ->assertSee(route('documents.index', ['case_id' => $case->id]));

        $this->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Manage documents')
            ->assertSee(route('documents.index', ['client_id' => $client->id]));
    }

    public function test_upload_requires_exactly_one_parent_and_a_supported_file(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        $client = $this->createClient();

        $this->actingAs($user)->post(route('documents.store'), [
            'case_id' => $case->id,
            'client_id' => $client->id,
            'file' => UploadedFile::fake()->create('payload.exe', 10, 'application/x-msdownload'),
        ])->assertSessionHasErrors(['case_id', 'client_id', 'file']);

        $this->assertDatabaseCount('documents', 0);
        Storage::disk('private_documents')->assertDirectoryEmpty('documents');
    }

    public function test_document_access_requires_authentication(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
        $this->post(route('documents.store'))->assertRedirect(route('login'));

        $case = $this->createCase();
        $path = 'documents/private-file.pdf';
        Storage::disk('private_documents')->put($path, '%PDF-1.4');
        $document = Document::create([
            'case_id' => $case->id,
            'original_name' => 'private-file.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 8,
        ]);

        $this->get(route('documents.download', $document))->assertRedirect(route('login'));
        $this->delete(route('documents.destroy', $document))->assertRedirect(route('login'));
    }

    public function test_deleting_a_case_removes_its_private_documents(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();
        $path = 'documents/case-file.pdf';
        Storage::disk('private_documents')->put($path, '%PDF-1.4');
        $document = Document::create([
            'case_id' => $case->id,
            'uploaded_by' => $user->id,
            'original_name' => 'case-file.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 8,
        ]);

        $case->delete();

        $this->assertDatabaseMissing('documents', ['id' => $document->id]);
        Storage::disk('private_documents')->assertMissing($path);
    }

    public function test_upload_rejects_files_over_ten_megabytes(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();

        $this->actingAs($user)->post(route('documents.store'), [
            'case_id' => $case->id,
            'client_id' => '',
            'file' => UploadedFile::fake()->create('oversized.pdf', 20481, 'application/pdf'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseCount('documents', 0);
        Storage::disk('private_documents')->assertDirectoryEmpty('documents');
    }

    public function test_upload_accepts_a_file_at_the_ten_megabyte_limit(): void
    {
        $user = $this->createUser();
        $case = $this->createCase();

        $this->actingAs($user)->post(route('documents.store'), [
            'case_id' => $case->id,
            'client_id' => '',
            'file' => UploadedFile::fake()->create('limit.pdf', 20480, 'application/pdf'),
        ])->assertRedirect(route('documents.index', ['case_id' => $case->id]));

        $this->assertDatabaseCount('documents', 1);
        $this->assertSame('google_drive', Document::firstOrFail()->storage_provider);
    }

    private function createUser(): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'test-password',
        ]);
    }

    private function createClient(): Client
    {
        return Client::create([
            'name' => 'Test Client',
            'phone' => '0123456789',
        ]);
    }

    private function createCase(): LegalCase
    {
        return LegalCase::create([
            'case_number' => 'TEST-001',
            'title' => 'Test Case',
            'client_id' => $this->createClient()->id,
            'case_type' => 'Civil',
        ]);
    }
}

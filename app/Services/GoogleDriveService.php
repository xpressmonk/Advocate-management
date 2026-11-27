<?php
namespace App\Services;
use App\Models\GoogleDriveConnection;
use App\Models\LegalCase;
use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class GoogleDriveService
{
    private function client(): Client
    {
        $path = storage_path('app/private/google/oauth-client.json');
        if (!is_file($path)) throw new RuntimeException('Google OAuth file missing: storage/app/private/google/oauth-client.json');
        $client = new Client();
        $client->setAuthConfig($path);
        $client->setRedirectUri(config('services.google_drive.redirect_uri'));
        $client->setAccessType('offline');
        $client->setPrompt('consent');
        $client->setIncludeGrantedScopes(true);
        $client->addScope(Drive::DRIVE_FILE);
        return $client;
    }

    public function authorizationUrl(string $state): string
    {
        $client = $this->client();
        $client->setState($state);
        return $client->createAuthUrl();
    }

    public function connect(string $code): void
    {
        $client = $this->client();
        $token = $client->fetchAccessTokenWithAuthCode($code);
        if (isset($token['error'])) throw new RuntimeException($token['error_description'] ?? 'Google authorization failed.');
        $connection = GoogleDriveConnection::first() ?? new GoogleDriveConnection();
        $oldToken = $connection->token ?? [];
        if (empty($token['refresh_token']) && !empty($oldToken['refresh_token'])) $token['refresh_token'] = $oldToken['refresh_token'];
        if (empty($token['refresh_token'])) throw new RuntimeException('Google did not return a refresh token. Revoke this app in your Google Account and connect again.');
        $connection->token = $token;
        $connection->save();
        $this->ensureRootFolder();
    }

    private function drive(): Drive
    {
        $connection = GoogleDriveConnection::first();
        if (!$connection || empty($connection->token)) throw new RuntimeException('Google Drive is not connected.');
        $client = $this->client();
        $token = $connection->token;
        $client->setAccessToken($token);
        if ($client->isAccessTokenExpired()) {
            $refresh = $token['refresh_token'] ?? null;
            if (!$refresh) throw new RuntimeException('Google Drive authorization expired. Reconnect the account.');
            $new = $client->fetchAccessTokenWithRefreshToken($refresh);
            if (isset($new['error'])) throw new RuntimeException('Google token refresh failed. Reconnect the account.');
            $token = array_merge($token, $new, ['refresh_token' => $refresh]);
            $connection->token = $token;
            $connection->save();
            $client->setAccessToken($token);
        }
        return new Drive($client);
    }

    public function ensureRootFolder(): string
    {
        $connection = GoogleDriveConnection::firstOrFail();
        if ($connection->root_folder_id) return $connection->root_folder_id;
        $folder = new DriveFile([
            'name' => config('services.google_drive.root_folder_name', 'Advocate Management'),
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);
        $created = $this->drive()->files->create($folder, ['fields' => 'id']);
        $connection->root_folder_id = $created->id;
        $connection->save();
        return $created->id;
    }

    public function createCaseFolder(LegalCase $case): string
    {
        if ($case->google_drive_folder_id) return $case->google_drive_folder_id;
        $folder = new DriveFile([
            'name' => $case->case_number.' - '.$case->title,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$this->ensureRootFolder()],
        ]);
        $created = $this->drive()->files->create($folder, ['fields' => 'id']);
        $case->forceFill(['google_drive_folder_id' => $created->id])->save();
        return $created->id;
    }

    public function uploadCaseDocument(LegalCase $case, UploadedFile $file): array
    {
        $driveFile = new DriveFile([
            'name' => basename(str_replace('\\', '/', $file->getClientOriginalName())),
            'parents' => [$this->createCaseFolder($case)],
        ]);
        $created = $this->drive()->files->create($driveFile, [
            'data' => file_get_contents($file->getRealPath()),
            'mimeType' => $file->getMimeType() ?: 'application/octet-stream',
            'uploadType' => 'multipart',
            'fields' => 'id,name,mimeType,size',
        ]);
        return [
            'id' => $created->id,
            'name' => $created->name,
            'mime_type' => $created->mimeType ?: 'application/octet-stream',
            'size' => (int)($created->size ?? $file->getSize()),
        ];
    }

    public function download(string $fileId)
    {
        return $this->drive()->files->get($fileId, ['alt' => 'media']);
    }

    public function delete(string $fileId): void
    {
        $this->drive()->files->delete($fileId);
    }

    public function isConnected(): bool
    {
        return GoogleDriveConnection::query()->whereNotNull('root_folder_id')->exists();
    }
}

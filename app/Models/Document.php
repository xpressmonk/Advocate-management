<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class Document extends Model
{
    protected $fillable = [
        'case_id', 'client_id', 'uploaded_by', 'original_name', 'path',
        'mime_type', 'size', 'google_drive_file_id', 'storage_provider',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Document $document): void {
            if ($document->storage_provider === 'google_drive') return;
            $disk = Storage::disk('private_documents');
            if ($disk->exists($document->path) && !$disk->delete($document->path)) {
                throw new RuntimeException('The stored document could not be deleted.');
            }
        });
    }

    public function case() { return $this->belongsTo(LegalCase::class, 'case_id'); }
    public function client() { return $this->belongsTo(Client::class); }
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}

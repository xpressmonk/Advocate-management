<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GoogleDriveConnection extends Model
{
    protected $fillable = ['token', 'root_folder_id'];
    protected function casts(): array { return ['token' => 'encrypted:array']; }
}

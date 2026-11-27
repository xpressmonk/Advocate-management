<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('cases', function (Blueprint $table): void {
            $table->string('google_drive_folder_id')->nullable()->unique();
        });
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('google_drive_file_id')->nullable()->unique();
            $table->string('storage_provider', 20)->default('local')->index();
        });
        Schema::create('google_drive_connections', function (Blueprint $table): void {
            $table->id();
            $table->longText('token');
            $table->string('root_folder_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('google_drive_connections');
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex(['storage_provider']);
            $table->dropUnique(['google_drive_file_id']);
            $table->dropColumn(['google_drive_file_id', 'storage_provider']);
        });
        Schema::table('cases', function (Blueprint $table): void {
            $table->dropUnique(['google_drive_folder_id']);
            $table->dropColumn('google_drive_folder_id');
        });
    }
};

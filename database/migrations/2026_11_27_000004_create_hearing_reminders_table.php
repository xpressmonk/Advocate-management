<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hearing_reminders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hearing_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 10);
            $table->string('recipient', 255);
            $table->dateTime('scheduled_for');
            $table->dateTime('sent_at')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('error')->nullable();
            $table->timestamps();
            $table->unique(['hearing_id', 'channel', 'scheduled_for']);
            $table->index(['status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hearing_reminders');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attachment_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_attachment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('mime_type');
            $table->unsignedInteger('version');
            $table->string('change_description')->nullable();
            $table->timestamp('uploaded_at');

            $table->index('task_attachment_id');
            $table->index('user_id');
            $table->index('version');
            $table->unique(['task_attachment_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachment_versions');
    }
};

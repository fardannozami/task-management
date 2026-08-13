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
        Schema::create('virus_scan_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_attachment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('scan_engine')->default('simulated-clamav');
            $table->string('status')->default('pending');
            $table->text('threats_found')->nullable();
            $table->string('action_taken')->default('none');
            $table->timestamp('scanned_at');
            $table->timestamps();

            $table->index('task_attachment_id');
            $table->index('status');
            $table->index('scanned_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('virus_scan_results');
    }
};

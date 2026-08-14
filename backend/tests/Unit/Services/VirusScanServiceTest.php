<?php

namespace Tests\Unit\Services;

use App\Models\Task;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Models\VirusScanResult;
use App\Services\VirusScanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VirusScanServiceTest extends TestCase
{
    use RefreshDatabase;

    private VirusScanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new VirusScanService;
    }

    public function test_it_marks_clean_files_as_clean(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'file_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $result = $this->service->scan($attachment);

        $this->assertEquals('clean', $result->status);
        $this->assertEquals('none', $result->action_taken);
    }

    public function test_it_detects_dangerous_extensions(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'file_name' => 'malware.exe',
            'mime_type' => 'application/octet-stream',
        ]);

        $result = $this->service->scan($attachment);

        $this->assertEquals('infected', $result->status);
        $this->assertEquals('quarantined', $result->action_taken);
    }

    public function test_it_detects_malicious_filename_patterns(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'file_name' => 'virus-simulation.zip',
            'mime_type' => 'application/zip',
        ]);

        $result = $this->service->scan($attachment);

        $this->assertEquals('infected', $result->status);
        $this->assertEquals('quarantined', $result->action_taken);
    }

    public function test_it_quarantines_infected_files(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
            'file_name' => 'backdoor.exe',
            'file_path' => 'uploads/test.exe',
        ]);

        Storage::fake('attachments');
        Storage::disk('attachments')->put('uploads/test.exe', 'malware content');

        $this->service->scan($attachment);

        $freshPath = $attachment->fresh()->file_path;
        $this->assertStringStartsWith('quarantine/', $freshPath);
        $this->assertStringContainsString('test.exe', $freshPath);
    }

    public function test_it_returns_previous_result_if_pending(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $user->id]);

        $attachment = TaskAttachment::factory()->create([
            'task_id' => $task->id,
        ]);

        VirusScanResult::create([
            'task_attachment_id' => $attachment->id,
            'scan_engine' => 'simulated-clamav',
            'status' => 'pending',
            'action_taken' => 'none',
            'scanned_at' => now(),
        ]);

        $result = $this->service->scan($attachment);

        $this->assertEquals('pending', $result->status);
    }
}

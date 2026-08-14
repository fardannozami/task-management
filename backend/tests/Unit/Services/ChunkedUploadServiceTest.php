<?php

namespace Tests\Unit\Services;

use App\Models\ChunkedUpload;
use App\Models\Task;
use App\Models\User;
use App\Services\ChunkedUploadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ChunkedUploadServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChunkedUploadService $service;
    private Task $task;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ChunkedUploadService();
        $this->user = User::factory()->create();
        auth('api')->login($this->user);
        $this->task = Task::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_it_can_initialize_chunked_upload(): void
    {
        $upload = $this->service->init($this->task, 'largefile.bin', 10485760);

        $this->assertInstanceOf(ChunkedUpload::class, $upload);
        $this->assertEquals('largefile.bin', $upload->original_file_name);
        $this->assertEquals(10485760, $upload->total_size);
        $this->assertEquals('initiated', $upload->status);
        $this->assertNotNull($upload->temp_path);
    }

    public function test_it_can_upload_chunks(): void
    {
        $upload = $this->service->init($this->task, 'largefile.bin', 10485760);

        $chunk = UploadedFile::fake()->create('chunk.bin', 1024);

        $updated = $this->service->uploadChunk($upload, $chunk, 0);

        $this->assertEquals(1, $updated->uploaded_chunks);
    }

    public function test_it_marks_ready_when_all_chunks_uploaded(): void
    {
        $upload = $this->service->init($this->task, 'largefile.bin', 5242880); // 5MB
        $chunk = UploadedFile::fake()->create('chunk.bin', 5120); // 5MB

        $this->service->uploadChunk($upload, $chunk, 0);

        $this->assertEquals('ready_to_merge', $upload->fresh()->status);
    }

    public function test_it_can_cancel_upload(): void
    {
        $upload = $this->service->init($this->task, 'largefile.bin', 10485760);

        $this->service->cancel($upload);

        $this->assertEquals('cancelled', $upload->fresh()->status);
        $this->assertNotNull($upload->fresh()->completed_at);
    }

    public function test_it_cannot_cancel_completed_upload(): void
    {
        $upload = $this->service->init($this->task, 'largefile.bin', 5242880);
        $chunk = UploadedFile::fake()->create('chunk.bin', 5120);
        $this->service->uploadChunk($upload, $chunk, 0);
        $this->service->merge($upload);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->service->cancel($upload);
    }
}

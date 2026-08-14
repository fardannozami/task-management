<?php

namespace Tests\Feature\ChunkedUploads;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ChunkedUploadApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    private Task $task;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
        $this->task = Task::factory()->create(['created_by' => $this->user->id]);
    }

    public function test_authenticated_user_can_initiate_chunked_upload(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'largefile.mp4',
                'total_size' => 10485760,
                'mime_type' => 'video/mp4',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'task_id',
                'original_file_name',
                'total_chunks',
                'chunk_size',
                'status',
            ]);
    }

    public function test_authenticated_user_can_upload_chunk(): void
    {
        $initResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'largefile.mp4',
                'total_size' => 10485760,
            ]);

        $uploadId = $initResponse->json('id');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/{$uploadId}/chunk", [
                'chunk' => UploadedFile::fake()->create('chunk.bin', 1024),
                'chunk_index' => 0,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'uploaded_chunks' => 1,
            ]);
    }

    public function test_authenticated_user_can_merge_chunks(): void
    {
        $image = UploadedFile::fake()->image('photo.png');

        $initResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'photo.png',
                'total_size' => $image->getSize(),
                'mime_type' => 'image/png',
            ]);

        $uploadId = $initResponse->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/{$uploadId}/chunk", [
                'chunk' => $image,
                'chunk_index' => 0,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/{$uploadId}/merge");

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'task_id',
                'file_name',
                'file_size',
                'mime_type',
            ])
            ->assertJson([
                'file_name' => 'photo.png',
                'mime_type' => 'image/png',
            ]);
    }

    public function test_authenticated_user_can_cancel_upload(): void
    {
        $initResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'largefile.mp4',
                'total_size' => 10485760,
            ]);

        $uploadId = $initResponse->json('id');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/chunked-uploads/{$uploadId}");

        $response->assertStatus(204);
    }

    public function test_init_rejects_unsupported_extension(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'shell.php',
                'total_size' => 1024,
                'mime_type' => 'application/octet-stream',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('file_name');
    }

    public function test_init_rejects_unsupported_mime_type(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'image.svg',
                'total_size' => 1024,
                'mime_type' => 'image/svg+xml',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('mime_type');
    }

    public function test_init_rejects_oversized_file(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'largefile.mp4',
                'total_size' => 51200 * 1024 + 1,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('total_size');
    }

    public function test_merge_rejects_content_type_mismatch(): void
    {
        $initResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'photo.png',
                'total_size' => 1024,
                'mime_type' => 'image/png',
            ]);

        $uploadId = $initResponse->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/{$uploadId}/chunk", [
                'chunk' => UploadedFile::fake()->create('fake.bin', 1024),
                'chunk_index' => 0,
            ]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/{$uploadId}/merge");

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Unsupported file content type detected.');
    }
}

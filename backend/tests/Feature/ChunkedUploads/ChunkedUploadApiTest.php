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
                'file_name' => 'largefile.bin',
                'total_size' => 10485760,
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
                'file_name' => 'largefile.bin',
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
        $initResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'largefile.bin',
                'total_size' => 5242880,
            ]);

        $uploadId = $initResponse->json('id');

        $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/{$uploadId}/chunk", [
                'chunk' => UploadedFile::fake()->create('chunk.bin', 5120),
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
            ]);
    }

    public function test_authenticated_user_can_cancel_upload(): void
    {
        $initResponse = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson("/api/chunked-uploads/tasks/{$this->task->id}/init", [
                'file_name' => 'largefile.bin',
                'total_size' => 10485760,
            ]);

        $uploadId = $initResponse->json('id');

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->deleteJson("/api/chunked-uploads/{$uploadId}");

        $response->assertStatus(204);
    }
}

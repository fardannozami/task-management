<?php

namespace Tests\Feature\Attachments;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AttachmentApiTest extends TestCase
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

    public function test_authenticated_user_can_upload_attachment(): void
    {
        $file = UploadedFile::fake()->image('document.png', 100, 100);
        
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->post("/api/tasks/{$this->task->id}/attachments", [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'file_name' => 'document.png',
                'mime_type' => 'image/png',
            ]);
    }

    public function test_upload_requires_valid_file(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson("/api/tasks/{$this->task->id}/attachments", [
                'file' => 'not-a-file',
            ]);

        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_download_attachment(): void
    {
        $attachment = \App\Models\TaskAttachment::factory()->create([
            'task_id' => $this->task->id,
            'file_path' => 'test.pdf',
            'file_name' => 'test.pdf',
        ]);

        Storage::fake('attachments');
        Storage::disk('attachments')->put('test.pdf', 'test content');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/attachments/{$attachment->id}/download");

        $response->assertStatus(200);
    }

    public function test_authenticated_user_can_delete_attachment(): void
    {
        $attachment = \App\Models\TaskAttachment::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/attachments/{$attachment->id}");

        $response->assertStatus(204);
    }

    public function test_authenticated_user_can_list_attachment_versions(): void
    {
        $attachment = \App\Models\TaskAttachment::factory()->create([
            'task_id' => $this->task->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson("/api/attachments/{$attachment->id}/versions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'version',
                    'file_name',
                    'change_description',
                ],
            ]);
    }
}

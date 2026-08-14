<?php

namespace Tests\Feature\Bulk;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkTaskApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->token = auth('api')->login($this->user);
    }

    public function test_authenticated_user_can_bulk_update_task_status(): void
    {
        $tasks = Task::factory()->count(3)->create(['created_by' => $this->user->id]);
        $taskIds = $tasks->pluck('id')->toArray();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/bulk/tasks/status', [
                'task_ids' => $taskIds,
                'status' => 'completed',
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Bulk status update queued.',
                'status' => 'completed',
            ]);

        foreach ($taskIds as $taskId) {
            $this->assertEquals('completed', Task::find($taskId)->status);
        }
    }

    public function test_bulk_update_validates_task_ids(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/bulk/tasks/status', [
                'task_ids' => [99999],
                'status' => 'completed',
            ]);

        $response->assertStatus(422);
    }

    public function test_bulk_update_validates_status(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer '.$this->token)
            ->postJson('/api/bulk/tasks/status', [
                'task_ids' => [1],
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422);
    }
}

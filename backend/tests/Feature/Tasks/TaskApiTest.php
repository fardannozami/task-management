<?php

namespace Tests\Feature\Tasks;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskApiTest extends TestCase
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

    public function test_authenticated_user_can_create_task(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/tasks', [
                'title' => 'Test Task',
                'description' => 'Test description',
                'status' => 'pending',
                'priority' => 'high',
                'assigned_user_id' => User::factory()->create()->id,
                'due_date' => '2026-09-01 10:00:00',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'title' => 'Test Task',
                'status' => 'pending',
                'priority' => 'high',
            ]);
    }

    public function test_authenticated_user_can_list_tasks(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'status',
                        'priority',
                        'assigned_user',
                        'creator',
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);
    }

    public function test_authenticated_user_can_update_task(): void
    {
        $task = Task::factory()->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson("/api/tasks/{$task->id}", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'completed']);
    }

    public function test_authenticated_user_can_delete_task(): void
    {
        $task = Task::factory()->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(204);
    }

    public function test_user_cannot_access_tasks_without_token(): void
    {
        $response = $this->getJson('/api/tasks');

        $response->assertStatus(401);
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        Task::factory()->count(2)->create(['status' => 'pending', 'created_by' => $this->user->id]);
        Task::factory()->count(3)->create(['status' => 'completed', 'created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tasks?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_tasks_can_be_searched(): void
    {
        Task::factory()->create(['title' => 'Laravel Task', 'created_by' => $this->user->id]);
        Task::factory()->create(['title' => 'PHP Task', 'created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/tasks?search=Laravel');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}

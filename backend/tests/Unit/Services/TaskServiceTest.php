<?php

namespace Tests\Unit\Services;

use App\Models\Task;
use App\Models\User;
use App\Services\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class TaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private TaskService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TaskService;
        $this->user = User::factory()->create();
        Auth::shouldReceive('id')->andReturn($this->user->id);
    }

    public function test_it_can_create_a_task(): void
    {
        $data = [
            'title' => 'Test Task',
            'description' => 'Test description',
            'status' => 'pending',
            'priority' => 'high',
            'assigned_user_id' => $this->user->id,
            'due_date' => '2026-09-01 10:00:00',
        ];

        $task = $this->service->createTask($data);

        $this->assertInstanceOf(Task::class, $task);
        $this->assertEquals('Test Task', $task->title);
        $this->assertEquals('pending', $task->status);
        $this->assertEquals($this->user->id, $task->assigned_user_id);
    }

    public function test_it_can_update_a_task(): void
    {
        $task = Task::factory()->create(['created_by' => $this->user->id]);

        $updated = $this->service->updateTask($task, ['status' => 'completed']);

        $this->assertEquals('completed', $updated->status);
    }

    public function test_it_can_delete_a_task(): void
    {
        $task = Task::factory()->create(['created_by' => $this->user->id]);
        $taskId = $task->id;

        $this->service->deleteTask($task);

        $this->assertNull(Task::find($taskId));
    }

    public function test_it_can_filter_tasks_by_status(): void
    {
        Task::factory()->count(3)->create(['status' => 'pending', 'created_by' => $this->user->id]);
        Task::factory()->count(2)->create(['status' => 'completed', 'created_by' => $this->user->id]);

        $result = $this->service->getTasks(['status' => 'pending']);

        $this->assertEquals(3, $result->total());
    }

    public function test_it_can_search_tasks(): void
    {
        Task::factory()->create(['title' => 'Laravel Development', 'created_by' => $this->user->id]);
        Task::factory()->create(['title' => 'PHP Testing', 'created_by' => $this->user->id]);

        $result = $this->service->getTasks(['search' => 'Laravel']);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('Laravel Development', $result->first()->title);
    }
}

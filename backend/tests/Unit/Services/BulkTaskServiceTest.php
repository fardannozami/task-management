<?php

namespace Tests\Unit\Services;

use App\Models\Task;
use App\Services\BulkTaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BulkTaskServiceTest extends TestCase
{
    use RefreshDatabase;

    private BulkTaskService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BulkTaskService();
    }

    public function test_it_can_bulk_update_task_status(): void
    {
        $tasks = Task::factory()->count(3)->create(['status' => 'pending']);
        $taskIds = $tasks->pluck('id')->toArray();

        $updated = $this->service->updateStatus($taskIds, 'completed');

        $this->assertEquals(3, $updated);
        $this->assertEquals('completed', Task::find($tasks->first()->id)->status);
    }

    public function test_it_can_bulk_update_task_priority(): void
    {
        $tasks = Task::factory()->count(3)->create(['priority' => 'low']);
        $taskIds = $tasks->pluck('id')->toArray();

        $updated = $this->service->updatePriority($taskIds, 'high');

        $this->assertEquals(3, $updated);
        $this->assertEquals('high', Task::find($tasks->first()->id)->priority);
    }

    public function test_it_throws_exception_for_invalid_status(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->updateStatus([1], 'invalid_status');
    }

    public function test_it_throws_exception_for_invalid_priority(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->service->updatePriority([1], 'invalid_priority');
    }
}

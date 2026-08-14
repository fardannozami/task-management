<?php

namespace Tests\Feature\Exports;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportApiTest extends TestCase
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

    public function test_authenticated_user_can_queue_csv_export(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/exports/tasks/csv', [
                'status' => 'pending',
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'CSV export queued. The file will be available shortly.',
                'format' => 'csv',
            ]);
    }

    public function test_authenticated_user_can_queue_pdf_export(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/exports/tasks/pdf', [
                'status' => 'pending',
            ]);

        $response->assertStatus(202)
            ->assertJson([
                'message' => 'PDF export queued. The file will be available shortly.',
                'format' => 'pdf',
            ]);
    }

    public function test_authenticated_user_can_download_csv(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/exports/tasks/csv/download?status=pending');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    public function test_authenticated_user_can_download_pdf(): void
    {
        Task::factory()->count(3)->create(['created_by' => $this->user->id]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->get('/api/exports/tasks/pdf/download?status=pending');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }
}

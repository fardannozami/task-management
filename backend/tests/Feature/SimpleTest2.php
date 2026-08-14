<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimpleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_works(): void
    {
        $this->assertTrue(true);
    }
}

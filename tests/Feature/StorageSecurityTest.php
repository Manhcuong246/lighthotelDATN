<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_route_blocks_parent_directory_traversal(): void
    {
        Storage::fake('public');

        $response = $this->get('/storage/../.env');

        $response->assertNotFound();
    }
}

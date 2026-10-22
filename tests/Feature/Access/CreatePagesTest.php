<?php

namespace Tests\Feature\Access;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatePagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regresi: route detail seperti clients/{client} pernah menangkap "create" sehingga halaman tambah 404.
     */
    public function test_superadmin_can_open_every_create_page(): void
    {
        $eo = User::factory()->superadmin()->create();

        foreach (['/clients/create', '/products/create', '/campaigns/create', '/forms/create', '/assignments/create', '/users/create'] as $url) {
            $this->actingAs($eo)->get($url)->assertOk();
        }
    }
}

<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\User;
use Database\Factories\KategoriFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class KategoriTest extends TestCase
{
    public function testIndexSuccess()
    {
        $user = User::factory()->create();
        Kategori::factory(4)->create();
        $this->get(uri: 'api/kategori',
            headers:['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors' => []
        ]);
    }
    public function testCreateSuccess()
    {
        $payload = [
            'nama'=>'Bersaudara'
        ];

        $user = User::factory()->create();
        $this->post(uri: 'api/kategori',
        data: $payload,
        headers:['Authorization' => $user->token])
        ->assertStatus(201)
        ->assertJson([
            'errors' => []
        ]);
    }
    public function testGetSuccess()
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create();
        $this->get(uri: 'api/kategori/'.$kategori->id,
        headers:['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors' => []
        ]);
    }
    public function testUpdateSuccess()
    {
        $payload = [
            'nama'=>'Bersaudara'
        ];

        $user = User::factory()->create();
        $kategori = Kategori::factory()->create();
        $this->put(uri: 'api/kategori/'.$kategori->id,
        data:$payload,
        headers:['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors' => []
        ]);
    }
    public function testDeleteSuccess()
    {
        $user = User::factory()->create();
        $kategori = Kategori::factory()->create();
        $this->delete(uri: 'api/kategori/'.$kategori->id,
        headers:['Authorization' => $user->token])
        ->assertStatus(200)
        ->assertJson([
            'errors' => []
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class KelasTest extends TestCase
{
    public function testShowKelasSuccess()
    {
        User::factory()->create();
        Kelas::factory(3)->create([
            'jenjang'=>'MI'
        ]);

        $this->get(uri: 'api/kelas/tk',headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[

            ]
        ]);
    }

    public function testCreateKelasSuccess()
    {
        User::factory()->create();
        $this->post('api/kelas/mi',
        [
            'nama'=>'Kelas 1'
        ],
        [
            'Authorization'=>'test'
        ])->assertStatus(201)
            ->assertJson([
                'errors'=>[

                ]
            ]);
    }

    public function testUpdateKelasSuccess()
    {
        User::factory()->create();
        $kelas = Kelas::factory()->create([
            'jenjang'=>'MI'
        ]);

        $this->put('api/kelas/mi/'.$kelas->id,
        [
            'nama'=>'Kelas 1'
        ],
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }

    public function testGetKelasSuccess()
    {
        User::factory()->create();
        $kelas = Kelas::factory()->create([]);
        $this->get(uri: 'api/kelas/'.$kelas->jenjang.'/'.$kelas->id,headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }

    public function testDeleteKelasSuccess()
    {
        User::factory()->create();
        $kelas = Kelas::factory()->create([]);
        $this->delete(uri: 'api/kelas/'.$kelas->jenjang.'/'.$kelas->id,headers:
        [
            'Authorization'=>'test'
        ])->assertStatus(200)
        ->assertJson([
            'errors'=>[]
        ]);
    }
}

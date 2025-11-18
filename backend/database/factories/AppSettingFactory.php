<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AppSetting>
 */
class AppSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama_sekolah' => 'Lembaga Pendidikan Handayani',
            'lokasi'=>'Pontianak',
            'alamat'=>$this->faker->address(),
            'email'=>$this->faker->unique()->safeEmail(),
            'telepon'=>$this->faker->phoneNumber(),
            'kepala_sekolah'=>$this->faker->name(),
            'bendahara'=>$this->faker->name(),
            'kode_pos'=>$this->faker->postcode(),
            'logo'=>$this->faker->imageUrl(),
        ];
    }
}

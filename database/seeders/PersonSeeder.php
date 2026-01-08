<?php

namespace Database\Seeders;

use App\Models\Email;
use App\Models\Person;
use App\Models\Phone;
use App\Models\Reference;
use Illuminate\Database\Seeder;

class PersonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Person::factory(50)->create()->each(function ($person) {
            Phone::factory(2)->create([
                'phoneable_id' => $person->id,
                'phoneable_type' => Person::class,
            ]);
            Email::factory(2)->create([
                'emailable_id' => $person->id,
                'emailable_type' => Person::class,
            ]);
            Reference::factory(3)->create([
                'person_id' => $person->id,
            ]);
        });
    }
}

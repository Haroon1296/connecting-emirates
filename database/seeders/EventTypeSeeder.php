<?php

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        EventType::query()->delete();

        EventType::create([
            'title'    => 'Birthday'
        ]);

        EventType::create([
            'title'    => 'Independence Day'
        ]);

        EventType::create([
            'title'    => 'Halloween'
        ]);

        EventType::create([
            'title'    => 'New Year Celebration'
        ]);
    }
}

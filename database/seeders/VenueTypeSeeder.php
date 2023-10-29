<?php

namespace Database\Seeders;

use App\Models\VenueType;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class VenueTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        VenueType::truncate();

        VenueType::create([
            'title'     =>      'American',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Arabic',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Chinese',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Dutch',
            'status'    =>      '0'
        ]);

        VenueType::create([
            'title'     =>      'Greek',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Indian',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Fast Food',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Italian',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Mexican',
            'status'    =>      '0'
        ]);

        VenueType::create([
            'title'     =>      'Pakistani',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Russian',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Seafood',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Spanish',
            'status'    =>      '1'
        ]);

        VenueType::create([
            'title'     =>      'Thai',
            'status'    =>      '0'
        ]);

        VenueType::create([
            'title'     =>      'Turkish',
            'status'    =>      '1'
        ]);
    }
}

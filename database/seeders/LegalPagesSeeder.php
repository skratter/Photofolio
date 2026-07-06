<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LegalPagesSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::firstOrCreate(
            ['slug' => 'impressum'],
            [
                'title' => 'Impressum',
                'content' => '<p>Bitte rechtssicheren Text einfügen.</p>',
                'type' => 'legal',
                'status' => 'published',
                'published_at' => now(),
            ]
        );

        Page::firstOrCreate(
            ['slug' => 'datenschutz'],
            [
                'title' => 'Datenschutzerklärung',
                'content' => '<p>Bitte rechtssicheren Text einfügen.</p>',
                'type' => 'legal',
                'status' => 'published',
                'published_at' => now(),
            ]
        );
    }
}

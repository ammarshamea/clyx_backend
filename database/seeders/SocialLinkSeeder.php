<?php

namespace Database\Seeders;

use App\Models\SocialLink;
use Illuminate\Database\Seeder;

class SocialLinkSeeder extends Seeder
{
    public function run(): void
    {
        $links = [
            ['platform' => 'instagram', 'url' => 'https://instagram.com', 'sort_order' => 1],
            ['platform' => 'twitter', 'url' => 'https://twitter.com', 'sort_order' => 2],
            ['platform' => 'linkedin', 'url' => 'https://linkedin.com', 'sort_order' => 3],
            ['platform' => 'youtube', 'url' => 'https://youtube.com', 'sort_order' => 4],
        ];

        foreach ($links as $link) {
            SocialLink::firstOrCreate(
                ['platform' => $link['platform']],
                array_merge($link, ['is_active' => true])
            );
        }
    }
}

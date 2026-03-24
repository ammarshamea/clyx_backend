<?php

namespace Database\Seeders;

use App\Models\ClientType;
use Illuminate\Database\Seeder;

class ClientTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80', 'sort_order' => 1],
            ['image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80', 'sort_order' => 2],
            ['image' => 'https://images.unsplash.com/photo-1568992687947-868a62a9f521?w=800&q=80', 'sort_order' => 3],
            ['image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&q=80', 'sort_order' => 4],
            ['image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80', 'sort_order' => 5],
        ];

        foreach ($types as $t) {
            ClientType::firstOrCreate(
                ['sort_order' => $t['sort_order']],
                array_merge($t, ['is_active' => true])
            );
        }
    }
}

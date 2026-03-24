<?php

namespace Database\Seeders;

use App\Models\ClientType;
use Illuminate\Database\Seeder;

class ClientTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['label_en' => 'Startups', 'label_ar' => 'الشركات الناشئة', 'image' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=800&q=80', 'sort_order' => 1],
            ['label_en' => 'SMEs', 'label_ar' => 'الشركات الصغيرة والمتوسطة', 'image' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80', 'sort_order' => 2],
            ['label_en' => 'Corporate', 'label_ar' => 'الشركات الكبرى', 'image' => 'https://images.unsplash.com/photo-1568992687947-868a62a9f521?w=800&q=80', 'sort_order' => 3],
            ['label_en' => 'Ecommerce Brands', 'label_ar' => 'علامات التجارة الإلكترونية', 'image' => 'https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=800&q=80', 'sort_order' => 4],
            ['label_en' => 'Personal Brands', 'label_ar' => 'العلامات الشخصية', 'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=800&q=80', 'sort_order' => 5],
        ];

        foreach ($types as $i => $t) {
            ClientType::firstOrCreate(
                ['label_en' => $t['label_en']],
                array_merge($t, ['is_active' => true])
            );
        }
    }
}

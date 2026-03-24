<?php

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

class ProjectCategorySeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'branding', 'name_en' => 'Branding', 'name_ar' => 'هوية بصرية', 'sort_order' => 1],
            ['slug' => 'web', 'name_en' => 'Web', 'name_ar' => 'ويب', 'sort_order' => 2],
            ['slug' => 'social-media', 'name_en' => 'Social Media', 'name_ar' => 'سوشيال ميديا', 'sort_order' => 3],
            ['slug' => 'marketing', 'name_en' => 'Marketing', 'name_ar' => 'تسويق', 'sort_order' => 4],
            ['slug' => 'photography', 'name_en' => 'Photography', 'name_ar' => 'تصوير', 'sort_order' => 5],
        ];

        foreach ($rows as $r) {
            ProjectCategory::firstOrCreate(
                ['slug' => $r['slug']],
                array_merge($r, ['is_active' => true])
            );
        }
    }
}

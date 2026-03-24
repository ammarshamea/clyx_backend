<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $categoryByNameEn = ProjectCategory::query()->pluck('id', 'name_en');

        $projects = [
            ['slug' => 'zenith-studio', 'title' => 'Zenith Studio', 'title_ar' => 'زينيث ستوديو', 'category' => 'Branding & Web', 'category_ar' => 'هوية وموقع', 'description' => 'Creative agency identity and website for a design studio.', 'description_ar' => 'هوية إبداعية وموقع لاستوديو تصميم.', 'image' => 'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=800&q=80', 'tags' => ['Branding', 'Web', 'Creative'], 'sort_order' => 1],
            ['slug' => 'monstre-cafe', 'title' => 'Monstre Cafe', 'title_ar' => 'مونستر كافيه', 'category' => 'Branding & Packaging', 'category_ar' => 'هوية وتغليف', 'description' => 'Bold and playful branding for an artisan coffee concept.', 'description_ar' => 'هوية جريئة ومبهجة لمفهوم قهوة حرفية.', 'image' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?w=800&q=80', 'tags' => ['Branding', 'Packaging', 'Cafe'], 'sort_order' => 2],
            ['slug' => 'biave', 'title' => 'Biave', 'title_ar' => 'بيف', 'category' => 'Web Development', 'category_ar' => 'تطوير الويب', 'description' => 'Minimal e-commerce platform for a luxury fashion brand.', 'description_ar' => 'منصة تجارة إلكترونية راقية لعلامة أزياء فاخرة.', 'image' => 'https://images.unsplash.com/photo-1558769132-cb1aea458c5e?w=800&q=80', 'tags' => ['Web', 'Ecommerce', 'Fashion'], 'sort_order' => 3],
            ['slug' => 'crumble', 'title' => 'Crumble', 'title_ar' => 'كرامبل', 'category' => 'Branding & Digital Marketing', 'category_ar' => 'هوية وتسويق رقمي', 'description' => 'Full brand identity and social media strategy for a bakery.', 'description_ar' => 'هوية كاملة واستراتيجية سوشيال ميديا لمخبزة.', 'image' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=800&q=80', 'tags' => ['Branding', 'Marketing', 'Food'], 'sort_order' => 4],
            ['slug' => 'wheat-bounties', 'title' => 'Wheat Bounties', 'title_ar' => 'ويت باونتيز', 'category' => 'Packaging Design', 'category_ar' => 'تصميم تغليف', 'description' => 'Premium packaging and visual identity for a grain product brand.', 'description_ar' => 'تغليف راقٍ وهوية بصرية لعلامة منتجات حبوب.', 'image' => 'https://images.unsplash.com/photo-1586201375761-83865001e31c?w=800&q=80', 'tags' => ['Packaging', 'Branding', 'Food'], 'sort_order' => 5],
            ['slug' => 'snack-bar', 'title' => 'Snack Bar', 'title_ar' => 'سناك بار', 'category' => 'Branding & Web', 'category_ar' => 'هوية وموقع', 'description' => 'Energetic brand identity and website for a modern snack concept.', 'description_ar' => 'هوية نابضة وموقع لمفهوم سناك عصري.', 'image' => 'https://images.unsplash.com/photo-1603532648955-039310d9ed75?w=800&q=80', 'tags' => ['Branding', 'Web', 'Food'], 'sort_order' => 6],
            ['slug' => 'oma-burger', 'title' => 'OMA Burger', 'title_ar' => 'أوما برجر', 'category' => 'Digital Marketing', 'category_ar' => 'تسويق رقمي', 'description' => 'Performance marketing and social campaigns for a burger brand.', 'description_ar' => 'تسويق أدائي وحملات اجتماعية لعلامة برجر.', 'image' => 'https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=800&q=80', 'tags' => ['Marketing', 'Social Media', 'Food'], 'sort_order' => 7],
            ['slug' => 'falafelcom', 'title' => 'Falafelcom', 'title_ar' => 'فلافل كوم', 'category' => 'Branding & Packaging', 'category_ar' => 'هوية وتغليف', 'description' => 'Contemporary rebrand of a beloved falafel restaurant chain.', 'description_ar' => 'إعادة هوية عصرية لسلسلة مطاعم فلافل محبوبة.', 'image' => 'https://images.unsplash.com/photo-1601050690597-df0568f70950?w=800&q=80', 'tags' => ['Branding', 'Packaging', 'Restaurant'], 'sort_order' => 8],
            ['slug' => 'places-app', 'title' => 'Places App', 'title_ar' => 'تطبيق بليسز', 'category' => 'UI/UX & Development', 'category_ar' => 'تصميم وتطوير', 'description' => 'Destination discovery app with immersive UI/UX design.', 'description_ar' => 'تطبيق اكتشاف وجهات بتصميم UI/UX غامر.', 'image' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=800&q=80', 'tags' => ['App', 'UI/UX', 'Mobile'], 'sort_order' => 9],
        ];

        foreach ($projects as $p) {
            $tags = $p['tags'] ?? [];
            $pid = null;
            foreach ($tags as $tag) {
                if ($categoryByNameEn->has($tag)) {
                    $pid = $categoryByNameEn[$tag];
                    break;
                }
            }

            Project::firstOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['is_active' => true, 'project_category_id' => $pid])
            );
        }
    }
}

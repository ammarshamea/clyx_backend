<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create super admin
        User::firstOrCreate(
            ['email' => 'admin@clyx.sa'],
            [
                'name'      => 'CLYX Admin',
                'password'  => Hash::make('Admin@1234'),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        // Create subscription plans
        $plans = [
            [
                'name'         => 'Starter',
                'name_ar'      => 'المبدئية',
                'description'  => 'Perfect for small restaurants',
                'description_ar' => 'مثالية للمطاعم الصغيرة',
                'price_monthly' => 99.00,
                'price_yearly'  => 990.00,
                'currency'      => 'SAR',
                'max_branches'  => 1,
                'max_products'  => 50,
                'features'      => ['menu', 'orders', 'basic_reports'],
                'sort_order'    => 1,
            ],
            [
                'name'         => 'Growth',
                'name_ar'      => 'النمو',
                'description'  => 'For growing restaurant chains',
                'description_ar' => 'للمطاعم المتنامية',
                'price_monthly' => 299.00,
                'price_yearly'  => 2990.00,
                'currency'      => 'SAR',
                'max_branches'  => 3,
                'max_products'  => 200,
                'features'      => ['menu', 'orders', 'analytics', 'loyalty', 'promotions'],
                'sort_order'    => 2,
            ],
            [
                'name'         => 'Enterprise',
                'name_ar'      => 'المؤسسية',
                'description'  => 'Unlimited power for large chains',
                'description_ar' => 'طاقة غير محدودة للسلاسل الكبيرة',
                'price_monthly' => 799.00,
                'price_yearly'  => 7990.00,
                'currency'      => 'SAR',
                'max_branches'  => null,
                'max_products'  => null,
                'features'      => ['menu', 'orders', 'analytics', 'loyalty', 'promotions', 'api_access', 'white_label', 'priority_support'],
                'sort_order'    => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['name' => $plan['name']], $plan);
        }

        $this->call([
            ProjectCategorySeeder::class,
            ProjectSeeder::class,
            ClientTypeSeeder::class,
            SocialLinkSeeder::class,
        ]);
    }
}

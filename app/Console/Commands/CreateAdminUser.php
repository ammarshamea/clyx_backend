<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminUser extends Command
{
    /**
     * Create a dashboard admin on the server (production/staging). Do not rely on seeders for real credentials.
     *
     * Examples (SSH on server, from project root):
     *   php artisan clyx:create-admin
     *   php artisan clyx:create-admin --email=you@domain.com --password="YourSecurePass123" --name="Site Admin"
     *   php artisan clyx:create-admin --email=... --password=... --role=admin
     */
    protected $signature = 'clyx:create-admin
                            {--email= : Admin email}
                            {--password= : Plain password (min 8 characters)}
                            {--name= : Display name}
                            {--role=super_admin : super_admin or admin}';

    protected $description = 'Create a dashboard admin user (use on server instead of seeders).';

    public function handle(): int
    {
        $email = $this->option('email') ?: $this->ask('Email address');
        $password = $this->option('password');
        if ($password === null || $password === '') {
            $password = $this->secret('Password (min 8 characters)');
        }
        $name = $this->option('name') ?: $this->ask('Full name', 'Admin');

        $roleOpt = strtolower((string) $this->option('role'));
        $role = $roleOpt === 'admin' ? 'admin' : 'super_admin';

        $validator = Validator::make(
            [
                'email' => $email,
                'password' => $password,
                'name' => $name,
            ],
            [
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
                'name' => ['required', 'string', 'max:255'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('A user with this email already exists.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'is_active' => true,
        ]);

        $this->info("Admin user created: {$email} ({$role})");

        return self::SUCCESS;
    }
}

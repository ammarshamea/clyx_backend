<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_info', function (Blueprint $table) {
            $table->id();
            $table->string('email');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('address_ar')->nullable();
            $table->timestamps();
        });

        \DB::table('contact_info')->insert([
            'email' => 'hello@clyx.agency',
            'phone' => '+966 50 000 0000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_info');
    }
};

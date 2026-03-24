<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            // DB connection details (encrypted)
            $table->string('db_driver')->default('mysql'); // mysql, sqlite, pgsql
            $table->text('db_host')->nullable();    // encrypted
            $table->string('db_port')->default('3306')->nullable();
            $table->text('db_database');            // encrypted
            $table->text('db_username')->nullable(); // encrypted
            $table->text('db_password')->nullable(); // encrypted
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->json('meta')->nullable(); // extra info
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};

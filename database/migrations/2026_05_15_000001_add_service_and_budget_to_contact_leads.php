<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_leads', function (Blueprint $table) {
            $table->string('service', 64)->nullable()->after('company');
            $table->string('budget', 64)->nullable()->after('service');
        });
    }

    public function down(): void
    {
        Schema::table('contact_leads', function (Blueprint $table) {
            $table->dropColumn(['service', 'budget']);
        });
    }
};

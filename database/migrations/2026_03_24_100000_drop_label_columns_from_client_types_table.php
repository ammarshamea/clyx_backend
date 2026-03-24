<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('client_types')) {
            return;
        }
        // One column per alter: safer for SQLite / some MySQL builds.
        Schema::table('client_types', function (Blueprint $table) {
            if (Schema::hasColumn('client_types', 'label_ar')) {
                $table->dropColumn('label_ar');
            }
        });
        Schema::table('client_types', function (Blueprint $table) {
            if (Schema::hasColumn('client_types', 'label_en')) {
                $table->dropColumn('label_en');
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_types', function (Blueprint $table) {
            if (! Schema::hasColumn('client_types', 'label_en')) {
                $table->string('label_en')->after('id');
                $table->string('label_ar')->nullable()->after('label_en');
            }
        });
    }
};

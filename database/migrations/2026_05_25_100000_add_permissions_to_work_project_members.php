<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_project_members', function (Blueprint $table) {
            $table->boolean('can_create_tasks')->default(false)->after('user_id');
            $table->boolean('can_edit_task_details')->default(false)->after('can_create_tasks');
            $table->boolean('can_assign_tasks')->default(false)->after('can_edit_task_details');
            $table->boolean('can_delete_tasks')->default(false)->after('can_assign_tasks');
            $table->boolean('can_moderate_content')->default(false)->after('can_delete_tasks');
        });
    }

    public function down(): void
    {
        Schema::table('work_project_members', function (Blueprint $table) {
            $table->dropColumn([
                'can_create_tasks',
                'can_edit_task_details',
                'can_assign_tasks',
                'can_delete_tasks',
                'can_moderate_content',
            ]);
        });
    }
};

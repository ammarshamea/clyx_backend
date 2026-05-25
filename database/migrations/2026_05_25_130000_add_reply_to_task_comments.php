<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->foreignId('reply_to_id')
                ->nullable()
                ->after('type')
                ->constrained('task_comments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_comments', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\TaskComment::class, 'reply_to_id');
            $table->dropColumn('reply_to_id');
        });
    }
};

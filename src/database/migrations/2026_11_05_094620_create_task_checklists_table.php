<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->string('image_path')->nullable();
            $table->date('due_date')->index();                        // 期限順の並び替え・期限切れ/間近の絞り込み
            $table->string('status', 20)->default('todo')->index(); // todo / doing / done
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_checklists');
    }
};

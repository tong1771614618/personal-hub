<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drinks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('drink_type', 30); // beer/wine/sake/soju/baijiu/whisky/cocktail/other
            $table->string('drink_name')->nullable(); // 自定义名称（如"青岛原浆"）
            $table->unsignedInteger('amount_ml'); // 毫升
            $table->decimal('alcohol_pct', 4, 1); // 酒精度 % (如 5.0 表示 5%)
            $table->decimal('pure_alcohol_ml', 7, 2); // 纯酒精 ml（amount_ml * alcohol_pct / 100）
            $table->decimal('standard_drinks', 5, 2); // 标准杯（按 10ml 纯酒精 = 1 杯）

            $table->text('note')->nullable();
            $table->string('location')->nullable(); // 地点
            $table->unsignedTinyInteger('company_count')->nullable(); // 同饮人数

            $table->timestamp('drank_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'drank_at']);
            $table->index(['user_id', 'drink_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drinks');
    }
};

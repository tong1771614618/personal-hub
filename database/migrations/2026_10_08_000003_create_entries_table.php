<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();

            $table->enum('type', ['thought', 'dream', 'learn', 'do', 'idea', 'resource'])->default('thought');

            $table->text('content');
            $table->string('category_tag')->nullable();
            $table->string('resource_url')->nullable();

            $table->unsignedInteger('duration_minutes')->nullable();

            $table->timestamp('happened_at')->useCurrent();
            $table->timestamps();

            $table->index(['user_id', 'happened_at']);
            $table->index(['user_id', 'type']);
            $table->index('category_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entries');
    }
};

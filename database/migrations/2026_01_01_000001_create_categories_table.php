<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('categories')
                  ->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable()->default('mdi-tag-outline');
            $table->string('accent_color')->nullable()->default('#0f9d6b');
            $table->string('status')->default('Active'); // Active, Draft
            $table->boolean('show_in_nav')->default(true);
            $table->boolean('featured')->default(false);
            $table->boolean('allow_reviews')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->index(['parent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // hero = home carousel slide, promo = image banner between sections, marquee = scrolling announcement text
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('type', 10)->default('hero'); // hero | promo | marquee
            $table->string('title')->nullable();
            $table->string('subtitle')->nullable();
            $table->string('button_text', 60)->nullable();
            $table->string('link')->nullable();
            $table->string('image')->nullable();
            $table->string('mobile_image')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        // Home page building blocks, shown in sort_order.
        Schema::create('home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20); // featured | new | deals | best_sellers | category | brand | categories | brands | promo
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('item_limit')->default(10);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
        });

        // Store-wide settings as key => JSON value.
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->text('value')->nullable();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();
        });

        // English text -> Bangla text. The English sentence itself is the key (stored with its md5 hash).
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->char('hash', 32)->unique();
            $table->text('source');                             // English
            $table->text('bn')->nullable();                     // Bangla
            $table->string('status', 10)->default('missing');   // missing | auto | reviewed
            $table->string('group', 10)->default('ui');         // ui | content
            $table->timestamps();

            $table->index(['status', 'group']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('settings');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('home_sections');
        Schema::dropIfExists('banners');
    }
};

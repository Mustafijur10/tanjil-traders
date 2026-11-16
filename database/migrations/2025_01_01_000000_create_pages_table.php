<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('slug')->unique();
            $t->text('short_description')->nullable();
            $t->longText('content')->nullable();
            $t->string('featured_image', 500)->nullable();

            // SEO
            $t->string('meta_title')->nullable();
            $t->string('meta_description', 500)->nullable();
            $t->string('meta_keywords', 500)->nullable();
            $t->string('canonical_url', 500)->nullable();
            $t->string('og_title')->nullable();
            $t->string('og_description', 500)->nullable();
            $t->string('og_image', 500)->nullable();
            $t->boolean('robots_index')->default(true);
            $t->boolean('robots_follow')->default(true);

            // Display settings
            $t->boolean('show_in_footer')->default(false);
            $t->boolean('show_in_navigation')->default(false);
            $t->boolean('show_in_sitemap')->default(true);
            $t->unsignedInteger('display_order')->default(0);
            $t->string('visibility', 20)->default('public'); // public | unlisted (reachable by URL, hidden from menus/sitemap, noindex)

            // Publishing
            $t->string('status', 20)->default('draft'); // draft | published
            $t->timestamp('published_at')->nullable();
            $t->timestamps();

            $t->index(['status', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};

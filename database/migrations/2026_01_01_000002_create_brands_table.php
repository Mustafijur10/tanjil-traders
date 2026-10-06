<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('tier')->default('Premium');
            $table->integer('sort_order')->default(0);

            // Media
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();

            // Company details
            $table->string('country')->nullable();
            $table->string('headquarters')->nullable();
            $table->integer('founded_year')->nullable();
            $table->string('website')->nullable();
            $table->json('category_ids')->nullable();

            // Warranty & service
            $table->integer('warranty_months')->default(12);
            $table->string('warranty_type')->default('Official brand warranty');
            $table->string('supply_type')->default('Official distributor');
            $table->integer('return_days')->default(7);
            $table->text('warranty_terms')->nullable();
            $table->text('service_centers')->nullable();

            // Support & compliance
            $table->string('support_email')->nullable();
            $table->string('support_phone')->nullable();
            $table->string('support_url')->nullable();
            $table->json('certifications')->nullable();

            // Social & SEO
            $table->json('social')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('keywords')->nullable();

            // Status & display options
            $table->string('status')->default('Active'); // Active, Draft, Inactive
            $table->boolean('is_official')->default(false);
            $table->boolean('feature_homepage')->default(false);
            $table->boolean('is_popular')->default(false);
            $table->boolean('show_in_nav')->default(true);
            $table->boolean('show_in_filters')->default(true);
            $table->boolean('allow_reviews')->default(true);

            $table->timestamps();

            $table->index(['status', 'feature_homepage']);
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
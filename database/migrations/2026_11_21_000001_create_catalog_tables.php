<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A category with parent_id = null is a main category, otherwise a subcategory.
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon', 60)->nullable();
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('show_in_nav')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['parent_id', 'is_active', 'sort_order']);
        });

        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Spec names: RAM, Storage, Color, Processor ...
        // select = fixed values (filterable, usable for variants), text = free text per product.
        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('type', 10)->default('select'); // select | text
            $table->string('unit', 20)->nullable();         // GB, inch, mAh
            $table->string('group', 60)->nullable();        // Memory, Display, Battery ...
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_comparable')->default(true);
            $table->boolean('is_variant')->default(false);  // can be used to build variants (Color, Storage)
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Spec values: 4GB, 8GB, 16GB ...
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('value');
            $table->string('slug');
            $table->string('swatch', 9)->nullable(); // hex colour for Color values
            $table->unsignedInteger('sort_order')->default(0);

            $table->unique(['attribute_id', 'slug']);
        });

        // Which specs a category uses (Laptops -> RAM, Storage, Processor ...)
        Schema::create('attribute_category', function (Blueprint $table) {
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            $table->primary(['attribute_id', 'category_id']);
        });

        // When a product has variants, price / sale_price / stock_qty hold the
        // lowest price and the total stock, kept in sync by the Product model.
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku', 60)->nullable()->unique();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->timestamp('sale_ends_at')->nullable();
            $table->decimal('cost_price', 12, 2)->nullable(); // for profit reports, never shown to customers
            $table->integer('stock_qty')->default(0);
            $table->string('warranty', 120)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->string('status', 10)->default('draft');   // draft | active
            $table->decimal('rating_avg', 2, 1)->default(0);  // cached from approved reviews
            $table->unsignedInteger('review_count')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->timestamps();

            $table->index(['status', 'category_id']);
            $table->index(['status', 'brand_id']);
            $table->index('price');
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt')->nullable();
            $table->unsignedInteger('sort_order')->default(0); // lowest = main image
        });

        // A product's spec values. Select specs use attribute_value_id, text specs use value.
        // A select spec can have several rows (Connectivity: Wi-Fi, Bluetooth).
        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('value')->nullable();

            $table->index(['attribute_value_id', 'product_id']);
            $table->index(['product_id', 'attribute_id']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 60)->nullable()->unique();
            $table->decimal('price', 12, 2);
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->integer('stock_qty')->default(0);
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
        });

        // Which values make a variant (8GB + 256GB + Black)
        Schema::create('product_variant_values', function (Blueprint $table) {
            $table->foreignId('variant_id')->constrained('product_variants')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();

            $table->primary(['variant_id', 'attribute_value_id']);
        });

        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->text('reply')->nullable(); // store's public reply
            $table->boolean('is_approved')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_approved']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('product_variant_values');
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
        Schema::dropIfExists('attribute_category');
        Schema::dropIfExists('attribute_values');
        Schema::dropIfExists('attributes');
        Schema::dropIfExists('brands');
        Schema::dropIfExists('categories');
    }
};

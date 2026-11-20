<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');                               // Inside Dhaka
            $table->string('delivery_time', 60)->nullable();      // 1-2 days
            $table->decimal('charge', 10, 2)->default(0);
            $table->decimal('free_over', 12, 2)->nullable();      // free delivery above this subtotal
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('type', 10)->default('fixed');        // fixed | percent
            $table->decimal('value', 10, 2);
            $table->decimal('min_order', 12, 2)->nullable();
            $table->decimal('max_discount', 12, 2)->nullable();  // cap for percent coupons
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // null = guest
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->text('address');
            $table->string('city', 80);
            $table->text('note')->nullable();
            $table->foreignId('shipping_method_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 12, 2);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->string('coupon_code', 40)->nullable();
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->string('payment_method', 20)->default('cod');    // cod | bkash | nagad
            $table->string('payment_status', 10)->default('unpaid'); // unpaid | paid | refunded
            $table->string('transaction_id', 60)->nullable();
            $table->string('status', 15)->default('pending');        // pending | confirmed | processing | shipped | delivered | cancelled | returned
            $table->string('source', 10)->default('web');            // web | admin
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('phone');
        });

        // Name, price and cost are copied at order time so old orders never change.
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('name');
            $table->string('variant_label')->nullable();
            $table->string('sku', 60)->nullable();
            $table->decimal('price', 12, 2);
            $table->decimal('cost_price', 12, 2)->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('total', 12, 2);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('status', 15);
            $table->text('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('wishlists', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'product_id']);
        });

        // Every stock change, with the balance after it.
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->integer('change');
            $table->integer('balance');
            $table->string('reason', 15); // sale | restock | adjustment | return | cancel
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('wishlists');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('shipping_methods');
    }
};

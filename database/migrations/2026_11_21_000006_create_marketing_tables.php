<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Promotions, traffic attribution, staff permissions and SMS:
 *   - sales can start in the future (sale_starts_at) and flash sales group products under one timer
 *   - orders remember the visitor and the traffic source that brought them
 *   - staff accounts can be limited to parts of the admin
 *   - every SMS sent to a customer is logged
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('sale_starts_at')->nullable()->after('sale_price');
        });

        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('discount_type', 10)->default('percent'); // percent | fixed
            $table->decimal('discount_value', 10, 2);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['flash_sale_id', 'product_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->char('visitor_id', 32)->nullable()->after('source');
            $table->string('traffic_source', 10)->nullable()->after('visitor_id'); // direct | search | social | referral | email | ads
        });

        Schema::table('users', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('role'); // staff only; null = every area except settings
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20);
            $table->text('message');
            $table->string('event', 20)->nullable();              // order_placed | order_confirmed | ... | test
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 10);                         // sent | failed
            $table->string('response', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('permissions'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn(['visitor_id', 'traffic_source']));
        Schema::dropIfExists('flash_sale_items');
        Schema::dropIfExists('flash_sales');
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('sale_starts_at'));
    }
};

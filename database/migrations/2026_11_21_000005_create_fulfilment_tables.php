<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Delivery and after-sales: couriers, tracking on orders, and returns/refunds.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80);                        // Pathao, Steadfast, RedX ...
            $table->string('phone', 20)->nullable();
            $table->string('tracking_url')->nullable();        // https://.../track/{code}
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('courier_id')->nullable()->after('shipping_method_id')->constrained()->nullOnDelete();
            $table->string('tracking_code', 60)->nullable()->after('courier_id');
            $table->timestamp('shipped_at')->nullable()->after('source');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        });

        // A customer (or staff for them) asks to send items back. Stock comes back when the items arrive.
        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 30);                      // damaged | wrong_item | not_as_described | changed_mind | other
            $table->text('details')->nullable();
            $table->string('status', 10)->default('requested'); // requested | approved | received | refunded | rejected
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->string('refund_method', 20)->nullable();   // cash | bkash | nagad | bank | store_credit
            $table->boolean('restock')->default(true);
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });

        Schema::create('order_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('qty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_return_items');
        Schema::dropIfExists('order_returns');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('courier_id');
            $table->dropColumn(['tracking_code', 'shipped_at', 'delivered_at']);
        });
        Schema::dropIfExists('couriers');
    }
};

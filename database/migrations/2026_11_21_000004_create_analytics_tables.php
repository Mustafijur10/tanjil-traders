<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per storefront page view. No raw IP is stored: visitor_id is a random
        // browser id and session_id is per tab session. Web Vitals arrive after the page loads.
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_id', 32);
            $table->char('session_id', 32);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('path', 500);
            $table->string('page_type', 15)->nullable();  // home | product | category | brand | search | page | cart | checkout
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('referrer', 500)->nullable();
            $table->string('source', 10)->default('direct'); // direct | search | social | referral | email | ads
            $table->string('source_name', 40)->nullable();    // google, facebook ...
            $table->string('device', 10)->nullable();         // desktop | mobile | tablet
            $table->string('browser', 30)->nullable();
            $table->string('os', 30)->nullable();
            $table->unsignedInteger('lcp')->nullable();        // ms
            $table->unsignedInteger('inp')->nullable();        // ms
            $table->decimal('cls', 6, 3)->nullable();
            $table->unsignedInteger('ttfb')->nullable();       // ms
            $table->timestamp('last_seen_at')->nullable();     // heartbeat, for live visitors and time on page
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('last_seen_at');
            $table->index(['product_id', 'created_at']);
            $table->index(['visitor_id', 'created_at']);
        });

        // What customers type in the search box.
        Schema::create('search_logs', function (Blueprint $table) {
            $table->id();
            $table->string('query', 120);
            $table->unsignedInteger('results_count');
            $table->char('visitor_id', 32)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['query', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_logs');
        Schema::dropIfExists('visits');
    }
};

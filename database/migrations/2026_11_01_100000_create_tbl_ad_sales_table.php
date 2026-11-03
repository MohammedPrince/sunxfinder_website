<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tbl_ad_sales')) {
            return;
        }

        // Plain indexed ids rather than foreign keys: a sale record must outlive the ad it paid for.
        Schema::create('tbl_ad_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable()->index();
            $table->string('client_name', 150)->nullable();
            $table->string('product', 30);
            $table->string('platform', 10)->default('app');
            $table->unsignedBigInteger('app_slider_id')->nullable()->index();
            $table->unsignedBigInteger('company_carousel_id')->nullable()->index();
            $table->string('link_url', 500)->nullable();
            $table->string('artwork_path', 255)->nullable();
            $table->text('request_note')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 3)->default('SDG');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable()->index();
            $table->string('status', 20)->default('requested')->index();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 150)->nullable();
            $table->text('admin_notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_ad_sales');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_customer_complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->enum('category', ['product_issue', 'service_issue', 'pricing_issue', 'delivery_issue', 'customer_service', 'other']);
            $table->text('description');
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending')->index();
            // Reserved for photo paths/metadata when durable upload storage is available.
            $table->json('photos')->nullable();
            $table->text('admin_reply')->nullable();
            $table->timestamp('customer_replied_at')->nullable();
            $table->timestamp('company_notified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_complaints');
    }
};

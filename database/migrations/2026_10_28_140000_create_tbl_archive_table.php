<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_archive', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->enum('type', [
                'customer',
                'product',
                'prodcut', // Legacy spelling accepted by existing app instructions.
                'product_spec',
                'offer',
                'requestd_quote', // Kept to match the customer app API payload.
                'requested_quote',
                'notification',
                'customer_notification',
                'company',
                'project',
                'project_milestone',
                'project_update',
                'rating',
                'rating_answer',
                'supplier',
                'appliance',
                'appliance_group',
                'company_carousel',
                'app_version',
                'rating_question',
                'customer_device_token',
                'api_endpoint',
                'sent_email',
                'other',
            ]);
            $table->unsignedBigInteger('item_id')->nullable();
            $table->json('data')->nullable();
            $table->string('source', 20)->default('website');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['customer_id', 'type']);
            $table->index(['type', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_archive');
    }
};

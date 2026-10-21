<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_customer_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->text('fcm_token');
            $table->char('token_hash', 64)->unique(
                'tbl_customer_device_tokens_token_hash_unique'
            );
            $table->string('platform', 20)->default('android');
            $table->string('device_name', 150)->nullable();
            $table->string('lang', 5)->default('en')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('customer_id', 'tbl_customer_device_tokens_customer_index');

            $table->foreign('customer_id')
                ->references('id')
                ->on('tbl_customers')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_device_tokens');
    }
};

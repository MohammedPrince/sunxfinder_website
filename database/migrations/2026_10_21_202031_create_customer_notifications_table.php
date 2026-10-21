<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_customer_notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('notification_id');
            $table->unsignedBigInteger('customer_id');

            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('notification_id')
                ->references('id')
                ->on('tbl_notifications')
                ->cascadeOnDelete();

            $table->unique(
                ['notification_id', 'customer_id'],
                'tbl_customer_notifications_unique_recipient'
            );

            $table->index(['customer_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_notifications');
    }
};

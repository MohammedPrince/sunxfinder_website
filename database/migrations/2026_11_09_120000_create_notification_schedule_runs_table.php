<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per attempt to send an automatic notification (scheduler or "Send next now"),
 * shown in Admin → Background jobs as done / partly delivered / failed.
 * Names and titles are copied so the row still reads well after a schedule is deleted.
 * No foreign keys on purpose (MyISAM on dev): plain indexes only.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('tbl_notification_schedule_runs')) {
            return;
        }

        Schema::create('tbl_notification_schedule_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->string('schedule_name', 150);
            $table->unsignedBigInteger('message_id')->nullable();
            $table->integer('message_order')->nullable();
            $table->string('message_title', 180)->nullable();
            $table->unsignedBigInteger('notification_id')->nullable();
            $table->string('trigger', 10)->default('auto');     // auto | manual
            $table->string('status', 10);                       // done | partial | failed
            $table->unsignedInteger('recipients')->default(0);  // customers
            $table->unsignedInteger('devices')->default(0);     // push tokens tried
            $table->unsignedInteger('delivered')->default(0);   // accepted by Firebase
            $table->unsignedInteger('failed_devices')->default(0);
            $table->string('error', 255)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamp('created_at')->nullable();

            $table->index('schedule_id');
            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_notification_schedule_runs');
    }
};

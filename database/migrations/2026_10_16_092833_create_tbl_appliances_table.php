<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tbl_appliances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('group_id')
                ->constrained('tbl_appliance_groups')
                ->cascadeOnDelete();

            $table->string('name', 150);
            $table->string('ar_name', 150);
            $table->unsignedInteger('watts');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_appliances');
    }
};

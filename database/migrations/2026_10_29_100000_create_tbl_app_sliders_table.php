<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_app_sliders', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120); // admin label, also the image alt text
            $table->string('description_ar', 255)->nullable();
            $table->string('description_en', 255)->nullable();
            $table->string('image_path', 255); // relative to public/, e.g. main/images/sliders/x.webp
            $table->unsignedSmallInteger('image_width')->nullable();
            $table->unsignedSmallInteger('image_height')->nullable();
            $table->string('link_url', 500)->nullable(); // empty = slide is not tappable
            $table->unsignedSmallInteger('interval_seconds')->default(5);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // The app query: active, not deleted, ordered.
            $table->index(['is_active', 'deleted_at', 'sort_order'], 'tbl_app_sliders_display_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_app_sliders');
    }
};

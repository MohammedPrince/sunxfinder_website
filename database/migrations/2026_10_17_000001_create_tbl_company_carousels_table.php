<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_company_carousels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->enum('platform', ['app', 'website', 'both'])
                ->default('app')
                ->index(); // app, website, both
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->unsignedSmallInteger('interval_seconds')->default(5);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['platform', 'is_active', 'sort_order'], 'tbl_company_carousel_display_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_company_carousels');
    }
};

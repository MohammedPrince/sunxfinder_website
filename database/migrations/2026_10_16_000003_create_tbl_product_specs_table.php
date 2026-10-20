<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('tbl_products')->cascadeOnDelete();
            $table->string('spec_name', 100);
            $table->string('spec_value', 150);
            $table->timestamps();
            $table->unique(['product_id', 'spec_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_product_specs');
    }
};

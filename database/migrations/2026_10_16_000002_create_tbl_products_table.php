<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('tbl_suppliers')
                ->restrictOnDelete();

            $table->string('name', 150);
            $table->string('model', 100)->nullable();

            $table->enum('type', [
                'inverter',
                'solar_panel',
                'battery',
                'other',
            ])->index();

            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['supplier_id', 'type']);
            $table->index('name');
            $table->index('model');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_products');
    }
};

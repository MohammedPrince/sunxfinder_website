<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // The 1-4 scale customers use to answer each rating question.
        // Admin can rename the labels, not add or remove points.
        Schema::create('tbl_rating_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('value')->unique();
            $table->string('label_en', 50);
            $table->string('label_ar', 50);
            $table->timestamps();
        });

        $now = now();

        DB::table('tbl_rating_values')->insert([
            ['value' => 1, 'label_en' => 'Poor', 'label_ar' => 'ضعيف', 'created_at' => $now, 'updated_at' => $now],
            ['value' => 2, 'label_en' => 'Good', 'label_ar' => 'جيد', 'created_at' => $now, 'updated_at' => $now],
            ['value' => 3, 'label_en' => 'Very good', 'label_ar' => 'جيد جداً', 'created_at' => $now, 'updated_at' => $now],
            ['value' => 4, 'label_en' => 'Excellent', 'label_ar' => 'ممتاز', 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rating_values');
    }
};

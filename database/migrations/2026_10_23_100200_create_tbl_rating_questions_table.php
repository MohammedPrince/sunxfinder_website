<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Questions admin sets for the rating form, e.g. "Price: how was the price?"
        Schema::create('tbl_rating_questions', function (Blueprint $table) {
            $table->id();
            $table->string('title_en', 100);
            $table->string('title_ar', 100);
            $table->string('question_en', 255)->nullable();
            $table->string('question_ar', 255)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();

        DB::table('tbl_rating_questions')->insert([
            [
                'title_en' => 'Price',
                'title_ar' => 'السعر',
                'question_en' => 'How was the price?',
                'question_ar' => 'كيف كان السعر؟',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title_en' => 'Service',
                'title_ar' => 'الخدمة',
                'question_en' => 'How was the service?',
                'question_ar' => 'كيف كانت الخدمة؟',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rating_questions');
    }
};

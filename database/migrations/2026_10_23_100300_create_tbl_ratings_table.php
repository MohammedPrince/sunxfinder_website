<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // One customer rating of a company, per quote request.
        Schema::create('tbl_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('offer_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();

            // Overall score from the app, 1-5. Feeds the company average.
            $table->unsignedTinyInteger('stars');
            $table->text('comment')->nullable();

            // Admin can hide a rating (e.g. abusive comment) without deleting it.
            $table->boolean('is_visible')->default(true);

            $table->timestamps();

            // One rating per request.
            $table->unique(['customer_id', 'quote_id']);
            $table->index(['company_id', 'is_visible']);
        });

        // Per-question answers on the 1-4 scale (tbl_rating_values).
        Schema::create('tbl_rating_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rating_id');
            $table->unsignedBigInteger('question_id')->index();
            $table->unsignedTinyInteger('value');
            $table->timestamps();

            $table->unique(['rating_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_rating_answers');
        Schema::dropIfExists('tbl_ratings');
    }
};

<?php

use App\Support\LegalDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tbl_legal_sections')) {
            Schema::create('tbl_legal_sections', function (Blueprint $table) {
                $table->id();
                $table->string('page', 30);
                // header: top of the page; section: numbered card; contact: closing box.
                $table->string('type', 20)->default('section');
                $table->string('eyebrow_en', 150)->nullable();
                $table->string('eyebrow_ar', 150)->nullable();
                $table->string('title_en');
                $table->string('title_ar');
                $table->longText('body_en')->nullable();
                $table->longText('body_ar')->nullable();
                $table->date('last_updated')->nullable();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['page', 'type', 'sort_order']);
            });
        }

        // Start with the current pages so their text is ready to edit. The website
        // falls back to the same text while a page has no rows, so a failure here
        // only means the admin loads it from Privacy & Terms instead.
        try {
            foreach (array_keys(LegalDefaults::FILES) as $page) {
                if (!\App\Models\LegalSection::withTrashed()->where('page', $page)->exists()) {
                    LegalDefaults::seed($page);
                }
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_legal_sections');
    }
};

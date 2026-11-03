<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_home_sections', function (Blueprint $table) {
            $table->id();
            $table->string('key', 50)->unique(); // e.g. featured_companies
            $table->boolean('is_visible')->default(true);
            $table->boolean('show_companies')->default(true);
            $table->string('title_en', 60)->nullable(); // null = the app's own title
            $table->string('title_ar', 60)->nullable();
            $table->unsignedInteger('sort_order')->default(0); // reserved for reordering sections later
            $table->timestamps();
        });

        // Today's behavior: the section shows, with companies.
        DB::table('tbl_home_sections')->insert([
            'key' => 'featured_companies',
            'is_visible' => true,
            'show_companies' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_home_sections');
    }
};

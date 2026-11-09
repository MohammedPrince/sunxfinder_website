<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * The default rows some migrations insert when they create their table. A database built
 * from database/schema/mysql-schema.sql (tests) has the tables but not these rows, so
 * tests seed them here. Safe to run again: it only fills empty tables / missing keys.
 */
class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $stamp = fn(array $row) => $row + ['created_at' => $now, 'updated_at' => $now];

        // 2026_10_23_100100: the 1-4 rating scale.
        DB::table('tbl_rating_values')->insertOrIgnore(array_map($stamp, [
            ['value' => 1, 'label_en' => 'Poor', 'label_ar' => 'ضعيف'],
            ['value' => 2, 'label_en' => 'Good', 'label_ar' => 'جيد'],
            ['value' => 3, 'label_en' => 'Very good', 'label_ar' => 'جيد جداً'],
            ['value' => 4, 'label_en' => 'Excellent', 'label_ar' => 'ممتاز'],
        ]));

        // 2026_10_23_100200: the two starting rating questions.
        if (!DB::table('tbl_rating_questions')->exists()) {
            DB::table('tbl_rating_questions')->insert(array_map($stamp, [
                ['title_en' => 'Price', 'title_ar' => 'السعر', 'question_en' => 'How was the price?', 'question_ar' => 'كيف كان السعر؟', 'sort_order' => 1, 'is_active' => true],
                ['title_en' => 'Service', 'title_ar' => 'الخدمة', 'question_en' => 'How was the service?', 'question_ar' => 'كيف كانت الخدمة؟', 'sort_order' => 2, 'is_active' => true],
            ]));
        }

        // 2026_10_23_100400: open quote requests allowed per customer.
        DB::table('tbl_settings')->insertOrIgnore($stamp(['key' => 'max_open_quotes', 'value' => '2']));

        // 2026_10_25_110000: the API servers the app can use.
        DB::table('tbl_api_endpoints')->insertOrIgnore(array_map($stamp, [
            ['name' => 'PRD', 'base_url' => 'https://sunxfinder.com/api/v1/customer', 'is_active' => true, 'is_online' => true],
            ['name' => 'DEV', 'base_url' => 'https://dev.sunxfinder.com/api/v1/customer', 'is_active' => false, 'is_online' => true],
        ]));

        // 2026_11_03_100000: the Home "featured companies" section, shown with companies.
        DB::table('tbl_home_sections')->insertOrIgnore($stamp(['key' => 'featured_companies', 'is_visible' => true, 'show_companies' => true]));
    }
}

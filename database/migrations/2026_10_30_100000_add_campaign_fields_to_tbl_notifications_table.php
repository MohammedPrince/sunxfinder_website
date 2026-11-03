<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rich customer campaigns (offers, new companies, news) reuse tbl_notifications so they
 * land in the app's existing inbox, unread count and read tracking.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_notifications', function (Blueprint $table) {
            // text = plain message, html = sanitized rich message (b, i, lists, links...)
            $table->string('format', 10)->default('text')->after('message_ar');

            $table->string('image_path', 255)->nullable()->after('format');
            $table->unsignedSmallInteger('image_width')->nullable()->after('image_path');
            $table->unsignedSmallInteger('image_height')->nullable()->after('image_width');

            // Call to action: an https:// page or a sunxfinder:// deep link.
            $table->string('link_url', 500)->nullable()->after('image_height');
            $table->string('link_label_en', 60)->nullable()->after('link_url');
            $table->string('link_label_ar', 60)->nullable()->after('link_label_en');

            // Featured company and its offer.
            $table->unsignedBigInteger('company_id')->nullable()->after('link_label_ar');
            $table->string('offer_title_en', 150)->nullable()->after('company_id');
            $table->string('offer_title_ar', 150)->nullable()->after('offer_title_en');
            $table->decimal('offer_price', 14, 2)->nullable()->after('offer_title_ar');
            $table->decimal('offer_old_price', 14, 2)->nullable()->after('offer_price');
            $table->string('offer_currency', 8)->nullable()->after('offer_old_price');
            $table->timestamp('offer_expires_at')->nullable()->after('offer_currency');

            // Audience "city": everyone in this city.
            $table->string('audience_city', 100)->nullable()->after('audience_type');

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_notifications', function (Blueprint $table) {
            $table->dropIndex(['company_id']);
            $table->dropColumn([
                'format', 'image_path', 'image_width', 'image_height',
                'link_url', 'link_label_en', 'link_label_ar',
                'company_id', 'offer_title_en', 'offer_title_ar', 'offer_price',
                'offer_old_price', 'offer_currency', 'offer_expires_at', 'audience_city',
            ]);
        });
    }
};

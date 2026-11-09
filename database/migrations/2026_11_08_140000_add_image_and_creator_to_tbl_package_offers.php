<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Package offers get an image (shown in the push, the inbox and the offer screen), and
 * record who built them: the company itself, or an admin from the customer campaign page.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('tbl_package_offers', function (Blueprint $table) {
            if (!Schema::hasColumn('tbl_package_offers', 'image_path')) {
                $table->string('image_path', 255)->nullable()->after('description_ar');
                $table->unsignedSmallInteger('image_width')->nullable()->after('image_path');
                $table->unsignedSmallInteger('image_height')->nullable()->after('image_width');
            }
            if (!Schema::hasColumn('tbl_package_offers', 'created_by_type')) {
                $table->string('created_by_type', 20)->default('company')->after('status'); // company | admin
                $table->unsignedBigInteger('created_by_id')->nullable()->after('created_by_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tbl_package_offers', function (Blueprint $table) {
            foreach (['image_path', 'image_width', 'image_height', 'created_by_type', 'created_by_id'] as $column) {
                if (Schema::hasColumn('tbl_package_offers', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

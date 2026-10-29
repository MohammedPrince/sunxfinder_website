<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    private const TYPES = [
        'customer', 'product', 'prodcut', 'product_spec', 'offer', 'requestd_quote', 'requested_quote',
        'notification', 'customer_notification', 'company', 'project', 'project_milestone', 'project_update',
        'rating', 'rating_answer', 'supplier', 'appliance', 'appliance_group', 'company_carousel', 'app_version',
        'rating_question', 'customer_device_token', 'api_endpoint', 'sent_email', 'other',
    ];

    public function up(): void
    {
        // Deleted app slides are archived like other admin items.
        $this->setTypes([...self::TYPES, 'app_slider']);
    }

    public function down(): void
    {
        DB::table('tbl_archive')->where('type', 'app_slider')->update(['type' => 'other']);
        $this->setTypes(self::TYPES);
    }

    private function setTypes(array $types): void
    {
        $list = implode(',', array_map(fn($type) => DB::getPdo()->quote($type), $types));
        DB::statement("ALTER TABLE tbl_archive MODIFY type ENUM({$list}) NOT NULL");
    }
};

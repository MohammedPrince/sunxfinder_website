<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A previous failed run may have left the table behind without its FK.
        Schema::dropIfExists('tbl_customer_device_tokens');

        // Match customer_id to the real type of tbl_customers.id (it differs on production).
        $parent = DB::selectOne(
            "SELECT c.COLUMN_TYPE AS column_type, t.ENGINE AS engine
             FROM information_schema.COLUMNS c
             JOIN information_schema.TABLES t
               ON t.TABLE_SCHEMA = c.TABLE_SCHEMA AND t.TABLE_NAME = c.TABLE_NAME
             WHERE c.TABLE_SCHEMA = DATABASE()
               AND c.TABLE_NAME = 'tbl_customers'
               AND c.COLUMN_NAME = 'id'"
        );

        $columnType = strtolower($parent->column_type ?? 'bigint unsigned');
        $isBig = str_contains($columnType, 'bigint');
        $isUnsigned = str_contains($columnType, 'unsigned');
        $canAddForeignKey = $parent && strtolower($parent->engine ?? '') === 'innodb';

        Schema::create('tbl_customer_device_tokens', function (Blueprint $table) use ($isBig, $isUnsigned, $canAddForeignKey) {
            $table->id();

            if ($isBig) {
                $table->bigInteger('customer_id', false, $isUnsigned);
            } else {
                $table->integer('customer_id', false, $isUnsigned);
            }

            $table->text('fcm_token');
            $table->char('token_hash', 64)->unique(
                'tbl_customer_device_tokens_token_hash_unique'
            );
            $table->string('platform', 20)->default('android');
            $table->string('device_name', 150)->nullable();
            $table->string('lang', 5)->default('en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index('customer_id', 'tbl_customer_device_tokens_customer_index');

            // MyISAM parents cannot hold FKs; skip the constraint rather than fail.
            if ($canAddForeignKey) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('tbl_customers')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_customer_device_tokens');
    }
};

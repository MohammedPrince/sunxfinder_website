<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // A MySQL JSON column serializes Unicode as \uXXXX when inspected. TEXT
        // lets us keep the same JSON structure while storing Arabic as UTF-8 text.
        Schema::table('tbl_archive', function (Blueprint $table) {
            $table->longText('data')->nullable()->change();
        });

        DB::table('tbl_archive')
            ->select(['id', 'data'])
            ->whereNotNull('data')
            ->orderBy('id')
            ->chunkById(250, function ($rows) {
                foreach ($rows as $row) {
                    $decoded = json_decode($row->data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        continue;
                    }

                    DB::table('tbl_archive')->where('id', $row->id)->update([
                        'data' => json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('tbl_archive', function (Blueprint $table) {
            $table->json('data')->nullable()->change();
        });
    }
};

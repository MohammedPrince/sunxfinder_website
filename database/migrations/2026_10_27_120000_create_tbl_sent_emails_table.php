<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_sent_emails', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_email', 191)->index();
            $table->string('recipient_name', 191)->nullable();
            $table->string('subject', 150);
            $table->longText('message');
            $table->string('lang', 2)->default('en');
            $table->timestamp('sent_at')->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_sent_emails');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_notifications', function (Blueprint $table) {
            $table->id();

            $table->string('sender_type', 20); // admin, company
            $table->unsignedBigInteger('sender_id')->nullable();

            $table->string('type', 40)->default('general');

            $table->string('title_en', 180);
            $table->string('title_ar', 180)->nullable();

            $table->text('message_en');
            $table->text('message_ar')->nullable();

            $table->string('audience_type', 20)->default('selected');
            // all, selected

            $table->string('status', 20)->default('published');
            // draft, published

            $table->timestamp('published_at')->nullable();
            $table->timestamps();
             $table->softDeletes();

            $table->index(['sender_type', 'sender_id']);
            $table->index(['type', 'status', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_notifications');
    }
};

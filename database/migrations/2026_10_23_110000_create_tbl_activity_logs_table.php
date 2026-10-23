<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Who did what, when and from where. Shown to admins only.
        Schema::create('tbl_activity_logs', function (Blueprint $table) {
            $table->id();

            // admin, company, customer, guest (not signed in) or system (queue/cron)
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();
            // Kept as text so the log still reads well after the account is deleted.
            $table->string('actor_name', 191)->nullable();

            // e.g. login, login_failed, created, updated, deleted, password_changed
            $table->string('action', 40);
            // e.g. auth, customers, companies, quotes, projects, ratings, catalog, app
            $table->string('category', 30);

            // The record the action was done on, if any.
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('subject_label', 191)->nullable();

            $table->string('description', 500);
            // Changed fields (old -> new) and extra context. Never passwords or tokens.
            $table->json('properties')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url', 500)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['actor_type', 'actor_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['action', 'created_at']);
            $table->index(['category', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_activity_logs');
    }
};

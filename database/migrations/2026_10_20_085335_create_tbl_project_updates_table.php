<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_project_updates', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('project_id');

            // Polymorphic-style author reference without assuming a users table.
            $table->string('updated_by_type', 30)->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();

            // Previous and new project status.
            $table->string('old_status', 30)->nullable();
            $table->string('new_status', 30)->nullable();

            // Progress recorded at the time of the update.
            $table->unsignedTinyInteger('progress_percent')->nullable();

            // Customer-facing update message.
            $table->text('message')->nullable();
            $table->boolean('visible_to_customer')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')
                ->references('id')
                ->on('tbl_projects')
                ->cascadeOnDelete();

            $table->index(['project_id', 'created_at']);
            $table->index(['updated_by_type', 'updated_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_project_updates');
    }
};

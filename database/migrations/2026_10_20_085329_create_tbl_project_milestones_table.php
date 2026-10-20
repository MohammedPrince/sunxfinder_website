<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tbl_project_milestones', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('project_id');

            $table->string('title', 150);
            $table->text('description')->nullable();

            // pending, in_progress, completed
            $table->string('status', 30)->default('pending');

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('project_id')
                ->references('id')
                ->on('tbl_projects')
                ->cascadeOnDelete();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_project_milestones');
    }
};

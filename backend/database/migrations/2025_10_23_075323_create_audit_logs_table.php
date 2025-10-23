<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('action', ['upload', 'download', 'delete', 'create_folder', 'delete_folder']);
                $table->enum('target_type', ['folder', 'file']);
                $table->unsignedBigInteger('target_id');
                $table->timestamp('timestamp')->useCurrent();

                $table->index(['user_id', 'action', 'timestamp']);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

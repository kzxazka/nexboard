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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('client_name');
            $table->string('client_email')->nullable();
            $table->text('description')->nullable();
            $table->date('deadline')->nullable();
            $table->enum('status', ['planning', 'in_progress', 'review', 'completed', 'on_hold'])->default('planning');
            $table->decimal('budget', 12, 2)->nullable();
            $table->json('columns')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};

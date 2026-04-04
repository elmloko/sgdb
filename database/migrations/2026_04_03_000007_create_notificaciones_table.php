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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo', ['asignacion', 'sla_vence', 'escalamiento', 'resolucion', 'comentario', 'critico']);
            $table->string('titulo');
            $table->text('mensaje');
            $table->boolean('leido')->default(false);
            $table->enum('canal', ['sistema', 'email', 'ambos'])->default('sistema');
            $table->foreignId('bug_id')->nullable()->constrained('bugs')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};

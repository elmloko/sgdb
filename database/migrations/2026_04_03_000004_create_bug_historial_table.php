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
        Schema::create('bug_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->enum('accion', ['cambio_estado', 'comentario', 'asignacion', 'adjunto', 'resolucion']);
            $table->string('valor_anterior')->nullable();
            $table->string('valor_nuevo')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_historial');
    }
};

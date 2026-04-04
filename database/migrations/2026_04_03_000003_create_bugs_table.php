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
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_num', 20)->unique(); // Ej: BUG-2026-0001
            $table->string('titulo');
            $table->text('descripcion');
            $table->enum('prioridad', ['critica', 'alta', 'media', 'baja']);
            $table->enum('estado', [
                'nuevo',
                'en_revision',
                'asignado',
                'en_desarrollo',
                'en_qa',
                'resuelto',
                'cerrado',
                'rechazado',
                'reabierto',
            ])->default('nuevo');
            $table->foreignId('proyecto_id')->constrained('proyectos')->restrictOnDelete();
            $table->string('modulo')->nullable();
            $table->enum('entorno', ['produccion', 'staging', 'desarrollo'])->default('desarrollo');
            $table->text('pasos_reproducir')->nullable();
            $table->text('comportamiento_esperado')->nullable();
            $table->text('comportamiento_actual')->nullable();
            $table->foreignId('reportado_por')->constrained('users')->restrictOnDelete();
            $table->foreignId('asignado_a')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sla_vence_en')->nullable();
            $table->timestamp('cerrado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bugs');
    }
};

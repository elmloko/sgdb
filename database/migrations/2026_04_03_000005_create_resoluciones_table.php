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
        Schema::create('resoluciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->unique()->constrained('bugs')->cascadeOnDelete();
            $table->enum('tipo', ['correccion', 'no_reproducible', 'duplicado', 'disenio', 'rechazado']);
            $table->text('causa_raiz');
            $table->text('solucion_aplicada');
            $table->json('archivos_modificados')->nullable();
            $table->string('commit_ref')->nullable();
            $table->boolean('requiere_deploy')->default(false);
            $table->text('notas_qa')->nullable();
            $table->text('prevencion_futura')->nullable();
            $table->foreignId('resuelto_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resoluciones');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permitir bugs reportados desde el formulario público (sin usuario registrado)
        Schema::table('bugs', function (Blueprint $table) {
            // Hacer FK nullable para reportantes externos
            $table->foreignId('reportado_por')->nullable()->change();
            $table->string('nombre_reportante', 150)->nullable()->after('reportado_por');
            $table->string('email_reportante', 150)->nullable()->after('nombre_reportante');
        });

        // Tabla de adjuntos (fotos/archivos) vinculados a un bug
        Schema::create('bug_adjuntos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bug_id')->constrained('bugs')->cascadeOnDelete();
            $table->string('nombre_original');        // nombre del archivo subido
            $table->string('ruta');                   // ruta relativa en storage/app/public
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('tamanio')->nullable(); // bytes
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bug_adjuntos');

        Schema::table('bugs', function (Blueprint $table) {
            $table->dropColumn(['nombre_reportante', 'email_reportante']);
            $table->foreignId('reportado_por')->nullable(false)->change();
        });
    }
};

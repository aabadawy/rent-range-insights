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
        Schema::create('districts', function (Blueprint $table) {
            $table->id();

            // N_SQ_QU - Numéro de section de quartier
            $table->string('district_section_number')->unique();

            // Numéro du quartier / C_QU
            $table->integer('district_number')->index();

            // C_QUINSEE - Code INSEE du quartier
            $table->string('insee_code')->index();

            // L_QU - Nom du quartier
            $table->string('district_name');

            // C_AR - Code de l'arrondissement
            $table->string('borough_code');

            // N_SQ_AR - Numéro de section de l'arrondissement
            $table->string('borough_section_number');

            // PERIMETRE - Périmètre du quartier (meters)
            $table->string('perimeter')->nullable();

            // SURFACE - Surface du quartier (square meters)
            $table->decimal('surface_area', 15, 8)->nullable();

            $table->decimal('longitude', 11, 8)->nullable()->index();

            $table->decimal('latitude', 10, 8)->nullable()->index();

            // ZIP CODE - Code postal
            $table->string('postal_code', 5)->index();

            $table->index(['longitude', 'latitude'], 'coordinate_index');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};

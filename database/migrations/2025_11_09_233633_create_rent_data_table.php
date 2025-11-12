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
        Schema::create('rent_data', function (Blueprint $table) {
            $table->id();

            // Secteurs géographiques
            $table->string('geographic_sector')->nullable();

            // Numéro du quartier (Foreign Key)
            $table->integer('district_number')->index();

            // Nom du quartier
            $table->string('district_name');

            // Nombre de pièces principales
            $table->integer('number_of_rooms')->index();

            // Epoque de construction (e.g., "Apres 1990", "1971-1990")
            $table->string('construction_period')->index();

            // Type de location (e.g., "meublé", "non meublé")
            $table->boolean('rental_type')->default(true)->index();

            // Loyers de référence (average rent in €/m²)
            $table->bigInteger('reference_rent')->nullable();

            // Loyers de référence majorés (maximum rent in €/m²)
            $table->bigInteger('maximum_rent')->nullable()->index();

            // Loyers de référence minorés (minimum rent in €/m²)
            $table->bigInteger('minimum_rent')->nullable()->index();

            // Année
            $table->year('year')->index();

            // Ville
            $table->string('city')->default('PARIS');

            $table->timestamps();

            // Composite index for common queries
            $table->index(['district_number', 'number_of_rooms', 'construction_period', 'rental_type'], 'rent_search_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rent_data');
    }
};

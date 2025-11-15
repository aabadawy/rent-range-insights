<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();

            $table->integer('district_number')->index();

            $table->string('district_name');

            $table->integer('number_of_rooms')->index();

            $table->smallInteger('construction_period')->index();

            $table->boolean('rental_type')->default(true)->index();

            $table->bigInteger('reference_rent')->nullable()->index();

            $table->bigInteger('maximum_rent')->nullable()->index();

            $table->bigInteger('minimum_rent')->nullable()->index();

            $table->year('year')->index();

            $table->string('city')->default('PARIS');

            $table->string('geographic_sector')->nullable();

            $table->geometry('geometry_shape', srid: 4326)->spatialIndex();

            $table->decimal('latitude', 10, 8)->index();

            $table->decimal('longitude', 11, 8)->index();

            $table->binary('unit_md5', 16)
                ->generatedAs(DB::raw(
                    'UNHEX(MD5(CONCAT(district_number, number_of_rooms, construction_period, rental_type, year, city, geographic_sector, latitude, longitude)))'
                ))->stored()->unique();

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
        Schema::dropIfExists('units');
    }
};

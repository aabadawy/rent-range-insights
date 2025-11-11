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
        Schema::create('district_boundaries', function (Blueprint $table) {
            $table->id();

            // District number (Foreign Key)
            $table->integer('district_number')->index();

            // Latitude coordinate of boundary point
            $table->decimal('latitude', 10, 8);

            // Longitude coordinate of boundary point
            $table->decimal('longitude', 11, 8);

            // Order/sequence of this point in the polygon (for drawing the shape)
            $table->integer('sequence_order');

            $table->timestamps();

            // Foreign key constraint
            $table->foreign('district_number')
                  ->references('district_number')
                  ->on('districts')
                  ->onDelete('cascade');

            // Index for efficient polygon queries
            $table->index(['district_number', 'sequence_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('district_boundaries');
    }
};

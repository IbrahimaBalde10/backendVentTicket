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
        Schema::create('trajets', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('point_depart');
            $table->string('point_arrivee');
            $table->decimal('prix', 8, 2);
            $table->text('description')->nullable(); //colonne ajouté
            $table->enum('statut', ['actif', 'inactif'])->default('actif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trajets');
    }
};

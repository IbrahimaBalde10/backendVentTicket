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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transaction_id'); // ID de la transaction associée
            $table->unsignedBigInteger('user_id'); // ID du type de user (client)
            $table->string('nom');
            $table->unsignedBigInteger('trajet_id'); // ID du trajet acheté
            $table->enum('type', ['aller_simple', 'aller_retour']);
            $table->text('qr_code')->nullable(); // Champ pour le code QR
            $table->enum('statut', ['valide', 'expire']); // état du ticket
            $table->timestamp('purchase_date')->nullable();
            $table->timestamp('expiration_date')->nullable();
            $table->date('date_depart'); // Date de départ du trajet
            $table->time('heure_depart'); // Heure de départ du trajet
            $table->timestamps();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('trajet_id')->references('id')->on('trajets');
            $table->foreign('transaction_id')->references('id')->on('transactions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};

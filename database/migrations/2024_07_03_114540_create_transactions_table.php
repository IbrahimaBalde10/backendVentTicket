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
    Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // ID de l'utilisateur effectuant la transaction
            $table->decimal('total_amount', 10, 2); // Montant total de la transaction
            $table->integer('quantity')->nullable(); // Quantité de tickets achetés (nullable pour les abonnements)
            $table->decimal('price', 8, 2); // Prix unitaire du ticket ou de l'abonnement
            $table->enum('transaction_name', ['ticket', 'subscription']); // Type de transaction
            // $table->unsignedBigInteger('subscription_type_id')->nullable(); // ID du type d'abonnement (nullable pour les tickets)
            $table->string('telephoneClient'); // Numéro de téléphone du client
            $table->enum('methodePaiement', ['espece', 'carte', 'mobile', 'en_ligne']); // Méthode de paiement
            $table->timestamps();
            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('subscription_type_id')->references('id')->on('subscription_types')->onDelete('set null');

    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

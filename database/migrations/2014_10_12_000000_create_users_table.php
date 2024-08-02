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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('telephone')->unique();
            $table->enum('role', ['Client', 'Vendeur', 'Comptable', 'Admin'])->default('Client');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->enum('status', ['active', 'desactive'])->default('active');
            $table->string('profile_photo')->nullable(); // Ajouter cette ligne
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

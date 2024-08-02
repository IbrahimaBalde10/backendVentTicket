<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('heuresDeparts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trajet_id')->constrained('trajets')->onDelete('cascade');
            $table->time('heureDepart');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heuresDeparts');
    }
};

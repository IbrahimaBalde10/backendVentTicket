<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\HeureDeDepart; // Dates_de_depart model
use App\Models\DateDeDepart; // Heures_de_depart model

class Trajet extends Model
{
    use HasFactory;

    protected $fillable = ['nom', 'point_depart', 'point_arrivee', 'prix', 'description', 'statut'];

     public function datesDeDepart()
    {
        return $this->hasMany(DateDeDepart::class);
    }

    public function heuresDeDepart()
    {
        return $this->hasMany(HeureDeDepart::class);
    }
}

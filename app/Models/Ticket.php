<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

use App\Models\Transaction; // Transaction model
use App\Models\TicketType; // TicketType model


class Ticket extends Model
{
    use HasFactory;

     public function type()
    {
        return $this->belongsTo(TicketType::class, 'ticket_type_id');
    }
    
    // protected $fillable = [
    //     'transaction_id', 
    //     'ticket_type_id',  
    //     'qr_code',
    //     'statut',
    //     'purchase_date',
    //     'expiration_date',
    //     'created_at',
    //     'updated_at',
    // ];

    // Indique les attributs qui peuvent être assignés en masse
    protected $fillable = [
        'transaction_id',
        'user_id',
        'trajet_id',
        'type',
        'nom', // Ajout de la colonne 'nom' à la liste des attributs remplissables
        'qr_code',
        'statut',
        'purchase_date',
        'expiration_date',
        'date_depart',
        'heure_depart' 
    ];

    // 
 // Relation avec le modèle User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec le modèle Trajet
    public function trajet()
    {
        return $this->belongsTo(Trajet::class);
    }

    // Relation avec le modèle Transaction
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    // 

    protected $dates = [
        'purchase_date',
        'expiration_date',
    ];
 public function calculateExpirationDate($type)
    {
        $this->purchase_date = Carbon::now();

        if ($type == 'Aller simple') {
            $this->expiration_date = $this->purchase_date->copy()->addHour(2);
        } elseif ($type == 'Aller retour') {
            $this->expiration_date = $this->purchase_date->copy()->addHours(5);
        }

        $this->save();
    }

    public function updateStatut()
    {
        $now = Carbon::now();

        if ($this->expiration_date && $now->greaterThanOrEqualTo($this->expiration_date)) {
            $this->statut = 'expire';
        } else {
            $this->statut= 'valide';
        }

        $this->save();
    }

public function getRemainingTimeAttribute()
    {
        $now = Carbon::now();

        if ($this->expiration_date && is_string($this->expiration_date)) {
            $expirationDate = Carbon::parse($this->expiration_date);
            if ($expirationDate->greaterThan($now)) {
                // return $expirationDate->diffForHumans($now, true);

                return response()->json(['Il vous reste: ' => $expirationDate->diffForHumans($now, true) ]);
            }
        }

        return 'Votre ticket est expiré';
    }


}

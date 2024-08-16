<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Ticket; // Ticket model
use App\Models\User; // User model

class Transaction extends Model
{
    use HasFactory;
    
     protected $fillable = [
        'user_id',
        'total_amount',
        'quantity',
        'price',
        'transaction_name',
        'telephoneClient',
        'methodePaiement',
        'start_date',
        'end_date',
        
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }
    
    // Relation avec l'utilisateur correspondant à user_id
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relation avec l'utilisateur correspondant à telephoneClient
    public function client()
    {
        return $this->belongsTo(User::class, 'telephoneClient', 'telephone');
    }
    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

     public function ticketType()
    {
        return $this->belongsTo(TicketType::class);
    }

    public function subscriptionType()
    {
        return $this->belongsTo(SubscriptionType::class);
    }
    
}

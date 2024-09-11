<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Trajet;
use App\Models\SubscriptionType;
use App\Models\Subscription;
use App\Models\Transaction;

class rechercheController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        // Rechercher dans la table Tickets
        $tickets = Ticket::where('type', 'LIKE', "%{$query}%")
            ->orWhere('statut', 'LIKE', "%{$query}%")
            ->orWhere('nom', 'LIKE', "%{$query}%")
            ->orWhere('type', 'LIKE', "%{$query}%")
            ->with(['user', 'trajet']) // Inclure les relations User et Trajet
            ->get();

        // Rechercher dans la table Users
        $users = User::where('nom', 'LIKE', "%{$query}%")
            ->orWhere('prenom', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('role', 'LIKE', "%{$query}%")
            ->orWhere('status', 'LIKE', "%{$query}%")
            ->orWhere('telephone', 'LIKE', "%{$query}%")
            ->get();

        // Rechercher dans la table Trajets
        $trajets = Trajet::where('prix', 'LIKE', "%{$query}%")
            ->orWhere('nom', 'LIKE', "%{$query}%")
            ->orWhere('point_depart', 'LIKE', "%{$query}%")
            ->orWhere('point_arrivee', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orWhere('statut', 'LIKE', "%{$query}%")
            ->get();

           // Rechercher dans la table subscription_types
        $subscription_types = SubscriptionType::where('price', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orWhere('statut', 'LIKE', "%{$query}%")
            ->get();

           // Rechercher dans la table subscriptions
        $subscriptions = Subscription::where('statut', 'LIKE', "%{$query}%")
            ->get();

          // Rechercher dans la table transactions
        $transactions = Transaction::where('transaction_name', 'LIKE', "%{$query}%")
            ->orWhere('methodePaiement', 'LIKE', "%{$query}%")
            ->get();
            
        return response()->json([
            'tickets' => $tickets,
            'users' => $users,
            'trajets' => $trajets,
            'subscription_types' => $subscription_types,
            'subscriptions' => $subscriptions,
            'transactions' => $transactions,
        ]);
    }
}

<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Trajet;

class rechercheController extends Controller
{
    public function search(Request $request)
    {
        $query = $request->input('query');

        // Rechercher dans la table Tickets
        $tickets = Ticket::where('type', 'LIKE', "%{$query}%")
            ->orWhere('statut', 'LIKE', "%{$query}%")
            ->orWhere('nom', 'LIKE', "%{$query}%")
            ->with(['user', 'trajet']) // Inclure les relations User et Trajet
            ->get();

        // Rechercher dans la table Users
        $users = User::where('nom', 'LIKE', "%{$query}%")
            ->orWhere('prenom', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->orWhere('telephone', 'LIKE', "%{$query}%")
            ->get();

        // Rechercher dans la table Trajets
        $trajets = Trajet::where('prix', 'LIKE', "%{$query}%")
            ->orWhere('nom', 'LIKE', "%{$query}%")
            ->get();

        return response()->json([
            // 'tickets' => $tickets,
            'users' => $users,
            'trajets' => $trajets,
        ]);
    }
}

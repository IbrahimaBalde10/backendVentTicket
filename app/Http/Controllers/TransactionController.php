<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\Trajet;
use App\Models\SubscriptionType;

class TransactionController extends Controller
{
    // statistique du user connecte
public function statistiquesUser(Request $request)
{
    try {
        // Récupérer l'utilisateur authentifié
        $user = auth()->user();

        // Vérifier si l'utilisateur est authentifié
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // Nombre de tickets liés à l'utilisateur
        $nombreTickets = Ticket::where('user_id', $user->id)->count();

        // Nombre d'abonnements liés à l'utilisateur
        $nombreAbonnements = Subscription::where('user_id', $user->id)->count();

        // Nombre de transactions liées à l'utilisateur
        $nombreTransactions = Transaction::where('user_id', $user->id)->count();

        // Montant total des transactions
        $montantTotal = Transaction::where('user_id', $user->id)->sum('total_amount');

         // Nombre de trajets
        $nombreTrajets = Trajet::count();


        // Nombree de types dabonnement
        $nombreTypeAbonnements = SubscriptionType::count();

        return response()->json([
            'Bonjour' => $user->nom .' '.$user->prenom,
            'nombre_tickets' => $nombreTickets,
            'nombre_abonnements' => $nombreAbonnements,
            'nombre_transactions' => $nombreTransactions,
            'montant_total' => $montantTotal,
            'nombreTrajets' => $nombreTrajets,
            'nombreTypeAbonnements' => $nombreTypeAbonnements
        ]);

    } catch (\Exception $e) {
        // Gérer les exceptions
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des statistiques'], 500);
    }
}

    // Méthode pour lister les transactions de l'utilisateur
    // public function index()
    // {
    
    //     $transactions = Transaction::all();
    //     return response()->json($transactions);
    // }
        // public function index(Request $request)
        // {
        //     $perPage = $request->input('perPage', 4);
        //     $page = $request->input('page', 1);

        //     $transactions = Transaction::paginate($perPage, ['*'], 'page', $page);
        //     return response()->json($transactions);
        // }

        public function index(Request $request)
        {
            $perPage = $request->input('perPage', 4);
            $page = $request->input('page', 1);

            $transactions = Transaction::with(['user', 'client'])
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json($transactions);
        }



    

// Supprimer
    public function destroy($id)
    {
        try{
               $transaction = Transaction::findOrFail($id);
        $transaction->delete();
        return response()->json(['message' => 'transaction deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'transaction non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de la transaction'], 500);
    }
}

 public function summary()
    {
       
        $totalTransactions = Transaction::count();
        $totalAmount = Transaction::sum('total_amount');
        // $totalAmount = Transaction::sum('price');
        return response()->json([
            'total_transactions' => $totalTransactions,
            'total_amount' => $totalAmount,
        ]);

        
    }

    public function transactionsByType()
    {
        $transactionsByType = Transaction::select('transaction_name', \DB::raw('count(*) as count'))
            ->groupBy('transaction_name')
            ->get();

        return response()->json($transactionsByType);
    }

    public function totalTransactionsByType()
    {
        $totalTransactionsByType = Transaction::select('transaction_name', \DB::raw('sum(total_amount) as total_amount'))
            ->groupBy('transaction_name')
            ->get();

        return response()->json($totalTransactionsByType);
    }

    public function getDailyRevenues()
    {
        $revenues = DB::table('transactions')
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total_revenue'))
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($revenues);
    }

    
    // Obtenir mes transactions et ses details
 public function maConso(Request $request)
{
    // Récupérer l'utilisateur authentifié
    $user = auth()->user();

    // Récupérer toutes les transactions de l'utilisateur
    $transactions = Transaction::where('user_id', $user->id)->get();

    // Vérifier si l'utilisateur a des transactions
    if ($transactions->isNotEmpty()) {
        // Parcourir les transactions et formater les résultats
        $formattedTransactions = $transactions->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_name' => $transaction->transaction_name,
                'total_amount' => $transaction->total_amount,
                'date' => $transaction->created_at->format('Y-m-d H:i:s'),
                'details' => $this->getTransactionDetails($transaction), // Fonction pour obtenir les détails du type spécifique
            ];
        });

        // Retourner les transactions dans la réponse JSON
        return response()->json([
            'has_transactions' => true,
            'transactions' => $formattedTransactions,
        ]);
    }

    // Si aucune transaction n'existe
    return response()->json([
        'has_transactions' => false,
        'message' => 'Vous n\'avez pas effectué de transactions.'
    ]);
}


// Fonction pour récupérer les détails spécifiques à une transaction
private function getTransactionDetails($transaction)
{
    if ($transaction->transaction_name === 'ticket') {
        // Chercher les détails du ticket associé à la transaction
        $ticket = Ticket::where('transaction_id', $transaction->id)->first();

        if ($ticket) {
            // Chercher le trajet lié au ticket pour obtenir son prix
            $trajet = Trajet::where('id', $ticket->trajet_id)->first();
            if ($trajet) {
                // Calculer le prix en fonction du type de ticket
                $prix = 0;
                if ($ticket->type === 'aller_retour') {
                    // Prix du trajet multiplié par 2 pour un aller-retour
                    $prix = $trajet->prix * 2;
                } elseif ($ticket->type === 'aller_simple') {
                    // Prix du trajet pour un aller-simple
                    $prix = $trajet->prix;
                }

                return [
                    'ticket_type' => $ticket->type,
                    'status' => $ticket->statut,
                    // 'valid_until' => $ticket->valid_until,
                    // 'qr_code' => $ticket->qr_code,
                    'prix' => $prix, // Ajout du prix calculé
                    'trajet' => [
                        'nom' => $trajet->nom, // Nom du trajet
                        'prix' => $trajet->prix // Prix unitaire du trajet
                    ]
                ];
            }
        }
    } elseif ($transaction->transaction_name === 'subscription') {
        // Chercher les détails de l'abonnement associé à la transaction
        $subscription = Subscription::where('transaction_id', $transaction->id)->first();

        if ($subscription) {
            return [
                'subscription_type' => $subscription->subscriptionType->name,
                'status' => $subscription->statut,
                'end_date' => $subscription->end_date,
                // 'qr_code' => $subscription->qr_code,
            ];
        }
    }

    // Si aucun détail spécifique n'est trouvé, retourner null
    return null;
}

 
// Méthode pour afficher les détails d'une transaction spécifique
    public function show($id)
    {
       try{
         $transaction = Transaction::with(['user', 'client'])->findOrFail($id);
        return response()->json($transaction);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas type de ticket non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Cette transaction n\'est pas trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de transaction'], 500);
    }
}


}
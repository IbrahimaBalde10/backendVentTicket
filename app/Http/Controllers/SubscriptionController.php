<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Subscription;
use App\Models\SubscriptionType;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;


class SubscriptionController extends Controller
{
    public function index()
    {
     $subscriptions = Subscription::with('type')->get();
        
        // Transformer les données pour inclure le nom du type de ticket
        $subscriptions = $subscriptions->map(function($subscription) {
            return [
                'id' => $subscription->id,
                // 'transaction_id' => $subscription->transaction_id,
                'name' => $subscription->type->name, 
                'price' => $subscription->type->price, // Prix du type de ticket
                'statut' => $subscription->statut,
                'start_date' => $subscription->start_date,
                'end_date' => $subscription->end_date,
            ];
        });

        return response()->json($subscriptions);
    }

//     // Méthode pour créer un abonnement
// public function create(Request $request)
// {
//     // Validation des données entrantes
//     $request->validate([
//         'subscription_type_id' => 'required|exists:subscription_types,id',
//         'telephone' => 'required|string',
//         'methodePaiement' =>  'required|in:espece,carte,mobile,en_ligne',
//         // Vous pouvez activer la validation des dates si elles sont nécessaires
//         // 'start_date' => 'required|date',
//         // 'end_date' => 'required|date|after:start_date',
//     ]);

//     // Récupérer les informations sur le type d'abonnement
//     $subscriptionType = SubscriptionType::findOrFail($request->subscription_type_id);
//     $price = $subscriptionType->price;

//      // Récupérer l'utilisateur authentifié
//     $user = auth()->user();

//     // Créer une nouvelle transaction pour l'abonnement
//     $transaction = new Transaction();
//     $transaction->user_id = auth()->id();
//     $transaction->total_amount = $price;
//     $transaction->quantity = 1; // Un abonnement est unique
//     $transaction->price = $price;
//     $transaction->transaction_name = 'subscription';
//     // $transaction->subscription_type_id = $subscriptionType->id;
//     $transaction->telephoneClient = $user->telephone;
//     $transaction->methodePaiement = $methodePaiement;
//     // $transaction->calculateEndDate($subscriptionType->name); // Utilisation du nom du type d'abonnement
//     $transaction->save();

//     // Créer l'abonnement correspondant
//     $subscription = new Subscription();
//     $subscription->user_id = auth()->id();
//     $subscription->subscription_type_id = $subscriptionType->id;
//     $subscription->calculateEndDate($subscriptionType->name); // Utilisation du nom du type d'abonnement
//     $subscription->updateStatut(); // Mettre à jour le statut de l'abonnement
//     $subscription->save();

//     return response()->json([
//         'message' => 'Subscription created successfully',
//         'subscription' => $subscription,
//         'transaction' => $transaction,
//         'Abonnement:' => $subscriptionType->name
//     ]);
// }

public function create(Request $request)
{
    // Validation des données entrantes
    $request->validate([
        'subscription_type_id' => 'required|exists:subscription_types,id',
        'methodePaiement' => 'required|in:espece,carte,mobile,en_ligne',
    ]);

    // Récupérer les informations sur le type d'abonnement
    $subscriptionType = SubscriptionType::findOrFail($request->subscription_type_id);
    $price = $subscriptionType->price;

    // Récupérer l'utilisateur authentifié
    $user = auth()->user();

    // Vérifiez si l'utilisateur est authentifié
    if (!$user) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }

    // Créer une nouvelle transaction pour l'abonnement
    $transaction = new Transaction();
    $transaction->user_id = $user->id;
    $transaction->total_amount = $price;
    $transaction->quantity = 1; // Un abonnement est unique
    $transaction->price = $price;
    $transaction->transaction_name = 'subscription';
    $transaction->telephoneClient = $user->telephone;
    $transaction->methodePaiement = $request->methodePaiement;
    $transaction->save();

    // Créer l'abonnement correspondant
    $subscription = new Subscription();
    $subscription->user_id = $user->id;
    $subscription->subscription_type_id = $subscriptionType->id;
    $subscription->transaction_id = $transaction->id;
    $subscription->start_date = now();
    $subscription->calculateEndDate($subscriptionType->name); // Utilisation du nom du type d'abonnement
    $subscription->updateStatut(); // Mettre à jour le statut de l'abonnement
    $subscription->save();

    return response()->json([
        'message' => 'Subscription created successfully',
        'subscription' => $subscription,
        'transaction' => $transaction,
        'Abonnement' => $subscriptionType->name
    ]);
}


    // Méthode pour afficher les abonnements d'un utilisateur
    // public function index()
    // {
    //     $subscriptions = Subscription::where('user_id', auth()->id())->get();

    //     return response()->json($subscriptions);
    // }

    // Méthode pour afficher les détails d'un abonnement spécifique
    public function show($id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->user_id != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json($subscription);
    }

    // Méthode pour mettre à jour un abonnement
    public function update(Request $request, $id)
    {
        $subscription = Subscription::findOrFail($id);

        if ($subscription->user_id != auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'subscription_type_id' => 'required|exists:subscription_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
        ]);

        $subscription->subscription_type_id = $request->subscription_type_id;
        $subscription->start_date = $request->start_date;
        $subscription->end_date = $request->end_date;
        $subscription->save();

        return response()->json([
            'message' => 'Subscription updated successfully',
            'subscription' => $subscription,
        ]);
    }

    // Méthode pour supprimer un abonnement
    public function destroy($id)
    {
         try{
        $subscription = Subscription::findOrFail($id);
        $subscription->delete();
        return response()->json(['message' => 'subscription deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'subscription non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails du subscription'], 500);
    }
    }


    // Méthode pour mettre à jour un abonnement
    public function updateSubscription(Request $request, $id)
    {
       // Récupérer l'abonnement à mettre à jour
        $subscription = Subscription::findOrFail($id);

        // Mettre à jour les attributs de l'abonnement
        if ($request->has('end_date')) {
            $subscription->end_date = Carbon::parse($request->input('end_date'));
        }

        // Appel de la méthode pour mettre à jour le statut de l'abonnement
        $subscription->updateStatut();

        // Enregistrer les modifications de l'abonnement
        $subscription->save();

        // Retourner une réponse JSON avec le message de succès et les détails de l'abonnement mis à jour
        return response()->json([
            'message' => 'Abonnement updated successfully',
            'subscription' => $subscription,
        ]);

    }

// methode permettant aux abonnées de verifier le statut de leur abonnement
public function checkSubscriptionStatus(Request $request)
{
    $user = auth()->user();

    $subscription = Subscription::where('user_id', $user->id)->latest()->first();

    if ($subscription) {
        $subscription->updateStatut(); // Mettez à jour le statut avant de renvoyer la réponse
        return response()->json([
            'status' => $subscription->statut,
            'end_date' => $subscription->end_date,
            // 'status' => $subscription->su
            'Abonnement:' => $subscription->subscription_type_id
        ]);
    }

    return response()->json([
        'message' => 'No subscription found'
    ], 404);
}

// verifier le satut de l'abonnement du client via son num telephone
public function checkSubscriptionStatusTel(Request $request)
{
    $request->validate([
        'telephone' => 'required',
    ]);

    $user = User::where('telephone', $request->telephone)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    $subscription = Subscription::where('user_id', $user->id)->latest()->first();

    if ($subscription) {
        $subscription->updateStatut(); // Mettez à jour le statut avant de renvoyer la réponse
        return response()->json([
            'status' => $subscription->statut,
            'end_date' => $subscription->end_date
        ]);
    }

    return response()->json([
        'message' => 'No subscription found'
    ], 404);
}

//Reabonner un client via son Tel et type d'abonnement indique
public function renewSubscription(Request $request)
{
    $request->validate([
        'telephone' => 'required',
        'subscription_type_id' => 'required|exists:subscription_types,id'
    ]);

    $user = User::where('telephone', $request->telephone)->first();

    if (!$user) {
        return response()->json([
            'message' => 'User not found'
        ], 404);
    }

    $subscription = Subscription::where('user_id', $user->id)->latest()->first();

    if ($subscription && $subscription->statut == 'valide') {
        return response()->json([
            'message' => 'Subscription is still valid',
            'end_date' => $subscription->end_date
        ]);
    }

    // Créer une nouvelle transaction pour le réabonnement
    $subscriptionType = SubscriptionType::findOrFail($request->subscription_type_id);
    $price = $subscriptionType->price;

    $transaction = new Transaction();
    $transaction->user_id = $user->id;
    $transaction->total_amount = $price;
    $transaction->quantity = 1;
    $transaction->price = $price;
    $transaction->transaction_name = 'subscription';
    $transaction->save();

    // Créer le réabonnement
    $newSubscription = new Subscription();
    $newSubscription->user_id = $user->id;
    $newSubscription->subscription_type_id = $subscriptionType->id;
    $newSubscription->calculateEndDate($subscriptionType->name);
    $newSubscription->updateStatut();
    $newSubscription->save();

    return response()->json([
        'message' => 'Subscription renewed successfully',
        'subscription' => $newSubscription
    ]);
}


// Méthode pour obtenir le nombre de subscriptions par type
    public function getSubscriptionsByType()
    {
        $subscriptionsByType = Subscription::selectRaw('subscription_type_id, COUNT(*) as count')
            ->groupBy('subscription_type_id')
            ->with('type')
            ->get();

        $data = $subscriptionsByType->map(function ($subscription) {
            return [
                'type_name' => $subscription->type->name,
                'count' => $subscription->count,
            ];
        });

        return response()->json($data);
    }

    // Méthode pour obtenir les revenus totaux par type de subscription
    public function getTotalRevenueByType()
    {
        $revenueByType = Subscription::selectRaw('subscription_type_id, COUNT(*) as count')
            ->groupBy('subscription_type_id')
            ->with('type')
            ->get();

        $data = $revenueByType->map(function ($subscription) {
            return [
                'type_name' => $subscription->type->name,
                'total_revenue' => $subscription->count * $subscription->type->price,
            ];
        });

        return response()->json($data);
    }

}

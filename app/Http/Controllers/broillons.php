<?php
//abonnement dun client 
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
        return response()->json(['error' => 'Vous devez vous connecté!!!'], 401);
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
        'message' => 'Abonnement cree avec succees',
        'subscription' => $subscription,
        'transaction' => $transaction,
        'Abonnement' => $subscriptionType->name
    ]);
}
<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminUserController;

use App\Http\Controllers\TrajetController;
use App\Http\Controllers\TicketTypeController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TransactionController; 
use App\Http\Controllers\SubscriptionTypeController; 
use App\Http\Controllers\SubscriptionController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Authentification routes
Route::post('/register', [AuthController::class, 'register']); 
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);


// Modifier le rôle
Route::middleware('auth:sanctum')->put('/users/{id}/role', [AuthController::class, 'updateRole']);

// l'utilisateur connecté édite ses informations
Route::middleware('auth:sanctum')->put('/users/updateProfile', [UserController::class, 'updateProfile']);

// recupere le user connecté
// Route::middleware('auth:sanctum')->get('/users/userConnecte', [UserController::class, 'userConnecte']);

// l'utilisateur affiche ses infos
Route::middleware('auth:sanctum')->get('/users/showProfile', [UserController::class, 'showProfile']);

// gestion des utilisateurs par l'admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
// Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);
    Route::put('/users/{id}/activate', [AdminUserController::class, 'activateUser']);
    Route::put('/users/{id}/deactivate', [AdminUserController::class, 'deactivateUser']);
});

// gestion des trajets par l'admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Route::apiResource('trajets', TrajetController::class);

    Route::get('/trajets', [TrajetController::class, 'index']);
    Route::post('/trajets', [TrajetController::class, 'store']);
    Route::get('/trajets/{id}', [TrajetController::class, 'show']);
    Route::put('/trajets/{id}', [TrajetController::class, 'update']);
    Route::delete('/trajets/{id}', [TrajetController::class, 'destroy']);
    // Route::put('/trajets/{id}/activate', [TrajetController::class, 'activateUser']);
    // Route::put('/trajets/{id}/deactivate', [TrajetController::class, 'deactivateUser']);

});

// gestion de type de tickets
// pour tout type d'utilisateur: il peut lister et afficher un type 
// Route::middleware(['auth:sanctum'])->group(function () {
//     Route::get('/ticketTypes', [TicketTypeController::class, 'index']);
//     Route::get('/ticketTypes/{ticketType}', [TicketTypeController::class, 'show']);
// });

// Middleware 'admin' appliqué pour gérer les types de tickets
// Route::middleware(['auth:sanctum', 'admin', 'comptable'])->group(function () {
//     Route::post('/ticketTypes', [TicketTypeController::class, 'store']);
//     Route::put('/ticketTypes/{ticketType}', [TicketTypeController::class, 'update']);
//     Route::delete('/ticketTypes/{ticketType}', [TicketTypeController::class, 'destroy']);
// });

// route pour créer des tickets et transactions concernés (achat)
Route::middleware('auth:sanctum')->post('/tickets/create', [TicketController::class, 'create']);

// route pour créer des tickets et transactions concernés (vente)
Route::middleware('auth:sanctum')->post('/tickets/vendreTicket', [TicketController::class, 'vendreTicket']);

// routes pour gérer les transactions pour les comptables
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
     Route::get('/transactions', [TransactionController::class, 'index']);
// Route::middleware('auth:sanctum')->get('/transactions', [TransactionController::class, 'index']);
     Route::get('/transactions/summary', [TransactionController::class, 'summary']);
     Route::get('/transactions/total-by-type', [TransactionController::class, 'totalTransactionsByType']);
     Route::get('/transactions/by-type', [TransactionController::class, 'transactionsByType']);
        // revenu par jour
     Route::get('/transactions/revenues-daily', [TransactionController::class, 'getDailyRevenues']);

     Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
     Route::get('/transactions/{id}', [TransactionController::class, 'show']);
});

// gestion de type de subscriptionTypes
// pour tout type d'utilisateur
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/subscriptionTypes', [SubscriptionTypeController::class, 'index']);
    Route::get('/subscriptionTypes/{subscriptionType}', [SubscriptionTypeController::class, 'show']);
});
// Middleware 'admin' appliqué pour gérer les subscriptionTypes
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::post('/subscriptionTypes', [SubscriptionTypeController::class, 'store']);
    Route::put('/subscriptionTypes/{subscriptionType}', [SubscriptionTypeController::class, 'update']);
    Route::delete('/subscriptionTypes/{subscriptionType}', [SubscriptionTypeController::class, 'destroy']);
});

// fin des verifications des erreurs( la suite ...)

// route pour créer des abonnements et transactions concernés
Route::middleware('auth:sanctum')->post('/subscription/create', [SubscriptionController::class, 'create']);



Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // afficher les tickets
    Route::get('/tickets', [TicketController::class, 'index']);
    // route pour editer un ticket (test seulement pour le statut)
    Route::put('/tickets/{id}', [TicketController::class, 'updateTicket']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    Route::get('tickets/by-type', [TicketController::class, 'getTicketsByType']);
    Route::get('tickets/revenue-by-type', [TicketController::class, 'getTotalRevenueByType']);
    Route::get('tickets/sales-by-period', [TicketController::class, 'getSalesByPeriod']);
    Route::get('tickets/revenue-by-period', [TicketController::class, 'getRevenueByPeriod']);

});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // afficher les tickets
    Route::get('/subscriptions', [SubscriptionController::class, 'index']);
    // route pour editer un abonnement (test seulement pour le statut)
    Route::put('/subscriptions/{id}', [SubscriptionController::class, 'updateSubscription']);
    Route::delete('/subscriptions/{id}', [SubscriptionController::class, 'destroy']);
    Route::get('subscriptions/by-type', [SubscriptionController::class, 'getSubscriptionsByType']);
    Route::get('subscriptions/revenue-by-type', [SubscriptionController::class, 'getTotalRevenueByType']);
});
// verifier le statut d'abonnement du client connecté
Route::middleware('auth:sanctum')->get('/subscriptions/status', [SubscriptionController::class, 'checkSubscriptionStatus']);

// verifier le statut d'abonnement du client via son num Te
Route::middleware('auth:sanctum')->post('/subscriptions/check', [SubscriptionController::class, 'checkSubscriptionStatusTel']);

// Reabonner un client 
Route::middleware('auth:sanctum')->post('/subscriptions/renew', [SubscriptionController::class, 'renewSubscription']);


// test qrCode
// use App\Http\Controllers\QRcodeGenerateController;



// Route::get('/qrCode', [TicketController::class,'qrcode']);
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
// use App\Models\TicketType;
use App\Models\Trajet;
use SimpleSoftwareIO\QrCode\Facades\QrCode; //test

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;



class TicketController extends Controller
{
     // Méthode pour lister les tickets
    // public function index()
    // {    
    //     $tickets = Ticket::all();
    //     return response()->json($tickets);
    // }

   
     public function index(Request $request)
        {
            $perPage = $request->input('perPage', 4);
            $page = $request->input('page', 1);

             $tickets = Ticket::paginate($perPage, ['*'], 'page', $page);
            //  $tickets = Ticket::all()
            //  ->paginate($perPage, ['*'], 'page', $page);
            return response()->json($tickets);
        }



    // Dans votre contrôleur(teste seulement)
    public function showTicket($id)
    
    {
        // $ticket = Ticket::findOrFail($id);
        return view('ticket.show', compact('ticket'));
    }

    // Méthode pour afficher les détails d'une transaction spécifique
    public function show($id)
    {
       try{
         $ticket = Ticket::with(['user', 'trajet'])->findOrFail($id);
        return response()->json($ticket);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas type de ticket non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Ce ticket n\'est pas trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails du ticket'], 500);
    }
}
    
// debut de creation de ticket par un client
public function create(Request $request)
{
    try {
        // Validation des données reçues
        $request->validate([
            'trajet_id' => 'required|exists:trajets,id',
            'type' => 'required|in:aller_simple,aller_retour',
            'quantity' => 'required|integer|min:1',
            'passengers' => 'required|array', // Liste des passagers additionnels
            'passengers.*' => 'string', // Nom des passagers additionnels
            'methodePaiement' =>  'required|in:espece,carte,mobile,en_ligne',
            'date_depart' => 'required|date',
            'heure_depart' => 'required|date_format:H:i',
            // 'dates_de_depart' => 'required|array',
            // 'dates_de_depart.*' => 'required|date',
            // 'heures_de_depart' => 'required|array',
            // 'heures_de_depart.*' => 'required|date_format:H:i',
        ]);

        // Récupération des informations sur le trajet et calcul du prix
        $trajet = Trajet::findOrFail($request->trajet_id);
        $unitPrice = $trajet->prix;
        $quantity = $request->quantity;
        $type = $request->type;
        $methodePaiement = $request->methodePaiement;

        // Calcul du prix total
        $totalAmount = $unitPrice * $quantity;
        if ($type === 'aller_retour') {
            $totalAmount *= 2; // Doubler le prix pour un aller-retour
        }

        // Créer une nouvelle transaction
        $transaction = new Transaction();
        // $transaction->user_id = Auth::id(); // ID du client connecté 
        $transaction->user_id = auth()->id(); // ID du client connecté 
        $transaction->total_amount = $totalAmount;
        $transaction->quantity = $quantity;
        $transaction->price = $unitPrice;
        $transaction->transaction_name = 'ticket';
        $transaction->telephoneClient =  Auth::user()->telephone;
        $transaction->methodePaiement = $methodePaiement;
        $transaction->save();

       
        $ticketsData = [];

        // Créer des tickets associés à la transaction
        for ($i = 0; $i < $quantity; $i++) {
            $ticket = new Ticket();
            $ticket->transaction_id = $transaction->id;
            // $ticket->user_id = Auth::id(); // ID du client 
            $ticket->user_id = auth()->id();  // ID du client connecté
            $ticket->trajet_id = $request->trajet_id;
            $ticket->type = $type;
            $ticket->date_depart = $request->date_depart;
            $ticket->heure_depart = $request->heure_depart;


            // Déterminer le nom du passager
            if ($i == 0) {
                // Premier ticket : nom du client connecté
                // $ticket->nom = Auth::user()->name;
                $ticket->nom = Auth::user()->nom.' '.Auth::user()->prenom;
            } else {
                // Tickets suivants : noms des passagers additionnels
                $passengers = $request->input('passengers', []);
                $passengerName = $passengers[$i - 1] ?? null;
                $ticket->nom = $passengerName;
            }

            // Calculer la date d'expiration et mettre à jour le statut
            $ticket->purchase_date = now();
            $ticket->calculateExpirationDate($type); // Méthode personnalisée pour définir l'expiration
            $ticket->updateStatut(); // Méthode personnalisée pour définir le statut

            // Sauvegarder le ticket
            $ticket->save();

            // Préparer les données des tickets pour la réponse
            $ticketsData[] = [
                'id' => $ticket->id,
                'transaction_id' => $ticket->transaction_id,
                'trajet_id' => $ticket->trajet_id,
                'user_id' => $ticket->trajet_id,
                'type' => $ticket->type,
                'nom' => $ticket->nom,
                'Tel du client principal' => Auth::user()->telephone,
                'qr_code' => $ticket->qr_code,
                'statut' => $ticket->statut,
                'date_depart' => $ticket->date_depart,
                'heure_depart' => $ticket->heure_depart,
                'purchase_date' => $ticket->purchase_date,
                'expiration_date' => $ticket->expiration_date
            ];
        }

        return response()->json([
            'message' => 'Tickets created successfully',
            'transaction' => $transaction,
            'tickets' => $ticketsData
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        // En cas d'erreur de validation, retourner une réponse JSON avec les messages d'erreur
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de modèle non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Le trajet spécifié est introuvable.'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la création des tickets.', 
        'details' => $e->getMessage()], 500);
    }
}
// fin de creation par un client

// debut de creation de ticket par un vendeur
  public function vendreTicket(Request $request)
{
    $request->validate([
        'trajet_id' => 'required|exists:trajets,id',
        'type' => 'required|in:aller_simple,aller_retour',
        'quantity' => 'required|integer|min:1',
        'passengers' => 'required|array',
        'passengers.*' => 'string',
        'telephone' => 'required|string',
        'nom' => 'required|string',
        'methodePaiement' =>  'required|in:espece,carte,mobile,en_ligne',
        'date_depart' => 'required|date',
        'heure_depart' => 'required|date_format:H:i',
        // 'dates_de_depart' => 'required|array',
        // 'dates_de_depart.*' => 'required|date',
        // 'heures_de_depart' => 'required|array',
        // 'heures_de_depart.*' => 'required|date_format:H:i',
    ]);

    $telephoneClient = $request->telephone;
    $nomClient = $request->nom;
    $methodePaiement = $request->methodePaiement;

    // Vérification de l'existence de l'utilisateur par numéro de téléphone
    $user = User::where('telephone', $telephoneClient)->first();
    $accountCreated = false;

    if (!$user) {
        // Générer un email unique
        $email = strtolower($nomClient) . '.' . $telephoneClient . '.' . Str::random(5) . '@temporaryemail.com';

        // Création d'un compte utilisateur avec un mot de passe prédéfini
        $temporaryPassword = 'passer123';
        $user = User::create([
            'nom' => $nomClient,
            'prenom' => $nomClient,
            'telephone' => $telephoneClient,
            'email' => $email,
            'password' => Hash::make($temporaryPassword),
        ]);

        $accountCreated = true;

        // Envoi d'un SMS au client avec les informations de connexion
        // Remplacez cette ligne par le code réel pour envoyer un SMS
        // SMS::send($telephoneClient, "Your account has been created. Email: " . $user->email . ", Password: " . $temporaryPassword);
    }

    $trajet = Trajet::findOrFail($request->trajet_id);
    $unitPrice = $trajet->prix;
    $quantity = $request->quantity;
    $type = $request->type;

    $totalAmount = $unitPrice * $quantity;
    if ($type === 'aller_retour') {
        $totalAmount *= 2;
    }

    $transaction = new Transaction();
    $transaction->user_id = auth()->id();
    $transaction->total_amount = $totalAmount;
    $transaction->quantity = $quantity;
    $transaction->price = $unitPrice;
    $transaction->transaction_name = 'ticket';
    $transaction->telephoneClient = $user->telephone;
    $transaction->methodePaiement = $methodePaiement;
    $transaction->save();

    $ticketsData = [];

    for ($i = 0; $i < $quantity; $i++) {
        $ticket = new Ticket();
        $ticket->transaction_id = $transaction->id;
        $ticket->user_id = $user->id;
        $ticket->trajet_id = $request->trajet_id;
        $ticket->type = $type;
        $ticket->date_depart = $request->date_depart;
        $ticket->heure_depart =  $request->heure_depart;


        if ($i == 0) {
            $ticket->nom = $user->nom;
        } else {
            $passengers = $request->input('passengers', []);
            $passengerName = $passengers[$i - 1] ?? null;
            $ticket->nom = $passengerName;
        }

        $ticket->purchase_date = now();
        $ticket->calculateExpirationDate($type);
        $ticket->updateStatut();
        $ticket->save();

        $ticketsData[] = [
            'id' => $ticket->id,
            'transaction_id' => $ticket->transaction_id,
            'trajet_id' => $ticket->trajet_id,
            'user_id' => $ticket->user_id,
            'type' => $ticket->type,
            'nom' => $ticket->nom,
            'telephone' => $user->telephone,
            'qr_code' => $ticket->qr_code,
            'statut' => $ticket->statut,
            'date_depart' => $ticket->date_depart,
            'heure_depart' => $ticket->heure_depart,
            'purchase_date' => $ticket->purchase_date,
            'expiration_date' => $ticket->expiration_date
        ];
    }

    return response()->json([
        'transaction_id' => $transaction->id,
        'total_amount' => $totalAmount,
        'tickets' => $ticketsData,
        'verification' => $accountCreated ? 'Le compte a été créé avec succès.' : 'Le client avait déjà un compte.'
    ]);
}

// fin de vente
    // Méthode pour créer un ticket
    // public function create1(Request $request)
    // {
    //     try {
    //         // Validation des données reçues
    //         $request->validate([
    //             'ticket_type_id' => 'required|exists:ticket_types,id',
    //             'quantity' => 'required|integer|min:1',
    //         ]);

    //         // Instancier un type de ticket $ticketType et récupérer ses infos
    //         $ticketType = TicketType::findOrFail($request->ticket_type_id);
    //         $unitPrice = $ticketType->price;
    //         $quantity = $request->quantity;
    //         $totalAmount = $unitPrice * $quantity;
    //         $ticket_type = $ticketType->name;

    //         // Créer une nouvelle transaction
    //         $transaction = new Transaction();
    //         $transaction->user_id = auth()->id();
    //         $transaction->total_amount = $totalAmount;
    //         $transaction->quantity = $quantity;
    //         $transaction->price = $unitPrice;
    //         $transaction->transaction_name = 'ticket';
    //         $transaction->ticket_type_id = $ticketType->id;
    //         $transaction->save();

    //         $ticketsData = [];

    //         // Créer des tickets associés à la transaction
    //         for ($i = 0; $i < $quantity; $i++) {
    //             $ticket = new Ticket();
    //             $ticket->transaction_id = $transaction->id;
    //             $ticket->ticket_type_id = $ticketType->id;

    //             // Générer le contenu du QR code avec les informations du ticket
    //             // $qrCodeContent = "Ticket ID: " . $ticket->id . "\n";
    //             // $qrCodeContent .= "Purchase Date: " . $ticket->purchase_date . "\n";
    //             // $qrCodeContent .= "Expiration Date: " . $ticket->expiration_date;

    //             // // Générer le QR code et le convertir en base64
    //             // $qrCode = QrCode::size(150)->generate($qrCodeContent);
    //             // $qrCodeBase64 = base64_encode($qrCode);

    //             // // Assigner le QR code encodé en base64 au modèle de ticket
    //             // $ticket->qr_code = $qrCodeBase64;
    //             // Calculer la date d'expiration et mettre à jour le statut
    //             $ticket->calculateExpirationDate($ticket_type);
    //             $ticket->updateStatut();

    //             // Appel de la méthode pour obtenir le temps restant
    //             $remainingTime = $ticket->getRemainingTimeAttribute();

    //             $ticket->save();

    //             // Préparer les données des tickets pour la réponse
    //             $ticketsData[] = [
    //                 'id' => $ticket->id,
    //                 'transaction_id' => $ticket->transaction_id,
    //                 'ticket_type_id' => $ticket->ticket_type_id,
    //                 'qr_code' => $ticket->qr_code,
    //                 'statut' => $ticket->statut,
    //                 'purchase_date' => $ticket->purchase_date,
    //                    'date_depart' => $ticket->date_depart,
    //         'heure_depart' => $ticket->heure_depart,
    //                 'expiration_date' => $ticket->expiration_date,
    //                 'remaining_time' => $remainingTime
    //             ];
    //         }

    //         return response()->json([
    //             'message' => 'Tickets created successfully',
    //             'transaction' => $transaction,
    //             'tickets' => $ticketsData
    //         ], 201);

    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         // En cas d'erreur de validation, retourner une réponse JSON avec les messages d'erreur
    //         return response()->json(['errors' => $e->errors()], 422);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         // En cas de modèle non trouvé, retourner une réponse JSON avec un message d'erreur clair
    //         return response()->json(['error' => 'Le type de ticket spécifié est introuvable.'], 404);
    //     } catch (\Exception $e) {
    //         // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
    //         return response()->json(['error' => 'Une erreur est survenue lors de la création des tickets.', 
    //         'details' => $e->getMessage()], 500);
    //     }
    // }



 // Méthode pour mettre à jour un ticket
    public function updateTicket(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        

        // Mise à jour des attributs du ticket
        $ticket->expiration_date = $request->input('expiration_date', $ticket->expiration_date);

        // Appel de la méthode pour mettre à jour le statut
        $ticket->updateStatut();

        // Enregistrement du ticket mis à jour
        $ticket->save();

        return response()->json([
            'message' => 'Ticket updated successfully',
            'ticket' => $ticket,
            'remaining_time' => $ticket->remaining_time, // Laravel utilisera automatiquement l'accessor pour calculer le temps restant
        ]);
    }


    // fonction pour gener un qrcode (test)
      // QR code generation
    public function qrcode(){
        $qrCodes = [];
// https://github.com/IbrahimaBalde10/memoire/commits?author=IbrahimaBalde10
        $qrCodes['simple']        = QrCode::size(150)->generate('https://github.com/IbrahimaBalde10/');
        $qrCodes['simple'] = QrCode::size(150)->generate('Hello, de BALDEV');
        $qrCodes['simple']        = QrCode::size(150)->generate('https://minhazulmin.github.io/');
        $qrCodes['changeColor']   = QrCode::size(150)->color(255, 0, 0)->generate('https://minhazulmin.github.io/');
        $qrCodes['changeBgColor'] = QrCode::size(150)->backgroundColor(255, 0, 0)->generate('https://minhazulmin.github.io/');
        $qrCodes['styleDot']      = QrCode::size(150)->style('dot')->generate('https://minhazulmin.github.io/');
        $qrCodes['styleSquare']   = QrCode::size(150)->style('square')->generate('https://minhazulmin.github.io/');
        $qrCodes['styleRound']    = QrCode::size(150)->style('round')->generate('https://minhazulmin.github.io/');

        return view('qrcode',$qrCodes);
        //  return response()->json([
        //     'message' => 'QrCode generation successfully',
        //     'CodeQr:'=> $qrCodes
        //       ]);
    }


// Supprimer un ticket
    public function destroy($id)
    {
        try{
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();
        return response()->json(['message' => 'ticket deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'ticket non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails du ticket'], 500);
    }
}

// Méthode pour obtenir le nombre de tickets par type
    public function getTicketsByType()
    {
        $ticketsByType = Ticket::selectRaw('ticket_type_id, COUNT(*) as count')
            ->groupBy('ticket_type_id')
            ->with('type')
            ->get();

        $data = $ticketsByType->map(function ($ticket) {
            return [
                'type_name' => $ticket->type->name,
                'count' => $ticket->count,
            ];
        });

        return response()->json($data);
    }

    // Méthode pour obtenir les revenus totaux par type de ticket
    public function getTotalRevenueByType()
    {
        $revenueByType = Ticket::selectRaw('ticket_type_id, COUNT(*) as count')
            ->groupBy('ticket_type_id')
            ->with('type')
            ->get();

        $data = $revenueByType->map(function ($ticket) {
            return [
                'type_name' => $ticket->type->name,
                'total_revenue' => $ticket->count * $ticket->type->price,
            ];
        });

        return response()->json($data);
    }

    public function getSalesByPeriod(Request $request)
    {
        // Exemple de récupération des ventes de tickets par mois
        $salesByPeriod = Ticket::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('COUNT(*) as total_sales'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json($salesByPeriod);
    }

    // a refaire (ca ne fonctionne pas)
    public function getRevenueByPeriod(Request $request)
    {
        // Exemple de récupération des revenus par mois
        $revenueByPeriod = Ticket::select(DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'), DB::raw('SUM(price) as total_revenue'))
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get();

        return response()->json($revenueByPeriod);
    }
}
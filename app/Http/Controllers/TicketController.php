<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\TicketType;

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

    public function index()
    {
        $tickets = Ticket::with('type')->get();
        
        // Transformer les données pour inclure le nom du type de ticket
        $tickets = $tickets->map(function($ticket) {
            return [
                'id' => $ticket->id,
                'transaction_id' => $ticket->transaction_id,
                'name' => $ticket->type->name, 
                'price' => $ticket->type->price, // Prix du type de ticket
                'statut' => $ticket->statut,
                'expiration_date' => $ticket->expiration_date,
                'updated_at' => $ticket->updated_at->format('Y-m-d H:i:s'), //formater la date
            ];
        });

        return response()->json($tickets);
    }
    // Dans votre contrôleur
    public function showTicket($id)
    
    {
        // $ticket = Ticket::findOrFail($id);
        return view('ticket.show', compact('ticket'));
    }
    

    // Méthode pour créer un ticket
    public function create(Request $request)
    {
        try {
            // Validation des données reçues
            $request->validate([
                'ticket_type_id' => 'required|exists:ticket_types,id',
                'quantity' => 'required|integer|min:1',
            ]);

            // Instancier un type de ticket $ticketType et récupérer ses infos
            $ticketType = TicketType::findOrFail($request->ticket_type_id);
            $unitPrice = $ticketType->price;
            $quantity = $request->quantity;
            $totalAmount = $unitPrice * $quantity;
            $ticket_type = $ticketType->name;

            // Créer une nouvelle transaction
            $transaction = new Transaction();
            $transaction->user_id = auth()->id();
            $transaction->total_amount = $totalAmount;
            $transaction->quantity = $quantity;
            $transaction->price = $unitPrice;
            $transaction->transaction_name = 'ticket';
            $transaction->ticket_type_id = $ticketType->id;
            $transaction->save();

            $ticketsData = [];

            // Créer des tickets associés à la transaction
            for ($i = 0; $i < $quantity; $i++) {
                $ticket = new Ticket();
                $ticket->transaction_id = $transaction->id;
                $ticket->ticket_type_id = $ticketType->id;

                // Générer le contenu du QR code avec les informations du ticket
                // $qrCodeContent = "Ticket ID: " . $ticket->id . "\n";
                // $qrCodeContent .= "Purchase Date: " . $ticket->purchase_date . "\n";
                // $qrCodeContent .= "Expiration Date: " . $ticket->expiration_date;

                // // Générer le QR code et le convertir en base64
                // $qrCode = QrCode::size(150)->generate($qrCodeContent);
                // $qrCodeBase64 = base64_encode($qrCode);

                // // Assigner le QR code encodé en base64 au modèle de ticket
                // $ticket->qr_code = $qrCodeBase64;
                // Calculer la date d'expiration et mettre à jour le statut
                $ticket->calculateExpirationDate($ticket_type);
                $ticket->updateStatut();

                // Appel de la méthode pour obtenir le temps restant
                $remainingTime = $ticket->getRemainingTimeAttribute();

                $ticket->save();

                // Préparer les données des tickets pour la réponse
                $ticketsData[] = [
                    'id' => $ticket->id,
                    'transaction_id' => $ticket->transaction_id,
                    'ticket_type_id' => $ticket->ticket_type_id,
                    'qr_code' => $ticket->qr_code,
                    'statut' => $ticket->statut,
                    'purchase_date' => $ticket->purchase_date,
                    'expiration_date' => $ticket->expiration_date,
                    'remaining_time' => $remainingTime
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
            return response()->json(['error' => 'Le type de ticket spécifié est introuvable.'], 404);
        } catch (\Exception $e) {
            // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
            return response()->json(['error' => 'Une erreur est survenue lors de la création des tickets.', 
            'details' => $e->getMessage()], 500);
        }
    }



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
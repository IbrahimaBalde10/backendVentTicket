<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
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

}
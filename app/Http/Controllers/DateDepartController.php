<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trajet; // Trajet model
use App\Models\DateDeDepart;

class DateDepartController extends Controller
{

    public function index(Request $request, $trajetId)
{
    try {
        // Vérifier si le trajet spécifié existe
        $trajet = Trajet::findOrFail($trajetId);

        // Nombre d'éléments par page
        $perPage = $request->query('perPage', 10); // Utilise 10 comme valeur par défaut si 'perPage' n'est pas spécifié
        $page = $request->query('page', 1); // Utilise 1 comme valeur par défaut si 'page' n'est pas spécifié

        // Récupère les dates de départ pour le trajet spécifié avec pagination
        $datesDeDepart = DateDeDepart::where('trajet_id', $trajetId)->paginate($perPage, ['*'], 'page', $page);

        return response()->json($datesDeDepart);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de trajet non trouvé
        return response()->json(['error' => 'Trajet non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json([
            'error' => 'Une erreur est survenue lors de la récupération des dates de départ',
            'details' => $e->getMessage()
        ], 500);
    }
}

//  public function store(Request $request)
//     {
//         $request->validate([
//             'trajet_id' => 'required|exists:trajets,id',
//             'dateDepart.*' => 'required|date',
            
//         ]);

//         $dateDepart = DateDeDepart::create([
//             'trajet_id' => $request->trajet_id,
//             'dateDepart' => $request->dateDepart,
//             ]);

//         return response()->json(['message' => 'Date ajouté avec succès', 'date' => $dateDepart], 201);
//     }
public function store(Request $request, $trajet_id)
{
    // Valider la présence de trajet_id dans l'URL
    $request->validate([
        'dateDepart.*' => 'required|date',
    ]);

    // Vérifier l'existence du trajet
    $trajet = Trajet::find($trajet_id);
    if (!$trajet) {
        return response()->json(['message' => 'Trajet non trouvé'], 404);
    }

    // Créer la nouvelle date de départ
    $dateDepart = DateDeDepart::create([
        'trajet_id' => $trajet_id,
        'dateDepart' => $request->dateDepart,
    ]);

    return response()->json(['message' => 'Date ajoutée avec succès', 'date' => $dateDepart], 201);
}


    // affichage
 public function show($trajetId, $dateId)
{
    try {
        // Rechercher la date de départ par l'ID et vérifier si elle appartient au trajet spécifié
        $dateDepart = DateDeDepart::where('trajet_id', $trajetId)->findOrFail($dateId);

        return response()->json($dateDepart);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de date de départ non trouvée, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Cette date de départ n\'est pas trouvée'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de la date de départ'], 500);
    }
}

public function update(Request $request, $trajetId, $dateId)
{
    try {
        // Trouver la date de départ par l'ID et vérifier si elle appartient au trajet spécifié
        $dateDepart = DateDeDepart::where('trajet_id', $trajetId)->findOrFail($dateId);

        // Validation des données
        $request->validate([
            'dateDepart' => 'sometimes|required|date', // Validation pour une date
        ]);

        // Mettre à jour la date de départ
        $dateDepart->update([
            'dateDepart' => $request->dateDepart,
        ]);

        return response()->json([
            'message' => 'Date de départ modifiée avec succès',
            'dateDepart' => $dateDepart
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de date de départ non trouvée
        return response()->json(['error' => 'Date de départ non trouvée'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json([
            'error' => 'Une erreur est survenue lors de la mise à jour de la date de départ',
            'details' => $e->getMessage()
        ], 500);
    }
}

public function destroy($trajetId, $dateId)
{
    try {
        // Trouver la date de départ par l'ID et vérifier si elle appartient au trajet spécifié
        $dateDepart = DateDeDepart::where('trajet_id', $trajetId)->findOrFail($dateId);
        
        // Supprimer la date de départ
        $dateDepart->delete();

        return response()->json(['message' => 'Date de départ supprimée avec succès']);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de date de départ non trouvée
        return response()->json(['error' => 'Date de départ non trouvée'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json(['error' => 'Une erreur est survenue lors de la suppression de la date de départ'], 500);
    }
}

}

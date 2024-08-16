<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trajet; // Trajet model
use App\Models\HeureDeDepart;

class HeureDepartController extends Controller
{

    public function index(Request $request, $trajetId)
{
    try {
        // Vérifier si le trajet spécifié existe
        $trajet = Trajet::findOrFail($trajetId);

        // Nombre d'éléments par page
        $perPage = $request->query('perPage', 10); // Utilise 10 comme valeur par défaut si 'perPage' n'est pas spécifié
        $page = $request->query('page', 1); // Utilise 1 comme valeur par défaut si 'page' n'est pas spécifié

        // Récupère les heures de départ pour le trajet spécifié avec pagination
        $heuresDeDepart = HeureDeDepart::where('trajet_id', $trajetId)->paginate($perPage, ['*'], 'page', $page);

        return response()->json($heuresDeDepart);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de trajet non trouvé
        return response()->json(['error' => 'Trajet non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json([
            'error' => 'Une erreur est survenue lors de la récupération des heures de départ',
            'details' => $e->getMessage()
        ], 500);
    }
}

//  public function store(Request $request)
//     {
//         $request->validate([
//             'trajet_id' => 'required|exists:trajets,id',
//             'heureDepart.*' => 'required|date_format:H:i',
            
//         ]);

//         $heureDepart = HeureDeDepart::create([
//             'trajet_id' => $request->trajet_id,
//             'heureDepart' => $request->heureDepart,
//             ]);

//         return response()->json(['message' => 'Heure ajouté avec succès', 'date' => $heureDepart], 201);
//     }

public function store(Request $request, $trajet_id)
{
    // Valider la présence de trajet_id dans l'URL
    $request->validate([
        'heureDepart.*' => 'required|te_format:H:i',
    ]);

    // Vérifier l'existence du trajet
    $trajet = Trajet::find($trajet_id);
    if (!$trajet) {
        return response()->json(['message' => 'Trajet non trouvé'], 404);
    }

    // Créer la nouvelle heure de départ
    $heureDepart = HeureDeDepart::create([
        'trajet_id' => $trajet_id,
        'heureDepart' => $request->heureDepart,
    ]);

    return response()->json(['message' => 'Heure ajoutée avec succès', 'heure' => $heureDepart], 201);
}

    // affichage
 public function show($trajetId, $heureId)
{
    try {
        // Rechercher la date de départ par l'ID et vérifier si elle appartient au trajet spécifié
        $heureDepart = HeureDeDepart::where('trajet_id', $trajetId)->findOrFail($heureId);

        return response()->json($heureDepart);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de date de départ non trouvée, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Cette heure de départ n\'est pas trouvée'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de la date de départ'], 500);
    }
}

public function update(Request $request, $trajetId, $heureId)
{
    try {
        // Trouver la date de départ par l'ID et vérifier si elle appartient au trajet spécifié
        $heureDepart = HeureDeDepart::where('trajet_id', $trajetId)->findOrFail($heureId);

        // Validation des données
        $request->validate([
            'heureDepart' => 'sometimes|required|date_format:H:i', // Validation pour une date
        ]);

        // Mettre à jour la date de départ
        $heureDepart->update([
            'heureDepart' => $request->heureDepart,
            'trajet_id' => $trajetId,
        ]);

        return response()->json([
            'message' => 'Heure de départ modifiée avec succès',
            'heureDepart' => $heureDepart
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de date de départ non trouvée
        return response()->json(['error' => 'Heure de départ non trouvée'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json([
            'error' => 'Une erreur est survenue lors de la mise à jour de l\'heure de départ',
            'details' => $e->getMessage()
        ], 500);
    }
}

public function destroy($trajetId, $heureId)
{
    try {
        // Trouver la date de départ par l'ID et vérifier si elle appartient au trajet spécifié
        $heureDepart = HeureDeDepart::where('trajet_id', $trajetId)->findOrFail($heureId);
        
        // Supprimer la date de départ
        $heureDepart->delete();

        return response()->json(['message' => 'Heure de départ supprimée avec succès']);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de date de départ non trouvée
        return response()->json(['error' => 'Heure de départ non trouvée'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json(['error' => 'Une erreur est survenue lors de la suppression de la date de départ'], 500);
    }
}

}

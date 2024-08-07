<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Trajet; // Trajet model
use App\Models\DateDeDepart;
use App\Models\HeureDeDepart;

class TrajetController extends Controller
{
    //  public function index()
    // {
    //     return Trajet::all();
    // //    return $trajet = Trajet::with('datesDeDepart','heuresDeDepart')->get();

    // }
    public function index(Request $request){
    // Nombre d'éléments par page
    $perPage = $request->query('perPage', 10); // Utilise 10 comme valeur par défaut si 'perPage' n'est pas spécifié
    $page = $request->query('page', 1); // Utilise 1 comme valeur par défaut si 'page' n'est pas spécifié

    // Récupère les utilisateurs avec pagination
    $trajets = Trajet::paginate($perPage, ['*'], 'page', $page);

    return response()->json($trajets);
}

 public function store(Request $request)
    {
        $request->validate([
            'point_depart' => 'required|string|max:255',
            'point_arrivee' => 'required|string|max:255',
            'prix' => 'required|numeric',
            // 'statut' => 'required|in:actif,inactif',
            'dates_de_depart' => 'required|array',
            'dates_de_depart.*' => 'required|date',
            'heures_de_depart' => 'required|array',
            'heures_de_depart.*' => 'required|date_format:H:i',
            'description' => 'nullable|string', // Validation pour la description
        ]);

        $nom = $request->point_depart . ' --> ' . $request->point_arrivee;

        $existingTrajet = Trajet::where('nom', $nom)->first();
        if ($existingTrajet) {
            return response()->json(['message' => 'Ce trajet existe déjà'], 400);
        }

        $trajet = Trajet::create([
            'nom' => $nom,
            'point_depart' => $request->point_depart,
            'point_arrivee' => $request->point_arrivee,
            'prix' => $request->prix, //  $user->role = 'Client'; // Default role is Client
            'statut' => $request->statut,
            'description' => $request->description, // Ajout de la description
        ]);

        if (is_array($request->dates_de_depart)) {
            foreach ($request->dates_de_depart as $date) {
                DateDeDepart::create([
                    'dateDepart' => $date,
                    'trajet_id' => $trajet->id,
                ]);
            }
        }

        if (is_array($request->heures_de_depart)) {
            foreach ($request->heures_de_depart as $heure) {
                HeureDeDepart::create([
                    'heureDepart' => $heure,
                    'trajet_id' => $trajet->id,
                ]);
            }
        }

        return response()->json(['message' => 'Trajet ajouté avec succès', 'trajet' => $trajet], 201);
    }

    // affichage
  public function show($id)
    {
        // Afficher un type de trajet spécifique
        
     try{
        //  $trajet = Trajet::findOrFail($id);
        $trajet = Trajet::with('datesDeDepart', 'heuresDeDepart')->where('id', $id)->get();

        return response()->json($trajet);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas type de ticket non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Ce trajet n\'est pas trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails du trajet'], 500);
    }
    }

    // public function update(Request $request, $id)
    // {
    //     try {
    //     // Trouver le type de ticket ou échouer
    //     $trajet = Trajet::findOrFail($id);
    //     $request->validate([
    //         'nom' => 'sometimes|required|string|max:255',
    //         'point_depart' => 'sometimes|required|string|max:255',
    //         'point_arrivee' => 'sometimes|required|string|max:255',
    //         'prix' => 'sometimes|required|numeric',
    //         'date_depart' => 'sometimes|required|date',
    //         'heure_depart' => 'sometimes|required|date_format:H:i',
    //         'statut' => 'sometimes|required|in:actif,inactif',
    //     ]);

    //     $trajet->update($request->all());

    //      return response()->json(['message' => 'trajet modifié avec succe',
    //     'trajet'=>$trajet]);
    // } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //     // En cas de ticketType non trouvé, retourner une réponse JSON avec un message d'erreur clair
    //     return response()->json(['error' => 'trajet non trouvé'], 404);
    // } catch (\Exception $e) {
    //     // En cas d'autres erreurs, retourner une réponse JSON avec le message d'erreur exact
    //     return response()->json(['error' => 'Une erreur est survenue lors de la mise à jour du trajet', 
    //     'details' => $e->getMessage()], 500);
    // }
    // }
public function update(Request $request, $id)
{
    try {
        // Trouver le trajet ou échouer
        $trajet = Trajet::findOrFail($id);

        // Validation des données
        $request->validate([
            'point_depart' => 'sometimes|required|string|max:255',
            'point_arrivee' => 'sometimes|required|string|max:255',
            'prix' => 'sometimes|required|numeric',
            // 'dates_de_depart' => 'sometimes|array',
            // 'dates_de_depart.*' => 'sometimes|required|date',
            // 'heures_de_depart' => 'sometimes|array',
            // 'heures_de_depart.*' => 'sometimes|required|date_format:H:i',
            'statut' => 'sometimes|required|in:actif,inactif',
            'description' => 'nullable|string',
        ]);

        // Construire le nom du trajet basé sur les nouvelles informations
        $nom = $request->point_depart . ' --> ' . $request->point_arrivee;

        // Vérifier si un trajet avec le même nom existe déjà
        $existingTrajet = Trajet::where('nom', $nom)->where('id', '!=', $id)->first();
        if ($existingTrajet) {
            return response()->json(['message' => 'Ce trajet existe déjà'], 400);
        }

        // Mettre à jour le trajet
        $trajet->update([
            'point_depart' => $request->point_depart ?? $trajet->point_depart,
            'point_arrivee' => $request->point_arrivee ?? $trajet->point_arrivee,
            'prix' => $request->prix ?? $trajet->prix,
            'statut' => $request->statut ?? $trajet->statut,
            'description' => $request->description ?? $trajet->description,
            'nom' => $nom,
        ]);

        // // Mettre à jour les dates de départ
        // if ($request->has('dates_de_depart')) {
        //     // Supprimer les dates existantes
        //     $trajet->datesDeDepart()->delete();

        //     // Ajouter les nouvelles dates
        //     foreach ($request->dates_de_depart as $date) {
        //         DateDeDepart::create([
        //             'dateDepart' => $date,
        //             'trajet_id' => $trajet->id,
        //         ]);
        //     }
        // }

        // // Mettre à jour les heures de départ
        // if ($request->has('heures_de_depart')) {
        //     // Supprimer les heures existantes
        //     $trajet->heuresDeDepart()->delete();

        //     // Ajouter les nouvelles heures
        //     foreach ($request->heures_de_depart as $heure) {
        //         HeureDeDepart::create([
        //             'heureDepart' => $heure,
        //             'trajet_id' => $trajet->id,
        //         ]);
        //     }
        // }

        return response()->json([
            'message' => 'Trajet modifié avec succès',
            'trajet' => $trajet
        ]);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas de trajet non trouvé
        return response()->json(['error' => 'Trajet non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs
        return response()->json([
            'error' => 'Une erreur est survenue lors de la mise à jour du trajet',
            'details' => $e->getMessage()
        ], 500);
    }
}

    public function destroy($id)
    {
         // Supprimer un trajet
        try{
                $trajet = Trajet::findOrFail($id);
                $trajet->delete();
                return response()->json(['message' => 'trajet supprimé avec succès']);
            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
            return response()->json(['error' => 'trajet non trouvé'], 404);
        } catch (\Exception $e) {
            // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
            return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de trajet'], 500);
        }
    }
    
}

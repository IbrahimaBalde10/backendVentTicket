<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // For hashing passwords
use App\Models\User; // User model

class AdminUserController extends Controller
{
    // // Liste tous les utilisateurs
// public function index(Request $request){
//     // 
//     $sorBy = $request->get('sorBy', 'created_at');
//     $sortOrder = $request->get('sorOrder', 'desc');

//     $users = User::orderBy($sorBy, $sortOrder)
//                 ->paginate($request->get('perPage', 10));
    
//     return response()->json([
//         'data' => $users->items(),
//         'total' => $users->total(),
//         'perPage' => $users->perPage(),
//         'curentPage' => $users->curentPage(),

//     ]);

// }

public function index(Request $request){
    // Nombre d'éléments par page
    $perPage = $request->query('perPage', 10); // Utilise 10 comme valeur par défaut si 'perPage' n'est pas spécifié
    $page = $request->query('page', 1); // Utilise 1 comme valeur par défaut si 'page' n'est pas spécifié

    // Récupère les utilisateurs avec pagination
    $users = User::paginate($perPage, ['*'], 'page', $page);

    return response()->json($users);
}
// public function index(Request $request)
// {
//     $perPage = $request->get('per_page', 2);
//     $users = User::paginate($perPage);

//     return response()->json([
//         'data' => $users->items(), // Les utilisateurs pour la page actuelle
//         'meta' => [
//             'current_page' => $users->currentPage(),
//             'from' => $users->firstItem(),
//             'last_page' => $users->lastPage(),
//             'links' => [
//                 'first' => $users->url(1),
//                 'last' => $users->url($users->lastPage()),
//                 'prev' => $users->previousPageUrl(),
//                 'next' => $users->nextPageUrl()
//             ],
//             'per_page' => $users->perPage(),
//             'to' => $users->lastItem(),
//             'total' => $users->total()
//         ]
//     ]);
// }


    public function store(Request $request,)
    {
        try {
            // Validate incoming request fields
            $request->validate([
                'nom' => 'required|string|max:255', // email must be a string, not exceed 255 characters and it is required
                'prenom' => 'required|string|max:255', // Last email must be a string, not exceed 255 characters and it is required
                'telephone' => 'required|string|max:20|unique:users', // Phone number must be a string, not exceed 20 characters and it is required
                'email' => 'required|string|email|max:255|unique:users', // Email must be a string, a valid email, not exceed 255 characters, it is required and it must be unique in the users table
                'password' => 'required|string|min:6', // Password must be a string, at least 6 characters and it is required
            ]);

            //  $existingUser = User::where('email', $request->email)->first();

        // if ($existingUser) {return response()->json(['message' => 'Cet user existe déjà'], 400); }

            // Create new User
            $user = User::create([
                'nom' => $request->nom,
                'prenom' => $request->prenom,
                'telephone' => $request->telephone,
                'email' => $request->email,
                'password' => Hash::make($request->password), // Hash the password
                'role' => $request->role, // Default role is Client
            ]);

            // Return user data as JSON with a 201 (created) HTTP status code
            return response()->json(['user' => $user,
                "message"=>"utilisateur ajouté avec succèe"], 201);
        } catch (ValidationException $e) {
            // Return validation errors with a 422 (Unprocessable Entity) HTTP status code
            return response()->json(['errors' => $e->errors()], 422);
        } catch (Exception $e) {
            // Return general error message with a 500 (Internal Server Error) HTTP status code
            return response()->json(['message' => 'An error occurred during registration',
         'error' => $e->getMessage()], 500);
        }
    }

   // Afficher les détails d'un utilisateur
public function show($id)
{
    try {
        // Trouver l'utilisateur par son ID
        $user = User::findOrFail($id);

        // Retourner les détails de l'utilisateur en JSON
        return response()->json($user);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de l\'utilisateur'], 500);
    }
}


    // Mettre à jour les informations d'un utilisateur
    public function update(Request $request, $id)
    {
        try {
             $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json(['message' => 'User updated successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de l\'utilisateur'], 500);
    }
    }

    // Supprimer un utilisateur
    public function destroy($id)
    {
    try{
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    } catch (\Exception $e) {
        // En cas d'autres erreurs, retourner une réponse JSON avec un message d'erreur général
        return response()->json(['error' => 'Une erreur est survenue lors de la récupération des détails de l\'utilisateur'], 500);
    }

    }
   
    // Activer un utilisateur
    public function activateUser($id)
    {
        try {
            $user = User::findOrFail($id);
            
            // Vérifier si l'utilisateur est déjà activé
            if ($user->status === 'active') {
                return response()->json(['message' => 'User is already active'], 400);
            }

            $user->status = 'active';
            $user->save();

            return response()->json(['message' => 'User activated successfully']);
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    } catch (\Exception $e) {
            return response()->json(['message' => 'Error activating user', 'error' => $e->getMessage()], 500);
        }
    }

    // Désactiver un utilisateur
    public function deactivateUser($id)
    {
        try {
            $user = User::findOrFail($id);

            // Vérifier si l'utilisateur est déjà désactivé
            if ($user->status === 'desactive') {
                return response()->json(['message' => 'User is already desactive'], 400);
            }

            $user->status = 'desactive';
            $user->save();

            return response()->json(['message' => 'User deactivated successfully']);
        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // En cas d'utilisateur non trouvé, retourner une réponse JSON avec un message d'erreur clair
        return response()->json(['error' => 'Utilisateur non trouvé'], 404);
    } catch (\Exception $e) {
            return response()->json(['message' => 'Error deactivating user', 'error' => $e->getMessage()], 500);
        }
    }

}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class userController extends Controller
{
    //REGISTRE USER
    public function registre(Request $req)
    {
        try {

            $existUser = User::where("numero",$req->numero)
            ->orwhere("email",$req->email)
            ->exists();

            if($existUser){
                return response()->json([
                    "status"=>false,
                    "message"=>"Ce utilisateur existe"
                ],401);
            }

            $user = User::create([
               "name"=>$req->name,
               "numero"=>$req->numero,
               "email"=>$req->email,
               "password"=>bcrypt($req->password)
            ]);

            $token = $user->createToken("user_token")->plainTextToken;
            
            return response()->json([
                "status"=>true,
                "token"=>$token,
                "user"=>$user,
                "message"=>"user create"
            ],201);

        } catch (Exception $err) {
            return response()->json([
                "status"=>false,
                "message"=>$err->getMessage()
            ],500);
        }
    }

    // Durée de blocage en secondes (1 heure)
    const BLOCK_DURATION = 5 * 60 * 1000; 
    // Nombre maximal de tentatives
    const TENTATIVES_MAX = 5;

    //LOGIN USER
    public function login(Request $request)
{
    try {
      
        // Recherche de l'utilisateur dans la base de données
        $user = User::where('numero', $request->contacts)
            ->orwhere("email", $request->contacts)->first();

        // Si l'utilisateur n'existe pas, retourner un message d'erreur
        if (!$user) {
            return response()->json([
                "status" => false,
                "message" => 'Votre email ou numéro de téléphone est incorrect'
            ], 401);
        }

         // Vérifier si l'utilisateur est bloqué (a atteint le nombre maximal de tentatives)
         if ($user->tentatives >= self::TENTATIVES_MAX && $user->tentatives_expires > Carbon::now()) {
            // Convertir 'tentatives_expires' en chaîne de caractères pour l'afficher
            $tempsDattente = Carbon::parse($user->tentatives_expires)->toDateTimeString();
            error_log("Temps d'attente: " . $tempsDattente);
            return response()->json([
                'message' => "Nombre maximal de tentatives atteint. Veuillez réessayer après {$tempsDattente}."
            ], 429);
        }

        // Vérification du mot de passe
        if (!Hash::check($request->password, $user->password)) {
            $user->tentatives += 1;
            // Si le nombre maximal de tentatives est atteint, définir la date d'expiration du blocage
            if ($user->tentatives >= self::TENTATIVES_MAX) {
                $user->tentatives_expires = Carbon::now()->addMilliseconds(self::BLOCK_DURATION);
            }
            // Sauvegarder les modifications de l'utilisateur
            $user->save();
            return response()->json([
                "status" => false,
                "message" => 'Votre mot de passe est incorrect'
            ], 401);
        }
        // Réinitialiser les tentatives après la modification du mot de passe
        $user->tentatives = 0; 
         // Réinitialiser la date d'expiration
        $user->tentatives_expires = Carbon::now();
        
        // Authentification réussie, génération d'un jeton JWT
        $token = $user->createToken("user_token")->plainTextToken;
      
        // Retour des informations sur l'utilisateur et du jeton JWT
        return response()->json([
            "status" => true,
            "message" => "Connecté avec succès !!",
            'token' => $token,
            'user' => $user,
        ], 200);

    } catch (\Exception $error) {
        return response()->json([
            "status" => false,
            "message" => $error->getMessage()
        ], 500);
    }
}

}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Exception;
use Illuminate\Http\Request;
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

        // Vérification du mot de passe
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                "status" => false,
                "message" => 'Votre mot de passe est incorrect'
            ], 401);
        }

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

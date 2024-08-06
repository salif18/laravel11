<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ResetController extends Controller
{
    // Durée de blocage en secondes (1 heure)
    const BLOCK_DURATION = 5 * 60 * 1000; 
    // Nombre maximal de tentatives
    const TENTATIVES_MAX = 3;

    // Fonction pour réinitialiser le token de l'utilisateur
    public function reset(Request $req)
    {
        try {
            // Récupérer les informations de l'utilisateur et vérifier s'il existe
            $user = User::where("numero", $req->numero)
                        ->where("email", $req->email)
                        ->first();

            // Si l'utilisateur n'existe pas, retourner une réponse d'erreur
            if (!$user) {
                return response()->json([
                    "status" => false,
                    "message" => "Cet utilisateur n'existe pas. Veuillez fournir l'email et le numéro avec lesquels vous vous êtes inscrit."
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

            // Générer un nouveau token aléatoire de 4 chiffres
            $newToken = str_pad(rand(0, 9999), 4, "0", STR_PAD_LEFT);

            // Mettre à jour le token et incrémenter les tentatives
            $user->remember_token = $newToken;
            $user->tentatives += 1;

            // Si le nombre maximal de tentatives est atteint, définir la date d'expiration du blocage
            if ($user->tentatives >= self::TENTATIVES_MAX) {
                $user->tentatives_expires = Carbon::now()->addMilliseconds(self::BLOCK_DURATION);
            }

            // Sauvegarder les modifications de l'utilisateur
            $user->save();

            // Retourner une réponse avec le nouveau token
            return response()->json([
                "status" => true,
                "message" => "Veuillez fournir ce token $newToken"
            ], 201);

        } catch (\Exception $err) {
            // Retourner une réponse d'erreur en cas d'exception
            return response()->json([
                "status" => false,
                "error" => $err->getMessage()
            ], 500);
        }
    }

    // Fonction pour valider le nouveau mot de passe
    public function valide(Request $req)
    {
        try {
            // Vérifier si le token fourni existe
            $user = User::where("remember_token", $req->reset_token)->first();
            if (!$user) {
                return response()->json([
                    "status" => false,
                    "message" => "Ce token est déjà expiré"
                ], 401);
            }

            // Vérifier si les nouveaux mots de passe sont identiques
            if ($req->new_password != $req->confirm_password) {
                return response()->json([
                    "status" => false,
                    "message" => "Les deux mots de passe ne sont pas identiques"
                ], 401);
            }

            // Chiffrer le nouveau mot de passe
            $hashedNewPassword = bcrypt($req->new_password);
            $user->password = $hashedNewPassword;
            $user->remember_token = null;
            $user->tentatives = 0; // Réinitialiser les tentatives après la modification du mot de passe
            $user->tentatives_expires = Carbon::now(); // Réinitialiser la date d'expiration
            $user->save();

            // Retourner une réponse de succès
            return response()->json([
                "status" => true,
                "message" => "Mot de passe modifié avec succès"
            ], 200);

        } catch (\Exception $err) {
            // Retourner une réponse d'erreur en cas d'exception
            return response()->json([
                "status" => false,
                "error" => $err->getMessage()
            ], 500);
        }
    }
}

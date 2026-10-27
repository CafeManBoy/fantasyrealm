<?php

namespace App\Core;

// Session PHP : garde des informations d'une page à l'autre (compte connecté, jeton CSRF...).
// Le navigateur ne reçoit qu'un identifiant, dans un cookie ; les données restent sur le serveur.
class Session
{
    // Démarre la session avec un cookie protégé
    public function demarrer(): void
    {
        $enProduction = true;
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $enProduction = false;
        }

        // PHP refuse un identifiant de session qu'il n'a pas créé lui-même
        // (protection contre la fixation de session)
        ini_set('session.use_strict_mode', '1');

        session_set_cookie_params([
            // 0 : le cookie est supprimé à la fermeture du navigateur
            'lifetime' => 0,
            'path' => '/',
            // secure : cookie envoyé seulement en HTTPS. Activé d'après APP_ENV, car chez
            // l'hébergeur le HTTPS est géré par un proxy et PHP ne le voit pas toujours
            'secure' => $enProduction,
            // httponly : le JavaScript ne peut pas lire le cookie (limite le vol de session par XSS)
            'httponly' => true,
            // Lax : le cookie n'est pas envoyé avec un formulaire POST venant d'un autre site
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    // Ajoute un message à afficher sur la page suivante (après une redirection).
    // Les messages forment une liste : un message n'en efface pas un autre
    // (ex. « compte suspendu » puis « connectez-vous »).
    public function ajouterMessage(string $message): void
    {
        $_SESSION['messages'][] = $message;
    }

    // Renvoie les messages en attente et les efface : ils ne s'affichent qu'une fois
    public function lireMessages(): array
    {
        if (!isset($_SESSION['messages'])) {
            return [];
        }

        $messages = $_SESSION['messages'];
        unset($_SESSION['messages']);

        return $messages;
    }
}

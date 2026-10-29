<?php

namespace App\Core;

// Protection CSRF : chaque formulaire envoie un jeton secret, connu seulement de la session.
// Un autre site ne connaît pas ce jeton : il ne peut donc pas faire envoyer nos formulaires
// par le navigateur d'un utilisateur connecté.
class Csrf
{
    // Renvoie le jeton de la session (créé au premier appel)
    public function obtenirJeton(): string
    {
        if (!isset($_SESSION['csrf'])) {
            // 32 octets aléatoires, écrits en hexadécimal (64 caractères)
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf'];
    }

    // Vérifie que le formulaire envoyé contient le jeton de la session
    public function jetonEstValide(): bool
    {
        if (!isset($_SESSION['csrf']) || !isset($_POST['csrf']) || !is_string($_POST['csrf'])) {
            return false;
        }

        // hash_equals compare en temps constant : la durée de la comparaison ne permet pas
        // de deviner le jeton caractère par caractère
        return hash_equals($_SESSION['csrf'], $_POST['csrf']);
    }
}

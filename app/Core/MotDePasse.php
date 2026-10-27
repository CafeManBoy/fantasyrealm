<?php

namespace App\Core;

// Règles des mots de passe (recommandation de la CNIL : 12 caractères au moins,
// avec majuscule, minuscule, chiffre et caractère spécial)
class MotDePasse
{
    // Renvoie la liste des règles que le mot de passe ne respecte pas (vide si tout est bon)
    public function reglesNonRespectees(string $motDePasse): array
    {
        $regles = [];

        // mb_strlen compte les caractères (une lettre accentuée compte pour un seul caractère)
        if (mb_strlen($motDePasse) < 12) {
            $regles[] = 'au moins 12 caractères';
        }
        if (preg_match('/[A-Z]/', $motDePasse) !== 1) {
            $regles[] = 'une majuscule';
        }
        if (preg_match('/[a-z]/', $motDePasse) !== 1) {
            $regles[] = 'une minuscule';
        }
        if (preg_match('/[0-9]/', $motDePasse) !== 1) {
            $regles[] = 'un chiffre';
        }
        // Caractère spécial : tout ce qui n'est ni une lettre sans accent, ni un chiffre
        if (preg_match('/[^A-Za-z0-9]/', $motDePasse) !== 1) {
            $regles[] = 'un caractère spécial';
        }

        return $regles;
    }

    // bcrypt (l'algorithme de password_hash) ignore tout ce qui dépasse 72 octets : deux mots
    // de passe qui commencent par les mêmes 72 octets seraient acceptés tous les deux.
    // On refuse donc les mots de passe plus longs. strlen compte les octets
    // (une lettre accentuée en vaut 2).
    public function estTropLong(string $motDePasse): bool
    {
        return strlen($motDePasse) > 72;
    }
}

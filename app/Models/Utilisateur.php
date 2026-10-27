<?php

namespace App\Models;

use App\Core\Database;

// Accès à la table utilisateur (les comptes). Le libellé du rôle est lu dans la table role.
class Utilisateur
{
    // Renvoie le compte qui a ce mail, ou null s'il n'existe pas
    public function trouverParMail(string $mail): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT u.id_utilisateur, u.pseudo, u.mail, u.password, u.suspendu,
                    u.motdepasse_a_modifier, r.libelle AS role
             FROM utilisateur u
             JOIN role r ON r.id_role = u.id_role
             WHERE u.mail = :mail'
        );
        $requete->execute(['mail' => $mail]);
        $utilisateur = $requete->fetch();

        // fetch() renvoie false quand aucune ligne ne correspond
        if ($utilisateur === false) {
            return null;
        }

        return $utilisateur;
    }

    // Renvoie le compte qui a cet identifiant, ou null s'il n'existe pas (ou plus)
    public function trouverParId(int $id): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT u.id_utilisateur, u.pseudo, u.mail, u.password, u.suspendu,
                    u.motdepasse_a_modifier, r.libelle AS role
             FROM utilisateur u
             JOIN role r ON r.id_role = u.id_role
             WHERE u.id_utilisateur = :id'
        );
        $requete->execute(['id' => $id]);
        $utilisateur = $requete->fetch();

        if ($utilisateur === false) {
            return null;
        }

        return $utilisateur;
    }

    // Remplace le hash du mot de passe (sans changer le mot de passe lui-même)
    public function modifierHash(int $id, string $hash): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE utilisateur SET password = :hash WHERE id_utilisateur = :id'
        );
        $requete->execute(['hash' => $hash, 'id' => $id]);
    }
}

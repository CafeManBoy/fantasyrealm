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

    // true si un compte utilise déjà ce mail
    public function mailExiste(string $mail): bool
    {
        $requete = Database::connexion()->prepare('SELECT COUNT(*) FROM utilisateur WHERE mail = :mail');
        $requete->execute(['mail' => $mail]);

        return $requete->fetchColumn() > 0;
    }

    // true si un compte utilise déjà ce pseudo. Les majuscules ne comptent pas : la collation
    // utf8mb4_unicode_ci de la base considère « Elandra » et « elandra » comme identiques.
    public function pseudoExiste(string $pseudo): bool
    {
        $requete = Database::connexion()->prepare('SELECT COUNT(*) FROM utilisateur WHERE pseudo = :pseudo');
        $requete->execute(['pseudo' => $pseudo]);

        return $requete->fetchColumn() > 0;
    }

    // Crée un compte joueur (rôle « utilisateur ») et renvoie son identifiant
    public function creer(string $pseudo, string $mail, string $hash): int
    {
        $connexion = Database::connexion();
        $requete = $connexion->prepare(
            "INSERT INTO utilisateur (pseudo, mail, password, id_role)
             VALUES (:pseudo, :mail, :hash, (SELECT id_role FROM role WHERE libelle = 'utilisateur'))"
        );
        $requete->execute(['pseudo' => $pseudo, 'mail' => $mail, 'hash' => $hash]);

        // Identifiant attribué par AUTO_INCREMENT
        return (int) $connexion->lastInsertId();
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

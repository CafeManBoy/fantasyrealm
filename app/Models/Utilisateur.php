<?php

namespace App\Models;

use App\Core\Database;

// Accès à la table utilisateur (les comptes). Le libellé du rôle est lu dans la table role.
class Utilisateur
{
    // Renvoie le compte qui a ce mail, ou null s'il n'existe pas.
    // est_bloque vaut 1 si la connexion est bloquée en ce moment : la comparaison des dates
    // est faite par MySQL (NOW()), avec l'heure du serveur de base de données.
    public function trouverParMail(string $mail): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT u.id_utilisateur, u.pseudo, u.mail, u.password, u.suspendu,
                    u.motdepasse_a_modifier, u.tentatives_echouees,
                    (u.bloque_jusqu_a IS NOT NULL AND u.bloque_jusqu_a > NOW()) AS est_bloque,
                    r.libelle AS role
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

    // Compte un échec de connexion de plus
    public function ajouterEchec(int $id): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE utilisateur SET tentatives_echouees = tentatives_echouees + 1 WHERE id_utilisateur = :id'
        );
        $requete->execute(['id' => $id]);
    }

    // Bloque la connexion au compte pendant 15 minutes (date calculée par MySQL)
    // et remet le compteur d'échecs à 0 pour la suite
    public function bloquer(int $id): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE utilisateur
             SET tentatives_echouees = 0, bloque_jusqu_a = NOW() + INTERVAL 15 MINUTE
             WHERE id_utilisateur = :id'
        );
        $requete->execute(['id' => $id]);
    }

    // Remet le compteur d'échecs à 0 et lève un éventuel blocage (après une connexion réussie)
    public function reinitialiserEchecs(int $id): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE utilisateur SET tentatives_echouees = 0, bloque_jusqu_a = NULL WHERE id_utilisateur = :id'
        );
        $requete->execute(['id' => $id]);
    }

    // Enregistre le nouveau mot de passe choisi par l'utilisateur : il n'a plus à le changer
    public function modifierMotDePasse(int $id, string $hash): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE utilisateur SET password = :hash, motdepasse_a_modifier = 0 WHERE id_utilisateur = :id'
        );
        $requete->execute(['hash' => $hash, 'id' => $id]);
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

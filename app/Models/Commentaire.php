<?php

namespace App\Models;

use App\Core\Database;

// Accès à la table commentaire : les avis (note + commentaire) déposés sur les personnages.
// statut : 'en_attente' (pas encore visible) ou 'valide' (publié). Un avis refusé est supprimé.
class Commentaire
{
    // Avis validés d'un personnage, du plus récent au plus ancien.
    // Les avis en attente ne sont jamais affichés publiquement.
    public function listerValides(int $idPersonnage): array
    {
        $requete = Database::connexion()->prepare(
            "SELECT note, commentaire, date_commentaire, pseudo_auteur
             FROM commentaire
             WHERE id_personnage = :id AND statut = 'valide'
             ORDER BY date_commentaire DESC, id_commentaire DESC"
        );
        $requete->execute(['id' => $idPersonnage]);

        return $requete->fetchAll();
    }

    // Espace employé : avis en attente de validation, du plus ancien au plus récent,
    // avec le nom du personnage concerné
    public function listerEnAttente(): array
    {
        $requete = Database::connexion()->query(
            "SELECT c.id_commentaire, c.note, c.commentaire, c.date_commentaire, c.pseudo_auteur,
                    p.id_personnage, p.nom AS nom_personnage
             FROM commentaire c
             JOIN personnage p ON p.id_personnage = c.id_personnage
             WHERE c.statut = 'en_attente'
             ORDER BY c.date_commentaire, c.id_commentaire"
        );

        return $requete->fetchAll();
    }

    public function compterEnAttente(): int
    {
        $requete = Database::connexion()->query("SELECT COUNT(*) FROM commentaire WHERE statut = 'en_attente'");

        return (int) $requete->fetchColumn();
    }

    // Espace employé : un avis, avec son personnage et le pseudo et le mail du propriétaire
    // du personnage (pour le mail de publication), ou null s'il n'existe pas
    public function trouverAvecProprietaire(int $id): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT c.id_commentaire, c.note, c.statut, c.pseudo_auteur,
                    p.id_personnage, p.nom AS nom_personnage, u.pseudo, u.mail
             FROM commentaire c
             JOIN personnage p ON p.id_personnage = c.id_personnage
             JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
             WHERE c.id_commentaire = :id'
        );
        $requete->execute(['id' => $id]);
        $avis = $requete->fetch();

        if ($avis === false) {
            return null;
        }

        return $avis;
    }

    // Publication d'un avis par un employé
    public function valider(int $id): void
    {
        $requete = Database::connexion()->prepare("UPDATE commentaire SET statut = 'valide' WHERE id_commentaire = :id");
        $requete->execute(['id' => $id]);
    }

    // Avis refusé : supprimé définitivement (decisions.md, minimisation des données)
    public function supprimer(int $id): void
    {
        $requete = Database::connexion()->prepare('DELETE FROM commentaire WHERE id_commentaire = :id');
        $requete->execute(['id' => $id]);
    }

    // L'avis de ce joueur sur ce personnage (avec son statut), ou null s'il n'en a pas donné
    public function trouverAvisDe(int $idPersonnage, int $idUtilisateur): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT id_commentaire, statut
             FROM commentaire
             WHERE id_personnage = :id_personnage AND id_utilisateur = :id_utilisateur'
        );
        $requete->execute(['id_personnage' => $idPersonnage, 'id_utilisateur' => $idUtilisateur]);
        $avis = $requete->fetch();

        if ($avis === false) {
            return null;
        }

        return $avis;
    }

    // Enregistre un avis en attente de validation, daté du jour (valeurs déjà vérifiées).
    // pseudo_auteur : copie du pseudo, affichée avec l'avis. Si le compte est supprimé plus tard,
    // l'avis reste et pseudo_auteur devient « Compte supprimé » (Utilisateur::supprimer()).
    public function creer(int $note, string $commentaire, int $idPersonnage, int $idUtilisateur, string $pseudo): void
    {
        $requete = Database::connexion()->prepare(
            "INSERT INTO commentaire (note, commentaire, date_commentaire, statut,
                                      id_personnage, id_utilisateur, pseudo_auteur)
             VALUES (:note, :commentaire, CURDATE(), 'en_attente',
                     :id_personnage, :id_utilisateur, :pseudo_auteur)"
        );
        $requete->execute([
            'note' => $note,
            'commentaire' => $commentaire,
            'id_personnage' => $idPersonnage,
            'id_utilisateur' => $idUtilisateur,
            'pseudo_auteur' => $pseudo,
        ]);
    }
}

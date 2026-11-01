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
    // pseudo_auteur : copie du pseudo, gardée même si le compte est supprimé plus tard.
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

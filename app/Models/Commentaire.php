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
}

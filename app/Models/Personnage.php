<?php

namespace App\Models;

use App\Core\Database;

// Accès à la table personnage et aux articles qu'il porte (tables dispose et article).
class Personnage
{
    // Personnages d'un joueur, du plus récent au plus ancien
    public function listerParUtilisateur(int $idUtilisateur): array
    {
        $requete = Database::connexion()->prepare(
            'SELECT id_personnage, nom, genre, couleur_peau, couleur_yeux, couleur_cheveux,
                    forme_yeux, forme_nez, forme_bouche, coupe_cheveux, partage, autorise, date_creation
             FROM personnage
             WHERE id_utilisateur = :id
             ORDER BY date_creation DESC'
        );
        $requete->execute(['id' => $idUtilisateur]);

        return $requete->fetchAll();
    }

    // Articles portés par un personnage. Un article désactivé (actif = 0) n'est affiché nulle part :
    // il reste lié au personnage en base, mais il n'est pas renvoyé ici.
    public function listerArticlesActifs(int $idPersonnage): array
    {
        $requete = Database::connexion()->prepare(
            'SELECT a.id_article, a.nom, a.type, a.image
             FROM dispose d
             JOIN article a ON a.id_article = d.id_article
             WHERE d.id_personnage = :id AND a.actif = 1'
        );
        $requete->execute(['id' => $idPersonnage]);

        return $requete->fetchAll();
    }
}

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

    // Vrai si un autre personnage porte déjà ce nom.
    // La comparaison suit l'interclassement de la base (utf8mb4_unicode_ci) : elle ne tient compte
    // ni des majuscules ni des accents (« zephyr » = « Zéphyr »), comme la contrainte UNIQUE.
    // $idAExclure : le personnage que l'on modifie (il peut garder son propre nom)
    public function nomExiste(string $nom, ?int $idAExclure = null): bool
    {
        $requete = Database::connexion()->prepare(
            'SELECT COUNT(*) FROM personnage WHERE nom = :nom AND id_personnage <> :id'
        );
        // 0 : aucun personnage n'a cet id, donc personne n'est exclu
        $id = 0;
        if ($idAExclure !== null) {
            $id = $idAExclure;
        }
        $requete->execute(['nom' => $nom, 'id' => $id]);

        return $requete->fetchColumn() > 0;
    }

    // Crée un personnage en attente de validation et non partagé (valeurs par défaut de la table).
    // $personnage : nom, genre, formes, coupe et couleurs, déjà vérifiés par le contrôleur.
    // Renvoie l'id du nouveau personnage.
    public function creer(array $personnage, int $idUtilisateur): int
    {
        $connexion = Database::connexion();
        $requete = $connexion->prepare(
            'INSERT INTO personnage (nom, genre, couleur_peau, couleur_yeux, couleur_cheveux,
                                     forme_yeux, forme_nez, forme_bouche, coupe_cheveux, id_utilisateur)
             VALUES (:nom, :genre, :couleur_peau, :couleur_yeux, :couleur_cheveux,
                     :forme_yeux, :forme_nez, :forme_bouche, :coupe_cheveux, :id_utilisateur)'
        );
        $requete->execute([
            'nom' => $personnage['nom'],
            'genre' => $personnage['genre'],
            'couleur_peau' => $personnage['couleur_peau'],
            'couleur_yeux' => $personnage['couleur_yeux'],
            'couleur_cheveux' => $personnage['couleur_cheveux'],
            'forme_yeux' => $personnage['forme_yeux'],
            'forme_nez' => $personnage['forme_nez'],
            'forme_bouche' => $personnage['forme_bouche'],
            'coupe_cheveux' => $personnage['coupe_cheveux'],
            'id_utilisateur' => $idUtilisateur,
        ]);

        return (int) $connexion->lastInsertId();
    }
}

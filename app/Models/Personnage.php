<?php

namespace App\Models;

use App\Core\Database;
use PDO;

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

    // Le personnage $id, seulement s'il appartient au joueur $idUtilisateur ; sinon null.
    // C'est la vérification d'accès : un id changé dans l'adresse ne donne rien.
    public function trouverPourProprietaire(int $id, int $idUtilisateur): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT id_personnage, nom, genre, couleur_peau, couleur_yeux, couleur_cheveux,
                    forme_yeux, forme_nez, forme_bouche, coupe_cheveux, partage, autorise, date_creation
             FROM personnage
             WHERE id_personnage = :id AND id_utilisateur = :id_utilisateur'
        );
        $requete->execute(['id' => $id, 'id_utilisateur' => $idUtilisateur]);
        $personnage = $requete->fetch();

        if ($personnage === false) {
            return null;
        }

        return $personnage;
    }

    // Enregistre le nom et l'apparence (valeurs déjà vérifiées par le contrôleur)
    public function modifierApparence(int $id, array $personnage): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE personnage
             SET nom = :nom, genre = :genre, couleur_peau = :couleur_peau, couleur_yeux = :couleur_yeux,
                 couleur_cheveux = :couleur_cheveux, forme_yeux = :forme_yeux, forme_nez = :forme_nez,
                 forme_bouche = :forme_bouche, coupe_cheveux = :coupe_cheveux
             WHERE id_personnage = :id'
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
            'id' => $id,
        ]);
    }

    // Après un changement de nom : de nouveau en attente de validation, et plus partagé
    public function remettreEnAttente(int $id): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE personnage SET autorise = 0, partage = 0 WHERE id_personnage = :id'
        );
        $requete->execute(['id' => $id]);
    }

    // Équipe un article
    public function ajouterArticle(int $idPersonnage, int $idArticle): void
    {
        $requete = Database::connexion()->prepare(
            'INSERT INTO dispose (id_personnage, id_article) VALUES (:id_personnage, :id_article)'
        );
        $requete->execute(['id_personnage' => $idPersonnage, 'id_article' => $idArticle]);
    }

    // Retire un seul article (le joueur a choisi « aucun » pour son type)
    public function retirerArticle(int $idPersonnage, int $idArticle): void
    {
        $requete = Database::connexion()->prepare(
            'DELETE FROM dispose WHERE id_personnage = :id_personnage AND id_article = :id_article'
        );
        $requete->execute(['id_personnage' => $idPersonnage, 'id_article' => $idArticle]);
    }

    // Noms de tous les articles d'un type portés par le personnage, actifs ou désactivés
    // (pour le journal, juste avant de les remplacer)
    public function nomsArticlesDuType(int $idPersonnage, string $type): array
    {
        $requete = Database::connexion()->prepare(
            'SELECT a.nom
             FROM dispose d
             JOIN article a ON a.id_article = d.id_article
             WHERE d.id_personnage = :id_personnage AND a.type = :type'
        );
        $requete->execute(['id_personnage' => $idPersonnage, 'type' => $type]);

        // FETCH_COLUMN : un simple tableau de noms au lieu d'un tableau de lignes
        return $requete->fetchAll(PDO::FETCH_COLUMN);
    }

    // Retire tous les articles d'un type, actifs ou désactivés (le joueur en a choisi un autre :
    // un seul article par type)
    public function retirerArticlesDuType(int $idPersonnage, string $type): void
    {
        $requete = Database::connexion()->prepare(
            'DELETE d FROM dispose d
             JOIN article a ON a.id_article = d.id_article
             WHERE d.id_personnage = :id_personnage AND a.type = :type'
        );
        $requete->execute(['id_personnage' => $idPersonnage, 'type' => $type]);
    }
}

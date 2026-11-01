<?php

namespace App\Models;

use App\Core\Database;
use PDO;
use PDOException;

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

    // Personnages de la galerie, du plus récent au plus ancien, avec le pseudo de leur créateur.
    // Galerie (decisions.md) : validé, partagé, et propriétaire non suspendu.
    // $filtres : 'genre', 'du', 'au', 'pseudo' ('' = pas de filtre), déjà vérifiés par le contrôleur.
    // Chaque filtre ajoute un morceau de SQL écrit ici, en dur ; sa valeur passe en paramètre
    // préparé : aucune saisie de l'utilisateur n'est collée dans la requête.
    public function listerGalerie(array $filtres): array
    {
        $sql = $this->requeteGalerie();
        $parametres = [];

        if ($filtres['genre'] !== '') {
            $sql .= ' AND p.genre = :genre';
            $parametres['genre'] = $filtres['genre'];
        }
        // DATE() garde seulement le jour : les deux bornes sont incluses, quelle que soit l'heure
        if ($filtres['du'] !== '') {
            $sql .= ' AND DATE(p.date_creation) >= :du';
            $parametres['du'] = $filtres['du'];
        }
        if ($filtres['au'] !== '') {
            $sql .= ' AND DATE(p.date_creation) <= :au';
            $parametres['au'] = $filtres['au'];
        }
        if ($filtres['pseudo'] !== '') {
            $sql .= ' AND u.pseudo = :pseudo';
            $parametres['pseudo'] = $filtres['pseudo'];
        }
        $sql .= ' ORDER BY p.date_creation DESC';

        $requete = Database::connexion()->prepare($sql);
        $requete->execute($parametres);

        return $requete->fetchAll();
    }

    // Le personnage $id s'il est dans la galerie, sinon null (en attente, non partagé,
    // propriétaire suspendu ou inexistant)
    public function trouverDansGalerie(int $id): ?array
    {
        $requete = Database::connexion()->prepare($this->requeteGalerie() . ' AND p.id_personnage = :id');
        $requete->execute(['id' => $id]);
        $personnage = $requete->fetch();

        if ($personnage === false) {
            return null;
        }

        return $personnage;
    }

    // Pseudos des joueurs qui ont au moins un personnage dans la galerie (liste du filtre)
    public function pseudosGalerie(): array
    {
        $requete = Database::connexion()->query(
            'SELECT DISTINCT u.pseudo
             FROM personnage p
             JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
             WHERE p.autorise = 1 AND p.partage = 1 AND u.suspendu = 0
             ORDER BY u.pseudo'
        );

        return $requete->fetchAll(PDO::FETCH_COLUMN);
    }

    // Début commun des requêtes de la galerie : les colonnes utiles et les 3 conditions de visibilité
    private function requeteGalerie(): string
    {
        return 'SELECT p.id_personnage, p.nom, p.genre, p.couleur_peau, p.couleur_yeux, p.couleur_cheveux,
                       p.forme_yeux, p.forme_nez, p.forme_bouche, p.coupe_cheveux, p.date_creation,
                       p.id_utilisateur, u.pseudo
                FROM personnage p
                JOIN utilisateur u ON u.id_utilisateur = p.id_utilisateur
                WHERE p.autorise = 1 AND p.partage = 1 AND u.suspendu = 0';
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

    // Partage (true) ou arrêt du partage (false) dans la galerie
    public function changerPartage(int $id, bool $partage): void
    {
        $requete = Database::connexion()->prepare(
            'UPDATE personnage SET partage = :partage WHERE id_personnage = :id'
        );
        $requete->execute(['partage' => (int) $partage, 'id' => $id]);
    }

    // Suppression définitive. La base supprime aussi son équipement et ses avis
    // (clés étrangères ON DELETE CASCADE des tables dispose et commentaire).
    public function supprimer(int $id): void
    {
        $requete = Database::connexion()->prepare('DELETE FROM personnage WHERE id_personnage = :id');
        $requete->execute(['id' => $id]);
    }

    // Copie l'apparence et les articles du personnage $id sous un nouveau nom (déjà vérifié).
    // La copie appartient au même joueur ; elle est en attente et non partagée (valeurs par défaut).
    // Transaction : les deux requêtes réussissent ensemble, ou rien n'est enregistré
    // (pas de copie sans ses articles). Renvoie l'id de la copie.
    public function dupliquer(int $id, string $nouveauNom): int
    {
        $connexion = Database::connexion();
        $connexion->beginTransaction();
        try {
            $requete = $connexion->prepare(
                'INSERT INTO personnage (nom, genre, couleur_peau, couleur_yeux, couleur_cheveux,
                                         forme_yeux, forme_nez, forme_bouche, coupe_cheveux, id_utilisateur)
                 SELECT :nom, genre, couleur_peau, couleur_yeux, couleur_cheveux,
                        forme_yeux, forme_nez, forme_bouche, coupe_cheveux, id_utilisateur
                 FROM personnage
                 WHERE id_personnage = :id'
            );
            $requete->execute(['nom' => $nouveauNom, 'id' => $id]);
            $idCopie = (int) $connexion->lastInsertId();

            $requete = $connexion->prepare(
                'INSERT INTO dispose (id_personnage, id_article)
                 SELECT :id_copie, id_article FROM dispose WHERE id_personnage = :id'
            );
            $requete->execute(['id_copie' => $idCopie, 'id' => $id]);

            $connexion->commit();
        } catch (PDOException $e) {
            // Annule la première requête si la deuxième a échoué, puis laisse remonter l'erreur
            $connexion->rollBack();
            throw $e;
        }

        return $idCopie;
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

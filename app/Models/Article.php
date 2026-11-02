<?php

namespace App\Models;

use App\Core\Database;

// Accès à la table article : les vêtements, armures, accessoires et armes proposés par la plateforme.
class Article
{
    // Les quatre types d'articles cités par le sujet (clé enregistrée en base => libellé affiché).
    // Ce sont aussi les valeurs autorisées par la contrainte CHECK de la table article.
    public function types(): array
    {
        return [
            'vetement' => 'Vêtement',
            'armure' => 'Armure',
            'accessoire' => 'Accessoire',
            'arme' => 'Arme',
        ];
    }

    // Articles actifs (proposés aux joueurs), rangés par type : type => [id_article => article].
    // Chaque type est présent, même sans article.
    public function listerActifsParType(): array
    {
        $articles = [];
        foreach ($this->types() as $type => $libelle) {
            $articles[$type] = [];
        }

        // Aucune donnée venant de l'utilisateur : une requête simple suffit (pas de paramètre)
        $requete = Database::connexion()->query(
            'SELECT id_article, nom, type, image FROM article WHERE actif = 1 ORDER BY nom'
        );
        foreach ($requete->fetchAll() as $article) {
            $articles[$article['type']][$article['id_article']] = $article;
        }

        return $articles;
    }

    // Espace employé : tous les articles (actifs ou non), avec le nombre de personnages qui les portent.
    // LEFT JOIN : un article porté par personne apparaît aussi (avec 0).
    public function listerTous(): array
    {
        $requete = Database::connexion()->query(
            'SELECT a.id_article, a.nom, a.type, a.image, a.actif, COUNT(d.id_personnage) AS nombre_personnages
             FROM article a
             LEFT JOIN dispose d ON d.id_article = a.id_article
             GROUP BY a.id_article, a.nom, a.type, a.image, a.actif
             ORDER BY a.type, a.nom'
        );

        return $requete->fetchAll();
    }

    // Un article, ou null s'il n'existe pas
    public function trouver(int $id): ?array
    {
        $requete = Database::connexion()->prepare(
            'SELECT id_article, nom, type, image, actif FROM article WHERE id_article = :id'
        );
        $requete->execute(['id' => $id]);
        $article = $requete->fetch();

        if ($article === false) {
            return null;
        }

        return $article;
    }

    // Nouvel article, actif par défaut. $image : chemin du calque (ex. /uploads/articles/3f9c….png)
    public function creer(string $nom, string $type, string $image): void
    {
        $requete = Database::connexion()->prepare(
            'INSERT INTO article (nom, type, image) VALUES (:nom, :type, :image)'
        );
        $requete->execute(['nom' => $nom, 'type' => $type, 'image' => $image]);
    }

    // Activer (true) ou désactiver (false) : un article désactivé reste lié aux personnages
    public function changerActif(int $id, bool $actif): void
    {
        $requete = Database::connexion()->prepare('UPDATE article SET actif = :actif WHERE id_article = :id');
        $requete->execute(['actif' => (int) $actif, 'id' => $id]);
    }

    // Personnages qui portent cet article (pour le journal, juste avant de le supprimer)
    public function personnagesQuiPortent(int $id): array
    {
        $requete = Database::connexion()->prepare(
            'SELECT p.id_personnage, p.nom
             FROM dispose d
             JOIN personnage p ON p.id_personnage = d.id_personnage
             WHERE d.id_article = :id'
        );
        $requete->execute(['id' => $id]);

        return $requete->fetchAll();
    }

    // Suppression définitive : la base le retire aussi de tous les personnages (ON DELETE CASCADE)
    public function supprimer(int $id): void
    {
        $requete = Database::connexion()->prepare('DELETE FROM article WHERE id_article = :id');
        $requete->execute(['id' => $id]);
    }
}

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
}

<?php

namespace App\Controllers;

use App\Models\Article;
use App\Models\Avatar;
use App\Models\Commentaire;
use App\Models\Personnage;

// Galerie des personnages partagés (page globale avec filtres), page détail et avis.
// La galerie et le détail sont publics : pas besoin d'être connecté pour les voir.
class GalerieController extends Controller
{
    // GET /personnages, avec des filtres facultatifs dans l'adresse :
    // /personnages?genre=femme&du=2026-08-01&au=2026-08-31&pseudo=Korvak
    public function galerie(): void
    {
        $modele = new Personnage();
        $avatar = new Avatar();
        $pseudos = $modele->pseudosGalerie();
        $verification = $this->verifierFiltres($this->lireFiltres(), $pseudos);
        $filtres = $verification['filtres'];

        // Calques de l'avatar de chaque personnage, avec ses articles actifs
        $personnages = $modele->listerGalerie($filtres);
        foreach ($personnages as $index => $personnage) {
            $articles = $modele->listerArticlesActifs($personnage['id_personnage']);
            $personnages[$index]['calques'] = $avatar->calques($personnage, $articles);
        }

        $this->afficher('galerie', 'Galerie des personnages', [
            'personnages' => $personnages,
            'filtres' => $filtres,
            'messagesFiltres' => $verification['messages'],
            'genres' => $avatar->parties()['genre']['options'],
            'pseudos' => $pseudos,
        ]);
    }

    // GET /personnage?id=12 : détail d'un personnage de la galerie, avec ses avis validés
    public function detail(): void
    {
        $personnage = $this->chargerPersonnageDeLaGalerie($this->lireId($_GET));
        $this->afficherDetail($personnage);
    }

    // Personnage de la galerie (validé, partagé, propriétaire non suspendu) ; sinon page 404,
    // même si l'id existe : un personnage en attente ou non partagé ne se montre pas
    private function chargerPersonnageDeLaGalerie(int $id): array
    {
        $modele = new Personnage();
        $personnage = $modele->trouverDansGalerie($id);
        if ($personnage === null) {
            $this->afficherPageIntrouvable();
        }

        return $personnage;
    }

    private function afficherDetail(array $personnage): void
    {
        $modele = new Personnage();
        $modeleArticle = new Article();
        $modeleCommentaire = new Commentaire();
        $avatar = new Avatar();
        // Articles actifs seulement : un article désactivé n'est affiché nulle part (decisions.md)
        $articles = $modele->listerArticlesActifs($personnage['id_personnage']);

        $this->afficher('personnage-detail', $personnage['nom'], [
            'personnage' => $personnage,
            'calques' => $avatar->calques($personnage, $articles),
            'caracteristiques' => $this->caracteristiques($personnage),
            'articles' => $articles,
            'typesArticles' => $modeleArticle->types(),
            'avis' => $modeleCommentaire->listerValides($personnage['id_personnage']),
        ]);
    }

    // Informations de création à afficher : libellé du champ => valeur lisible
    // (« Forme du nez » => « Nez romain »), et pour les couleurs, le code #RRGGBB vérifié (sinon null)
    private function caracteristiques(array $personnage): array
    {
        $avatar = new Avatar();
        $caracteristiques = [];
        foreach ($avatar->parties() as $colonne => $partie) {
            $caracteristiques[] = [
                'libelle' => $partie['libelle'],
                'valeur' => $avatar->libelleOption($colonne, $personnage[$colonne]),
                'couleur' => null,
            ];
        }
        foreach ($avatar->couleurs() as $colonne => $couleur) {
            $code = null;
            if ($avatar->couleurEstValide($personnage[$colonne])) {
                $code = $personnage[$colonne];
            }
            $caracteristiques[] = ['libelle' => $couleur['libelle'], 'valeur' => $personnage[$colonne], 'couleur' => $code];
        }

        return $caracteristiques;
    }

    // Filtres lus dans l'adresse ('' = pas de filtre)
    private function lireFiltres(): array
    {
        return [
            'genre' => $this->lireParametre('genre'),
            'du' => $this->lireParametre('du'),
            'au' => $this->lireParametre('au'),
            'pseudo' => $this->lireParametre('pseudo'),
        ];
    }

    // Vérifie les filtres. Un filtre invalide est vidé (donc ignoré) et un message l'explique.
    // Renvoie ['filtres' => les filtres corrigés, 'messages' => les messages à afficher].
    private function verifierFiltres(array $filtres, array $pseudos): array
    {
        $avatar = new Avatar();
        $messages = [];

        // Listes blanches : le genre doit exister dans la configuration, le pseudo dans la liste
        if ($filtres['genre'] !== '' && !$avatar->optionExiste('genre', $filtres['genre'])) {
            $filtres['genre'] = '';
            $messages[] = 'Genre inconnu : ce filtre est ignoré.';
        }
        if ($filtres['pseudo'] !== '' && !in_array($filtres['pseudo'], $pseudos, true)) {
            $filtres['pseudo'] = '';
            $messages[] = 'Aucun personnage partagé pour ce pseudo : ce filtre est ignoré.';
        }

        foreach (['du' => 'de début', 'au' => 'de fin'] as $champ => $texte) {
            if ($filtres[$champ] !== '' && !$this->dateEstValide($filtres[$champ])) {
                $filtres[$champ] = '';
                $messages[] = 'Date ' . $texte . ' invalide : elle est ignorée.';
            }
        }

        // Au format AAAA-MM-JJ, les dates se comparent comme du texte
        if ($filtres['du'] !== '' && $filtres['au'] !== '' && $filtres['du'] > $filtres['au']) {
            $filtres['du'] = '';
            $filtres['au'] = '';
            $messages[] = 'La date de fin est avant la date de début : le filtre des dates est ignoré.';
        }

        return ['filtres' => $filtres, 'messages' => $messages];
    }

    // Vrai pour une vraie date au format AAAA-MM-JJ (celui des champs type="date").
    // checkdate() refuse les dates qui n'existent pas, comme le 30 février.
    private function dateEstValide(string $date): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $date, $morceaux) !== 1) {
            return false;
        }

        return checkdate((int) $morceaux[2], (int) $morceaux[3], (int) $morceaux[1]);
    }
}

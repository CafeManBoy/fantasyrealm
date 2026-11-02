<?php

namespace App\Models;

// Avatar d'un personnage. Les données viennent de config/avatar.php (pas de la base) :
// la classe lit les options, vérifie les choix et construit la liste des calques
// (images superposées) à afficher.
class Avatar
{
    // Les choix de forme : colonne => libellé, calque et options (voir config/avatar.php)
    private array $parties;

    // Les trois couleurs : colonne => libellé, calque et couleur par défaut
    private array $couleurs;

    public function __construct()
    {
        $config = require __DIR__ . '/../../config/avatar.php';
        $this->parties = $config['parties'];
        $this->couleurs = $config['couleurs'];
    }

    // Les choix de forme, pour générer le formulaire
    public function parties(): array
    {
        return $this->parties;
    }

    // Les couleurs, pour générer le formulaire
    public function couleurs(): array
    {
        return $this->couleurs;
    }

    // Vrai si $cle est une option qui existe pour cette partie (liste blanche).
    // Ex. : optionExiste('forme_nez', 'romain') → true ; optionExiste('forme_nez', '../x') → false
    public function optionExiste(string $partie, string $cle): bool
    {
        return isset($this->parties[$partie]['options'][$cle]);
    }

    // Libellé affiché d'une option (ex. 'romain' → « Nez romain ») ; la clé elle-même si elle est inconnue
    public function libelleOption(string $partie, string $cle): string
    {
        if (!$this->optionExiste($partie, $cle)) {
            return $cle;
        }

        return $this->parties[$partie]['options'][$cle]['libelle'];
    }

    // Vrai si $couleur est un code hexadécimal #RRGGBB.
    // Règle de sécurité : rien d'autre ne doit être placé dans un attribut style.
    // \z : vraie fin du texte ($ accepterait encore un retour à la ligne final)
    public function couleurEstValide(string $couleur): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}\z/', $couleur) === 1;
    }

    // Valeurs du formulaire de création : nom vide, première option de chaque partie,
    // couleurs par défaut de la configuration
    public function personnageParDefaut(): array
    {
        $personnage = ['nom' => ''];
        foreach ($this->parties as $colonne => $partie) {
            // array_key_first : la clé de la première option de la liste
            $personnage[$colonne] = array_key_first($partie['options']);
        }
        foreach ($this->couleurs as $colonne => $couleur) {
            $personnage[$colonne] = $couleur['defaut'];
        }

        return $personnage;
    }

    // Liste des calques d'un personnage, du fond vers l'avant :
    // corps → vêtement → armure → yeux → nez → bouche → cheveux → accessoire → arme.
    // $personnage : une ligne de la table personnage (ou les valeurs du formulaire)
    // $articles : ses articles actifs (chacun avec 'type' et 'image')
    // $avecCalquesVides : true pour l'aperçu du formulaire. Un type d'article non porté donne
    //   alors un calque vide (image null), que le JavaScript remplira si le joueur en choisit un.
    // Chaque calque est un tableau : partie, type ('masque' ou 'image'), image (chemin), couleur.
    public function calques(array $personnage, array $articles, bool $avecCalquesVides = false): array
    {
        $calques = $this->calquesColores('genre', $personnage['genre'], $personnage['couleur_peau']);
        $calques = array_merge($calques, $this->calquesArticle('vetement', $articles, $avecCalquesVides));
        $calques = array_merge($calques, $this->calquesArticle('armure', $articles, $avecCalquesVides));
        $calques = array_merge($calques, $this->calquesColores('forme_yeux', $personnage['forme_yeux'], $personnage['couleur_yeux']));
        $calques = array_merge($calques, $this->calquesSimples('forme_nez', $personnage['forme_nez']));
        $calques = array_merge($calques, $this->calquesSimples('forme_bouche', $personnage['forme_bouche']));
        $calques = array_merge($calques, $this->calquesColores('coupe_cheveux', $personnage['coupe_cheveux'], $personnage['couleur_cheveux']));
        $calques = array_merge($calques, $this->calquesArticle('accessoire', $articles, $avecCalquesVides));
        $calques = array_merge($calques, $this->calquesArticle('arme', $articles, $avecCalquesVides));

        return $calques;
    }

    // Partie colorable : la zone à colorer (masque), puis les traits par-dessus.
    // Les chemins viennent uniquement de la configuration, jamais de la base.
    // Une option inconnue ne donne aucun calque : la page s'affiche quand même.
    private function calquesColores(string $partie, string $cle, string $couleur): array
    {
        if (!$this->optionExiste($partie, $cle)) {
            return [];
        }
        $nomCalque = $this->parties[$partie]['calque'];
        $option = $this->parties[$partie]['options'][$cle];

        // Couleur invalide : null, et le CSS utilise sa couleur par défaut
        if (!$this->couleurEstValide($couleur)) {
            $couleur = null;
        }

        return [
            ['partie' => $nomCalque, 'type' => 'masque', 'image' => $option['couleur'], 'couleur' => $couleur],
            ['partie' => $nomCalque, 'type' => 'image', 'image' => $option['trait'], 'couleur' => null],
        ];
    }

    // Partie non colorable (nez, bouche) : une seule image
    private function calquesSimples(string $partie, string $cle): array
    {
        if (!$this->optionExiste($partie, $cle)) {
            return [];
        }
        $nomCalque = $this->parties[$partie]['calque'];

        return [
            ['partie' => $nomCalque, 'type' => 'image', 'image' => $this->parties[$partie]['options'][$cle]['image'], 'couleur' => null],
        ];
    }

    // Article du type demandé (vetement, armure, accessoire ou arme), s'il y en a un.
    // Le chemin de l'image est enregistré en base par le site (seed.sql ou envoi par un employé).
    private function calquesArticle(string $type, array $articles, bool $avecCalquesVides): array
    {
        $calques = [];
        foreach ($articles as $article) {
            if ($article['type'] === $type) {
                $calques[] = ['partie' => $type, 'type' => 'image', 'image' => $article['image'], 'couleur' => null];
            }
        }

        if (count($calques) === 0 && $avecCalquesVides) {
            $calques[] = ['partie' => $type, 'type' => 'image', 'image' => null, 'couleur' => null];
        }

        return $calques;
    }
}

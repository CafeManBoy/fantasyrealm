<?php

namespace App\Models;

// Avatar d'un personnage. Les données viennent de config/avatar.php (pas de la base) :
// la classe lit les options et construit la liste des calques (images superposées) à afficher.
class Avatar
{
    // Contenu de config/avatar.php : partie => clé de l'option => libellé et chemins des fichiers
    private array $options;

    public function __construct()
    {
        $this->options = require __DIR__ . '/../../config/avatar.php';
    }

    // Toutes les options, pour générer les formulaires
    public function options(): array
    {
        return $this->options;
    }

    // Vrai si $cle est une option qui existe pour cette partie (liste blanche).
    // Ex. : optionExiste('forme_nez', 'romain') → true ; optionExiste('forme_nez', '../x') → false
    public function optionExiste(string $partie, string $cle): bool
    {
        return isset($this->options[$partie][$cle]);
    }

    // Vrai si $couleur est un code hexadécimal #RRGGBB.
    // Règle de sécurité : rien d'autre ne doit être placé dans un attribut style.
    public function couleurEstValide(string $couleur): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $couleur) === 1;
    }

    // Liste des calques d'un personnage, du fond vers l'avant :
    // corps → vêtement → armure → yeux → nez → bouche → cheveux → accessoire → arme.
    // $personnage : une ligne de la table personnage
    // $articles : ses articles actifs (chacun avec 'type' et 'image')
    // Chaque calque est un tableau : partie, type ('masque' ou 'image'), image (chemin), couleur.
    public function calques(array $personnage, array $articles): array
    {
        $calques = $this->calquesColores('corps', 'genre', $personnage['genre'], $personnage['couleur_peau']);
        $calques = array_merge($calques, $this->calquesArticle('vetement', $articles));
        $calques = array_merge($calques, $this->calquesArticle('armure', $articles));
        $calques = array_merge($calques, $this->calquesColores('yeux', 'forme_yeux', $personnage['forme_yeux'], $personnage['couleur_yeux']));
        $calques = array_merge($calques, $this->calquesSimples('nez', 'forme_nez', $personnage['forme_nez']));
        $calques = array_merge($calques, $this->calquesSimples('bouche', 'forme_bouche', $personnage['forme_bouche']));
        $calques = array_merge($calques, $this->calquesColores('cheveux', 'coupe_cheveux', $personnage['coupe_cheveux'], $personnage['couleur_cheveux']));
        $calques = array_merge($calques, $this->calquesArticle('accessoire', $articles));
        $calques = array_merge($calques, $this->calquesArticle('arme', $articles));

        return $calques;
    }

    // Partie colorable : la zone à colorer (masque), puis les traits par-dessus.
    // Les chemins viennent uniquement de la configuration, jamais de la base.
    // Une option inconnue ne donne aucun calque : la page s'affiche quand même.
    private function calquesColores(string $nomCalque, string $partie, string $cle, string $couleur): array
    {
        if (!$this->optionExiste($partie, $cle)) {
            return [];
        }
        $option = $this->options[$partie][$cle];

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
    private function calquesSimples(string $nomCalque, string $partie, string $cle): array
    {
        if (!$this->optionExiste($partie, $cle)) {
            return [];
        }

        return [
            ['partie' => $nomCalque, 'type' => 'image', 'image' => $this->options[$partie][$cle]['image'], 'couleur' => null],
        ];
    }

    // Article du type demandé (vetement, armure, accessoire ou arme), s'il y en a un.
    // Le chemin de l'image est enregistré en base par le site (seed.sql ou envoi par un employé).
    private function calquesArticle(string $type, array $articles): array
    {
        $calques = [];
        foreach ($articles as $article) {
            if ($article['type'] === $type) {
                $calques[] = ['partie' => $type, 'type' => 'image', 'image' => $article['image'], 'couleur' => null];
            }
        }

        return $calques;
    }
}

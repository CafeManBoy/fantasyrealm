<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Models\Avatar;
use App\Models\Personnage;

// Personnages du joueur : création, modification, équipement, partage, suppression, duplication.
// Réservé au rôle utilisateur. Chaque action qui modifie des données passe par un formulaire POST
// avec jeton CSRF, et elle est enregistrée dans le journal MongoDB.
class PersonnageController extends Controller
{
    public function formulaireCreation(): void
    {
        $this->exigerRole(['utilisateur']);

        $avatar = new Avatar();
        $this->afficherFormulaire($avatar->personnageParDefaut(), []);
    }

    public function traiterCreation(): void
    {
        $this->exigerRole(['utilisateur']);
        $personnage = $this->lirePersonnage();

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherFormulaire($personnage, ['general' => 'Formulaire expiré, réessayez.']);
            return;
        }

        $erreurs = $this->validerPersonnage($personnage, null);
        if (count($erreurs) > 0) {
            $this->afficherFormulaire($personnage, $erreurs);
            return;
        }

        // Journal : à la création, chaque valeur passe de « rien » (null) à la valeur choisie
        $changements = [];
        foreach ($personnage as $colonne => $valeur) {
            $changements[$colonne] = ['avant' => null, 'apres' => $valeur];
        }

        $modele = new Personnage();
        $personnage['id_personnage'] = $modele->creer($personnage, $this->utilisateurConnecte['id_utilisateur']);

        $logger = new Logger();
        $logger->enregistrer('creation', $personnage, $this->utilisateurConnecte, $changements);

        $this->session->ajouterMessage('Votre personnage « ' . $personnage['nom'] . ' » a été créé. '
            . 'Un employé doit valider son nom avant que vous puissiez le modifier et le partager.');
        $this->rediriger('/espace-joueur');
    }

    // Valeurs envoyées par le formulaire : le nom, puis une valeur par partie et par couleur.
    // La liste des champs vient de config/avatar.php (aucune liste recopiée ici).
    private function lirePersonnage(): array
    {
        $avatar = new Avatar();
        $personnage = ['nom' => $this->lireChamp('nom')];

        foreach ($avatar->parties() as $colonne => $partie) {
            $personnage[$colonne] = $this->lireChamp($colonne);
        }
        foreach ($avatar->couleurs() as $colonne => $couleur) {
            $personnage[$colonne] = $this->lireChamp($colonne);
        }

        return $personnage;
    }

    // Vérifie le nom, les options et les couleurs. Renvoie les messages d'erreur rangés par champ
    // (tableau vide si tout est correct). $idActuel : le personnage modifié (null à la création).
    private function validerPersonnage(array $personnage, ?int $idActuel): array
    {
        $erreurs = [];
        $avatar = new Avatar();
        $modele = new Personnage();

        // 3 à 30 caractères, en commençant par une lettre ; ensuite des lettres (\p{L}, accents
        // compris), des espaces, des apostrophes ou des tirets. /u : texte lu en UTF-8 (« é » = 1 caractère)
        if (preg_match('/^\p{L}[\p{L} \'’-]{2,29}\z/u', $personnage['nom']) !== 1) {
            $erreurs['nom'] = 'Le nom doit faire de 3 à 30 caractères, commencer par une lettre et ne contenir '
                . 'que des lettres, des espaces, des apostrophes ou des tirets.';
        } elseif ($modele->nomExiste($personnage['nom'], $idActuel)) {
            $erreurs['nom'] = 'Ce nom est déjà pris (les majuscules et les accents ne comptent pas) : choisissez-en un autre.';
        }

        // Liste blanche : chaque option doit exister dans config/avatar.php
        foreach ($avatar->parties() as $colonne => $partie) {
            if (!$avatar->optionExiste($colonne, $personnage[$colonne])) {
                $erreurs[$colonne] = 'Choisissez une option de la liste.';
            }
        }
        foreach ($avatar->couleurs() as $colonne => $couleur) {
            if (!$avatar->couleurEstValide($personnage[$colonne])) {
                $erreurs[$colonne] = 'Choisissez une couleur au format #RRGGBB.';
            }
        }

        return $erreurs;
    }

    // Affiche le formulaire avec l'aperçu de l'avatar.
    // $personnage : valeurs des champs ; $erreurs : messages par champ, ou 'general'
    private function afficherFormulaire(array $personnage, array $erreurs): void
    {
        $avatar = new Avatar();
        $this->afficher('personnage-formulaire', 'Créer un personnage', [
            'personnage' => $personnage,
            'erreurs' => $erreurs,
            'parties' => $avatar->parties(),
            'couleurs' => $avatar->couleurs(),
            // true : un calque vide par type d'article, que l'aperçu JavaScript pourra remplir
            'calques' => $avatar->calques($personnage, [], true),
        ]);
    }
}

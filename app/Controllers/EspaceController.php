<?php

namespace App\Controllers;

use App\Models\Avatar;
use App\Models\Commentaire;
use App\Models\Personnage;

// Espaces personnels, un par rôle. Leur contenu arrive avec les fonctionnalités suivantes.
class EspaceController extends Controller
{
    public function espaceJoueur(): void
    {
        $this->exigerRole(['utilisateur']);

        // Seulement les personnages du compte connecté (id lu dans la session, jamais dans l'URL)
        $modelePersonnage = new Personnage();
        $avatar = new Avatar();
        $personnages = $modelePersonnage->listerParUtilisateur($this->utilisateurConnecte['id_utilisateur']);

        // Calques de l'avatar de chaque personnage, avec ses articles actifs
        foreach ($personnages as $index => $personnage) {
            $articles = $modelePersonnage->listerArticlesActifs($personnage['id_personnage']);
            $personnages[$index]['calques'] = $avatar->calques($personnage, $articles);
        }

        $this->afficher('espace-joueur', 'Mon espace', ['personnages' => $personnages]);
    }

    public function espaceEmploye(): void
    {
        // L'administrateur peut faire tout ce que fait un employé (sujet)
        $this->exigerRole(['employe', 'admin']);

        // Tableau de bord : ce qui attend une décision
        $modelePersonnage = new Personnage();
        $modeleCommentaire = new Commentaire();
        $this->afficher('espace-employe', 'Espace employé', [
            'nomsEnAttente' => $modelePersonnage->compterEnAttente(),
            'avisEnAttente' => $modeleCommentaire->compterEnAttente(),
        ]);
    }

    public function espaceAdmin(): void
    {
        $this->exigerRole(['admin']);
        $this->afficher('espace-admin', 'Espace administrateur');
    }
}

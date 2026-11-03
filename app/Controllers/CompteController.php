<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Logger;
use App\Models\Personnage;
use App\Models\Utilisateur;

// Page « Mon compte » : changement volontaire du mot de passe (tous les comptes sauf l'admin) et
// suppression de son compte (joueurs seulement, droit à l'effacement du RGPD).
// Le mot de passe actuel est toujours demandé : une session volée ne suffit pas. Comme à la
// connexion, 5 erreurs de suite (les deux formulaires comptent ensemble) bloquent le compte
// 15 minutes et ferment la session.
// Chaque action qui modifie des données est un formulaire POST avec jeton CSRF.
class CompteController extends Controller
{
    // GET /mon-compte : ouverte à tous les rôles (l'admin y lit seulement pourquoi il n'a pas le formulaire)
    public function monCompte(): void
    {
        $this->exigerRole(['utilisateur', 'employe', 'admin']);
        $this->afficherMonCompte([], []);
    }

    // POST /mon-compte/mot-de-passe : nouveau mot de passe choisi par le compte connecté.
    // L'admin n'est pas dans la liste des rôles : 403 même si la requête est envoyée sans le
    // formulaire. Ses identifiants sont publics et il ne peut pas être recréé depuis le site :
    // son mot de passe se gère seulement dans seed.sql (decisions.md).
    public function changerMotDePasse(): void
    {
        $this->exigerRole(['utilisateur', 'employe']);

        $actuel = $this->lireMotDePasse('mot_de_passe_actuel');
        $nouveau = $this->lireMotDePasse('mot_de_passe');
        $confirmation = $this->lireMotDePasse('confirmation');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherMonCompte(['general' => 'Formulaire expiré, réessayez.'], []);
            return;
        }

        $erreurActuel = $this->erreurMotDePasseActuel($actuel);
        if ($erreurActuel !== null) {
            $this->afficherMonCompte(['mot_de_passe_actuel' => $erreurActuel], []);
            return;
        }

        $erreurs = $this->validerNouveauMotDePasse($nouveau, $confirmation);
        if (count($erreurs) > 0) {
            $this->afficherMonCompte($erreurs, []);
            return;
        }

        $modele = new Utilisateur();
        $id = $this->utilisateurConnecte['id_utilisateur'];
        $modele->modifierMotDePasse($id, password_hash($nouveau, PASSWORD_DEFAULT));

        // Comme pour le changement forcé : cette session garde l'empreinte du nouveau hash et reste
        // ouverte (avec un nouvel identifiant), les autres sessions du compte seront fermées.
        $auth = new Auth();
        $auth->connecter($modele->trouverParId($id));
        $this->session->ajouterMessage('Votre mot de passe a été changé. Vos autres sessions ont été fermées.');
        $this->rediriger('/mon-compte');
    }

    // POST /mon-compte/supprimer : le joueur supprime définitivement son compte.
    // Réservé au rôle utilisateur : 403 pour un employé (son compte est géré par l'admin) et pour
    // l'admin (jamais supprimé), même si la requête est envoyée sans le formulaire.
    public function supprimerCompte(): void
    {
        $this->exigerRole(['utilisateur']);

        $motDePasse = $this->lireMotDePasse('mot_de_passe_actuel');
        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherMonCompte([], ['general' => 'Formulaire expiré, réessayez.']);
            return;
        }

        // Même vérification et même compteur d'erreurs que pour le changement de mot de passe
        $erreurActuel = $this->erreurMotDePasseActuel($motDePasse);
        if ($erreurActuel !== null) {
            $this->afficherMonCompte([], ['mot_de_passe_actuel' => $erreurActuel]);
            return;
        }

        $compte = $this->utilisateurConnecte;

        // Personnages lus AVANT la suppression du compte, pour le journal
        $modelePersonnage = new Personnage();
        $personnages = $modelePersonnage->listerParUtilisateur($compte['id_utilisateur']);

        $modele = new Utilisateur();
        $modele->supprimer($compte['id_utilisateur']);
        $this->journaliserSuppression($personnages, $compte);

        // Le compte n'existe plus : la session est fermée, retour à l'accueil avec un message
        $auth = new Auth();
        $auth->deconnecter();
        $this->session->ajouterMessage('Votre compte et vos ' . count($personnages) . ' personnage(s) ont été '
            . 'supprimés. Vos avis restent publiés, signés « Compte supprimé ».');
        $this->rediriger('/');
    }

    // Journal MongoDB : un document « suppression » par personnage du compte supprimé,
    // avec le joueur comme auteur (comme quand un employé supprime un compte)
    private function journaliserSuppression(array $personnages, array $compte): void
    {
        $logger = new Logger();
        foreach ($personnages as $personnage) {
            $logger->enregistrer('suppression', $personnage, $compte, [
                'nom' => ['avant' => $personnage['nom'], 'apres' => null],
                'compte' => ['avant' => $compte['pseudo'], 'apres' => null],
            ]);
        }
    }

    // Vérifie le mot de passe actuel. Renvoie le message d'erreur, ou null s'il est correct.
    // Même règle qu'à la connexion : à la 5e erreur de suite, le compte est bloqué 15 minutes et
    // la session est fermée. Un compte déjà bloqué (par exemple par des essais dans une autre
    // session) est refusé sans vérifier le mot de passe, et sa session est fermée aussi.
    private function erreurMotDePasseActuel(string $motDePasse): ?string
    {
        $compte = $this->utilisateurConnecte;

        if ($compte['est_bloque']) {
            $this->fermerSessionCompteBloque();
        }
        // Champ vide : pas compté comme une erreur (comme à la connexion)
        if ($motDePasse === '') {
            return 'Saisissez votre mot de passe actuel.';
        }
        if (!password_verify($motDePasse, $compte['password'])) {
            if ($this->enregistrerEchec($compte)) {
                $this->fermerSessionCompteBloque();
            }
            return 'Mot de passe actuel incorrect. Après 5 erreurs, le compte est bloqué 15 minutes.';
        }

        // Bon mot de passe : le compteur d'erreurs repart de zéro
        if ($compte['tentatives_echouees'] > 0) {
            $modele = new Utilisateur();
            $modele->reinitialiserEchecs($compte['id_utilisateur']);
        }

        return null;
    }

    // Trop d'erreurs : la session est fermée et on revient à la connexion, qui refusera
    // le compte pendant 15 minutes
    private function fermerSessionCompteBloque(): void
    {
        $auth = new Auth();
        $auth->deconnecter();
        $this->session->ajouterMessage('Trop d\'erreurs de mot de passe : le compte est bloqué 15 minutes. '
            . 'Vous pourrez ensuite vous reconnecter.');
        $this->rediriger('/connexion');
    }

    // Affiche la page. Chaque formulaire a ses erreurs : $erreursMotDePasse pour le changement de
    // mot de passe, $erreursSuppression pour la suppression (les mots de passe ne sont jamais réaffichés).
    private function afficherMonCompte(array $erreursMotDePasse, array $erreursSuppression): void
    {
        $this->afficher('mon-compte', 'Mon compte', [
            'erreursMotDePasse' => $erreursMotDePasse,
            'erreursSuppression' => $erreursSuppression,
        ]);
    }
}

<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Models\Utilisateur;
use DateTimeZone;

// Espace administrateur : comptes employés (création, mot de passe, suspension, suppression)
// et journal des modifications des personnages (MongoDB).
// Réservé au rôle admin. Le compte admin, lui, est créé seulement dans seed.sql (sujet) :
// aucune page ne permet d'en créer un, de le suspendre ou de le supprimer.
// Chaque action qui modifie des données est un formulaire POST avec jeton CSRF.
class AdminController extends Controller
{
    // Libellés des actions du journal (une action absente de cette liste est affichée telle quelle)
    private array $libellesActions = [
        'creation' => 'Création',
        'modification' => 'Modification (nom ou apparence)',
        'equipement' => 'Équipement',
        'partage' => 'Partage',
        'arret_partage' => 'Arrêt du partage',
        'duplication' => 'Duplication',
        'suppression' => 'Suppression',
        'validation' => 'Validation du nom',
        'rejet' => 'Rejet du nom',
    ];

    // GET /admin/logs : les 100 dernières modifications des personnages (journal MongoDB)
    public function logs(): void
    {
        $this->exigerRole(['admin']);

        $logger = new Logger();
        $documents = $logger->derniers(100);

        // null : Atlas n'a pas répondu, la page affiche « journal indisponible »
        $lignes = null;
        if ($documents !== null) {
            $lignes = $this->preparerLignes($documents);
        }

        $this->afficher('admin-logs', 'Journal des personnages (admin)', [
            'lignes' => $lignes,
            'environnement' => $logger->environnement(),
        ]);
    }

    // Met chaque document du journal sous la forme affichée : date à l'heure de Paris,
    // libellé de l'action, personnage, auteur et une ligne de texte par changement
    private function preparerLignes(array $documents): array
    {
        $lignes = [];
        foreach ($documents as $document) {
            // Date enregistrée en UTC par MongoDB, affichée à l'heure de Paris
            $date = $document['date']->toDateTime();
            $date->setTimezone(new DateTimeZone('Europe/Paris'));

            $action = $document['action'];
            if (isset($this->libellesActions[$action])) {
                $action = $this->libellesActions[$action];
            }

            $lignes[] = [
                'date' => $date->format('d/m/Y H:i:s'),
                'action' => $action,
                'personnage' => $document['personnage']['nom'] . ' (id ' . $document['personnage']['id'] . ')',
                'auteur' => $document['auteur']['pseudo'],
                'changements' => $this->texteChangements($document['changements']),
            ];
        }

        return $lignes;
    }

    // Une ligne de texte par changement : « champ : avant → après »
    private function texteChangements(array $changements): array
    {
        $textes = [];
        foreach ($changements as $champ => $valeurs) {
            $textes[] = $champ . ' : ' . $this->texteValeur($valeurs['avant']) . ' → ' . $this->texteValeur($valeurs['apres']);
        }

        return $textes;
    }

    // Une valeur du journal en texte : « — » pour rien (null), « oui » / « non » pour un booléen
    private function texteValeur(mixed $valeur): string
    {
        if ($valeur === null) {
            return '—';
        }
        if ($valeur === true) {
            return 'oui';
        }
        if ($valeur === false) {
            return 'non';
        }

        return (string) $valeur;
    }

    // GET /admin/employes : création d'un employé, changement de mot de passe et liste des employés
    public function employes(): void
    {
        $this->exigerRole(['admin']);
        $this->afficherEmployes([], [], '', 0);
    }

    // POST /admin/employe/creer : nouveau compte employé avec le mail et le mot de passe saisis
    public function creerEmploye(): void
    {
        $this->exigerRole(['admin']);

        // Mail en minuscules, comme à l'inscription
        $mail = mb_strtolower($this->lireChamp('mail'));
        $motDePasse = $this->lireMotDePasse('mot_de_passe');
        $confirmation = $this->lireMotDePasse('confirmation');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherEmployes(['general' => 'Formulaire expiré, réessayez.'], [], $mail, 0);
            return;
        }

        $erreurs = $this->validerCreation($mail, $motDePasse, $confirmation);
        if (count($erreurs) > 0) {
            $this->afficherEmployes($erreurs, [], $mail, 0);
            return;
        }

        $pseudo = $this->genererPseudo($mail);
        $modele = new Utilisateur();
        $modele->creerEmploye($pseudo, $mail, password_hash($motDePasse, PASSWORD_DEFAULT));

        $this->retourAvecMessage('/admin/employes', 'Le compte employé « ' . $pseudo . ' » est créé. '
            . 'Donnez-lui son mot de passe : il se connectera avec son adresse mail et devra choisir '
            . 'un nouveau mot de passe à sa première connexion.');
    }

    // POST /admin/employe/mot-de-passe : l'admin choisit un nouveau mot de passe pour un employé
    public function modifierMotDePasse(): void
    {
        $this->exigerRole(['admin']);

        $id = $this->lireId($_POST);
        $motDePasse = $this->lireMotDePasse('mot_de_passe');
        $confirmation = $this->lireMotDePasse('confirmation');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherEmployes([], ['general' => 'Formulaire expiré, réessayez.'], '', $id);
            return;
        }

        // Seulement un compte employé : rien de choisi, l'admin ou un joueur → message d'erreur
        $modele = new Utilisateur();
        $employe = $modele->trouverEmploye($id);
        $erreurs = $this->validerMotDePasse($motDePasse, $confirmation);
        if ($employe === null) {
            $erreurs['employe'] = 'Choisissez un employé dans la liste.';
        }
        if (count($erreurs) > 0) {
            $this->afficherEmployes([], $erreurs, '', $id);
            return;
        }

        $modele->modifierMotDePasseParAdmin($employe['id_utilisateur'], password_hash($motDePasse, PASSWORD_DEFAULT));

        $this->retourAvecMessage('/admin/employes', 'Le mot de passe de « ' . $employe['pseudo'] . ' » est changé : '
            . 'ses sessions ouvertes sont fermées et il devra en choisir un nouveau à sa prochaine connexion.');
    }

    // POST /admin/employe/suspendre : suspend un employé actif, réactive un employé suspendu
    public function basculerSuspension(): void
    {
        $this->exigerRole(['admin']);
        $this->exigerJetonValide('/admin/employes');
        $employe = $this->chargerEmploye($this->lireId($_POST));

        $suspendu = !$employe['suspendu'];
        $modele = new Utilisateur();
        $modele->changerSuspension($employe['id_utilisateur'], $suspendu);

        $message = 'Le compte employé « ' . $employe['pseudo'] . ' » est réactivé.';
        if ($suspendu) {
            $message = 'Le compte employé « ' . $employe['pseudo'] . ' » est suspendu : il ne peut plus se '
                . 'connecter (il est déconnecté s\'il l\'était).';
        }
        $this->retourAvecMessage('/admin/employes', $message);
    }

    // POST /admin/employe/supprimer : suppression définitive du compte employé.
    // Un employé n'a ni personnage ni avis : seule sa ligne de la table utilisateur disparaît.
    // Ses actions restent dans le journal MongoDB, signées de son pseudo.
    public function supprimerEmploye(): void
    {
        $this->exigerRole(['admin']);
        $this->exigerJetonValide('/admin/employes');
        $employe = $this->chargerEmploye($this->lireId($_POST));

        $modele = new Utilisateur();
        $modele->supprimer($employe['id_utilisateur']);

        $this->retourAvecMessage('/admin/employes', 'Le compte employé « ' . $employe['pseudo'] . ' » est supprimé. '
            . 'Ses actions restent dans le journal, signées de son pseudo.');
    }

    // Vérifie le mail et le mot de passe d'un nouvel employé. Renvoie les erreurs rangées par champ.
    private function validerCreation(string $mail, string $motDePasse, string $confirmation): array
    {
        // Erreurs du mot de passe d'abord, puis celle du mail s'il y en a une
        $erreurs = $this->validerMotDePasse($motDePasse, $confirmation);
        $modele = new Utilisateur();

        if (strlen($mail) > 254 || filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
            $erreurs['mail'] = 'Saisissez une adresse mail valide.';
        } elseif ($modele->mailExiste($mail)) {
            $erreurs['mail'] = 'Cette adresse mail est déjà utilisée par un compte.';
        }

        return $erreurs;
    }

    // Vérifie un mot de passe choisi par l'admin (règle de la CNIL) et sa confirmation
    private function validerMotDePasse(string $motDePasse, string $confirmation): array
    {
        $erreurs = [];

        $erreurMotDePasse = $this->erreurMotDePasse($motDePasse);
        if ($erreurMotDePasse !== null) {
            $erreurs['mot_de_passe'] = $erreurMotDePasse;
        } elseif ($motDePasse !== $confirmation) {
            $erreurs['confirmation'] = 'La confirmation ne correspond pas au mot de passe.';
        }

        return $erreurs;
    }

    // Pseudo d'un nouvel employé, tiré de son mail (decisions.md) : la partie avant le @, sans les
    // caractères interdits dans un pseudo, coupée à 18 caractères pour laisser la place à un numéro
    // (20 au maximum). S'il reste moins de 3 caractères : « employe ». Pris : 2, 3... à la suite.
    private function genererPseudo(string $mail): string
    {
        // strrpos : DERNIER @ (le domaine ne peut pas en contenir)
        $debut = substr($mail, 0, strrpos($mail, '@'));
        $base = substr(preg_replace('/[^A-Za-z0-9_-]/', '', $debut), 0, 18);
        if (strlen($base) < 3) {
            $base = 'employe';
        }

        $modele = new Utilisateur();
        $pseudo = $base;
        $numero = 2;
        while ($modele->pseudoExiste($pseudo)) {
            $pseudo = $base . $numero;
            $numero++;
        }

        return $pseudo;
    }

    // Compte visé : seulement un compte employé, sinon page 404 (l'admin et les joueurs compris)
    private function chargerEmploye(int $id): array
    {
        $modele = new Utilisateur();
        $employe = $modele->trouverEmploye($id);
        if ($employe === null) {
            $this->afficherPageIntrouvable();
        }

        return $employe;
    }

    // Affiche la page des employés. Les deux formulaires ont chacun leurs erreurs :
    // $erreursCreation et $mail pour la création, $erreursMotDePasse et $idEmploye pour le mot de passe
    // (les mots de passe ne sont jamais réaffichés).
    private function afficherEmployes(array $erreursCreation, array $erreursMotDePasse, string $mail, int $idEmploye): void
    {
        $modele = new Utilisateur();
        $this->afficher('admin-employes', 'Comptes employés (admin)', [
            'employes' => $modele->listerEmployes(),
            'erreursCreation' => $erreursCreation,
            'erreursMotDePasse' => $erreursMotDePasse,
            'mail' => $mail,
            'idEmploye' => $idEmploye,
        ]);
    }

    // Jeton CSRF invalide : retour à la page avec un message, sans rien modifier
    private function exigerJetonValide(string $retour): void
    {
        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->retourAvecMessage($retour, 'Formulaire expiré, réessayez.');
        }
    }

    // Message à afficher, puis retour à la page (Post/Redirect/Get)
    private function retourAvecMessage(string $adresse, string $message): void
    {
        $this->session->ajouterMessage($message);
        $this->rediriger($adresse);
    }
}

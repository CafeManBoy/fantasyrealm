<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Mailer;
use App\Models\Personnage;

// Espace employé : modération des noms de personnages et des avis, gestion des articles,
// des personnages et des comptes des joueurs. Réservé aux rôles employe et admin
// (l'administrateur peut faire tout ce que fait un employé, d'après le sujet).
// Chaque action qui modifie des données est un formulaire POST avec jeton CSRF.
class EmployeController extends Controller
{
    // Rôles autorisés sur toutes les pages et toutes les actions de ce contrôleur
    private array $roles = ['employe', 'admin'];

    // GET /employe/personnages : les noms en attente, puis tous les personnages
    public function personnages(): void
    {
        $this->exigerRole($this->roles);

        $modele = new Personnage();
        $this->afficher('employe-personnages', 'Personnages (employé)', [
            'enAttente' => $modele->listerEnAttente(),
            'tous' => $modele->listerTous(),
        ]);
    }

    // POST /employe/personnage/valider : le nom est accepté, puis un mail prévient le propriétaire
    public function validerNom(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/personnages');
        $personnage = $this->chargerPersonnage($this->lireId($_POST));
        if ($personnage['autorise']) {
            $this->retourAvecMessage('/employe/personnages', '« ' . $personnage['nom'] . ' » est déjà validé.');
        }

        $modele = new Personnage();
        $modele->valider($personnage['id_personnage']);
        $this->journaliser('validation', $personnage, ['autorise' => ['avant' => false, 'apres' => true]]);

        $texte = 'Bonjour ' . $personnage['pseudo'] . ",\n\n"
            . 'Bonne nouvelle : le nom de votre personnage « ' . $personnage['nom'] . " » a été validé.\n\n"
            . "Vous pouvez maintenant le modifier, l'équiper et le partager dans la galerie depuis votre espace :\n"
            . $_ENV['APP_URL'] . "/espace-joueur\n";
        $envoye = $this->envoyerMail($personnage['mail'], 'Votre personnage « ' . $personnage['nom'] . ' » est validé', $texte);

        $this->retourAvecMessage('/employe/personnages', $this->messageAvecMail('« ' . $personnage['nom'] . ' » est validé.', $envoye));
    }

    // POST /employe/personnage/rejeter : motif obligatoire, mail au propriétaire, personnage supprimé
    // définitivement (sujet). Seulement pour un personnage en attente.
    public function rejeterNom(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/personnages');
        $personnage = $this->chargerPersonnage($this->lireId($_POST));
        if ($personnage['autorise']) {
            $this->retourAvecMessage('/employe/personnages', '« ' . $personnage['nom'] . ' » est déjà validé : '
                . 'son nom ne peut plus être rejeté (vous pouvez supprimer le personnage).');
        }

        // \r\n → \n : un retour à la ligne compte pour 1 caractère, comme dans le navigateur
        $motif = str_replace("\r\n", "\n", $this->lireChamp('motif'));
        $longueur = mb_strlen($motif);
        if ($longueur < 10 || $longueur > 500) {
            $this->retourAvecMessage('/employe/personnages', 'Le motif est obligatoire et doit faire de 10 à 500 caractères '
                . '(il en fait ' . $longueur . ') : « ' . $personnage['nom'] . ' » n\'a pas été rejeté.');
        }

        $modele = new Personnage();
        $modele->supprimer($personnage['id_personnage']);
        $this->journaliser('rejet', $personnage, [
            'nom' => ['avant' => $personnage['nom'], 'apres' => null],
            'motif' => ['avant' => null, 'apres' => $motif],
        ]);

        $texte = 'Bonjour ' . $personnage['pseudo'] . ",\n\n"
            . 'Le nom de votre personnage « ' . $personnage['nom'] . " » n'a pas été accepté, pour la raison suivante :\n\n"
            . $motif . "\n\n"
            . "Ce personnage a donc été supprimé. Vous pouvez en créer un autre, avec un nouveau nom, depuis votre espace :\n"
            . $_ENV['APP_URL'] . "/espace-joueur\n";
        $envoye = $this->envoyerMail($personnage['mail'], 'Le nom de votre personnage n\'a pas été accepté', $texte);

        $this->retourAvecMessage('/employe/personnages', $this->messageAvecMail('« ' . $personnage['nom'] . ' » a été rejeté et supprimé.', $envoye));
    }

    // POST /employe/personnage/supprimer : suppression définitive de n'importe quel personnage (sujet)
    public function supprimerPersonnage(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/personnages');
        $personnage = $this->chargerPersonnage($this->lireId($_POST));

        $modele = new Personnage();
        $modele->supprimer($personnage['id_personnage']);
        $this->journaliser('suppression', $personnage, [
            'nom' => ['avant' => $personnage['nom'], 'apres' => null],
            'autorise' => ['avant' => (bool) $personnage['autorise'], 'apres' => null],
            'partage' => ['avant' => (bool) $personnage['partage'], 'apres' => null],
        ]);

        $this->retourAvecMessage('/employe/personnages', '« ' . $personnage['nom'] . ' » (joueur '
            . $personnage['pseudo'] . ') a été supprimé.');
    }

    // Personnage visé par l'action, avec son propriétaire ; page 404 s'il n'existe pas
    private function chargerPersonnage(int $id): array
    {
        $modele = new Personnage();
        $personnage = $modele->trouverAvecProprietaire($id);
        if ($personnage === null) {
            $this->afficherPageIntrouvable();
        }

        return $personnage;
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

    // Journal MongoDB : l'auteur est l'employé (ou l'admin) connecté
    private function journaliser(string $action, array $personnage, array $changements): void
    {
        $logger = new Logger();
        $logger->enregistrer($action, $personnage, $this->utilisateurConnecte, $changements);
    }

    // Envoie un mail ; renvoie false s'il n'est pas parti (l'action reste faite : decisions.md)
    private function envoyerMail(string $destinataire, string $sujet, string $texte): bool
    {
        $mailer = new Mailer();

        return $mailer->envoyer($destinataire, $sujet, $texte);
    }

    // Ajoute au message ce qu'il en est du mail au joueur
    private function messageAvecMail(string $message, bool $envoye): string
    {
        if ($envoye) {
            return $message . ' Un mail a été envoyé au joueur.';
        }

        return $message . ' Mais le mail n\'a pas pu être envoyé au joueur.';
    }
}

<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Mailer;
use App\Models\Utilisateur;

// Formulaire de contact : la demande est envoyée par mail à l'entreprise.
// Elle n'est pas enregistrée en base (minimisation des données).
class ContactController extends Controller
{
    public function formulaire(): void
    {
        // Compte connecté : son mail et son pseudo sont pré-remplis (sujet)
        $mail = '';
        $pseudo = '';
        if ($this->utilisateurConnecte !== null) {
            $mail = $this->utilisateurConnecte['mail'];
            $pseudo = $this->utilisateurConnecte['pseudo'];
        }

        $this->afficherContact($mail, $pseudo, '', []);
    }

    public function traiter(): void
    {
        // Compte connecté : on prend son mail et son pseudo, pas ceux envoyés par le formulaire
        // (les champs sont en lecture seule, mais une requête peut être fabriquée à la main)
        if ($this->utilisateurConnecte !== null) {
            $mail = $this->utilisateurConnecte['mail'];
            $pseudo = $this->utilisateurConnecte['pseudo'];
        } else {
            $mail = mb_strtolower($this->lireChamp('mail'));
            $pseudo = $this->lireChamp('pseudo');
        }
        // Retours à la ligne Windows (\r\n) remplacés par \n : chaque retour à la ligne compte
        // alors pour 1 caractère, comme dans le navigateur (attribut maxlength)
        $demande = str_replace("\r\n", "\n", $this->lireChamp('demande'));

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherContact($mail, $pseudo, $demande, ['general' => 'Formulaire expiré, réessayez.']);
            return;
        }

        // Compte qui porte ce pseudo (null s'il n'existe pas), suspendus compris : le site leur
        // dit justement d'écrire depuis cette page
        $modele = new Utilisateur();
        $compte = $modele->trouverParPseudo($pseudo);

        $erreurs = $this->validerDemande($mail, $pseudo, $compte, $demande);
        if (count($erreurs) > 0) {
            $this->afficherContact($mail, $pseudo, $demande, $erreurs);
            return;
        }

        if (!$this->envoyerDemande($mail, $compte, $demande)) {
            $this->afficherContact($mail, $pseudo, $demande, [
                'general' => 'Votre demande n\'a pas pu être envoyée. Réessayez dans quelques minutes.',
            ]);
            return;
        }

        // Redirection après l'envoi : actualiser la page n'envoie pas la demande une deuxième fois
        $this->session->ajouterMessage('Votre demande a bien été envoyée : nous vous répondrons par mail.');
        $this->rediriger('/contact');
    }

    // Vérifie les champs du formulaire. Renvoie les erreurs rangées par champ (vide si tout est bon).
    // $compte : le compte qui porte le pseudo saisi, ou null s'il n'existe pas
    private function validerDemande(string $mail, string $pseudo, ?array $compte, string $demande): array
    {
        $erreurs = [];

        if (strlen($mail) > 254 || filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
            $erreurs['mail'] = 'Saisissez une adresse mail valide.';
        }

        if ($pseudo === '') {
            $erreurs['pseudo'] = 'Saisissez le pseudo de votre compte.';
        } elseif ($compte === null) {
            $erreurs['pseudo'] = 'Aucun compte ne porte ce pseudo.';
        }

        // mb_strlen compte les caractères (strlen compterait les octets : 2 pour « é »)
        $longueur = mb_strlen($demande);
        if ($longueur < 10 || $longueur > 2000) {
            $erreurs['demande'] = 'Votre demande doit faire de 10 à 2000 caractères (elle en fait ' . $longueur . ').';
        }

        return $erreurs;
    }

    // Envoie la demande à l'adresse de l'entreprise (MAIL_CONTACT du .env). « Répondre » dans la
    // messagerie de l'équipe écrit directement au demandeur. Renvoie true si le mail est parti.
    private function envoyerDemande(string $mail, array $compte, string $demande): bool
    {
        // Indications pour l'équipe seulement (jamais affichées au demandeur) : n'importe qui peut
        // écrire avec le pseudo d'un autre. Si le mail saisi n'est pas celui du compte, il faut
        // vérifier l'identité de la personne avant d'agir sur ce compte.
        $connecte = 'non';
        if ($this->utilisateurConnecte !== null) {
            $connecte = 'oui';
        }
        $memeMail = 'non';
        if ($mail === mb_strtolower($compte['mail'])) {
            $memeMail = 'oui';
        }

        $texte = "Nouvelle demande envoyée depuis la page Contact du site.\n\n"
            . 'Pseudo : ' . $compte['pseudo'] . "\n"
            . 'Mail : ' . $mail . "\n"
            . 'Expéditeur connecté : ' . $connecte . "\n"
            . 'Mail saisi = mail du compte : ' . $memeMail . "\n"
            . "(si « non » : vérifier l'identité de la personne avant d'agir sur ce compte)\n\n"
            . "Demande :\n" . $demande . "\n";

        $mailer = new Mailer();
        return $mailer->envoyer($_ENV['MAIL_CONTACT'], 'Demande de contact : ' . $compte['pseudo'], $texte, $mail);
    }

    // Affiche le formulaire avec les valeurs à mettre dans les champs et les erreurs éventuelles.
    // estConnecte : le mail et le pseudo sont alors en lecture seule (ce sont ceux du compte).
    private function afficherContact(string $mail, string $pseudo, string $demande, array $erreurs): void
    {
        $this->afficher('contact', 'Contact', [
            'mail' => $mail,
            'pseudo' => $pseudo,
            'demande' => $demande,
            'erreurs' => $erreurs,
            'estConnecte' => $this->utilisateurConnecte !== null,
        ]);
    }
}

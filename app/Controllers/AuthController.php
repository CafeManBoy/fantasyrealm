<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\MotDePasse;
use App\Models\Utilisateur;

// Comptes : inscription, connexion, déconnexion, mot de passe oublié et changement de mot de passe
class AuthController extends Controller
{
    public function formulaireInscription(): void
    {
        if ($this->utilisateurConnecte !== null) {
            $this->rediriger($this->adresseEspace($this->utilisateurConnecte['role']));
        }

        $this->afficherInscription('', '', []);
    }

    public function traiterInscription(): void
    {
        // Mail en minuscules : deux comptes ne peuvent pas différer seulement par les majuscules
        $mail = mb_strtolower($this->lireChamp('mail'));
        $pseudo = $this->lireChamp('pseudo');
        $motDePasse = $this->lireMotDePasse('mot_de_passe');
        $confirmation = $this->lireMotDePasse('confirmation');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherInscription($mail, $pseudo, ['general' => 'Formulaire expiré, réessayez.']);
            return;
        }

        $erreurs = $this->validerInscription($mail, $pseudo, $motDePasse, $confirmation);
        if (count($erreurs) > 0) {
            $this->afficherInscription($mail, $pseudo, $erreurs);
            return;
        }

        $modele = new Utilisateur();
        $id = $modele->creer($pseudo, $mail, password_hash($motDePasse, PASSWORD_DEFAULT));

        // Le nouveau compte est connecté tout de suite
        $auth = new Auth();
        $auth->connecter($modele->trouverParId($id));
        $this->session->ajouterMessage('Bienvenue ' . $pseudo . ', votre compte est créé.');
        $this->rediriger('/espace-joueur');
    }

    public function formulaireConnexion(): void
    {
        // Déjà connecté : on va directement à son espace
        if ($this->utilisateurConnecte !== null) {
            $this->rediriger($this->adresseEspace($this->utilisateurConnecte['role']));
        }

        $this->afficherConnexion('', null);
    }

    public function traiterConnexion(): void
    {
        // Mail en minuscules : c'est ainsi qu'il est enregistré
        $mail = mb_strtolower($this->lireChamp('mail'));
        $motDePasse = $this->lireMotDePasse('mot_de_passe');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherConnexion($mail, 'Formulaire expiré, réessayez.');
            return;
        }

        if ($mail === '' || $motDePasse === '') {
            $this->afficherConnexion($mail, 'Saisissez votre mail et votre mot de passe.');
            return;
        }

        $modele = new Utilisateur();
        $utilisateur = $modele->trouverParMail($mail);
        $erreur = $this->erreurDeConnexion($utilisateur, $motDePasse);
        if ($erreur !== null) {
            $this->afficherConnexion($mail, $erreur);
            return;
        }

        $this->terminerConnexion($utilisateur, $motDePasse);
    }

    // Connexion acceptée : compteur d'échecs remis à 0, hash mis à jour si besoin,
    // ouverture de la session, puis redirection vers l'espace du compte
    private function terminerConnexion(array $utilisateur, string $motDePasse): void
    {
        $modele = new Utilisateur();
        if ($utilisateur['tentatives_echouees'] > 0) {
            $modele->reinitialiserEchecs($utilisateur['id_utilisateur']);
        }

        // Le hash est recalculé si PHP a augmenté son coût par défaut depuis sa création.
        // Le compte est alors relu : la session doit garder l'empreinte du NOUVEAU hash,
        // sinon elle serait fermée dès la page suivante.
        if (password_needs_rehash($utilisateur['password'], PASSWORD_DEFAULT)) {
            $modele->modifierHash($utilisateur['id_utilisateur'], password_hash($motDePasse, PASSWORD_DEFAULT));
            $utilisateur = $modele->trouverParId($utilisateur['id_utilisateur']);
        }

        $auth = new Auth();
        $auth->connecter($utilisateur);

        if ($utilisateur['motdepasse_a_modifier']) {
            $this->rediriger('/changer-mot-de-passe');
        }
        $this->rediriger($this->adresseEspace($utilisateur['role']));
    }

    public function formulaireMotDePasseOublie(): void
    {
        if ($this->utilisateurConnecte !== null) {
            $this->rediriger($this->adresseEspace($this->utilisateurConnecte['role']));
        }

        $this->afficherMotDePasseOublie('', '', null);
    }

    public function traiterMotDePasseOublie(): void
    {
        $mail = mb_strtolower($this->lireChamp('mail'));
        $pseudo = $this->lireChamp('pseudo');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherMotDePasseOublie($mail, $pseudo, 'Formulaire expiré, réessayez.');
            return;
        }

        if ($mail === '' || $pseudo === '') {
            $this->afficherMotDePasseOublie($mail, $pseudo, 'Saisissez le mail et le pseudo de votre compte.');
            return;
        }

        // Rien n'est envoyé si le compte n'existe pas, s'il est suspendu, ou si un mot de passe
        // lui a déjà été envoyé il y a moins de 15 minutes
        $modele = new Utilisateur();
        $compte = $modele->trouverPourMotDePasseOublie($mail, $pseudo);
        if ($compte !== null && !$compte['suspendu'] && !$compte['demande_recente']) {
            $this->envoyerNouveauMotDePasse($compte);
        }

        // Même message dans tous les cas : la page ne révèle pas si le compte existe
        $this->session->ajouterMessage('Si un compte correspond à ce mail et à ce pseudo, un nouveau mot de passe '
            . 'vient de lui être envoyé par mail. Pensez à regarder dans les courriers indésirables.');
        $this->rediriger('/connexion');
    }

    // Génère un nouveau mot de passe et l'envoie par mail. Il n'est enregistré que si le mail
    // est bien parti : sinon, le titulaire du compte perdrait son mot de passe sans recevoir le nouveau.
    private function envoyerNouveauMotDePasse(array $compte): void
    {
        $generateur = new MotDePasse();
        $nouveau = $generateur->generer();

        $texte = 'Bonjour ' . $compte['pseudo'] . ",\n\n"
            . "Vous avez demandé un nouveau mot de passe pour votre compte FantasyRealm Online.\n\n"
            . 'Votre nouveau mot de passe : ' . $nouveau . "\n\n"
            . 'Connectez-vous avec ce mot de passe sur ' . $_ENV['APP_URL'] . "/connexion :\n"
            . "vous devrez ensuite en choisir un nouveau.\n\n"
            . "Si vous n'êtes pas à l'origine de cette demande, connectez-vous quand même avec ce mot de passe\n"
            . "pour en choisir un nouveau, puis prévenez-nous depuis la page Contact.\n";

        $mailer = new Mailer();
        if ($mailer->envoyer($compte['mail'], 'Votre nouveau mot de passe FantasyRealm Online', $texte)) {
            $modele = new Utilisateur();
            $modele->enregistrerMotDePasseProvisoire($compte['id_utilisateur'], password_hash($nouveau, PASSWORD_DEFAULT));
        }
    }

    // Affiche le formulaire « mot de passe oublié » avec les valeurs déjà saisies
    private function afficherMotDePasseOublie(string $mail, string $pseudo, ?string $erreur): void
    {
        $this->afficher('mot-de-passe-oublie', 'Mot de passe oublié', ['mail' => $mail, 'pseudo' => $pseudo, 'erreur' => $erreur]);
    }

    public function formulaireChangementMotDePasse(): void
    {
        $this->verifierChangementDemande();
        $this->afficherChangement([]);
    }

    public function traiterChangementMotDePasse(): void
    {
        $this->verifierChangementDemande();

        $nouveau = $this->lireMotDePasse('mot_de_passe');
        $confirmation = $this->lireMotDePasse('confirmation');

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherChangement(['general' => 'Formulaire expiré, réessayez.']);
            return;
        }

        $erreurs = $this->validerNouveauMotDePasse($nouveau, $confirmation);
        if (count($erreurs) > 0) {
            $this->afficherChangement($erreurs);
            return;
        }

        $modele = new Utilisateur();
        $modele->modifierMotDePasse($this->utilisateurConnecte['id_utilisateur'], password_hash($nouveau, PASSWORD_DEFAULT));

        // Le compte est relu pour que cette session garde l'empreinte du nouveau hash :
        // elle reste ouverte, alors que les autres sessions du compte seront fermées.
        // Nouvel identifiant de session après le changement de mot de passe.
        $compte = $modele->trouverParId($this->utilisateurConnecte['id_utilisateur']);
        $auth = new Auth();
        $auth->connecter($compte);
        $this->session->ajouterMessage('Votre mot de passe a été changé.');
        $this->rediriger($this->adresseEspace($this->utilisateurConnecte['role']));
    }

    // Vérifie le nouveau mot de passe : règles, différent de l'actuel, confirmation identique
    private function validerNouveauMotDePasse(string $nouveau, string $confirmation): array
    {
        $erreurs = [];
        $erreurMotDePasse = $this->erreurMotDePasse($nouveau);

        if ($erreurMotDePasse !== null) {
            $erreurs['mot_de_passe'] = $erreurMotDePasse;
        } elseif (password_verify($nouveau, $this->utilisateurConnecte['password'])) {
            $erreurs['mot_de_passe'] = 'Le nouveau mot de passe doit être différent de l\'actuel.';
        } elseif ($nouveau !== $confirmation) {
            $erreurs['confirmation'] = 'La confirmation ne correspond pas au mot de passe.';
        }

        return $erreurs;
    }

    // La page de changement n'a de sens que pour un compte connecté qui doit changer son mot de passe
    private function verifierChangementDemande(): void
    {
        if ($this->utilisateurConnecte === null) {
            $this->session->ajouterMessage('Connectez-vous pour accéder à cette page.');
            $this->rediriger('/connexion');
        }

        if (!$this->utilisateurConnecte['motdepasse_a_modifier']) {
            $this->rediriger($this->adresseEspace($this->utilisateurConnecte['role']));
        }
    }

    // Affiche le formulaire de changement de mot de passe avec les erreurs
    private function afficherChangement(array $erreurs): void
    {
        $this->afficher('changer-mot-de-passe', 'Changer de mot de passe', ['erreurs' => $erreurs]);
    }

    public function traiterDeconnexion(): void
    {
        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->session->ajouterMessage('Formulaire expiré, réessayez.');
            $this->rediriger('/');
        }

        $auth = new Auth();
        $auth->deconnecter();
        $this->session->ajouterMessage('Vous êtes déconnecté.');
        $this->rediriger('/');
    }

    // Vérifie les champs de l'inscription. Renvoie les erreurs rangées par champ (vide si tout est bon).
    private function validerInscription(string $mail, string $pseudo, string $motDePasse, string $confirmation): array
    {
        $erreurs = [];
        $modele = new Utilisateur();

        if (strlen($mail) > 254 || filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
            $erreurs['mail'] = 'Saisissez une adresse mail valide.';
        } elseif ($modele->mailExiste($mail)) {
            $erreurs['mail'] = 'Cette adresse mail est déjà utilisée par un compte.';
        }

        // \z : vraie fin du texte ($ accepterait encore un retour à la ligne final)
        if (preg_match('/^[A-Za-z0-9_-]{3,20}\z/', $pseudo) !== 1) {
            $erreurs['pseudo'] = 'Le pseudo doit faire de 3 à 20 caractères : lettres sans accent, chiffres, « _ » ou « - ».';
        } elseif ($modele->pseudoExiste($pseudo)) {
            $erreurs['pseudo'] = 'Ce pseudo est déjà pris.';
        }

        $erreurMotDePasse = $this->erreurMotDePasse($motDePasse);
        if ($erreurMotDePasse !== null) {
            $erreurs['mot_de_passe'] = $erreurMotDePasse;
        } elseif ($motDePasse !== $confirmation) {
            $erreurs['confirmation'] = 'La confirmation ne correspond pas au mot de passe.';
        }

        return $erreurs;
    }

    // Renvoie le message d'erreur si le mot de passe ne respecte pas les règles, sinon null
    private function erreurMotDePasse(string $motDePasse): ?string
    {
        $verificateur = new MotDePasse();

        if ($verificateur->estTropLong($motDePasse)) {
            return 'Le mot de passe est trop long : 72 caractères au maximum (moins si vous utilisez des accents).';
        }

        $regles = $verificateur->reglesNonRespectees($motDePasse);
        if (count($regles) > 0) {
            return 'Le mot de passe doit contenir ' . implode(', ', $regles) . '.';
        }

        return null;
    }

    // Affiche le formulaire d'inscription avec les valeurs déjà saisies et les erreurs
    private function afficherInscription(string $mail, string $pseudo, array $erreurs): void
    {
        $this->afficher('inscription', 'Créer un compte', ['mail' => $mail, 'pseudo' => $pseudo, 'erreurs' => $erreurs]);
    }

    // Renvoie le message d'erreur à afficher, ou null si la connexion est acceptée
    private function erreurDeConnexion(?array $utilisateur, string $motDePasse): ?string
    {
        // Même message si le mail est inconnu ou si le mot de passe est faux :
        // on ne révèle pas quels mails ont un compte
        $messageGenerique = 'Mail ou mot de passe incorrect.';

        if ($utilisateur === null) {
            return $messageGenerique;
        }

        // Compte bloqué après 5 échecs : le mot de passe n'est même pas vérifié
        if ($utilisateur['est_bloque']) {
            return 'Trop de tentatives de connexion : réessayez dans 15 minutes.';
        }

        if (!password_verify($motDePasse, $utilisateur['password'])) {
            $this->enregistrerEchec($utilisateur);
            return $messageGenerique;
        }

        // Message précis seulement quand le mot de passe est correct : c'est le titulaire du compte
        if ($utilisateur['suspendu']) {
            return 'Ce compte est suspendu. Pour en savoir plus, écrivez-nous depuis la page Contact.';
        }

        return null;
    }

    // Compte un échec de connexion. Au 5e échec de suite, la connexion au compte est bloquée
    // 15 minutes : deviner un mot de passe en essayant des milliers de combinaisons devient impossible.
    private function enregistrerEchec(array $utilisateur): void
    {
        $modele = new Utilisateur();

        if ($utilisateur['tentatives_echouees'] + 1 >= 5) {
            $modele->bloquer($utilisateur['id_utilisateur']);
        } else {
            $modele->ajouterEchec($utilisateur['id_utilisateur']);
        }
    }

    // Affiche le formulaire de connexion avec le mail déjà saisi et un éventuel message d'erreur
    private function afficherConnexion(string $mail, ?string $erreur): void
    {
        $this->afficher('connexion', 'Connexion', ['mail' => $mail, 'erreur' => $erreur]);
    }
}

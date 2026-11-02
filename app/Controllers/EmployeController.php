<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Core\Mailer;
use App\Models\Article;
use App\Models\Commentaire;
use App\Models\Personnage;
use App\Models\Utilisateur;
use finfo;

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

    // GET /employe/avis : les avis en attente de validation
    public function avis(): void
    {
        $this->exigerRole($this->roles);

        $modele = new Commentaire();
        $this->afficher('employe-avis', 'Avis (employé)', ['enAttente' => $modele->listerEnAttente()]);
    }

    // POST /employe/avis/valider : l'avis est publié, puis un mail prévient le propriétaire
    // du personnage qu'un nouvel avis est disponible (sujet)
    public function validerAvis(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/avis');
        $avis = $this->chargerAvisEnAttente($this->lireId($_POST));

        $modele = new Commentaire();
        $modele->valider($avis['id_commentaire']);

        $texte = 'Bonjour ' . $avis['pseudo'] . ",\n\n"
            . 'Un nouvel avis de ' . $avis['pseudo_auteur'] . ' (note : ' . $avis['note'] . '/5) est disponible sur votre personnage « '
            . $avis['nom_personnage'] . " ».\n\n"
            . "Vous pouvez le lire sur sa page :\n"
            . $_ENV['APP_URL'] . '/personnage?id=' . $avis['id_personnage'] . "\n";
        $envoye = $this->envoyerMail($avis['mail'], 'Nouvel avis sur votre personnage « ' . $avis['nom_personnage'] . ' »', $texte);

        $this->retourAvecMessage('/employe/avis', $this->messageAvecMail('L\'avis de ' . $avis['pseudo_auteur']
            . ' sur « ' . $avis['nom_personnage'] . ' » est publié.', $envoye));
    }

    // POST /employe/avis/refuser : l'avis est supprimé définitivement (decisions.md), sans mail
    public function refuserAvis(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/avis');
        $avis = $this->chargerAvisEnAttente($this->lireId($_POST));

        $modele = new Commentaire();
        $modele->supprimer($avis['id_commentaire']);

        $this->retourAvecMessage('/employe/avis', 'L\'avis de ' . $avis['pseudo_auteur'] . ' sur « '
            . $avis['nom_personnage'] . ' » a été refusé et supprimé.');
    }

    // GET /employe/articles : formulaire d'ajout et liste des articles
    public function articles(): void
    {
        $this->exigerRole($this->roles);
        $this->afficherArticles([], '', '');
    }

    // POST /employe/article/ajouter : nouvel article avec son calque PNG
    public function ajouterArticle(): void
    {
        $this->exigerRole($this->roles);

        // Envoi plus lourd que post_max_size (php.ini) : PHP vide alors $_POST ET $_FILES.
        // Sans ce test, le jeton CSRF manquerait et l'employé lirait « Formulaire expiré ».
        if (count($_POST) === 0 && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
            $this->retourAvecMessage('/employe/articles', 'Fichier trop lourd : l\'image doit faire 2 Mo au maximum.');
        }

        $nom = $this->lireChamp('nom');
        $type = $this->lireChamp('type');
        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherArticles(['general' => 'Formulaire expiré, réessayez.'], $nom, $type);
            return;
        }

        $erreurs = $this->verifierArticle($nom, $type);
        $erreurImage = $this->verifierImage();
        if ($erreurImage !== null) {
            $erreurs['image'] = $erreurImage;
        }
        if (count($erreurs) > 0) {
            $this->afficherArticles($erreurs, $nom, $type);
            return;
        }

        $chemin = $this->enregistrerImage();
        if ($chemin === null) {
            $this->afficherArticles(['image' => 'L\'image n\'a pas pu être enregistrée. Réessayez.'], $nom, $type);
            return;
        }

        $modele = new Article();
        $modele->creer($nom, $type, $chemin);
        $this->retourAvecMessage('/employe/articles', 'L\'article « ' . $nom . ' » a été ajouté.');
    }

    // POST /employe/article/basculer : désactive un article actif, réactive un article désactivé
    public function basculerArticle(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/articles');
        $article = $this->chargerArticle($this->lireId($_POST));

        $actif = !$article['actif'];
        $modele = new Article();
        $modele->changerActif($article['id_article'], $actif);

        $message = 'L\'article « ' . $article['nom'] . ' » est désactivé : il n\'est plus proposé ni affiché.';
        if ($actif) {
            $message = 'L\'article « ' . $article['nom'] . ' » est réactivé.';
        }
        $this->retourAvecMessage('/employe/articles', $message);
    }

    // POST /employe/article/supprimer : l'article est retiré de tous les personnages (decisions.md)
    public function supprimerArticle(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/articles');
        $article = $this->chargerArticle($this->lireId($_POST));

        // Liste des personnages touchés, lue AVANT la suppression (pour le journal)
        $modele = new Article();
        $personnages = $modele->personnagesQuiPortent($article['id_article']);
        $modele->supprimer($article['id_article']);
        $this->effacerImageEnvoyee($article['image']);

        foreach ($personnages as $personnage) {
            $this->journaliser('equipement', $personnage, [$article['type'] => ['avant' => $article['nom'], 'apres' => null]]);
        }

        $this->retourAvecMessage('/employe/articles', 'L\'article « ' . $article['nom'] . ' » a été supprimé (retiré de '
            . count($personnages) . ' personnage(s)).');
    }

    // Nom de 3 à 50 caractères ; type parmi les 4 types (liste blanche)
    private function verifierArticle(string $nom, string $type): array
    {
        $erreurs = [];
        $longueur = mb_strlen($nom);
        if ($longueur < 3 || $longueur > 50) {
            $erreurs['nom'] = 'Le nom doit faire de 3 à 50 caractères.';
        }

        $modele = new Article();
        if (!array_key_exists($type, $modele->types())) {
            $erreurs['type'] = 'Choisissez un type de la liste.';
        }

        return $erreurs;
    }

    // Vérifie le fichier envoyé (règles de CLAUDE.md). Renvoie le message d'erreur, ou null s'il est correct.
    private function verifierImage(): ?string
    {
        if (!isset($_FILES['image']) || !is_array($_FILES['image']) || $_FILES['image']['error'] === UPLOAD_ERR_NO_FILE) {
            return 'Choisissez une image PNG.';
        }
        $fichier = $_FILES['image'];

        // INI_SIZE : plus lourd que upload_max_filesize (php.ini)
        if ($fichier['error'] === UPLOAD_ERR_INI_SIZE || $fichier['error'] === UPLOAD_ERR_FORM_SIZE) {
            return 'Fichier trop lourd : l\'image doit faire 2 Mo au maximum.';
        }
        if ($fichier['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($fichier['tmp_name'])) {
            return 'Le fichier n\'a pas pu être envoyé. Réessayez.';
        }
        if ($fichier['size'] > 2 * 1024 * 1024) {
            return 'Fichier trop lourd : l\'image doit faire 2 Mo au maximum.';
        }

        // finfo lit le contenu du fichier : un fichier renommé en .png n'est pas accepté
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo->file($fichier['tmp_name']) !== 'image/png') {
            return 'Le fichier doit être une image PNG (le contenu est vérifié, pas seulement l\'extension).';
        }

        // getimagesize : [0] largeur, [1] hauteur. Même gabarit que les autres calques.
        $taille = getimagesize($fichier['tmp_name']);
        if ($taille === false || $taille[0] !== 600 || $taille[1] !== 800) {
            return 'L\'image doit faire exactement 600 × 800 pixels, comme les autres calques.';
        }

        return null;
    }

    // Enregistre le fichier sous un nom aléatoire (jamais celui choisi par l'utilisateur).
    // Renvoie son chemin pour le site (/uploads/articles/….png), ou null en cas d'échec.
    private function enregistrerImage(): ?string
    {
        $dossier = __DIR__ . '/../../public/uploads/articles';
        if (!is_dir($dossier) && !mkdir($dossier, 0755, true)) {
            error_log('Dossier des calques impossible à créer');
            return null;
        }

        // 16 octets aléatoires → 32 caractères hexadécimaux : impossible à deviner
        $nomFichier = bin2hex(random_bytes(16)) . '.png';
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dossier . '/' . $nomFichier)) {
            error_log('Calque envoyé impossible à enregistrer');
            return null;
        }

        return '/uploads/articles/' . $nomFichier;
    }

    // Efface le fichier d'un article supprimé, SEULEMENT s'il a été envoyé par un employé :
    // le chemin doit avoir exactement la forme d'un nom généré (jamais les images du jeu de données)
    private function effacerImageEnvoyee(string $chemin): void
    {
        if (preg_match('#^/uploads/articles/[0-9a-f]{32}\.png\z#', $chemin) !== 1) {
            return;
        }
        $fichier = __DIR__ . '/../../public' . $chemin;
        if (is_file($fichier)) {
            unlink($fichier);
        }
    }

    // Article visé par l'action ; page 404 s'il n'existe pas
    private function chargerArticle(int $id): array
    {
        $modele = new Article();
        $article = $modele->trouver($id);
        if ($article === null) {
            $this->afficherPageIntrouvable();
        }

        return $article;
    }

    // $erreurs, $nom, $type : formulaire d'ajout réaffiché après une erreur
    private function afficherArticles(array $erreurs, string $nom, string $type): void
    {
        $modele = new Article();
        $this->afficher('employe-articles', 'Articles (employé)', [
            'articles' => $modele->listerTous(),
            'types' => $modele->types(),
            'erreurs' => $erreurs,
            'nom' => $nom,
            'type' => $type,
        ]);
    }

    // GET /employe/comptes : les comptes des joueurs (rôle utilisateur)
    public function comptes(): void
    {
        $this->exigerRole($this->roles);

        $modele = new Utilisateur();
        $this->afficher('employe-comptes', 'Comptes (employé)', ['joueurs' => $modele->listerJoueurs()]);
    }

    // POST /employe/compte/suspendre : suspend un compte actif, réactive un compte suspendu
    public function basculerSuspension(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/comptes');
        $joueur = $this->chargerJoueur($this->lireId($_POST));

        $suspendu = !$joueur['suspendu'];
        $modele = new Utilisateur();
        $modele->changerSuspension($joueur['id_utilisateur'], $suspendu);

        $message = 'Le compte « ' . $joueur['pseudo'] . ' » est réactivé.';
        if ($suspendu) {
            $message = 'Le compte « ' . $joueur['pseudo'] . ' » est suspendu : il ne peut plus se connecter '
                . '(il est déconnecté s\'il l\'était) et ses personnages n\'apparaissent plus dans la galerie.';
        }
        $this->retourAvecMessage('/employe/comptes', $message);
    }

    // POST /employe/compte/supprimer : suppression définitive du compte et de ses personnages
    // (decisions.md : ses avis restent, signés de son pseudo)
    public function supprimerCompte(): void
    {
        $this->exigerRole($this->roles);
        $this->exigerJetonValide('/employe/comptes');
        $joueur = $this->chargerJoueur($this->lireId($_POST));

        // Personnages lus AVANT la suppression du compte, pour le journal
        $modelePersonnage = new Personnage();
        $personnages = $modelePersonnage->listerParUtilisateur($joueur['id_utilisateur']);

        $modele = new Utilisateur();
        $modele->supprimer($joueur['id_utilisateur']);

        foreach ($personnages as $personnage) {
            $this->journaliser('suppression', $personnage, [
                'nom' => ['avant' => $personnage['nom'], 'apres' => null],
                'compte' => ['avant' => $joueur['pseudo'], 'apres' => null],
            ]);
        }

        $this->retourAvecMessage('/employe/comptes', 'Le compte « ' . $joueur['pseudo'] . ' » et ses '
            . count($personnages) . ' personnage(s) ont été supprimés. Ses avis restent, signés de son pseudo.');
    }

    // Compte visé : seulement un compte de joueur, sinon page 404 (employés et admin compris)
    private function chargerJoueur(int $id): array
    {
        $modele = new Utilisateur();
        $joueur = $modele->trouverJoueur($id);
        if ($joueur === null) {
            $this->afficherPageIntrouvable();
        }

        return $joueur;
    }

    // Avis visé par l'action : 404 s'il n'existe pas ; retour avec un message s'il est déjà publié
    // (seuls les avis en attente se valident ou se refusent)
    private function chargerAvisEnAttente(int $id): array
    {
        $modele = new Commentaire();
        $avis = $modele->trouverAvecProprietaire($id);
        if ($avis === null) {
            $this->afficherPageIntrouvable();
        }
        if ($avis['statut'] !== 'en_attente') {
            $this->retourAvecMessage('/employe/avis', 'Cet avis est déjà publié.');
        }

        return $avis;
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

<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Logger;
use App\Models\Article;
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
        $this->afficherFormulaire($avatar->personnageParDefaut(), [], null);
    }

    public function traiterCreation(): void
    {
        $this->exigerRole(['utilisateur']);
        $personnage = $this->lirePersonnage();

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherFormulaire($personnage, ['general' => 'Formulaire expiré, réessayez.'], null);
            return;
        }

        $erreurs = $this->validerPersonnage($personnage, null);
        if (count($erreurs) > 0) {
            $this->afficherFormulaire($personnage, $erreurs, null);
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

    // GET /personnage/modifier?id=12
    public function formulaireModification(): void
    {
        $this->exigerRole(['utilisateur']);
        $personnage = $this->chargerPersonnageModifiable($this->lireId($_GET));

        $this->afficherFormulaire($personnage, [], $this->equipementActuel($personnage['id_personnage']));
    }

    public function traiterModification(): void
    {
        $this->exigerRole(['utilisateur']);
        $ancien = $this->chargerPersonnageModifiable($this->lireId($_POST));
        $personnage = $this->lirePersonnage();
        $personnage['id_personnage'] = $ancien['id_personnage'];
        $equipement = $this->lireEquipement();

        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->afficherFormulaire($personnage, ['general' => 'Formulaire expiré, réessayez.'], $equipement);
            return;
        }

        // array_merge : les erreurs de l'apparence et celles de l'équipement dans un seul tableau
        $erreurs = array_merge(
            $this->validerPersonnage($personnage, $ancien['id_personnage']),
            $this->validerEquipement($equipement)
        );
        if (count($erreurs) > 0) {
            $this->afficherFormulaire($personnage, $erreurs, $equipement);
            return;
        }

        $changementsApparence = $this->enregistrerApparence($ancien, $personnage);
        $changementsEquipement = $this->enregistrerEquipement($personnage, $equipement);
        $this->ajouterMessageModification($changementsApparence, $changementsEquipement);
        $this->rediriger('/espace-joueur');
    }

    // Bouton « Partager » / « Arrêter le partage » de l'espace joueur : inverse l'état de partage
    public function traiterPartage(): void
    {
        $this->exigerRole(['utilisateur']);
        $this->exigerJetonValide();
        $personnage = $this->chargerPersonnageModifiable($this->lireId($_POST));

        $partage = !$personnage['partage'];
        $modele = new Personnage();
        $modele->changerPartage($personnage['id_personnage'], $partage);

        $action = 'arret_partage';
        $message = '« ' . $personnage['nom'] . ' » n\'est plus visible dans la galerie.';
        if ($partage) {
            $action = 'partage';
            $message = '« ' . $personnage['nom'] . ' » est maintenant visible dans la galerie.';
        }
        $logger = new Logger();
        $logger->enregistrer($action, $personnage, $this->utilisateurConnecte,
            ['partage' => ['avant' => (bool) $personnage['partage'], 'apres' => $partage]]);

        $this->session->ajouterMessage($message);
        $this->rediriger('/espace-joueur');
    }

    // Suppression définitive, possible même pour un personnage en attente (decisions.md)
    public function traiterSuppression(): void
    {
        $this->exigerRole(['utilisateur']);
        $this->exigerJetonValide();
        $personnage = $this->chargerPersonnageDuJoueur($this->lireId($_POST));

        $modele = new Personnage();
        $modele->supprimer($personnage['id_personnage']);

        // Journal : les dernières valeurs du personnage (avant), puis plus rien (après)
        $logger = new Logger();
        $logger->enregistrer('suppression', $personnage, $this->utilisateurConnecte, [
            'nom' => ['avant' => $personnage['nom'], 'apres' => null],
            'autorise' => ['avant' => (bool) $personnage['autorise'], 'apres' => null],
            'partage' => ['avant' => (bool) $personnage['partage'], 'apres' => null],
        ]);

        $this->session->ajouterMessage('« ' . $personnage['nom'] . ' » a été supprimé.');
        $this->rediriger('/espace-joueur');
    }

    // Duplication : même apparence et mêmes articles, nouveau nom obligatoire.
    // Seul un personnage validé peut être dupliqué ; la copie est en attente (decisions.md).
    public function traiterDuplication(): void
    {
        $this->exigerRole(['utilisateur']);
        $this->exigerJetonValide();
        $original = $this->chargerPersonnageModifiable($this->lireId($_POST));

        // Le nouveau nom suit les mêmes règles qu'à la création (format et nom unique)
        $copie = $original;
        $copie['nom'] = $this->lireChamp('nouveau_nom');
        $erreurs = $this->validerPersonnage($copie, null);
        if (count($erreurs) > 0) {
            $message = 'Duplication de « ' . $original['nom'] . ' » impossible : ';
            if (isset($erreurs['nom'])) {
                $message .= $erreurs['nom'];
            } else {
                // Une option de son apparence a été retirée de config/avatar.php
                $message .= 'son apparence contient une option qui n\'existe plus. Modifiez-le d\'abord.';
            }
            $this->session->ajouterMessage($message);
            $this->rediriger('/espace-joueur');
        }

        $modele = new Personnage();
        $copie['id_personnage'] = $modele->dupliquer($original['id_personnage'], $copie['nom']);

        $logger = new Logger();
        $logger->enregistrer('duplication', $copie, $this->utilisateurConnecte, [
            'nom' => ['avant' => null, 'apres' => $copie['nom']],
            'copie_de' => ['avant' => null, 'apres' => $original['nom'] . ' (id ' . $original['id_personnage'] . ')'],
        ]);

        $this->session->ajouterMessage('« ' . $copie['nom'] . ' » a été créé à partir de « ' . $original['nom'] . ' ». '
            . 'Un employé doit valider son nom.');
        $this->rediriger('/espace-joueur');
    }

    // Petits formulaires de l'espace joueur (partage, suppression, duplication) : un jeton CSRF
    // invalide renvoie à l'espace joueur avec un message, sans rien modifier
    private function exigerJetonValide(): void
    {
        $csrf = new Csrf();
        if (!$csrf->jetonEstValide()) {
            $this->session->ajouterMessage('Formulaire expiré, réessayez.');
            $this->rediriger('/espace-joueur');
        }
    }

    // Id du personnage lu dans l'adresse ($_GET) ou dans le formulaire ($_POST) ; 0 s'il est absent
    // ou invalide (aucun personnage n'a l'id 0)
    private function lireId(array $source): int
    {
        if (!isset($source['id']) || !is_string($source['id']) || !ctype_digit($source['id'])) {
            return 0;
        }

        return (int) $source['id'];
    }

    // Personnage du joueur connecté. S'il n'existe pas ou appartient à un autre joueur : page 404.
    private function chargerPersonnageDuJoueur(int $id): array
    {
        $modele = new Personnage();
        $personnage = $modele->trouverPourProprietaire($id, $this->utilisateurConnecte['id_utilisateur']);
        if ($personnage === null) {
            $this->afficherPageIntrouvable();
        }

        return $personnage;
    }

    // Personnage du joueur connecté ET validé. En attente : retour à l'espace joueur avec un message
    // (decisions.md : un personnage en attente peut seulement être supprimé).
    private function chargerPersonnageModifiable(int $id): array
    {
        $personnage = $this->chargerPersonnageDuJoueur($id);
        if (!$personnage['autorise']) {
            $this->session->ajouterMessage('« ' . $personnage['nom'] . ' » attend la validation de son nom : '
                . 'il ne peut pas encore être modifié, équipé, partagé ni dupliqué.');
            $this->rediriger('/espace-joueur');
        }

        return $personnage;
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

    // Choix des 4 listes d'articles : type => id de l'article choisi ('' = « aucun »)
    private function lireEquipement(): array
    {
        $modeleArticle = new Article();
        $equipement = [];
        foreach ($modeleArticle->types() as $type => $libelle) {
            $equipement[$type] = $this->lireChamp('article_' . $type);
        }

        return $equipement;
    }

    // Chaque choix doit être « aucun » ou un article ACTIF de ce type (liste blanche)
    private function validerEquipement(array $equipement): array
    {
        $erreurs = [];
        $modeleArticle = new Article();
        foreach ($modeleArticle->listerActifsParType() as $type => $articles) {
            $choix = $equipement[$type];
            if ($choix !== '' && (!ctype_digit($choix) || !isset($articles[(int) $choix]))) {
                $erreurs['article_' . $type] = 'Choisissez un article de la liste.';
            }
        }

        return $erreurs;
    }

    // Articles actifs portés aujourd'hui : type => id ('' si aucun). Un article désactivé n'y est
    // pas : il n'est proposé nulle part, la liste affiche donc « aucun » pour son type.
    private function equipementActuel(int $idPersonnage): array
    {
        $modele = new Personnage();
        $modeleArticle = new Article();
        $equipement = [];
        foreach ($modeleArticle->types() as $type => $libelle) {
            $equipement[$type] = '';
        }
        foreach ($modele->listerArticlesActifs($idPersonnage) as $article) {
            $equipement[$article['type']] = (string) $article['id_article'];
        }

        return $equipement;
    }

    // Enregistre le nom et l'apparence s'ils ont changé, et l'écrit dans le journal.
    // Renvoie les changements (champ => avant / après), vide si rien n'a changé.
    private function enregistrerApparence(array $ancien, array $personnage): array
    {
        $changements = [];
        foreach ($personnage as $colonne => $valeur) {
            if ($colonne !== 'id_personnage' && $ancien[$colonne] !== $valeur) {
                $changements[$colonne] = ['avant' => $ancien[$colonne], 'apres' => $valeur];
            }
        }
        if (count($changements) === 0) {
            return [];
        }

        $modele = new Personnage();
        $modele->modifierApparence($ancien['id_personnage'], $personnage);

        // Nouveau nom : il doit être validé à nouveau, et le personnage n'est plus partagé (decisions.md)
        if (isset($changements['nom'])) {
            $modele->remettreEnAttente($ancien['id_personnage']);
            $changements['autorise'] = ['avant' => (bool) $ancien['autorise'], 'apres' => false];
            $changements['partage'] = ['avant' => (bool) $ancien['partage'], 'apres' => false];
        }

        $logger = new Logger();
        $logger->enregistrer('modification', $personnage, $this->utilisateurConnecte, $changements);

        return $changements;
    }

    // Enregistre l'équipement type par type, SANS tout supprimer puis tout réinsérer (decisions.md) :
    // - même choix qu'avant : rien ne change (un article désactivé éventuel reste lié) ;
    // - « aucun » : seul l'article actif est retiré (un article désactivé reste lié) ;
    // - un autre article : il remplace tous ceux de ce type, désactivés compris.
    // Renvoie les changements (type => nom de l'article avant / après).
    private function enregistrerEquipement(array $personnage, array $equipement): array
    {
        $modele = new Personnage();
        $modeleArticle = new Article();
        $proposes = $modeleArticle->listerActifsParType();
        $actuel = $this->equipementActuel($personnage['id_personnage']);
        $changements = [];

        foreach ($equipement as $type => $choix) {
            if ($choix === $actuel[$type]) {
                continue;
            }
            $avant = $this->nomArticle($proposes, $type, $actuel[$type]);
            if ($choix === '') {
                $modele->retirerArticle($personnage['id_personnage'], (int) $actuel[$type]);
            } else {
                // Le journal cite aussi un article désactivé ainsi retiré
                $noms = $modele->nomsArticlesDuType($personnage['id_personnage'], $type);
                if (count($noms) > 0) {
                    $avant = implode(', ', $noms);
                }
                $modele->retirerArticlesDuType($personnage['id_personnage'], $type);
                $modele->ajouterArticle($personnage['id_personnage'], (int) $choix);
            }
            $changements[$type] = ['avant' => $avant, 'apres' => $this->nomArticle($proposes, $type, $choix)];
        }

        if (count($changements) > 0) {
            $logger = new Logger();
            $logger->enregistrer('equipement', $personnage, $this->utilisateurConnecte, $changements);
        }

        return $changements;
    }

    // Nom d'un article actif pour le journal (null pour « aucun »)
    private function nomArticle(array $proposes, string $type, string $idArticle): ?string
    {
        if ($idArticle === '') {
            return null;
        }

        return $proposes[$type][(int) $idArticle]['nom'];
    }

    private function ajouterMessageModification(array $changementsApparence, array $changementsEquipement): void
    {
        if (count($changementsApparence) === 0 && count($changementsEquipement) === 0) {
            $this->session->ajouterMessage('Aucune modification : le personnage est resté tel quel.');
            return;
        }

        $message = 'Le personnage a été modifié.';
        if (isset($changementsApparence['nom'])) {
            $message .= ' Son nouveau nom doit être validé par un employé : il est de nouveau en attente '
                . 'et n\'est plus partagé.';
        }
        $this->session->ajouterMessage($message);
    }

    // Affiche le formulaire avec l'aperçu de l'avatar.
    // $equipement : null pour la création ; pour la modification, type => id de l'article choisi ('' = aucun)
    private function afficherFormulaire(array $personnage, array $erreurs, ?array $equipement): void
    {
        $avatar = new Avatar();
        $donnees = [
            'personnage' => $personnage,
            'erreurs' => $erreurs,
            'parties' => $avatar->parties(),
            'couleurs' => $avatar->couleurs(),
            'equipement' => $equipement,
            // true : un calque vide par type d'article non porté, que l'aperçu JavaScript pourra remplir
            'calques' => $avatar->calques($personnage, [], true),
        ];
        $titre = 'Créer un personnage';

        if ($equipement !== null) {
            $modeleArticle = new Article();
            $proposes = $modeleArticle->listerActifsParType();
            $donnees['typesArticles'] = $modeleArticle->types();
            $donnees['articlesProposes'] = $proposes;
            $donnees['calques'] = $avatar->calques($personnage, $this->articlesChoisis($proposes, $equipement), true);
            $titre = 'Modifier un personnage';
        }

        $this->afficher('personnage-formulaire', $titre, $donnees);
    }

    // Articles à montrer dans l'aperçu : ceux choisis dans les listes (un choix invalide est ignoré)
    private function articlesChoisis(array $proposes, array $equipement): array
    {
        $articles = [];
        foreach ($equipement as $type => $choix) {
            if ($choix !== '' && isset($proposes[$type][(int) $choix])) {
                $articles[] = $proposes[$type][(int) $choix];
            }
        }

        return $articles;
    }
}

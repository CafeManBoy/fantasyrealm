-- =====================================================================
-- FantasyRealm Online : création des tables
-- ---------------------------------------------------------------------
-- À importer dans la base fantasyrealm, puis importer seed.sql.
-- ATTENTION : réimporter ce fichier supprime toutes les données.
--
-- Tables tirées du MCD du sujet (noms en français), avec les corrections
-- justifiées dans docs/notes/base-de-donnees.md.
-- Moteur InnoDB : il gère les clés étrangères et les transactions.
-- =====================================================================

-- Encodage utilisé pour lire ce fichier (accents des commentaires et des valeurs)
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Suppression des anciennes tables
-- On commence par les tables qui dépendent des autres : une table visée
-- par une clé étrangère ne peut pas être supprimée avant celles qui la visent.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS commentaire;
DROP TABLE IF EXISTS dispose;
DROP TABLE IF EXISTS personnage;
DROP TABLE IF EXISTS article;
DROP TABLE IF EXISTS utilisateur;
DROP TABLE IF EXISTS role;

-- ---------------------------------------------------------------------
-- role : les trois rôles du site (utilisateur, employe, admin)
-- ---------------------------------------------------------------------
CREATE TABLE role (
    id_role INT NOT NULL AUTO_INCREMENT,
    libelle VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_role),
    CONSTRAINT uq_role_libelle UNIQUE (libelle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- utilisateur : comptes des joueurs, des employés et de l'administrateur
-- ---------------------------------------------------------------------
CREATE TABLE utilisateur (
    id_utilisateur INT NOT NULL AUTO_INCREMENT,
    pseudo VARCHAR(50) NOT NULL,
    -- 255 caractères : une adresse mail peut en compter jusqu'à 254 (50 dans le MCD)
    mail VARCHAR(255) NOT NULL,
    -- Hash du mot de passe (password_hash) : au moins 60 caractères (50 dans le MCD)
    password VARCHAR(255) NOT NULL,
    suspendu BOOLEAN NOT NULL DEFAULT FALSE,
    -- 1 = l'utilisateur doit changer son mot de passe à sa prochaine connexion
    motdepasse_a_modifier BOOLEAN NOT NULL DEFAULT FALSE,
    id_role INT NOT NULL,
    PRIMARY KEY (id_utilisateur),
    -- Deux comptes ne peuvent pas avoir le même mail ni le même pseudo (exigé par le sujet)
    CONSTRAINT uq_utilisateur_mail UNIQUE (mail),
    CONSTRAINT uq_utilisateur_pseudo UNIQUE (pseudo),
    -- RESTRICT : un rôle encore attribué à un compte ne peut pas être supprimé
    CONSTRAINT fk_utilisateur_role FOREIGN KEY (id_role)
        REFERENCES role (id_role) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- personnage : personnages créés par les joueurs
-- Les colonnes forme_* et coupe_cheveux contiennent la CLÉ de l'option choisie
-- (ex. « rond »), définie dans config/avatar.php ; les colonnes couleur_* un code
-- « #RRGGBB ». Pas de colonne image : l'avatar est recomposé à partir des calques.
-- ---------------------------------------------------------------------
CREATE TABLE personnage (
    id_personnage INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    couleur_peau VARCHAR(50) NOT NULL,
    couleur_yeux VARCHAR(50) NOT NULL,
    couleur_cheveux VARCHAR(50) NOT NULL,
    forme_yeux VARCHAR(50) NOT NULL,
    forme_nez VARCHAR(50) NOT NULL,
    forme_bouche VARCHAR(50) NOT NULL,
    -- Ajout au MCD : la coupe de cheveux
    coupe_cheveux VARCHAR(50) NOT NULL,
    -- 1 = visible dans la galerie
    partage BOOLEAN NOT NULL DEFAULT FALSE,
    -- 1 = nom validé par un employé (0 = en attente)
    autorise BOOLEAN NOT NULL DEFAULT FALSE,
    -- Ajout au MCD : nécessaire pour filtrer la galerie par dates
    date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur INT NOT NULL,
    PRIMARY KEY (id_personnage),
    -- Nom unique : règle de la duplication (« doit avoir un nom différent »)
    CONSTRAINT uq_personnage_nom UNIQUE (nom),
    -- CASCADE : supprimer un compte supprime ses personnages
    CONSTRAINT fk_personnage_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- article : vêtements, armures, accessoires et armes proposés par le studio
-- ---------------------------------------------------------------------
CREATE TABLE article (
    id_article INT NOT NULL AUTO_INCREMENT,
    nom VARCHAR(50) NOT NULL,
    -- 0 = désactivé : plus proposé ni affiché, mais gardé en base avec ses liens
    actif BOOLEAN NOT NULL DEFAULT TRUE,
    -- Chemin du calque (ex. /img/avatar/articles/arc-long.svg) plutôt qu'un BLOB :
    -- le fichier est servi directement par le serveur et la base reste légère
    image VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_article),
    -- Les quatre types d'articles cités par le sujet
    CONSTRAINT chk_article_type CHECK (type IN ('vetement', 'armure', 'accessoire', 'arme'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- dispose : articles équipés par chaque personnage (table de jointure)
-- La règle « un seul article par type et par personnage » est vérifiée en PHP.
-- ---------------------------------------------------------------------
CREATE TABLE dispose (
    id_personnage INT NOT NULL,
    id_article INT NOT NULL,
    -- Clé primaire sur les deux colonnes : un article n'est équipé qu'une fois par personnage
    PRIMARY KEY (id_personnage, id_article),
    -- CASCADE : un personnage supprimé perd son équipement ;
    -- un article supprimé est retiré de tous les personnages
    CONSTRAINT fk_dispose_personnage FOREIGN KEY (id_personnage)
        REFERENCES personnage (id_personnage) ON DELETE CASCADE,
    CONSTRAINT fk_dispose_article FOREIGN KEY (id_article)
        REFERENCES article (id_article) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- commentaire : avis déposés sur les personnages (note + commentaire)
-- ---------------------------------------------------------------------
CREATE TABLE commentaire (
    id_commentaire INT NOT NULL AUTO_INCREMENT,
    note INT NOT NULL,
    -- TEXT plutôt que VARCHAR(50) : un avis dépasse vite 50 caractères
    commentaire TEXT NOT NULL,
    date_commentaire DATE NOT NULL,
    -- en_attente : pas encore visible ; valide : publié (un avis refusé est supprimé)
    statut VARCHAR(50) NOT NULL DEFAULT 'en_attente',
    -- Ajout au MCD : le personnage concerné
    id_personnage INT NOT NULL,
    -- Ajout au MCD : l'auteur. Peut devenir NULL si son compte est supprimé
    id_utilisateur INT NULL,
    -- Ajout au MCD : copie du pseudo de l'auteur, conservée après la suppression du compte
    pseudo_auteur VARCHAR(50) NOT NULL,
    PRIMARY KEY (id_commentaire),
    CONSTRAINT chk_commentaire_note CHECK (note BETWEEN 1 AND 5),
    CONSTRAINT chk_commentaire_statut CHECK (statut IN ('en_attente', 'valide')),
    -- Un seul avis par utilisateur et par personnage
    CONSTRAINT uq_commentaire_personnage_auteur UNIQUE (id_personnage, id_utilisateur),
    -- CASCADE : les avis d'un personnage supprimé sont supprimés avec lui
    CONSTRAINT fk_commentaire_personnage FOREIGN KEY (id_personnage)
        REFERENCES personnage (id_personnage) ON DELETE CASCADE,
    -- SET NULL : l'avis reste quand son auteur supprime son compte (pseudo_auteur est gardé)
    CONSTRAINT fk_commentaire_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateur (id_utilisateur) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

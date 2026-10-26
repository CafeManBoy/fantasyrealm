-- =====================================================================
-- FantasyRealm Online : jeu de données de démonstration
-- ---------------------------------------------------------------------
-- À importer juste après schema.sql (les tables doivent être vides).
-- Réimporter schema.sql puis seed.sql remet le site dans son état de démo.
--
-- Comptes fictifs (adresses en @example.com, domaine réservé aux exemples).
-- Les mots de passe sont stockés hachés (bcrypt, password_hash) ; ils figurent
-- en clair dans le manuel d'utilisation (identifiants de démonstration).
-- Les id sont écrits explicitement pour que les liens entre tables soient lisibles.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Rôles
-- ---------------------------------------------------------------------
INSERT INTO role (id_role, libelle) VALUES
    (1, 'utilisateur'),
    (2, 'employe'),
    (3, 'admin');

-- ---------------------------------------------------------------------
-- Comptes : un compte par cas à tester
-- Le compte administrateur n'est créé QUE ici (impossible depuis le site).
-- ---------------------------------------------------------------------
INSERT INTO utilisateur (id_utilisateur, pseudo, mail, password, suspendu, motdepasse_a_modifier, id_role) VALUES
    -- Administrateur
    (1, 'Admin', 'admin@example.com', '$2y$10$qdF9igoEkShCZ/aNIJCVne3B51acL4ZeGM/8RlzzHH42R4IapyUIq', FALSE, FALSE, 3),
    -- Employé
    (2, 'employe', 'employe@example.com', '$2y$10$GldEA/W8F8R6qHJ.bItzJuKP8bgunfxrhm4f3zxIvmrkrC83SA7zC', FALSE, FALSE, 2),
    -- Joueuse avec des personnages dans tous les états
    (3, 'Elandra', 'elandra@example.com', '$2y$10$1CgCE4V3n0.G889SERctAOeFtktCqMyS.9GDkCVKjn5pWzYUDlRJa', FALSE, FALSE, 1),
    -- Joueur avec des personnages partagés (permet de tester le filtre par pseudo)
    (4, 'Korvak', 'korvak@example.com', '$2y$10$a8QiFHFhzOdgFX9.OFrLdeyRbNhQf1RAHxtYFiHKp3b9MDub4rOuW', FALSE, FALSE, 1),
    -- Joueur sans personnage
    (5, 'Novice', 'novice@example.com', '$2y$10$HdEIc7hMnGhlG295U5fMreaaDAwKq.7nlOaQRzZ.qETkoe5aJzbGe', FALSE, FALSE, 1),
    -- Compte suspendu : ne peut pas se connecter, son personnage partagé n'apparaît pas dans la galerie
    (6, 'Banni', 'banni@example.com', '$2y$10$nllZGoaXgfew5ToBjv3Ey.aT1EMAl8I4hm5.mgNtBUfPM.mhGPHPa', TRUE, FALSE, 1),
    -- Compte qui doit changer son mot de passe à la prochaine connexion
    (7, 'Distrait', 'distrait@example.com', '$2y$10$fGx3dq8COpjzV0WOEEcctultTotmNUOsebhrOq7ZLAYb75XXbZY3e', FALSE, TRUE, 1);

-- ---------------------------------------------------------------------
-- Personnages
-- Clés des options (config/avatar.php) :
--   genre : homme, femme, autre
--   forme_yeux : positive_canthal_tilt, negative_canthal_tilt, rond
--   forme_nez : romain, germain, celte
--   forme_bouche : fine, pulpeuse, souriante
--   coupe_cheveux : courte, longue, attachee
-- Galerie (validé + partagé + propriétaire non suspendu) : personnages 1, 2, 3, 4, 7, 8 et 9
-- ---------------------------------------------------------------------
INSERT INTO personnage (id_personnage, nom, genre, couleur_peau, couleur_yeux, couleur_cheveux,
                        forme_yeux, forme_nez, forme_bouche, coupe_cheveux,
                        partage, autorise, date_creation, id_utilisateur) VALUES
    -- Personnages d'Elandra (id 3)
    (1, 'Sylvaine', 'femme', '#f1c27d', '#2e8b57', '#b5651d',
        'positive_canthal_tilt', 'romain', 'fine', 'longue', TRUE, TRUE, '2026-06-03 14:12:00', 3),
    (2, 'Thalos', 'homme', '#8d5524', '#4b3621', '#1c1c1c',
        'rond', 'germain', 'souriante', 'courte', TRUE, TRUE, '2026-07-15 09:30:00', 3),
    (3, 'Nyx', 'autre', '#c68642', '#6a5acd', '#e6e6fa',
        'negative_canthal_tilt', 'celte', 'pulpeuse', 'attachee', TRUE, TRUE, '2026-08-22 18:45:00', 3),
    -- Nom accentué : vérifie l'encodage utf8mb4 de bout en bout
    (4, 'Zéphyr', 'homme', '#ffdbac', '#1e90ff', '#f5deb3',
        'positive_canthal_tilt', 'celte', 'fine', 'courte', TRUE, TRUE, '2026-10-02 11:05:00', 3),
    -- Validé mais non partagé : visible seulement dans l'espace d'Elandra
    (5, 'Isolde', 'femme', '#e0ac69', '#8b4513', '#8b0000',
        'rond', 'romain', 'souriante', 'attachee', FALSE, TRUE, '2026-09-10 16:20:00', 3),
    -- En attente de validation du nom
    (6, 'Morwen', 'femme', '#fce4d6', '#708090', '#2f4f4f',
        'negative_canthal_tilt', 'germain', 'fine', 'longue', FALSE, FALSE, '2026-10-20 10:00:00', 3),

    -- Personnages de Korvak (id 4)
    (7, 'Brann', 'homme', '#a0522d', '#556b2f', '#ff8c00',
        'negative_canthal_tilt', 'germain', 'pulpeuse', 'longue', TRUE, TRUE, '2026-06-28 20:15:00', 4),
    (8, 'Aelys', 'femme', '#fce4d6', '#00ced1', '#ffd700',
        'positive_canthal_tilt', 'celte', 'souriante', 'longue', TRUE, TRUE, '2026-08-05 13:40:00', 4),
    (9, 'Orik', 'autre', '#7d4b2a', '#a52a2a', '#808080',
        'rond', 'germain', 'fine', 'courte', TRUE, TRUE, '2026-09-27 17:55:00', 4),
    -- En attente de validation du nom
    (10, 'Grimbold', 'homme', '#d2a679', '#000080', '#a9a9a9',
        'rond', 'romain', 'pulpeuse', 'courte', FALSE, FALSE, '2026-10-24 08:25:00', 4),

    -- Personnage du compte suspendu Banni (id 6) : validé et partagé, mais absent de la galerie
    (11, 'Vexis', 'homme', '#f5cba7', '#800000', '#000000',
        'negative_canthal_tilt', 'romain', 'fine', 'courte', TRUE, TRUE, '2026-07-01 22:10:00', 6);

-- ---------------------------------------------------------------------
-- Articles : chaque type est représenté, dont un article désactivé
-- Les images sont des calques provisoires (créés à l'étape avatars) ; si un dessin
-- définitif change de nom de fichier, il faut aussi modifier son chemin ici.
-- ---------------------------------------------------------------------
INSERT INTO article (id_article, nom, actif, image, type) VALUES
    (1, 'Tunique de lin', TRUE, '/img/avatar/articles/tunique-de-lin.svg', 'vetement'),
    (2, 'Robe de mage', TRUE, '/img/avatar/articles/robe-de-mage.svg', 'vetement'),
    (3, 'Armure de cuir', TRUE, '/img/avatar/articles/armure-de-cuir.svg', 'armure'),
    (4, 'Cuirasse de fer', TRUE, '/img/avatar/articles/cuirasse-de-fer.svg', 'armure'),
    (5, 'Cape de voyage', TRUE, '/img/avatar/articles/cape-de-voyage.svg', 'accessoire'),
    (6, 'Amulette de lune', TRUE, '/img/avatar/articles/amulette-de-lune.svg', 'accessoire'),
    -- Désactivé : plus proposé ni affiché, mais toujours lié au personnage Aelys
    (7, 'Couronne ancienne', FALSE, '/img/avatar/articles/couronne-ancienne.svg', 'accessoire'),
    (8, 'Épée courte', TRUE, '/img/avatar/articles/epee-courte.svg', 'arme'),
    (9, 'Bâton de chêne', TRUE, '/img/avatar/articles/baton-de-chene.svg', 'arme'),
    (10, 'Arc long', TRUE, '/img/avatar/articles/arc-long.svg', 'arme');

-- ---------------------------------------------------------------------
-- Équipement : seulement des personnages validés, au plus un article par type
-- ---------------------------------------------------------------------
INSERT INTO dispose (id_personnage, id_article) VALUES
    (1, 2), (1, 6), (1, 9),     -- Sylvaine : robe de mage, amulette de lune, bâton de chêne
    (2, 1), (2, 4), (2, 8),     -- Thalos : tunique de lin, cuirasse de fer, épée courte
    (3, 2), (3, 5),             -- Nyx : robe de mage, cape de voyage
    (4, 1), (4, 3), (4, 10),    -- Zéphyr : tunique de lin, armure de cuir, arc long
    (5, 1), (5, 6),             -- Isolde : tunique de lin, amulette de lune
    (7, 4), (7, 5), (7, 8),     -- Brann : cuirasse de fer, cape de voyage, épée courte
    (8, 2), (8, 7), (8, 10),    -- Aelys : robe de mage, couronne ancienne (désactivée), arc long
    (9, 3), (9, 9),             -- Orik : armure de cuir, bâton de chêne
    (11, 4), (11, 8);           -- Vexis : cuirasse de fer, épée courte

-- ---------------------------------------------------------------------
-- Avis : validés et en attente, jamais sur ses propres personnages
-- ---------------------------------------------------------------------
INSERT INTO commentaire (id_commentaire, note, commentaire, date_commentaire, statut,
                         id_personnage, id_utilisateur, pseudo_auteur) VALUES
    (1, 5, 'Magnifique ! Ses yeux verts et sa robe de mage vont parfaitement ensemble.',
        '2026-06-10', 'valide', 1, 4, 'Korvak'),
    (2, 4, 'Très réussie, le bâton de chêne lui donne un vrai air de druidesse.',
        '2026-06-15', 'valide', 1, 5, 'Novice'),
    (3, 4, 'Un guerrier solide, la cuirasse de fer lui va bien.',
        '2026-07-20', 'valide', 2, 4, 'Korvak'),
    (4, 5, 'Trop stylé 🔥 Le roux avec la cape, c''est parfait.',
        '2026-07-02', 'valide', 7, 3, 'Elandra'),
    (5, 3, 'Joli, mais l''arc long cache un peu le visage.',
        '2026-10-21', 'en_attente', 8, 3, 'Elandra'),
    (6, 5, 'Mon personnage préféré de la galerie !',
        '2026-10-22', 'en_attente', 9, 5, 'Novice'),
    -- Auteur dont le compte a été supprimé : id_utilisateur vide, pseudo conservé
    (7, 4, 'Des couleurs originales, bravo.',
        '2026-08-30', 'valide', 3, NULL, 'AncienJoueur'),
    (8, 2, 'Les couleurs ne vont pas très bien ensemble à mon goût.',
        '2026-10-23', 'en_attente', 4, 7, 'Distrait');

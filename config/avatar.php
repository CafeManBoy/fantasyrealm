<?php
// Options de l'avatar : c'est la SEULE liste des options du site.
// Le formulaire, l'aperçu JS et la validation côté serveur sont générés à partir de ce tableau.
//
// Ajouter un dessin : déposer le fichier dans public/img/avatar/<dossier>/, puis ajouter une ligne ici.
// Remplacer un dessin : déposer le nouveau fichier et changer seulement son chemin ici.
//
// - Les clés des parties ('genre', 'forme_yeux'...) sont les noms des colonnes de la table personnage.
// - Les clés des options ('homme', 'romain'...) sont enregistrées en base : sans accent ni espace.
//   Renommer une clé oblige à modifier aussi database/seed.sql.
// - Parties colorables (corps, yeux, cheveux) : deux fichiers par option.
//   'couleur' : la zone à colorer (masque CSS rempli avec la couleur choisie).
//   'trait'   : les contours et détails, affichés tels quels par-dessus.
// - Parties non colorables (nez, bouche) : un seul fichier, 'image'.
// - Toutes les images font 600 x 800 px, fond transparent, dessinées sur le même gabarit.
// - Les articles (vêtements, armes...) ne sont pas ici : ils sont en base (table article).

return [
    // Le corps dépend du genre. Il est coloré avec couleur_peau.
    'genre' => [
        'homme' => [
            'libelle' => 'Homme',
            'couleur' => '/img/avatar/corps/homme-couleur.svg',
            'trait' => '/img/avatar/corps/homme-trait.svg',
        ],
        'femme' => [
            'libelle' => 'Femme',
            'couleur' => '/img/avatar/corps/femme-couleur.svg',
            'trait' => '/img/avatar/corps/femme-trait.svg',
        ],
        'autre' => [
            'libelle' => 'Autre',
            'couleur' => '/img/avatar/corps/autre-couleur.svg',
            'trait' => '/img/avatar/corps/autre-trait.svg',
        ],
    ],

    // Les yeux sont colorés avec couleur_yeux (la zone à colorer est l'iris).
    'forme_yeux' => [
        'positive_canthal_tilt' => [
            'libelle' => 'Positive canthal tilt (en amande, coin extérieur relevé)',
            'couleur' => '/img/avatar/yeux/positive_canthal_tilt-couleur.svg',
            'trait' => '/img/avatar/yeux/positive_canthal_tilt-trait.svg',
        ],
        'negative_canthal_tilt' => [
            'libelle' => 'Negative canthal tilt (en amande, coin extérieur tombant)',
            'couleur' => '/img/avatar/yeux/negative_canthal_tilt-couleur.svg',
            'trait' => '/img/avatar/yeux/negative_canthal_tilt-trait.svg',
        ],
        'rond' => [
            'libelle' => 'Ronds',
            'couleur' => '/img/avatar/yeux/rond-couleur.svg',
            'trait' => '/img/avatar/yeux/rond-trait.svg',
        ],
    ],

    'forme_nez' => [
        'romain' => [
            'libelle' => 'Nez romain',
            'image' => '/img/avatar/nez/romain.svg',
        ],
        'germain' => [
            'libelle' => 'Nez germain',
            'image' => '/img/avatar/nez/germain.svg',
        ],
        'celte' => [
            'libelle' => 'Nez celte',
            'image' => '/img/avatar/nez/celte.svg',
        ],
    ],

    'forme_bouche' => [
        'fine' => [
            'libelle' => 'Bouche fine',
            'image' => '/img/avatar/bouche/fine.svg',
        ],
        'pulpeuse' => [
            'libelle' => 'Bouche pulpeuse',
            'image' => '/img/avatar/bouche/pulpeuse.svg',
        ],
        'souriante' => [
            'libelle' => 'Bouche souriante',
            'image' => '/img/avatar/bouche/souriante.svg',
        ],
    ],

    // Les cheveux sont colorés avec couleur_cheveux.
    'coupe_cheveux' => [
        'courte' => [
            'libelle' => 'Coupe courte',
            'couleur' => '/img/avatar/cheveux/courte-couleur.svg',
            'trait' => '/img/avatar/cheveux/courte-trait.svg',
        ],
        'longue' => [
            'libelle' => 'Cheveux longs',
            'couleur' => '/img/avatar/cheveux/longue-couleur.svg',
            'trait' => '/img/avatar/cheveux/longue-trait.svg',
        ],
        'attachee' => [
            'libelle' => 'Cheveux attachés (chignon)',
            'couleur' => '/img/avatar/cheveux/attachee-couleur.svg',
            'trait' => '/img/avatar/cheveux/attachee-trait.svg',
        ],
    ],
];

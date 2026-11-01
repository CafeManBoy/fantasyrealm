<?php
// Options de l'avatar : c'est la SEULE liste des options du site.
// Le formulaire, l'aperçu JS et la validation côté serveur sont générés à partir de ce tableau.
//
// Ajouter un dessin : déposer le fichier dans public/img/avatar/<dossier>/, puis ajouter une ligne ici.
// Remplacer un dessin : déposer le nouveau fichier et changer seulement son chemin ici.
//
// 'parties' : les choix de forme. Les clés ('genre', 'forme_yeux'...) sont les noms des colonnes de
//   la table personnage. Pour chacune :
//   - 'libelle' : texte affiché au-dessus de la liste dans le formulaire ;
//   - 'calque'  : nom du calque dans l'avatar (attribut data-partie, utilisé par l'aperçu JS) ;
//   - 'options' : les choix possibles. Leurs clés ('homme', 'romain'...) sont enregistrées en base :
//     sans accent ni espace. Renommer une clé oblige à modifier aussi database/seed.sql.
//     Partie colorable (corps, yeux, cheveux) : deux fichiers par option.
//       'couleur' : la zone à colorer (masque CSS rempli avec la couleur choisie) ;
//       'trait'   : les contours et détails, affichés tels quels par-dessus.
//     Partie non colorable (nez, bouche) : un seul fichier, 'image'.
// 'couleurs' : les trois couleurs (colonnes couleur_*), le calque qu'elles colorent et la couleur
//   proposée par défaut dans le formulaire de création.
//
// Toutes les images font 600 x 800 px, fond transparent, dessinées sur le même gabarit.
// Les articles (vêtements, armes...) ne sont pas ici : ils sont en base (table article).

return [
    'parties' => [
        // Le corps dépend du genre
        'genre' => [
            'libelle' => 'Genre',
            'calque' => 'corps',
            'options' => [
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
        ],

        // La zone à colorer des yeux est l'iris
        'forme_yeux' => [
            'libelle' => 'Forme des yeux',
            'calque' => 'yeux',
            'options' => [
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
        ],

        'forme_nez' => [
            'libelle' => 'Forme du nez',
            'calque' => 'nez',
            'options' => [
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
        ],

        'forme_bouche' => [
            'libelle' => 'Forme de la bouche',
            'calque' => 'bouche',
            'options' => [
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
        ],

        'coupe_cheveux' => [
            'libelle' => 'Coupe de cheveux',
            'calque' => 'cheveux',
            'options' => [
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
        ],
    ],

    'couleurs' => [
        'couleur_peau' => [
            'libelle' => 'Couleur de la peau',
            'calque' => 'corps',
            'defaut' => '#f1c27d',
        ],
        'couleur_yeux' => [
            'libelle' => 'Couleur des yeux',
            'calque' => 'yeux',
            'defaut' => '#2e8b57',
        ],
        'couleur_cheveux' => [
            'libelle' => 'Couleur des cheveux',
            'calque' => 'cheveux',
            'defaut' => '#4b3621',
        ],
    ],
];

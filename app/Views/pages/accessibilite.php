<?php
// Déclaration d'accessibilité (RGAA), lien dans le pied de page.
// Aucun taux de conformité : les 106 critères du RGAA n'ont pas été audités un par un.
// L'audit est interne et non exhaustif : la page dit exactement ce qui a été vérifié (ni plus ni
// moins), ce qui ne l'a pas été, et les non-conformités connues. À refaire après un changement
// des couleurs (charte graphique) ou des pages.
?>
<div class="container py-5">
    <div class="texte-legal">
        <h1>Déclaration d'accessibilité</h1>

        <p>
            PixelVerse Studios veut que FantasyRealm Online soit utilisable par tous, y compris au clavier
            seul, avec un lecteur d'écran ou avec un zoom important. Cette déclaration s'applique au site
            <a href="https://fantasyrealm5.alwaysdata.net/">fantasyrealm5.alwaysdata.net</a>.
        </p>

        <h2>État de conformité</h2>
        <p>
            Le site s'appuie sur le référentiel général d'amélioration de l'accessibilité (RGAA). Il n'a pas
            fait l'objet d'un audit complet de ses 106 critères : aucun taux de conformité ne peut donc être
            annoncé, et le site est déclaré <strong>non conforme</strong> au sens du RGAA (c'est le statut
            prévu tant qu'aucun audit complet n'a été réalisé). Il a fait l'objet d'un <strong>audit interne,
            non exhaustif</strong>, réalisé par l'équipe qui l'a développé : ce qui a été vérifié et les
            contenus non accessibles connus sont détaillés ci-dessous.
        </p>

        <h2>Résultats de l'audit interne</h2>
        <p>Points vérifiés sur les pages de la liste plus bas, sans défaut restant :</p>
        <ul>
            <li>
                aucune erreur relevée par l'outil de test automatique (règles des niveaux A et AA des WCAG
                2.0, 2.1 et 2.2, et bonnes pratiques) : langue de la page, titres de page, structure des
                titres et des zones, étiquettes des champs, textes alternatifs, noms des liens et des
                boutons, tableaux, attributs ARIA ;
            </li>
            <li>
                contrastes : les couleurs de texte du site atteignent 4,6:1 au moins (4,5:1 exigé), les
                bordures des champs et le contour du focus 5:1 au moins (3:1 exigé) ;
            </li>
            <li>
                navigation au clavier seul, sur les pages qui ont des formulaires ou des actions : tous les
                liens, boutons et champs sont atteignables dans un ordre logique, avec un contour visible ;
                lien « Aller au contenu » ; ouverture du menu sur petit écran ;
            </li>
            <li>
                messages d'erreur des formulaires : annoncés, et reliés à leur champ ;
            </li>
            <li>
                pas de défilement horizontal à 640 pixels de large (comme un zoom à 200 %, mesuré sur
                6 pages) et à 320 pixels de large (comme un zoom à 400 %, mesuré sur 16 pages), sauf les
                tableaux, qui défilent dans leur cadre ;
            </li>
            <li>
                avatars : chaque avatar est annoncé comme une seule image (« Avatar de » suivi du nom du
                personnage), et la page d'un personnage décrit son apparence et son équipement en texte.
            </li>
        </ul>

        <h2>Contenus non accessibles</h2>
        <p>
            Aucune non-conformité n'est connue à ce jour sur les points vérifiés. Trois défauts trouvés
            pendant l'audit ont été corrigés le 3 novembre 2026 : bordure des champs de formulaire trop
            pâle, contour du focus des champs trop pâle, et pages trop larges à 320 pixels (espace joueur,
            articles de l'espace employé).
        </p>
        <p>Limites connues :</p>
        <ul>
            <li>
                les couleurs d'un personnage (peau, yeux, cheveux) sont indiquées par leur code (par exemple
                #7d4b2a) et non par un nom de couleur ;
            </li>
            <li>
                sur petit écran, le menu s'ouvre grâce au JavaScript : sans JavaScript, ses liens restent
                cachés.
            </li>
        </ul>
        <p>
            Non vérifié lors de cet audit, donc sans garantie : la navigation avec un lecteur d'écran
            (NVDA, VoiceOver), les autres navigateurs que Chromium, l'espacement du texte modifié par
            l'utilisateur, et les 106 critères du RGAA un par un.
        </p>

        <h2>Établissement de cette déclaration</h2>
        <ul>
            <li>
                Déclaration établie le 3 novembre 2026, à la suite de l'audit interne du même jour, et mise
                à jour le 5 novembre 2026 après le changement de charte graphique : l'outil de test
                automatique, les contrastes et les largeurs de page ont été vérifiés à nouveau.
            </li>
            <li>
                Technologies utilisées : HTML5, CSS (Bootstrap 5) et JavaScript. Le JavaScript sert à
                l'aperçu de l'avatar, aux questions de confirmation et à l'ouverture du menu sur petit
                écran ; les formulaires fonctionnent sans lui.
            </li>
            <li>
                Méthode : navigateur Chromium sous Windows ; outil de test automatique axe-core 4.10.2 sur
                39 pages ou états de page (dont les formulaires affichés avec leurs erreurs) ; calcul des
                rapports de contraste de toutes les couleurs de la feuille de style, et vérification à la main
                de ce que l'outil ne peut pas mesurer (listes déroulantes, étoiles des avis, champs en
                erreur) ; navigation au clavier seul (Tab, Maj+Tab, Entrée, Espace, flèches) sur les 17 pages
                qui ont des formulaires ou des actions, le 3 novembre ; contour du focus vérifié à nouveau
                le 5 novembre sur l'espace joueur, l'ordre de navigation n'ayant pas changé ; largeur de la
                page mesurée dans des fenêtres de 640 et de 320 pixels de large ; menu testé en affichage de
                téléphone (375 pixels).
            </li>
            <li>
                Pages vérifiées : accueil ; galerie (avec et sans filtre) ; page d'un personnage, avec le
                formulaire d'avis ; connexion, inscription, mot de passe oublié et contact ; espace joueur ;
                création et modification d'un personnage ; Mon compte ; changement de mot de passe
                obligatoire ; espace employé (personnages, avis, articles, comptes) ; espace administrateur
                (comptes employés, journal des personnages) ; mentions légales, conditions générales de vente,
                politique de confidentialité, cette déclaration ; pages « accès refusé » et « page
                introuvable ».
            </li>
        </ul>

        <h2>Retour d'information et contact</h2>
        <p>
            Si vous n'arrivez pas à utiliser un contenu ou une fonctionnalité du site, écrivez-nous depuis la
            <a href="/contact">page Contact</a> ou à <a href="mailto:contact@example.com">contact@example.com</a> :
            nous vous proposerons une autre façon d'y accéder.
        </p>

        <h2>Voies de recours</h2>
        <p>
            Si vous nous avez signalé un défaut d'accessibilité et que notre réponse ne vous satisfait pas,
            vous pouvez saisir le Défenseur des droits :
        </p>
        <ul class="mb-0">
            <li>
                par le formulaire en ligne :
                <a href="https://formulaire.defenseurdesdroits.fr/">formulaire.defenseurdesdroits.fr</a> ;
            </li>
            <li>
                par courrier, gratuitement et sans timbre : Défenseur des droits, Libre réponse 71120,
                75342 Paris CEDEX 07.
            </li>
        </ul>
    </div>
</div>

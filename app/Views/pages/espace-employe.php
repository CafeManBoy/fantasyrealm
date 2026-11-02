<?php
// Espace employé (rôles employe et admin) : tableau de bord vers les outils de modération.
// $donnees['nomsEnAttente'] : nombre de noms de personnages à valider
// $donnees['avisEnAttente'] : nombre d'avis à valider
?>
<div class="container py-5">
    <h1>Espace employé</h1>
    <p>Bonjour <?= htmlspecialchars($utilisateurConnecte['pseudo']) ?>, bienvenue dans l'espace employé.</p>

    <ul class="row list-unstyled g-3 mt-2">
        <li class="col-sm-6 col-lg-3">
            <div class="carte-avantage h-100">
                <h2 class="h5"><a href="/employe/personnages">Personnages</a></h2>
                <p class="mb-0">
                    <?= $donnees['nomsEnAttente'] ?> nom<?php if ($donnees['nomsEnAttente'] > 1): ?>s<?php endif; ?> à valider.
                    Valider ou rejeter les noms, supprimer un personnage.
                </p>
            </div>
        </li>
        <li class="col-sm-6 col-lg-3">
            <div class="carte-avantage h-100">
                <h2 class="h5"><a href="/employe/avis">Avis</a></h2>
                <p class="mb-0">
                    <?= $donnees['avisEnAttente'] ?> avis à valider.
                    Publier ou refuser les avis des joueurs.
                </p>
            </div>
        </li>
    </ul>
</div>

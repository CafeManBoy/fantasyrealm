<?php
// Espace du joueur (rôle utilisateur) : la liste de ses personnages.
// $donnees['personnages'] : ses personnages, chacun avec les calques de son avatar ('calques')
?>
<div class="container py-5">
    <h1>Mon espace</h1>
    <p>Bonjour <?= htmlspecialchars($utilisateurConnecte['pseudo']) ?>, bienvenue dans votre espace joueur.</p>

    <h2 class="mt-4 mb-3">Mes personnages</h2>
    <p><a href="/personnage/creer" class="btn btn-primary">Créer un personnage</a></p>
    <?php if (count($donnees['personnages']) === 0): ?>
        <p>Vous n'avez pas encore de personnage.</p>
    <?php else: ?>
        <ul class="row list-unstyled g-3">
            <?php foreach ($donnees['personnages'] as $personnage): ?>
                <li class="col-6 col-md-4 col-lg-3">
                    <article class="carte-personnage">
                        <?php
                        $avatarNom = $personnage['nom'];
                        $avatarCalques = $personnage['calques'];
                        require __DIR__ . '/../partials/avatar.php';
                        ?>
                        <h3 class="h5 mt-2 mb-1"><?= htmlspecialchars($personnage['nom']) ?></h3>
                        <p class="mb-0">
                            <?php if (!$personnage['autorise']): ?>
                                <span class="badge badge-attente">En attente de validation</span>
                            <?php elseif ($personnage['partage']): ?>
                                <span class="badge badge-partage">Partagé dans la galerie</span>
                            <?php else: ?>
                                <span class="badge badge-valide">Validé, non partagé</span>
                            <?php endif; ?>
                        </p>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

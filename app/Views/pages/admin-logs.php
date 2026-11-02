<?php
// Espace admin : journal des modifications des personnages (collection MongoDB logs_personnages).
// $donnees['lignes'] : les 100 derniers documents déjà mis en forme par AdminController
//                      (date, action, personnage, auteur, changements), ou null si Atlas ne répond pas
// $donnees['environnement'] : 'dev' ou 'prod' (seuls les documents de cet environnement sont lus)
$lignes = $donnees['lignes'];
?>
<div class="container py-5">
    <p><a href="/espace-admin">← Espace administrateur</a></p>
    <h1>Journal des personnages</h1>
    <p>
        Les 100 dernières modifications des personnages (environnement « <?= htmlspecialchars($donnees['environnement']) ?> »),
        de la plus récente à la plus ancienne : noms, apparence, équipement, partage, validation et suppression.
        Heures de Paris.
    </p>

    <?php if ($lignes === null): ?>
        <div class="alert alert-warning" role="alert">
            Le journal est indisponible pour le moment : la base MongoDB ne répond pas. Réessayez plus tard.
        </div>
    <?php elseif (count($lignes) === 0): ?>
        <p>Aucune modification enregistrée pour le moment.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <caption class="visually-hidden">Dernières modifications des personnages</caption>
                <thead>
                    <tr>
                        <th scope="col">Date</th>
                        <th scope="col">Action</th>
                        <th scope="col">Personnage</th>
                        <th scope="col">Auteur</th>
                        <th scope="col">Changements (avant → après)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lignes as $ligne): ?>
                        <tr>
                            <td class="text-nowrap"><?= htmlspecialchars($ligne['date']) ?></td>
                            <td><?= htmlspecialchars($ligne['action']) ?></td>
                            <td><?= htmlspecialchars($ligne['personnage']) ?></td>
                            <td><?= htmlspecialchars($ligne['auteur']) ?></td>
                            <td>
                                <?php if (count($ligne['changements']) === 0): ?>
                                    —
                                <?php else: ?>
                                    <ul class="list-unstyled mb-0 small">
                                        <?php foreach ($ligne['changements'] as $changement): ?>
                                            <li><?= htmlspecialchars($changement) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

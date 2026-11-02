<?php
// Espace employé : comptes des joueurs (rôle utilisateur) à suspendre, réactiver ou supprimer.
// $donnees['joueurs'] : id, pseudo, suspendu, nombre de personnages. Pas de mail (minimisation).
?>
<div class="container py-5">
    <p><a href="/espace-employe">← Espace employé</a></p>
    <h1>Comptes des joueurs (<?= count($donnees['joueurs']) ?>)</h1>
    <p>Les comptes employé et administrateur ne sont pas listés : ils sont gérés par l'administrateur.</p>

    <div class="table-responsive">
        <table class="table align-middle">
            <caption class="visually-hidden">Comptes des joueurs</caption>
            <thead>
                <tr>
                    <th scope="col">Pseudo</th>
                    <th scope="col">Personnages</th>
                    <th scope="col">État</th>
                    <th scope="col">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($donnees['joueurs'] as $joueur): ?>
                    <?php
                    $id = htmlspecialchars((string) $joueur['id_utilisateur']);
                    $pseudo = htmlspecialchars($joueur['pseudo']);
                    $nombre = (int) $joueur['nombre_personnages'];
                    ?>
                    <tr>
                        <td><?= $pseudo ?></td>
                        <td><?= $nombre ?></td>
                        <td>
                            <?php if ($joueur['suspendu']): ?>
                                <span class="badge badge-attente">Suspendu</span>
                            <?php else: ?>
                                <span class="badge badge-partage">Actif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <form method="post" action="/employe/compte/suspendre">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <?php if ($joueur['suspendu']): ?>Réactiver<?php else: ?>Suspendre<?php endif; ?><span class="visually-hidden"> <?= $pseudo ?></span>
                                    </button>
                                </form>
                                <form method="post" action="/employe/compte/supprimer"
                                      data-confirmation="Supprimer définitivement le compte « <?= $pseudo ?> » et ses <?= $nombre ?> personnage(s) ? Ses avis resteront, signés de son pseudo.">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer<span class="visually-hidden"> le compte <?= $pseudo ?></span></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

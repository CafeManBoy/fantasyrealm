<?php
// Espace employé : noms de personnages à valider ou rejeter, puis tous les personnages.
// $donnees['enAttente'] : personnages en attente (id, nom, date de création, pseudo du joueur)
// $donnees['tous'] : tous les personnages (id, nom, autorise, partage, pseudo du joueur)
// Chaque bouton est un petit formulaire POST avec le jeton CSRF ($jetonCsrf, fourni par le gabarit).
$nombreEnAttente = count($donnees['enAttente']);
?>
<div class="container py-5">
    <p><a href="/espace-employe">← Espace employé</a></p>
    <h1>Personnages</h1>

    <section class="mt-4" aria-labelledby="titre-en-attente">
        <h2 id="titre-en-attente">Noms en attente de validation (<?= $nombreEnAttente ?>)</h2>
        <?php if ($nombreEnAttente === 0): ?>
            <p>Aucun nom à valider.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table align-middle">
                    <caption class="visually-hidden">Noms de personnages en attente de validation</caption>
                    <thead>
                        <tr>
                            <th scope="col">Nom</th>
                            <th scope="col">Joueur</th>
                            <th scope="col">Créé le</th>
                            <th scope="col">Décision</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donnees['enAttente'] as $personnage): ?>
                            <?php
                            $id = htmlspecialchars((string) $personnage['id_personnage']);
                            $nom = htmlspecialchars($personnage['nom']);
                            ?>
                            <tr>
                                <td><?= $nom ?></td>
                                <td><?= htmlspecialchars($personnage['pseudo']) ?></td>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($personnage['date_creation']))) ?></td>
                                <td>
                                    <form method="post" action="/employe/personnage/valider" class="d-inline">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">Valider<span class="visually-hidden"> <?= $nom ?></span></button>
                                    </form>
                                    <!-- Rejet : zone dépliable avec le motif obligatoire -->
                                    <details class="mt-2">
                                        <summary>Rejeter<span class="visually-hidden"> <?= $nom ?></span></summary>
                                        <form method="post" action="/employe/personnage/rejeter" class="mt-2"
                                              data-confirmation="Rejeter « <?= $nom ?> » ? Le personnage sera supprimé définitivement.">
                                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                            <input type="hidden" name="id" value="<?= $id ?>">
                                            <label for="motif-<?= $id ?>" class="form-label small">Motif (envoyé au joueur, 10 à 500 caractères)</label>
                                            <textarea class="form-control form-control-sm mb-2" id="motif-<?= $id ?>" name="motif"
                                                      rows="2" minlength="10" maxlength="500" required></textarea>
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Rejeter et supprimer</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="mt-5" aria-labelledby="titre-tous">
        <h2 id="titre-tous">Tous les personnages (<?= count($donnees['tous']) ?>)</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <caption class="visually-hidden">Tous les personnages, par joueur</caption>
                <thead>
                    <tr>
                        <th scope="col">Joueur</th>
                        <th scope="col">Nom</th>
                        <th scope="col">État</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donnees['tous'] as $personnage): ?>
                        <?php
                        $id = htmlspecialchars((string) $personnage['id_personnage']);
                        $nom = htmlspecialchars($personnage['nom']);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($personnage['pseudo']) ?></td>
                            <td><?= $nom ?></td>
                            <td>
                                <?php if (!$personnage['autorise']): ?>
                                    <span class="badge badge-attente">En attente</span>
                                <?php elseif ($personnage['partage']): ?>
                                    <span class="badge badge-partage">Partagé</span>
                                <?php else: ?>
                                    <span class="badge badge-valide">Validé, non partagé</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="post" action="/employe/personnage/supprimer"
                                      data-confirmation="Supprimer définitivement « <?= $nom ?> » ? Ses avis seront supprimés aussi.">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer<span class="visually-hidden"> <?= $nom ?></span></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php
// Espace employé : ajout d'un article (avec son calque PNG) et liste de tous les articles.
// $donnees['articles'] : tous les articles (id, nom, type, image, actif, nombre_personnages)
// $donnees['types'] : les 4 types (clé => libellé)
// $donnees['erreurs'], $donnees['nom'], $donnees['type'] : formulaire réaffiché après une erreur
$erreurs = $donnees['erreurs'];
?>
<div class="container py-5">
    <p><a href="/espace-employe">← Espace employé</a></p>
    <h1>Articles</h1>

    <section class="mt-4 filtres-galerie" aria-labelledby="titre-ajout">
        <h2 id="titre-ajout" class="h4">Ajouter un article</h2>

        <?php if (isset($erreurs['general'])): ?>
            <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
        <?php elseif (count($erreurs) > 0): ?>
            <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
        <?php endif; ?>

        <!-- enctype multipart/form-data : obligatoire pour envoyer un fichier -->
        <form method="post" action="/employe/article/ajouter" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control<?php if (isset($erreurs['nom'])): ?> is-invalid<?php endif; ?>"
                           id="nom" name="nom" value="<?= htmlspecialchars($donnees['nom']) ?>" minlength="3" maxlength="50" required
                           <?php if (isset($erreurs['nom'])): ?>aria-invalid="true" aria-describedby="erreur-nom"<?php endif; ?>>
                    <?php if (isset($erreurs['nom'])): ?>
                        <div id="erreur-nom" class="invalid-feedback"><?= htmlspecialchars($erreurs['nom']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type</label>
                    <select class="form-select<?php if (isset($erreurs['type'])): ?> is-invalid<?php endif; ?>" id="type" name="type" required
                            <?php if (isset($erreurs['type'])): ?>aria-invalid="true" aria-describedby="erreur-type"<?php endif; ?>>
                        <option value="">Choisir un type</option>
                        <?php foreach ($donnees['types'] as $cle => $libelle): ?>
                            <option value="<?= htmlspecialchars($cle) ?>" <?php if ($cle === $donnees['type']): ?>selected<?php endif; ?>><?= htmlspecialchars($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erreurs['type'])): ?>
                        <div id="erreur-type" class="invalid-feedback"><?= htmlspecialchars($erreurs['type']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col-md-5">
                    <label for="image" class="form-label">Calque (image PNG)</label>
                    <!-- accept : le navigateur propose d'abord les PNG (confort seulement, le serveur vérifie) -->
                    <input type="file" class="form-control<?php if (isset($erreurs['image'])): ?> is-invalid<?php endif; ?>"
                           id="image" name="image" accept="image/png" required
                           aria-describedby="aide-image<?php if (isset($erreurs['image'])): ?> erreur-image<?php endif; ?>"
                           <?php if (isset($erreurs['image'])): ?>aria-invalid="true"<?php endif; ?>>
                    <div id="aide-image" class="form-text">PNG, 600 × 800 pixels, fond transparent, 2 Mo au maximum.</div>
                    <?php if (isset($erreurs['image'])): ?>
                        <div id="erreur-image" class="invalid-feedback"><?= htmlspecialchars($erreurs['image']) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Ajouter l'article</button>
        </form>
    </section>

    <section class="mt-5" aria-labelledby="titre-liste">
        <h2 id="titre-liste" class="h4">Tous les articles (<?= count($donnees['articles']) ?>)</h2>
        <div class="table-responsive">
            <table class="table align-middle">
                <caption class="visually-hidden">Articles, par type</caption>
                <thead>
                    <tr>
                        <th scope="col">Calque</th>
                        <th scope="col">Nom</th>
                        <th scope="col">Type</th>
                        <th scope="col">État</th>
                        <th scope="col">Porté par</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donnees['articles'] as $article): ?>
                        <?php
                        $id = htmlspecialchars((string) $article['id_article']);
                        $nom = htmlspecialchars($article['nom']);
                        $nombre = (int) $article['nombre_personnages'];
                        ?>
                        <tr>
                            <!-- alt vide : le nom de l'article est dans la colonne suivante -->
                            <td><img src="<?= htmlspecialchars($article['image']) ?>" alt="" class="miniature-article"></td>
                            <td><?= $nom ?></td>
                            <td><?= htmlspecialchars($donnees['types'][$article['type']]) ?></td>
                            <td>
                                <?php if ($article['actif']): ?>
                                    <span class="badge badge-partage">Actif</span>
                                <?php else: ?>
                                    <span class="badge badge-valide">Désactivé</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $nombre ?> personnage<?php if ($nombre > 1): ?>s<?php endif; ?></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="post" action="/employe/article/basculer">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <?php if ($article['actif']): ?>Désactiver<?php else: ?>Réactiver<?php endif; ?><span class="visually-hidden"> <?= $nom ?></span>
                                        </button>
                                    </form>
                                    <form method="post" action="/employe/article/supprimer"
                                          data-confirmation="Supprimer définitivement « <?= $nom ?> » ? Il sera retiré de <?= $nombre ?> personnage(s).">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer<span class="visually-hidden"> <?= $nom ?></span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php
// Formulaire de création OU de modification d'un personnage, avec l'aperçu de son avatar.
// Les listes et les couleurs sont générées depuis config/avatar.php (aucune option recopiée ici).
// $donnees['personnage'] : valeurs des champs ; $donnees['erreurs'] : messages par champ ou 'general'
// $donnees['parties'], $donnees['couleurs'] : la configuration des avatars
// $donnees['calques'] : calques de l'aperçu (avec des calques vides pour les articles)
// $donnees['equipement'] : null à la création ; en modification, type => id de l'article choisi
//   ('' = aucun), avec $donnees['typesArticles'] et $donnees['articlesProposes'] (articles actifs par type)
// Les attributs data- donnent les chemins des images au JavaScript de l'aperçu (apercu-avatar.js).
// Champs en erreur : classe is-invalid, aria-invalid et aria-describedby (comme à l'inscription).
$personnage = $donnees['personnage'];
$erreurs = $donnees['erreurs'];
$estModification = $donnees['equipement'] !== null;
?>
<div class="container py-5">
    <?php if ($estModification): ?>
        <h1>Modifier un personnage</h1>
        <p>Changez son apparence et son équipement : l'aperçu change à chaque choix.</p>
    <?php else: ?>
        <h1>Créer un personnage</h1>
        <p>Choisissez son nom et son apparence : l'aperçu change à chaque choix. Un employé validera ensuite son nom.</p>
    <?php endif; ?>

    <?php if (isset($erreurs['general'])): ?>
        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
    <?php elseif (count($erreurs) > 0): ?>
        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Aperçu : à droite sur grand écran, au-dessus du formulaire sur mobile -->
        <div class="col-md-5 col-lg-4 order-md-2">
            <div class="apercu-avatar">
                <h2 class="h5">Aperçu</h2>
                <?php
                $avatarNom = 'votre personnage';
                if ($personnage['nom'] !== '') {
                    $avatarNom = $personnage['nom'];
                }
                $avatarCalques = $donnees['calques'];
                require __DIR__ . '/../partials/avatar.php';
                ?>
            </div>
        </div>

        <div class="col-md-7 col-lg-8 order-md-1">
            <?php if ($estModification): ?>
                <form method="post" action="/personnage/modifier" data-apercu-avatar>
                <!-- L'id est vérifié côté serveur : le personnage doit appartenir au joueur connecté -->
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $personnage['id_personnage']) ?>">
            <?php else: ?>
                <form method="post" action="/personnage/creer" data-apercu-avatar>
            <?php endif; ?>
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                <div class="mb-3">
                    <label for="nom" class="form-label">Nom du personnage</label>
                    <input type="text" class="form-control<?php if (isset($erreurs['nom'])): ?> is-invalid<?php endif; ?>"
                           id="nom" name="nom" value="<?= htmlspecialchars($personnage['nom']) ?>"
                           minlength="3" maxlength="30" autocomplete="off" required
                           aria-describedby="aide-nom<?php if (isset($erreurs['nom'])): ?> erreur-nom<?php endif; ?>"
                           <?php if (isset($erreurs['nom'])): ?>aria-invalid="true"<?php endif; ?>>
                    <div id="aide-nom" class="form-text">
                        3 à 30 caractères : lettres, espaces, apostrophes ou tirets. Le nom doit être unique.
                        <?php if ($estModification): ?>
                            Changer le nom le remet en attente de validation et arrête son partage.
                        <?php endif; ?>
                    </div>
                    <?php if (isset($erreurs['nom'])): ?>
                        <div id="erreur-nom" class="invalid-feedback"><?= htmlspecialchars($erreurs['nom']) ?></div>
                    <?php endif; ?>
                </div>

                <fieldset class="mb-3">
                    <legend class="h5">Corps et visage</legend>
                    <?php foreach ($donnees['parties'] as $colonne => $partie): ?>
                        <div class="mb-3">
                            <label for="<?= htmlspecialchars($colonne) ?>" class="form-label"><?= htmlspecialchars($partie['libelle']) ?></label>
                            <select class="form-select<?php if (isset($erreurs[$colonne])): ?> is-invalid<?php endif; ?>"
                                    id="<?= htmlspecialchars($colonne) ?>" name="<?= htmlspecialchars($colonne) ?>"
                                    data-calque="<?= htmlspecialchars($partie['calque']) ?>"
                                    <?php if (isset($erreurs[$colonne])): ?>aria-invalid="true" aria-describedby="erreur-<?= htmlspecialchars($colonne) ?>"<?php endif; ?>>
                                <?php foreach ($partie['options'] as $cle => $option): ?>
                                    <option value="<?= htmlspecialchars($cle) ?>"
                                        <?php if (isset($option['image'])): ?>
                                            data-image="<?= htmlspecialchars($option['image']) ?>"
                                        <?php else: ?>
                                            data-couleur="<?= htmlspecialchars($option['couleur']) ?>"
                                            data-trait="<?= htmlspecialchars($option['trait']) ?>"
                                        <?php endif; ?>
                                        <?php if ($cle === $personnage[$colonne]): ?>selected<?php endif; ?>><?= htmlspecialchars($option['libelle']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($erreurs[$colonne])): ?>
                                <div id="erreur-<?= htmlspecialchars($colonne) ?>" class="invalid-feedback"><?= htmlspecialchars($erreurs[$colonne]) ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </fieldset>

                <fieldset class="mb-4">
                    <legend class="h5">Couleurs</legend>
                    <div class="row g-3">
                        <?php foreach ($donnees['couleurs'] as $colonne => $couleur): ?>
                            <div class="col-sm-4">
                                <label for="<?= htmlspecialchars($colonne) ?>" class="form-label"><?= htmlspecialchars($couleur['libelle']) ?></label>
                                <input type="color" class="form-control form-control-color<?php if (isset($erreurs[$colonne])): ?> is-invalid<?php endif; ?>"
                                       id="<?= htmlspecialchars($colonne) ?>" name="<?= htmlspecialchars($colonne) ?>"
                                       value="<?= htmlspecialchars($personnage[$colonne]) ?>"
                                       data-calque="<?= htmlspecialchars($couleur['calque']) ?>"
                                       <?php if (isset($erreurs[$colonne])): ?>aria-invalid="true" aria-describedby="erreur-<?= htmlspecialchars($colonne) ?>"<?php endif; ?>>
                                <?php if (isset($erreurs[$colonne])): ?>
                                    <div id="erreur-<?= htmlspecialchars($colonne) ?>" class="invalid-feedback"><?= htmlspecialchars($erreurs[$colonne]) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <?php if ($estModification): ?>
                    <fieldset class="mb-4">
                        <legend class="h5">Équipement</legend>
                        <p class="form-text mt-0">Un seul article par type, parmi ceux proposés par la plateforme.</p>
                        <div class="row g-3">
                            <?php foreach ($donnees['typesArticles'] as $type => $libelle): ?>
                                <?php $champ = 'article_' . $type; ?>
                                <div class="col-sm-6">
                                    <label for="<?= htmlspecialchars($champ) ?>" class="form-label"><?= htmlspecialchars($libelle) ?></label>
                                    <select class="form-select<?php if (isset($erreurs[$champ])): ?> is-invalid<?php endif; ?>"
                                            id="<?= htmlspecialchars($champ) ?>" name="<?= htmlspecialchars($champ) ?>"
                                            data-article="<?= htmlspecialchars($type) ?>"
                                            <?php if (isset($erreurs[$champ])): ?>aria-invalid="true" aria-describedby="erreur-<?= htmlspecialchars($champ) ?>"<?php endif; ?>>
                                        <!-- Pas de data-image : l'aperçu cache le calque de ce type -->
                                        <option value="">Aucun</option>
                                        <?php foreach ($donnees['articlesProposes'][$type] as $idArticle => $article): ?>
                                            <option value="<?= htmlspecialchars((string) $idArticle) ?>"
                                                    data-image="<?= htmlspecialchars($article['image']) ?>"
                                                    <?php if ((string) $idArticle === $donnees['equipement'][$type]): ?>selected<?php endif; ?>><?= htmlspecialchars($article['nom']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (isset($erreurs[$champ])): ?>
                                        <div id="erreur-<?= htmlspecialchars($champ) ?>" class="invalid-feedback"><?= htmlspecialchars($erreurs[$champ]) ?></div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </fieldset>

                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                <?php else: ?>
                    <button type="submit" class="btn btn-primary">Créer le personnage</button>
                <?php endif; ?>
                <a href="/espace-joueur" class="btn btn-outline-primary ms-2">Annuler</a>
            </form>
        </div>
    </div>
</div>

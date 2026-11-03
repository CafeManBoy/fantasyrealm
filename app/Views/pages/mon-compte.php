<?php
// Page « Mon compte » : informations du compte et changement volontaire du mot de passe.
// $donnees['erreurs'] : messages du formulaire de mot de passe, rangés par champ
// (mot_de_passe_actuel, mot_de_passe, confirmation) ou 'general' (formulaire expiré).
// Les mots de passe ne sont jamais réaffichés. Pas de formulaire pour l'admin (decisions.md) :
// le serveur refuse de toute façon sa demande.
$erreurs = $donnees['erreurs'];
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <h1>Mon compte</h1>

            <section class="mt-4" aria-labelledby="titre-informations">
                <h2 id="titre-informations" class="h4">Mes informations</h2>
                <dl>
                    <dt>Pseudo</dt>
                    <dd><?= htmlspecialchars($utilisateurConnecte['pseudo']) ?></dd>
                    <dt>Adresse mail</dt>
                    <dd><?= htmlspecialchars($utilisateurConnecte['mail']) ?></dd>
                </dl>
            </section>

            <section class="mt-4" aria-labelledby="titre-mot-de-passe">
                <h2 id="titre-mot-de-passe" class="h4">Changer mon mot de passe</h2>

                <?php if ($utilisateurConnecte['role'] === 'admin'): ?>
                    <p>
                        Le mot de passe du compte administrateur ne se change pas depuis le site : ce compte de
                        démonstration ne peut pas être recréé depuis le site, son mot de passe est géré dans le
                        jeu de données.
                    </p>
                <?php else: ?>
                    <p>Vos autres sessions ouvertes (sur un autre appareil, par exemple) seront fermées.</p>

                    <?php if (isset($erreurs['general'])): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
                    <?php elseif (count($erreurs) > 0): ?>
                        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
                    <?php endif; ?>

                    <form method="post" action="/mon-compte/mot-de-passe">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                        <div class="mb-3">
                            <label for="mot_de_passe_actuel" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control<?php if (isset($erreurs['mot_de_passe_actuel'])): ?> is-invalid<?php endif; ?>"
                                   id="mot_de_passe_actuel" name="mot_de_passe_actuel" autocomplete="current-password" required
                                   <?php if (isset($erreurs['mot_de_passe_actuel'])): ?>aria-invalid="true" aria-describedby="erreur-mot-de-passe-actuel"<?php endif; ?>>
                            <?php if (isset($erreurs['mot_de_passe_actuel'])): ?>
                                <div id="erreur-mot-de-passe-actuel" class="invalid-feedback"><?= htmlspecialchars($erreurs['mot_de_passe_actuel']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="mot_de_passe" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control<?php if (isset($erreurs['mot_de_passe'])): ?> is-invalid<?php endif; ?>"
                                   id="mot_de_passe" name="mot_de_passe" minlength="12" autocomplete="new-password" required
                                   aria-describedby="aide-mot-de-passe<?php if (isset($erreurs['mot_de_passe'])): ?> erreur-mot-de-passe<?php endif; ?>"
                                   <?php if (isset($erreurs['mot_de_passe'])): ?>aria-invalid="true"<?php endif; ?>>
                            <div id="aide-mot-de-passe" class="form-text">
                                12 caractères au moins, avec une majuscule, une minuscule, un chiffre et un caractère spécial
                                (72 caractères au maximum). Il doit être différent de l'actuel.
                            </div>
                            <?php if (isset($erreurs['mot_de_passe'])): ?>
                                <div id="erreur-mot-de-passe" class="invalid-feedback"><?= htmlspecialchars($erreurs['mot_de_passe']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="confirmation" class="form-label">Confirmation du nouveau mot de passe</label>
                            <input type="password" class="form-control<?php if (isset($erreurs['confirmation'])): ?> is-invalid<?php endif; ?>"
                                   id="confirmation" name="confirmation" autocomplete="new-password" required
                                   <?php if (isset($erreurs['confirmation'])): ?>aria-invalid="true" aria-describedby="erreur-confirmation"<?php endif; ?>>
                            <?php if (isset($erreurs['confirmation'])): ?>
                                <div id="erreur-confirmation" class="invalid-feedback"><?= htmlspecialchars($erreurs['confirmation']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Changer mon mot de passe</button>
                    </form>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

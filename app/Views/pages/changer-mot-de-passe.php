<?php
// Changement de mot de passe obligatoire (après « mot de passe oublié », ou pour un compte
// créé par l'administrateur). $donnees['erreurs'] : messages rangés par champ
// (mot_de_passe, confirmation) ou 'general' (formulaire expiré).
$erreurs = $donnees['erreurs'];
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <h1>Changer de mot de passe</h1>
            <p>Pour protéger votre compte, choisissez un nouveau mot de passe avant de continuer.</p>

            <?php if (isset($erreurs['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
            <?php elseif (count($erreurs) > 0): ?>
                <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
            <?php endif; ?>

            <form method="post" action="/changer-mot-de-passe">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

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
        </div>
    </div>
</div>

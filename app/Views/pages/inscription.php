<?php
// Formulaire d'inscription : mail, pseudo, mot de passe et confirmation.
// $donnees['mail'], $donnees['pseudo'] : valeurs déjà saisies (réaffichées après une erreur)
// $donnees['erreurs'] : messages d'erreur rangés par champ (mail, pseudo, mot_de_passe,
// confirmation) ou 'general' (formulaire expiré). Les mots de passe ne sont jamais réaffichés.
// Pour chaque champ en erreur : classe is-invalid (bordure rouge), aria-invalid (annoncé par les
// lecteurs d'écran) et aria-describedby, qui relie le champ à son message d'erreur.
$erreurs = $donnees['erreurs'];
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <h1>Créer un compte</h1>

            <?php if (isset($erreurs['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
            <?php elseif (count($erreurs) > 0): ?>
                <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
            <?php endif; ?>

            <form method="post" action="/inscription">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                <div class="mb-3">
                    <label for="mail" class="form-label">Adresse mail</label>
                    <input type="email" class="form-control<?php if (isset($erreurs['mail'])): ?> is-invalid<?php endif; ?>"
                           id="mail" name="mail" value="<?= htmlspecialchars($donnees['mail']) ?>"
                           maxlength="254" autocomplete="email" required
                           <?php if (isset($erreurs['mail'])): ?>aria-invalid="true" aria-describedby="erreur-mail"<?php endif; ?>>
                    <?php if (isset($erreurs['mail'])): ?>
                        <div id="erreur-mail" class="invalid-feedback"><?= htmlspecialchars($erreurs['mail']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="pseudo" class="form-label">Pseudo</label>
                    <!-- pattern : même règle que la vérification du serveur (le tiret est échappé,
                         obligatoire avec le drapeau « v » que les navigateurs appliquent à pattern) -->
                    <input type="text" class="form-control<?php if (isset($erreurs['pseudo'])): ?> is-invalid<?php endif; ?>"
                           id="pseudo" name="pseudo" value="<?= htmlspecialchars($donnees['pseudo']) ?>"
                           minlength="3" maxlength="20" pattern="[A-Za-z0-9_\-]{3,20}" autocomplete="username" required
                           aria-describedby="aide-pseudo<?php if (isset($erreurs['pseudo'])): ?> erreur-pseudo<?php endif; ?>"
                           <?php if (isset($erreurs['pseudo'])): ?>aria-invalid="true"<?php endif; ?>>
                    <div id="aide-pseudo" class="form-text">
                        3 à 20 caractères : lettres sans accent, chiffres, « _ » ou « - ». Il sera affiché avec vos personnages et vos avis.
                    </div>
                    <?php if (isset($erreurs['pseudo'])): ?>
                        <div id="erreur-pseudo" class="invalid-feedback"><?= htmlspecialchars($erreurs['pseudo']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control<?php if (isset($erreurs['mot_de_passe'])): ?> is-invalid<?php endif; ?>"
                           id="mot_de_passe" name="mot_de_passe" minlength="12" autocomplete="new-password" required
                           aria-describedby="aide-mot-de-passe<?php if (isset($erreurs['mot_de_passe'])): ?> erreur-mot-de-passe<?php endif; ?>"
                           <?php if (isset($erreurs['mot_de_passe'])): ?>aria-invalid="true"<?php endif; ?>>
                    <div id="aide-mot-de-passe" class="form-text">
                        12 caractères au moins, avec une majuscule, une minuscule, un chiffre et un caractère spécial
                        (72 caractères au maximum).
                    </div>
                    <?php if (isset($erreurs['mot_de_passe'])): ?>
                        <div id="erreur-mot-de-passe" class="invalid-feedback"><?= htmlspecialchars($erreurs['mot_de_passe']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="confirmation" class="form-label">Confirmation du mot de passe</label>
                    <input type="password" class="form-control<?php if (isset($erreurs['confirmation'])): ?> is-invalid<?php endif; ?>"
                           id="confirmation" name="confirmation" autocomplete="new-password" required
                           <?php if (isset($erreurs['confirmation'])): ?>aria-invalid="true" aria-describedby="erreur-confirmation"<?php endif; ?>>
                    <?php if (isset($erreurs['confirmation'])): ?>
                        <div id="erreur-confirmation" class="invalid-feedback"><?= htmlspecialchars($erreurs['confirmation']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Créer mon compte</button>
            </form>

            <!-- Information RGPD : pourquoi ces données sont demandées et où en savoir plus -->
            <p class="form-text mt-4">
                Votre adresse mail, votre pseudo et votre mot de passe servent uniquement à créer et à gérer
                votre compte, et à vous envoyer les mails liés au service (validation de vos personnages,
                nouveau mot de passe...). Votre pseudo est visible des autres joueurs. Pour en savoir plus et
                exercer vos droits, consultez la <a href="/confidentialite">politique de confidentialité</a>.
            </p>

            <p class="mt-3">Déjà un compte ? <a href="/connexion">Se connecter</a></p>
        </div>
    </div>
</div>

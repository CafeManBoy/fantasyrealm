<?php
// Mot de passe oublié : le mail et le pseudo du compte (sujet). Un nouveau mot de passe est
// envoyé par mail ; il devra être changé à la connexion suivante.
// $donnees['mail'], $donnees['pseudo'] : valeurs déjà saisies ; $donnees['erreur'] : message ou null
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <h1>Mot de passe oublié</h1>
            <p>
                Indiquez le mail et le pseudo de votre compte : nous vous enverrons un nouveau mot de passe,
                que vous devrez changer à votre prochaine connexion.
            </p>

            <?php if ($donnees['erreur'] !== null): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($donnees['erreur']) ?></div>
            <?php endif; ?>

            <form method="post" action="/mot-de-passe-oublie">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                <div class="mb-3">
                    <label for="mail" class="form-label">Adresse mail</label>
                    <input type="email" class="form-control" id="mail" name="mail"
                           value="<?= htmlspecialchars($donnees['mail']) ?>" maxlength="254" autocomplete="email" required>
                </div>

                <div class="mb-3">
                    <label for="pseudo" class="form-label">Pseudo</label>
                    <input type="text" class="form-control" id="pseudo" name="pseudo"
                           value="<?= htmlspecialchars($donnees['pseudo']) ?>" maxlength="50" autocomplete="username" required>
                </div>

                <button type="submit" class="btn btn-primary">Recevoir un nouveau mot de passe</button>
            </form>

            <p class="mt-4"><a href="/connexion">Retour à la connexion</a></p>
        </div>
    </div>
</div>

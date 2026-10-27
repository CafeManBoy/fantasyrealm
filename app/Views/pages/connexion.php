<?php
// Formulaire de connexion : mail + mot de passe.
// $donnees['mail'] : mail déjà saisi (réaffiché après une erreur)
// $donnees['erreur'] : message d'erreur, ou null
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <h1>Connexion</h1>

            <?php if ($donnees['erreur'] !== null): ?>
                <!-- role="alert" : le message est lu tout de suite par les lecteurs d'écran -->
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($donnees['erreur']) ?></div>
            <?php endif; ?>

            <form method="post" action="/connexion">
                <!-- Jeton CSRF : prouve que le formulaire vient bien de notre site -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                <div class="mb-3">
                    <label for="mail" class="form-label">Adresse mail</label>
                    <input type="email" class="form-control" id="mail" name="mail"
                           value="<?= htmlspecialchars($donnees['mail']) ?>" autocomplete="email" required>
                </div>

                <div class="mb-3">
                    <label for="mot_de_passe" class="form-label">Mot de passe</label>
                    <input type="password" class="form-control" id="mot_de_passe" name="mot_de_passe"
                           autocomplete="current-password" required>
                </div>

                <button type="submit" class="btn btn-primary">Se connecter</button>
            </form>

            <p class="mt-4"><a href="/mot-de-passe-oublie">Mot de passe oublié ?</a></p>
            <p>Pas encore de compte ? <a href="/inscription">Créer un compte</a></p>
        </div>
    </div>
</div>

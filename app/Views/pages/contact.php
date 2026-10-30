<?php
// Formulaire de contact (sujet) : mail, pseudo et détail de la demande, envoyés par mail à l'entreprise.
// $donnees['mail'], $donnees['pseudo'], $donnees['demande'] : valeurs à afficher dans les champs
// $donnees['erreurs'] : messages d'erreur rangés par champ (mail, pseudo, demande) ou 'general'
// $donnees['estConnecte'] : true si un compte est connecté. Son mail et son pseudo sont alors
// pré-remplis en lecture seule (readonly) : le serveur utilise de toute façon ceux du compte.
// Champs en erreur : classe is-invalid, aria-invalid et aria-describedby (comme à l'inscription).
$erreurs = $donnees['erreurs'];
$estConnecte = $donnees['estConnecte'];
?>
<div class="container py-5">
    <div class="row">
        <div class="col-md-8 col-lg-6">
            <h1>Nous contacter</h1>
            <p>
                Une question sur le jeu, un problème avec votre compte ou vos personnages ?
                Écrivez-nous : nous vous répondrons par mail.
            </p>

            <?php if (isset($erreurs['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
            <?php elseif (count($erreurs) > 0): ?>
                <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
            <?php endif; ?>

            <form method="post" action="/contact">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                <?php if ($estConnecte): ?>
                    <p class="form-text">Vous êtes connecté : le mail et le pseudo de votre compte sont utilisés.</p>
                <?php endif; ?>

                <div class="mb-3">
                    <label for="mail" class="form-label">Adresse mail</label>
                    <input type="email" class="form-control<?php if (isset($erreurs['mail'])): ?> is-invalid<?php endif; ?>"
                           id="mail" name="mail" value="<?= htmlspecialchars($donnees['mail']) ?>"
                           maxlength="254" autocomplete="email" required<?php if ($estConnecte): ?> readonly<?php endif; ?>
                           <?php if (isset($erreurs['mail'])): ?>aria-invalid="true" aria-describedby="erreur-mail"<?php endif; ?>>
                    <?php if (isset($erreurs['mail'])): ?>
                        <div id="erreur-mail" class="invalid-feedback"><?= htmlspecialchars($erreurs['mail']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="pseudo" class="form-label">Pseudo</label>
                    <input type="text" class="form-control<?php if (isset($erreurs['pseudo'])): ?> is-invalid<?php endif; ?>"
                           id="pseudo" name="pseudo" value="<?= htmlspecialchars($donnees['pseudo']) ?>"
                           maxlength="50" autocomplete="username" required<?php if ($estConnecte): ?> readonly<?php endif; ?>
                           aria-describedby="aide-pseudo<?php if (isset($erreurs['pseudo'])): ?> erreur-pseudo<?php endif; ?>"
                           <?php if (isset($erreurs['pseudo'])): ?>aria-invalid="true"<?php endif; ?>>
                    <div id="aide-pseudo" class="form-text">Le pseudo de votre compte FantasyRealm Online.</div>
                    <?php if (isset($erreurs['pseudo'])): ?>
                        <div id="erreur-pseudo" class="invalid-feedback"><?= htmlspecialchars($erreurs['pseudo']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="demande" class="form-label">Détail de votre demande</label>
                    <!-- Le contenu de la zone de texte est collé aux balises : sinon les espaces et retours
                         à la ligne du code seraient ajoutés au texte de l'utilisateur -->
                    <textarea class="form-control<?php if (isset($erreurs['demande'])): ?> is-invalid<?php endif; ?>"
                              id="demande" name="demande" rows="8" minlength="10" maxlength="2000" required
                              aria-describedby="aide-demande<?php if (isset($erreurs['demande'])): ?> erreur-demande<?php endif; ?>"
                              <?php if (isset($erreurs['demande'])): ?>aria-invalid="true"<?php endif; ?>><?= htmlspecialchars($donnees['demande']) ?></textarea>
                    <div id="aide-demande" class="form-text">10 à 2000 caractères.</div>
                    <?php if (isset($erreurs['demande'])): ?>
                        <div id="erreur-demande" class="invalid-feedback"><?= htmlspecialchars($erreurs['demande']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Envoyer ma demande</button>
            </form>

            <!-- Information RGPD : ce que deviennent les données du formulaire -->
            <p class="form-text mt-4">
                Votre adresse mail, votre pseudo et votre demande sont transmis par mail à notre équipe,
                uniquement pour vous répondre. Ils ne sont pas enregistrés sur le site. Pour en savoir plus et
                exercer vos droits, consultez la <a href="/confidentialite">politique de confidentialité</a>.
            </p>
        </div>
    </div>
</div>

<?php
// Page « Mon compte » : informations du compte, changement volontaire du mot de passe et
// suppression du compte.
// $donnees['erreursMotDePasse'] : erreurs du formulaire de mot de passe, rangées par champ
// (mot_de_passe_actuel, mot_de_passe, confirmation) ou 'general' (formulaire expiré).
// $donnees['erreursSuppression'] : erreurs du formulaire de suppression (mot_de_passe_actuel ou 'general').
// Les mots de passe ne sont jamais réaffichés. Les formulaires ne sont affichés qu'aux rôles qui ont
// le droit de les envoyer (decisions.md) : le serveur refuse de toute façon les autres (403).
// Les identifiants du formulaire de suppression commencent par « suppression- » : deux champs d'une
// même page ne peuvent pas avoir le même id.
$erreursMotDePasse = $donnees['erreursMotDePasse'];
$erreursSuppression = $donnees['erreursSuppression'];
$role = $utilisateurConnecte['role'];
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

                <?php if ($role === 'admin'): ?>
                    <p>
                        Le mot de passe du compte administrateur ne se change pas depuis le site : ce compte de
                        démonstration ne peut pas être recréé depuis le site, son mot de passe est géré dans le
                        jeu de données.
                    </p>
                <?php else: ?>
                    <p>Vos autres sessions ouvertes (sur un autre appareil, par exemple) seront fermées.</p>

                    <?php if (isset($erreursMotDePasse['general'])): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreursMotDePasse['general']) ?></div>
                    <?php elseif (count($erreursMotDePasse) > 0): ?>
                        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
                    <?php endif; ?>

                    <form method="post" action="/mon-compte/mot-de-passe">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                        <div class="mb-3">
                            <label for="mot_de_passe_actuel" class="form-label">Mot de passe actuel</label>
                            <input type="password" class="form-control<?php if (isset($erreursMotDePasse['mot_de_passe_actuel'])): ?> is-invalid<?php endif; ?>"
                                   id="mot_de_passe_actuel" name="mot_de_passe_actuel" autocomplete="current-password" required
                                   <?php if (isset($erreursMotDePasse['mot_de_passe_actuel'])): ?>aria-invalid="true" aria-describedby="erreur-mot-de-passe-actuel"<?php endif; ?>>
                            <?php if (isset($erreursMotDePasse['mot_de_passe_actuel'])): ?>
                                <div id="erreur-mot-de-passe-actuel" class="invalid-feedback"><?= htmlspecialchars($erreursMotDePasse['mot_de_passe_actuel']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="mot_de_passe" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control<?php if (isset($erreursMotDePasse['mot_de_passe'])): ?> is-invalid<?php endif; ?>"
                                   id="mot_de_passe" name="mot_de_passe" minlength="12" autocomplete="new-password" required
                                   aria-describedby="aide-mot-de-passe<?php if (isset($erreursMotDePasse['mot_de_passe'])): ?> erreur-mot-de-passe<?php endif; ?>"
                                   <?php if (isset($erreursMotDePasse['mot_de_passe'])): ?>aria-invalid="true"<?php endif; ?>>
                            <div id="aide-mot-de-passe" class="form-text">
                                12 caractères au moins, avec une majuscule, une minuscule, un chiffre et un caractère spécial
                                (72 caractères au maximum). Il doit être différent de l'actuel.
                            </div>
                            <?php if (isset($erreursMotDePasse['mot_de_passe'])): ?>
                                <div id="erreur-mot-de-passe" class="invalid-feedback"><?= htmlspecialchars($erreursMotDePasse['mot_de_passe']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="confirmation" class="form-label">Confirmation du nouveau mot de passe</label>
                            <input type="password" class="form-control<?php if (isset($erreursMotDePasse['confirmation'])): ?> is-invalid<?php endif; ?>"
                                   id="confirmation" name="confirmation" autocomplete="new-password" required
                                   <?php if (isset($erreursMotDePasse['confirmation'])): ?>aria-invalid="true" aria-describedby="erreur-confirmation"<?php endif; ?>>
                            <?php if (isset($erreursMotDePasse['confirmation'])): ?>
                                <div id="erreur-confirmation" class="invalid-feedback"><?= htmlspecialchars($erreursMotDePasse['confirmation']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Changer mon mot de passe</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="mt-5" aria-labelledby="titre-suppression">
                <h2 id="titre-suppression" class="h4">Supprimer mon compte</h2>

                <?php if ($role === 'utilisateur'): ?>
                    <p>La suppression est définitive. Elle efface :</p>
                    <ul>
                        <li>votre compte : pseudo, adresse mail et mot de passe ;</li>
                        <li>vos personnages, avec leur équipement et les avis qu'ils ont reçus.</li>
                    </ul>
                    <p>
                        Les avis que vous avez écrits restent publiés, signés « Compte supprimé ». Le journal des
                        modifications des personnages garde votre pseudo jusqu'à 1 an (voir la
                        <a href="/confidentialite">politique de confidentialité</a>).
                    </p>

                    <?php if (isset($erreursSuppression['general'])): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreursSuppression['general']) ?></div>
                    <?php elseif (count($erreursSuppression) > 0): ?>
                        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
                    <?php endif; ?>

                    <!-- data-confirmation : question posée par confirmation.js avant l'envoi (confort seulement) -->
                    <form method="post" action="/mon-compte/supprimer"
                          data-confirmation="Supprimer définitivement votre compte et vos personnages ? Cette action ne peut pas être annulée.">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                        <div class="mb-3">
                            <label for="suppression-mot-de-passe" class="form-label">Mot de passe actuel (pour confirmer)</label>
                            <input type="password" class="form-control<?php if (isset($erreursSuppression['mot_de_passe_actuel'])): ?> is-invalid<?php endif; ?>"
                                   id="suppression-mot-de-passe" name="mot_de_passe_actuel" autocomplete="current-password" required
                                   <?php if (isset($erreursSuppression['mot_de_passe_actuel'])): ?>aria-invalid="true" aria-describedby="erreur-suppression-mot-de-passe"<?php endif; ?>>
                            <?php if (isset($erreursSuppression['mot_de_passe_actuel'])): ?>
                                <div id="erreur-suppression-mot-de-passe" class="invalid-feedback"><?= htmlspecialchars($erreursSuppression['mot_de_passe_actuel']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-danger">Supprimer définitivement mon compte</button>
                    </form>
                <?php elseif ($role === 'employe'): ?>
                    <p>Un compte employé est supprimé par l'administrateur : adressez-vous à lui.</p>
                <?php else: ?>
                    <p>Le compte administrateur ne peut pas être supprimé.</p>
                <?php endif; ?>
            </section>
        </div>
    </div>
</div>

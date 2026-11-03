<?php
// Espace admin : création d'un compte employé, changement du mot de passe d'un employé,
// et liste des employés (suspendre, réactiver, supprimer).
// $donnees['employes'] : id, pseudo, suspendu, motdepasse_a_modifier. Pas de mail (minimisation).
// $donnees['erreursCreation'], $donnees['mail'] : formulaire de création réaffiché après une erreur
// $donnees['erreursMotDePasse'], $donnees['idEmploye'] : formulaire de mot de passe réaffiché après une erreur
// Les mots de passe ne sont jamais réaffichés. Les identifiants des champs commencent par
// « creation- » ou « mdp- » : deux champs d'une même page ne peuvent pas avoir le même id.
$erreursCreation = $donnees['erreursCreation'];
$erreursMotDePasse = $donnees['erreursMotDePasse'];
?>
<div class="container py-5">
    <p><a href="/espace-admin">← Espace administrateur</a></p>
    <h1>Comptes employés</h1>

    <div class="row g-4 mt-1">
        <section class="col-lg-6" aria-labelledby="titre-creation">
            <div class="filtres-galerie h-100">
                <h2 id="titre-creation" class="h4">Créer un compte employé</h2>
                <p>Le pseudo est tiré de l'adresse mail. L'employé se connecte avec son mail et devra
                    choisir un nouveau mot de passe à sa première connexion.</p>

                <?php if (isset($erreursCreation['general'])): ?>
                    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreursCreation['general']) ?></div>
                <?php elseif (count($erreursCreation) > 0): ?>
                    <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
                <?php endif; ?>

                <form method="post" action="/admin/employe/creer">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                    <div class="mb-3">
                        <label for="creation-mail" class="form-label">Adresse mail de l'employé</label>
                        <input type="email" class="form-control<?php if (isset($erreursCreation['mail'])): ?> is-invalid<?php endif; ?>"
                               id="creation-mail" name="mail" value="<?= htmlspecialchars($donnees['mail']) ?>"
                               maxlength="254" autocomplete="off" required
                               <?php if (isset($erreursCreation['mail'])): ?>aria-invalid="true" aria-describedby="erreur-creation-mail"<?php endif; ?>>
                        <?php if (isset($erreursCreation['mail'])): ?>
                            <div id="erreur-creation-mail" class="invalid-feedback"><?= htmlspecialchars($erreursCreation['mail']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="creation-mot-de-passe" class="form-label">Mot de passe</label>
                        <!-- autocomplete="new-password" : le navigateur ne propose pas le mot de passe de l'admin -->
                        <input type="password" class="form-control<?php if (isset($erreursCreation['mot_de_passe'])): ?> is-invalid<?php endif; ?>"
                               id="creation-mot-de-passe" name="mot_de_passe" minlength="12" autocomplete="new-password" required
                               aria-describedby="aide-creation-mot-de-passe<?php if (isset($erreursCreation['mot_de_passe'])): ?> erreur-creation-mot-de-passe<?php endif; ?>"
                               <?php if (isset($erreursCreation['mot_de_passe'])): ?>aria-invalid="true"<?php endif; ?>>
                        <div id="aide-creation-mot-de-passe" class="form-text">
                            12 caractères au moins, avec une majuscule, une minuscule, un chiffre et un caractère spécial
                            (72 caractères au maximum).
                        </div>
                        <?php if (isset($erreursCreation['mot_de_passe'])): ?>
                            <div id="erreur-creation-mot-de-passe" class="invalid-feedback"><?= htmlspecialchars($erreursCreation['mot_de_passe']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="creation-confirmation" class="form-label">Confirmation du mot de passe</label>
                        <input type="password" class="form-control<?php if (isset($erreursCreation['confirmation'])): ?> is-invalid<?php endif; ?>"
                               id="creation-confirmation" name="confirmation" autocomplete="new-password" required
                               <?php if (isset($erreursCreation['confirmation'])): ?>aria-invalid="true" aria-describedby="erreur-creation-confirmation"<?php endif; ?>>
                        <?php if (isset($erreursCreation['confirmation'])): ?>
                            <div id="erreur-creation-confirmation" class="invalid-feedback"><?= htmlspecialchars($erreursCreation['confirmation']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary">Créer le compte</button>
                </form>
            </div>
        </section>

        <section class="col-lg-6" aria-labelledby="titre-mot-de-passe">
            <div class="filtres-galerie h-100">
                <h2 id="titre-mot-de-passe" class="h4">Changer le mot de passe d'un employé</h2>
                <p>Ses sessions ouvertes sont fermées, et il devra choisir un nouveau mot de passe à sa
                    prochaine connexion.</p>

                <?php if (count($donnees['employes']) === 0): ?>
                    <p class="mb-0">Aucun compte employé pour le moment.</p>
                <?php else: ?>
                    <?php if (isset($erreursMotDePasse['general'])): ?>
                        <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreursMotDePasse['general']) ?></div>
                    <?php elseif (count($erreursMotDePasse) > 0): ?>
                        <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
                    <?php endif; ?>

                    <form method="post" action="/admin/employe/mot-de-passe">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">

                        <div class="mb-3">
                            <label for="mdp-employe" class="form-label">Employé</label>
                            <select class="form-select<?php if (isset($erreursMotDePasse['employe'])): ?> is-invalid<?php endif; ?>"
                                    id="mdp-employe" name="id" required
                                    <?php if (isset($erreursMotDePasse['employe'])): ?>aria-invalid="true" aria-describedby="erreur-mdp-employe"<?php endif; ?>>
                                <option value="">Choisir un employé</option>
                                <?php foreach ($donnees['employes'] as $employe): ?>
                                    <option value="<?= htmlspecialchars((string) $employe['id_utilisateur']) ?>"
                                            <?php if ((int) $employe['id_utilisateur'] === $donnees['idEmploye']): ?>selected<?php endif; ?>><?= htmlspecialchars($employe['pseudo']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($erreursMotDePasse['employe'])): ?>
                                <div id="erreur-mdp-employe" class="invalid-feedback"><?= htmlspecialchars($erreursMotDePasse['employe']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="mdp-mot-de-passe" class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control<?php if (isset($erreursMotDePasse['mot_de_passe'])): ?> is-invalid<?php endif; ?>"
                                   id="mdp-mot-de-passe" name="mot_de_passe" minlength="12" autocomplete="new-password" required
                                   aria-describedby="aide-mdp-mot-de-passe<?php if (isset($erreursMotDePasse['mot_de_passe'])): ?> erreur-mdp-mot-de-passe<?php endif; ?>"
                                   <?php if (isset($erreursMotDePasse['mot_de_passe'])): ?>aria-invalid="true"<?php endif; ?>>
                            <div id="aide-mdp-mot-de-passe" class="form-text">Même règle qu'à la création.</div>
                            <?php if (isset($erreursMotDePasse['mot_de_passe'])): ?>
                                <div id="erreur-mdp-mot-de-passe" class="invalid-feedback"><?= htmlspecialchars($erreursMotDePasse['mot_de_passe']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="mdp-confirmation" class="form-label">Confirmation du nouveau mot de passe</label>
                            <input type="password" class="form-control<?php if (isset($erreursMotDePasse['confirmation'])): ?> is-invalid<?php endif; ?>"
                                   id="mdp-confirmation" name="confirmation" autocomplete="new-password" required
                                   <?php if (isset($erreursMotDePasse['confirmation'])): ?>aria-invalid="true" aria-describedby="erreur-mdp-confirmation"<?php endif; ?>>
                            <?php if (isset($erreursMotDePasse['confirmation'])): ?>
                                <div id="erreur-mdp-confirmation" class="invalid-feedback"><?= htmlspecialchars($erreursMotDePasse['confirmation']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </form>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <h2 class="h4 mt-5">Employés (<?= count($donnees['employes']) ?>)</h2>
    <?php if (count($donnees['employes']) > 0): ?>
        <div class="table-responsive">
            <table class="table align-middle">
                <caption class="visually-hidden">Comptes employés</caption>
                <thead>
                    <tr>
                        <th scope="col">Pseudo</th>
                        <th scope="col">État</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($donnees['employes'] as $employe): ?>
                        <?php
                        $id = htmlspecialchars((string) $employe['id_utilisateur']);
                        $pseudo = htmlspecialchars($employe['pseudo']);
                        ?>
                        <tr>
                            <td><?= $pseudo ?></td>
                            <td>
                                <?php if ($employe['suspendu']): ?>
                                    <span class="badge badge-attente">Suspendu</span>
                                <?php else: ?>
                                    <span class="badge badge-partage">Actif</span>
                                <?php endif; ?>
                                <?php if ($employe['motdepasse_a_modifier']): ?>
                                    <span class="badge badge-valide">Mot de passe à changer</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <form method="post" action="/admin/employe/suspendre">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <?php if ($employe['suspendu']): ?>Réactiver<?php else: ?>Suspendre<?php endif; ?><span class="visually-hidden"> <?= $pseudo ?></span>
                                        </button>
                                    </form>
                                    <form method="post" action="/admin/employe/supprimer"
                                          data-confirmation="Supprimer définitivement le compte employé « <?= $pseudo ?> » ?">
                                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                        <input type="hidden" name="id" value="<?= $id ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer<span class="visually-hidden"> le compte <?= $pseudo ?></span></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

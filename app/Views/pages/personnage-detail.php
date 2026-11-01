<?php
// Détail d'un personnage de la galerie : avatar, informations de création, équipement et avis validés.
// $donnees['personnage'] : le personnage (avec le pseudo de son créateur)
// $donnees['calques'] : calques de son avatar
// $donnees['caracteristiques'] : informations lisibles (libellé, valeur, couleur vérifiée ou null)
// $donnees['articles'] : ses articles actifs ; $donnees['typesArticles'] : libellés des types
// $donnees['avis'] : avis validés (note, commentaire, date, pseudo de l'auteur)
// $donnees['etatAvis'] : 'formulaire' si le visiteur peut donner un avis ; sinon $donnees['messageAvis']
//   explique pourquoi. $donnees['erreurs'], ['note'], ['commentaire'] : formulaire réaffiché après une erreur.
$personnage = $donnees['personnage'];
$nombreAvis = count($donnees['avis']);
$erreurs = $donnees['erreurs'];
// Libellés des notes, de la meilleure à la moins bonne
$libellesNotes = [5 => 'Excellent', 4 => 'Très bien', 3 => 'Bien', 2 => 'Moyen', 1 => 'Décevant'];
?>
<div class="container py-5">
    <p><a href="/personnages">← Retour à la galerie</a></p>

    <div class="row g-4">
        <div class="col-md-5 col-lg-4">
            <div class="avatar-detail">
                <?php
                $avatarNom = $personnage['nom'];
                $avatarCalques = $donnees['calques'];
                require __DIR__ . '/../partials/avatar.php';
                ?>
            </div>
        </div>

        <div class="col-md-7 col-lg-8">
            <h1><?= htmlspecialchars($personnage['nom']) ?></h1>
            <p>
                Créé par <strong><?= htmlspecialchars($personnage['pseudo']) ?></strong>
                le <?= htmlspecialchars(date('d/m/Y', strtotime($personnage['date_creation']))) ?>
            </p>

            <h2 class="h4 mt-4">Apparence</h2>
            <!-- dl : liste de couples « nom du champ / valeur » -->
            <dl class="row">
                <?php foreach ($donnees['caracteristiques'] as $caracteristique): ?>
                    <dt class="col-sm-5"><?= htmlspecialchars($caracteristique['libelle']) ?></dt>
                    <dd class="col-sm-7">
                        <?php if ($caracteristique['couleur'] !== null): ?>
                            <!-- Couleur vérifiée (#RRGGBB) avant d'être placée dans style ; le code est écrit à côté -->
                            <span class="pastille-couleur" style="background-color: <?= htmlspecialchars($caracteristique['couleur']) ?>;" aria-hidden="true"></span>
                        <?php endif; ?>
                        <?= htmlspecialchars($caracteristique['valeur']) ?>
                    </dd>
                <?php endforeach; ?>
            </dl>

            <h2 class="h4 mt-4">Équipement</h2>
            <?php if (count($donnees['articles']) === 0): ?>
                <p>Aucun article.</p>
            <?php else: ?>
                <ul>
                    <?php // Dans l'ordre des types : vêtement, armure, accessoire, arme ?>
                    <?php foreach ($donnees['typesArticles'] as $type => $libelle): ?>
                        <?php foreach ($donnees['articles'] as $article): ?>
                            <?php if ($article['type'] === $type): ?>
                                <li><?= htmlspecialchars($libelle) ?> : <?= htmlspecialchars($article['nom']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <section class="mt-5" aria-labelledby="titre-avis">
        <h2 id="titre-avis">Avis (<?= $nombreAvis ?>)</h2>

        <?php if ($nombreAvis === 0): ?>
            <p>Aucun avis pour le moment.</p>
        <?php else: ?>
            <ul class="list-unstyled liste-avis">
                <?php foreach ($donnees['avis'] as $avis): ?>
                    <li class="avis">
                        <p class="mb-1">
                            <strong><?= htmlspecialchars($avis['pseudo_auteur']) ?></strong>
                            <!-- Étoiles décoratives (aria-hidden) : la note est aussi écrite en texte -->
                            <span class="note-etoiles ms-2" aria-hidden="true"><?= str_repeat('★', (int) $avis['note']) . str_repeat('☆', 5 - (int) $avis['note']) ?></span>
                            <span class="visually-hidden">Note :</span> <?= (int) $avis['note'] ?>/5
                            <span class="text-body-secondary small ms-2">le <time datetime="<?= htmlspecialchars($avis['date_commentaire']) ?>"><?= htmlspecialchars(date('d/m/Y', strtotime($avis['date_commentaire']))) ?></time></span>
                        </p>
                        <!-- nl2br après htmlspecialchars : les retours à la ligne sont gardés, sans HTML injecté -->
                        <p class="mb-0"><?= nl2br(htmlspecialchars($avis['commentaire'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="mt-4 formulaire-avis" aria-labelledby="titre-donner-avis">
        <h2 id="titre-donner-avis" class="h4">Donner mon avis</h2>

        <?php if ($donnees['etatAvis'] === 'formulaire'): ?>
            <?php if (isset($erreurs['general'])): ?>
                <div class="alert alert-danger" role="alert"><?= htmlspecialchars($erreurs['general']) ?></div>
            <?php elseif (count($erreurs) > 0): ?>
                <div class="alert alert-danger" role="alert">Le formulaire contient des erreurs : corrigez les champs signalés.</div>
            <?php endif; ?>

            <form method="post" action="/avis">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                <!-- Vérifié côté serveur : le personnage doit être dans la galerie -->
                <input type="hidden" name="id" value="<?= htmlspecialchars((string) $personnage['id_personnage']) ?>">

                <div class="mb-3">
                    <label for="note" class="form-label">Note</label>
                    <select class="form-select<?php if (isset($erreurs['note'])): ?> is-invalid<?php endif; ?>" id="note" name="note" required
                            <?php if (isset($erreurs['note'])): ?>aria-invalid="true" aria-describedby="erreur-note"<?php endif; ?>>
                        <option value="">Choisir une note</option>
                        <?php foreach ($libellesNotes as $valeur => $libelle): ?>
                            <option value="<?= $valeur ?>" <?php if ((string) $valeur === $donnees['note']): ?>selected<?php endif; ?>><?= $valeur ?> sur 5 : <?= htmlspecialchars($libelle) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($erreurs['note'])): ?>
                        <div id="erreur-note" class="invalid-feedback"><?= htmlspecialchars($erreurs['note']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <label for="commentaire" class="form-label">Commentaire</label>
                    <!-- Contenu collé aux balises : sinon les espaces du code seraient ajoutés au texte -->
                    <textarea class="form-control<?php if (isset($erreurs['commentaire'])): ?> is-invalid<?php endif; ?>"
                              id="commentaire" name="commentaire" rows="4" minlength="10" maxlength="1000" required
                              aria-describedby="aide-commentaire<?php if (isset($erreurs['commentaire'])): ?> erreur-commentaire<?php endif; ?>"
                              <?php if (isset($erreurs['commentaire'])): ?>aria-invalid="true"<?php endif; ?>><?= htmlspecialchars($donnees['commentaire']) ?></textarea>
                    <div id="aide-commentaire" class="form-text">
                        10 à 1000 caractères. Votre avis sera affiché avec votre pseudo, après sa validation par un employé.
                    </div>
                    <?php if (isset($erreurs['commentaire'])): ?>
                        <div id="erreur-commentaire" class="invalid-feedback"><?= htmlspecialchars($erreurs['commentaire']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-primary">Envoyer mon avis</button>
            </form>
        <?php elseif ($donnees['etatAvis'] === 'connexion'): ?>
            <p><a href="/connexion">Connectez-vous</a> pour donner votre avis.</p>
        <?php else: ?>
            <p><?= htmlspecialchars($donnees['messageAvis']) ?></p>
        <?php endif; ?>
    </section>
</div>

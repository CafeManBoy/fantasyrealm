<?php
// Espace du joueur (rôle utilisateur) : la liste de ses personnages.
// $donnees['personnages'] : ses personnages, chacun avec les calques de son avatar ('calques')
?>
<div class="container py-5">
    <h1>Mon espace</h1>
    <p>Bonjour <?= htmlspecialchars($utilisateurConnecte['pseudo']) ?>, bienvenue dans votre espace joueur.</p>

    <h2 class="mt-4 mb-3">Mes personnages</h2>
    <p><a href="/personnage/creer" class="btn btn-primary">Créer un personnage</a></p>
    <?php if (count($donnees['personnages']) === 0): ?>
        <p>Vous n'avez pas encore de personnage.</p>
    <?php else: ?>
        <ul class="row list-unstyled g-3">
            <?php foreach ($donnees['personnages'] as $personnage): ?>
                <li class="col-6 col-md-4 col-lg-3">
                    <article class="carte-personnage">
                        <?php
                        $avatarNom = $personnage['nom'];
                        $avatarCalques = $personnage['calques'];
                        require __DIR__ . '/../partials/avatar.php';
                        ?>
                        <h3 class="h5 mt-2 mb-1"><?= htmlspecialchars($personnage['nom']) ?></h3>
                        <p class="mb-0">
                            <?php if (!$personnage['autorise']): ?>
                                <span class="badge badge-attente">En attente de validation</span>
                            <?php elseif ($personnage['partage']): ?>
                                <span class="badge badge-partage">Partagé dans la galerie</span>
                            <?php else: ?>
                                <span class="badge badge-valide">Validé, non partagé</span>
                            <?php endif; ?>
                        </p>
                        <?php
                        // Valeurs réutilisées dans les boutons ci-dessous (déjà échappées)
                        $id = htmlspecialchars((string) $personnage['id_personnage']);
                        $nom = htmlspecialchars($personnage['nom']);
                        ?>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <?php if ($personnage['autorise']): ?>
                                <!-- Lien simple (GET) : il affiche seulement le formulaire, il ne modifie rien -->
                                <a href="/personnage/modifier?id=<?= $id ?>" class="btn btn-sm btn-outline-primary">
                                    Modifier<span class="visually-hidden"> <?= $nom ?></span>
                                </a>

                                <!-- Actions qui modifient des données : formulaire POST avec jeton CSRF -->
                                <form method="post" action="/personnage/partager">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <?php if ($personnage['partage']): ?>Arrêter le partage<?php else: ?>Partager<?php endif; ?><span class="visually-hidden"> <?= $nom ?></span>
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="post" action="/personnage/supprimer"
                                  data-confirmation="Supprimer définitivement « <?= $nom ?> » ? Ses avis seront supprimés aussi.">
                                <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                <input type="hidden" name="id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Supprimer<span class="visually-hidden"> <?= $nom ?></span>
                                </button>
                            </form>
                        </div>

                        <?php if ($personnage['autorise']): ?>
                            <!-- details / summary : zone qui s'ouvre au clic ou au clavier, sans JavaScript -->
                            <details class="mt-2">
                                <summary>Dupliquer<span class="visually-hidden"> <?= $nom ?></span></summary>
                                <form method="post" action="/personnage/dupliquer" class="mt-2">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                                    <input type="hidden" name="id" value="<?= $id ?>">
                                    <label for="nouveau-nom-<?= $id ?>" class="form-label small">Nom de la copie</label>
                                    <input type="text" class="form-control form-control-sm mb-2" id="nouveau-nom-<?= $id ?>"
                                           name="nouveau_nom" minlength="3" maxlength="30" autocomplete="off" required>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Créer la copie</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
// Galerie : les personnages partagés, avec un formulaire de filtres.
// $donnees['personnages'] : personnages affichés (avec le pseudo de leur créateur et leurs calques)
// $donnees['filtres'] : filtres appliqués ('genre', 'du', 'au', 'pseudo'), réaffichés dans le formulaire
// $donnees['messagesFiltres'] : filtres ignorés parce qu'invalides
// $donnees['genres'] : options du genre (config/avatar.php) ; $donnees['pseudos'] : liste du filtre
// Formulaire en GET, traité en PHP : les filtres marchent sans JavaScript et l'adresse peut être partagée.
$filtres = $donnees['filtres'];
$nombre = count($donnees['personnages']);
?>
<div class="container py-5">
    <h1>Galerie des personnages</h1>
    <p>Les personnages que les joueurs ont choisi de partager. Cliquez sur un nom pour voir son détail et ses avis.</p>

    <form method="get" action="/personnages" class="filtres-galerie mb-4">
        <h2 class="h5">Filtrer</h2>
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-3">
                <label for="genre" class="form-label">Genre</label>
                <select class="form-select" id="genre" name="genre">
                    <option value="">Tous</option>
                    <?php foreach ($donnees['genres'] as $cle => $option): ?>
                        <option value="<?= htmlspecialchars($cle) ?>" <?php if ($cle === $filtres['genre']): ?>selected<?php endif; ?>><?= htmlspecialchars($option['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="du" class="form-label">Créé du</label>
                <input type="date" class="form-control" id="du" name="du" value="<?= htmlspecialchars($filtres['du']) ?>">
            </div>
            <div class="col-sm-6 col-lg-2">
                <label for="au" class="form-label">au</label>
                <input type="date" class="form-control" id="au" name="au" value="<?= htmlspecialchars($filtres['au']) ?>">
            </div>
            <div class="col-sm-6 col-lg-3">
                <label for="pseudo" class="form-label">Joueur</label>
                <select class="form-select" id="pseudo" name="pseudo">
                    <option value="">Tous</option>
                    <?php foreach ($donnees['pseudos'] as $pseudo): ?>
                        <option value="<?= htmlspecialchars($pseudo) ?>" <?php if ($pseudo === $filtres['pseudo']): ?>selected<?php endif; ?>><?= htmlspecialchars($pseudo) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button type="submit" class="btn btn-primary">Filtrer</button>
                <a href="/personnages" class="btn btn-outline-primary">Tout voir</a>
            </div>
        </div>
    </form>

    <?php foreach ($donnees['messagesFiltres'] as $message): ?>
        <div class="alert alert-warning" role="alert"><?= htmlspecialchars($message) ?></div>
    <?php endforeach; ?>

    <?php if ($nombre === 0): ?>
        <p>Aucun personnage ne correspond à ces filtres.</p>
    <?php else: ?>
        <p><?= $nombre ?> personnage<?php if ($nombre > 1): ?>s<?php endif; ?></p>
        <ul class="row list-unstyled g-3">
            <?php foreach ($donnees['personnages'] as $personnage): ?>
                <li class="col-6 col-md-4 col-lg-3">
                    <!-- position-relative + stretched-link (Bootstrap) : toute la carte est cliquable,
                         avec un seul lien, celui du nom -->
                    <article class="carte-personnage position-relative">
                        <?php
                        $avatarNom = $personnage['nom'];
                        $avatarCalques = $personnage['calques'];
                        require __DIR__ . '/../partials/avatar.php';
                        ?>
                        <h2 class="h5 mt-2 mb-1">
                            <a href="/personnage?id=<?= htmlspecialchars((string) $personnage['id_personnage']) ?>" class="stretched-link"><?= htmlspecialchars($personnage['nom']) ?></a>
                        </h2>
                        <p class="mb-0 small">
                            par <?= htmlspecialchars($personnage['pseudo']) ?>,
                            le <?= htmlspecialchars(date('d/m/Y', strtotime($personnage['date_creation']))) ?>
                        </p>
                    </article>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

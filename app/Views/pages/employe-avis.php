<?php
// Espace employé : avis en attente de validation (valider = publier + mail au propriétaire,
// refuser = supprimer).
// $donnees['enAttente'] : avis en attente (id, note, commentaire, date, auteur, personnage)
$nombre = count($donnees['enAttente']);
?>
<div class="container py-5">
    <p><a href="/espace-employe">← Espace employé</a></p>
    <h1>Avis en attente (<?= $nombre ?>)</h1>

    <?php if ($nombre === 0): ?>
        <p>Aucun avis à valider.</p>
    <?php else: ?>
        <ul class="list-unstyled liste-avis">
            <?php foreach ($donnees['enAttente'] as $avis): ?>
                <?php
                $id = htmlspecialchars((string) $avis['id_commentaire']);
                $description = htmlspecialchars('l\'avis de ' . $avis['pseudo_auteur'] . ' sur ' . $avis['nom_personnage']);
                ?>
                <li class="avis">
                    <h2 class="h5 mb-1">
                        Sur « <?= htmlspecialchars($avis['nom_personnage']) ?> »,
                        par <?= htmlspecialchars($avis['pseudo_auteur']) ?>
                    </h2>
                    <p class="mb-1">
                        <span class="note-etoiles" aria-hidden="true"><?= str_repeat('★', (int) $avis['note']) . str_repeat('☆', 5 - (int) $avis['note']) ?></span>
                        <span class="visually-hidden">Note :</span> <?= (int) $avis['note'] ?>/5
                        <span class="small ms-2">le <?= htmlspecialchars(date('d/m/Y', strtotime($avis['date_commentaire']))) ?></span>
                    </p>
                    <!-- Texte de l'avis : échappé, les balises éventuelles s'affichent comme du texte -->
                    <p><?= nl2br(htmlspecialchars($avis['commentaire'])) ?></p>
                    <div class="d-flex flex-wrap gap-2">
                        <form method="post" action="/employe/avis/valider">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-sm btn-primary">Publier<span class="visually-hidden"> <?= $description ?></span></button>
                        </form>
                        <form method="post" action="/employe/avis/refuser"
                              data-confirmation="Refuser cet avis ? Il sera supprimé définitivement.">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($jetonCsrf) ?>">
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Refuser<span class="visually-hidden"> <?= $description ?></span></button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

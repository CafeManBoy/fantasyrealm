<?php // Espace du joueur (rôle utilisateur) : ses personnages arriveront ici ?>
<div class="container py-5">
    <h1>Mon espace</h1>
    <p>Bonjour <?= htmlspecialchars($utilisateurConnecte['pseudo']) ?>, bienvenue dans votre espace joueur.</p>
    <p>Vous pourrez bientôt y créer, modifier, partager et dupliquer vos personnages.</p>
</div>

<?php // Espace administrateur : tableau de bord vers les comptes employés, le journal et l'espace employé ?>
<div class="container py-5">
    <h1>Espace administrateur</h1>
    <p>Bonjour <?= htmlspecialchars($utilisateurConnecte['pseudo']) ?>, bienvenue dans l'espace administrateur.</p>

    <ul class="row list-unstyled g-3 mt-2">
        <li class="col-sm-6 col-lg-4">
            <div class="carte-avantage h-100">
                <h2 class="h5"><a href="/admin/employes">Comptes employés</a></h2>
                <p class="mb-0">Créer un compte employé, changer son mot de passe, le suspendre ou le supprimer.</p>
            </div>
        </li>
        <li class="col-sm-6 col-lg-4">
            <div class="carte-avantage h-100">
                <h2 class="h5"><a href="/admin/logs">Journal des personnages</a></h2>
                <p class="mb-0">Les 100 dernières modifications : noms, apparence, équipement, partage, validation.</p>
            </div>
        </li>
        <li class="col-sm-6 col-lg-4">
            <div class="carte-avantage h-100">
                <h2 class="h5"><a href="/espace-employe">Espace employé</a></h2>
                <p class="mb-0">Toutes les opérations d'un employé : noms, avis, articles, personnages et comptes des joueurs.</p>
            </div>
        </li>
    </ul>
</div>

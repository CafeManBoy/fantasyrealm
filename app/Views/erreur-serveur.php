<?php
// Page affichée quand le site rencontre une erreur imprévue (base de données indisponible,
// fichier .env invalide...). Elle est autonome : elle n'utilise ni le gabarit, ni la base,
// ni le .env, qui sont peut-être justement la cause de l'erreur.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Erreur | FantasyRealm Online</title>
    <link rel="icon" href="data:,">
    <link rel="stylesheet" href="/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <main class="container py-5">
        <h1>Le site rencontre un problème</h1>
        <p>Nous n'avons pas pu afficher cette page. Réessayez dans quelques minutes.</p>
        <p><a href="/">Retour à l'accueil</a></p>
    </main>
</body>
</html>

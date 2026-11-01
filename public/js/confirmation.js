// Demande une confirmation avant d'envoyer un formulaire marqué data-confirmation (suppression).
// Confort seulement : la suppression est de toute façon protégée côté serveur (POST, jeton CSRF,
// propriétaire vérifié). Sans JavaScript, le formulaire est envoyé sans question.
// Chargé avec defer : la page est déjà lue quand ce code s'exécute.

document.querySelectorAll('form[data-confirmation]').forEach(function (formulaire) {
    formulaire.addEventListener('submit', function (evenement) {
        // Annuler dans la boîte de dialogue : le formulaire n'est pas envoyé
        if (!window.confirm(formulaire.dataset.confirmation)) {
            evenement.preventDefault();
        }
    });
});

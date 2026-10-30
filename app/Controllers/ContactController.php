<?php

namespace App\Controllers;

// Formulaire de contact : la demande est envoyée par mail à l'entreprise.
// Elle n'est pas enregistrée en base (minimisation des données).
class ContactController extends Controller
{
    public function formulaire(): void
    {
        // Compte connecté : son mail et son pseudo sont pré-remplis (sujet)
        $mail = '';
        $pseudo = '';
        if ($this->utilisateurConnecte !== null) {
            $mail = $this->utilisateurConnecte['mail'];
            $pseudo = $this->utilisateurConnecte['pseudo'];
        }

        $this->afficherContact($mail, $pseudo, '', []);
    }

    // Affiche le formulaire avec les valeurs à mettre dans les champs et les erreurs éventuelles.
    // estConnecte : le mail et le pseudo sont alors en lecture seule (ce sont ceux du compte).
    private function afficherContact(string $mail, string $pseudo, string $demande, array $erreurs): void
    {
        $this->afficher('contact', 'Contact', [
            'mail' => $mail,
            'pseudo' => $pseudo,
            'demande' => $demande,
            'erreurs' => $erreurs,
            'estConnecte' => $this->utilisateurConnecte !== null,
        ]);
    }
}

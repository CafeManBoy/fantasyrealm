<?php

namespace App\Controllers;

// Espaces personnels, un par rôle. Leur contenu arrivera avec les fonctionnalités suivantes.
class EspaceController extends Controller
{
    public function espaceJoueur(): void
    {
        $this->exigerRole(['utilisateur']);
        $this->afficher('espace-joueur', 'Mon espace');
    }

    public function espaceEmploye(): void
    {
        // L'administrateur peut faire tout ce que fait un employé (sujet)
        $this->exigerRole(['employe', 'admin']);
        $this->afficher('espace-employe', 'Espace employé');
    }

    public function espaceAdmin(): void
    {
        $this->exigerRole(['admin']);
        $this->afficher('espace-admin', 'Espace administrateur');
    }
}

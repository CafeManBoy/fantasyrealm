<?php

namespace App\Core;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Client;
use Throwable;

// Journal des modifications des personnages, enregistré dans MongoDB Atlas
// (base fantasyrealm, collection logs_personnages). Un document par action :
// date, environnement (dev ou prod), action, personnage (id, nom), auteur (id, pseudo)
// et changements (avant / après).
// Si Atlas ne répond pas, l'action de l'utilisateur aboutit quand même : l'erreur est
// seulement notée avec error_log(), sans page blanche.
class Logger
{
    // Noms de la base et de la collection dans Atlas (ce ne sont pas des secrets :
    // l'adresse de connexion, avec le mot de passe, est dans le fichier .env)
    private string $base = 'fantasyrealm';
    private string $collection = 'logs_personnages';

    // $action : 'creation', 'modification', 'equipement', 'partage', 'arret_partage',
    //           'suppression', 'duplication'...
    // $personnage : tableau avec 'id_personnage' et 'nom'
    // $auteur : tableau avec 'id_utilisateur' et 'pseudo' (le compte connecté)
    // $changements : champ => ['avant' => ..., 'apres' => ...] (vide si rien à détailler)
    // Renvoie true si le document a été enregistré.
    public function enregistrer(string $action, array $personnage, array $auteur, array $changements = []): bool
    {
        if (!isset($_ENV['MONGODB_URI']) || $_ENV['MONGODB_URI'] === '') {
            error_log('Log MongoDB non enregistré : MONGODB_URI absente du fichier .env');
            return false;
        }

        $document = [
            'date' => new UTCDateTime(),
            'environnement' => $this->environnement(),
            'action' => $action,
            'personnage' => ['id' => (int) $personnage['id_personnage'], 'nom' => $personnage['nom']],
            'auteur' => ['id' => (int) $auteur['id_utilisateur'], 'pseudo' => $auteur['pseudo']],
            'changements' => $changements,
        ];

        try {
            // 3 secondes au plus pour joindre Atlas : sinon, la page ne reste pas bloquée
            $client = new Client($_ENV['MONGODB_URI'], ['serverSelectionTimeoutMS' => 3000]);
            $client->selectCollection($this->base, $this->collection)->insertOne($document);
            return true;
        } catch (Throwable $e) {
            // Seulement le type d'erreur : le message peut contenir l'adresse du serveur
            error_log('Log MongoDB non enregistré (' . get_class($e) . ')');
            return false;
        }
    }

    // Les $nombre derniers documents de l'environnement courant, du plus récent au plus ancien
    // (page des logs de l'administrateur). Renvoie null si Atlas ne répond pas : la page affiche
    // alors « journal indisponible » au lieu d'une page blanche.
    public function derniers(int $nombre): ?array
    {
        if (!isset($_ENV['MONGODB_URI']) || $_ENV['MONGODB_URI'] === '') {
            error_log('Journal MongoDB non lu : MONGODB_URI absente du fichier .env');
            return null;
        }

        // Filtre : seulement cet environnement (les tests locaux n'apparaissent pas en production).
        // Il ne contient aucune valeur venue de l'utilisateur.
        $filtre = ['environnement' => $this->environnement()];
        $options = [
            'sort' => ['date' => -1],  // -1 : du plus récent au plus ancien
            'limit' => $nombre,
            // Documents lus comme des tableaux PHP (au lieu d'objets MongoDB)
            'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        ];

        try {
            $client = new Client($_ENV['MONGODB_URI'], ['serverSelectionTimeoutMS' => 3000]);
            $curseur = $client->selectCollection($this->base, $this->collection)->find($filtre, $options);

            // toArray() dans le try : c'est là que les documents sont vraiment lus dans Atlas
            return $curseur->toArray();
        } catch (Throwable $e) {
            error_log('Journal MongoDB non lu (' . get_class($e) . ')');
            return null;
        }
    }

    // 'dev' en local, 'prod' sinon (même règle que le reste du site : prod par défaut).
    // Publique : la page des logs affiche l'environnement dont elle montre les documents.
    public function environnement(): string
    {
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            return 'dev';
        }

        return 'prod';
    }
}

<?php

namespace App\Core;

use PDO;

// Connexion à la base MySQL / MariaDB avec PDO.
// Une seule connexion par requête : elle est créée au premier appel, puis réutilisée.
class Database
{
    // static : la même connexion est partagée par tous les modèles pendant la requête
    private static ?PDO $connexion = null;

    public static function connexion(): PDO
    {
        if (self::$connexion === null) {
            // charset=utf8mb4 : les accents (et tous les caractères) sont transmis correctement
            $dsn = 'mysql:host=' . $_ENV['DB_HOST'] . ';dbname=' . $_ENV['DB_NAME'] . ';charset=utf8mb4';

            self::$connexion = new PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], [
                // Une erreur SQL lance une exception au lieu d'être ignorée
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Chaque ligne de résultat est un tableau associatif : $ligne['pseudo']
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Vraies requêtes préparées par le serveur MySQL (pas une simulation par PDO)
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connexion;
    }
}

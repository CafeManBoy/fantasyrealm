-- =====================================================================
-- FantasyRealm Online : création de la base de données
-- ---------------------------------------------------------------------
-- À utiliser en LOCAL seulement, une seule fois. En production, la base
-- est en général créée par l'hébergeur : on importe directement schema.sql.
--
-- utf8mb4 : encodage UTF-8 complet (accents, mais aussi émojis, qui
-- prennent 4 octets et ne passent pas avec l'ancien « utf8 » de MySQL).
-- utf8mb4_unicode_ci : règles de tri et de comparaison ; « ci » = insensible
-- à la casse, donc « Admin » et « admin » sont considérés comme identiques.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS fantasyrealm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

-- Initialisation des bases de données : une base par micro-service.
-- Exécuté automatiquement par l'image postgres au premier démarrage
-- (volume vide). Les services se connectent avec les credentials
-- POSTGRES_USER / POSTGRES_PASSWORD définis dans .env.

CREATE DATABASE auth_db;
CREATE DATABASE asset_db;
CREATE DATABASE maintenance_db;
CREATE DATABASE inventory_db;
CREATE DATABASE reporting_db;

-- Base réservée au frontend (étape 13) si nécessaire.
CREATE DATABASE frontend_db;

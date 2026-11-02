-- Optionnel : l'application ajoute ces colonnes toute seule si elles manquent.
ALTER TABLE commandes
  ADD COLUMN nom_livraison VARCHAR(100) NULL,
  ADD COLUMN telephone     VARCHAR(20)  NULL,
  ADD COLUMN ville         VARCHAR(80)  NULL,
  ADD COLUMN adresse       VARCHAR(255) NULL;

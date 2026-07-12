-- ⚠️ VIDE TOUTES LES DONNÉES applicatives de la base (garde le schéma et la
-- version des migrations). À n'utiliser que sur un environnement dont les
-- données sont jetables (démo / test). Réinitialise aussi les séquences d'ID.
DO $$
DECLARE _tables text;
BEGIN
  SELECT string_agg(format('public.%I', tablename), ', ')
    INTO _tables
  FROM pg_tables
  WHERE schemaname = 'public'
    AND tablename <> 'doctrine_migration_versions';
  IF _tables IS NOT NULL THEN
    EXECUTE 'TRUNCATE TABLE ' || _tables || ' RESTART IDENTITY CASCADE';
  END IF;
END $$;

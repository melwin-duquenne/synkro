-- Donne à admin@demo.synkro.ovh une charge de travail RÉALISTE sur la semaine
-- COURANTE (dates relatives à now(), donc toujours valables le jour de la démo).
-- Idempotent : on efface d'abord ses événements existants. À lancer après le
-- chargement de la démo (demo-data.sql).
BEGIN;

-- Menage : événements existants de l'admin démo (+ participations liées)
DELETE FROM calendar_event_participant WHERE event_id IN (
  SELECT ce.id FROM calendar_event ce
  JOIN "user" u ON u.id = ce.user_id
  WHERE u.email = 'admin@demo.synkro.ovh'
);
DELETE FROM calendar_event
  WHERE user_id = (SELECT id FROM "user" WHERE email = 'admin@demo.synkro.ovh');

-- Événements de la semaine courante (lundi = date_trunc('week', CURRENT_DATE))
INSERT INTO calendar_event
  (room_id, user_id, entreprise_id, title, description, event_type, start_date, end_date, is_all_day, is_private, created_at)
SELECT
  (SELECT id FROM room WHERE entreprise_id = (SELECT id FROM entreprise WHERE name='Nexora') ORDER BY id LIMIT 1),
  (SELECT id FROM "user" WHERE email = 'admin@demo.synkro.ovh'),
  (SELECT id FROM entreprise WHERE name='Nexora'),
  v.title, v.descr, 'meeting', v.start_ts, v.end_ts, false, false, now()
FROM (VALUES
  -- Lundi : ~2,5 h (vert)
  ('Réunion hebdo Produit',        'Point d''équipe hebdomadaire',   date_trunc('week',CURRENT_DATE)+interval '9 hours 30 minutes', date_trunc('week',CURRENT_DATE)+interval '11 hours'),
  ('Point projet refonte',         'Suivi du chantier refonte',      date_trunc('week',CURRENT_DATE)+interval '14 hours',          date_trunc('week',CURRENT_DATE)+interval '15 hours'),
  -- Mardi : ~2 h (vert)
  ('Atelier UX & maquettes',       'Revue des maquettes avec le design', date_trunc('week',CURRENT_DATE)+interval '1 day 10 hours', date_trunc('week',CURRENT_DATE)+interval '1 day 12 hours'),
  -- Mercredi : ~7,5 h (orange, chargé)
  ('Standup équipe',               'Point quotidien',                date_trunc('week',CURRENT_DATE)+interval '2 days 9 hours',    date_trunc('week',CURRENT_DATE)+interval '2 days 9 hours 30 minutes'),
  ('Atelier priorisation backlog', 'Priorisation MoSCoW',            date_trunc('week',CURRENT_DATE)+interval '2 days 10 hours',   date_trunc('week',CURRENT_DATE)+interval '2 days 12 hours 30 minutes'),
  ('Revue de sprint',              'Démonstration des incréments',   date_trunc('week',CURRENT_DATE)+interval '2 days 14 hours',   date_trunc('week',CURRENT_DATE)+interval '2 days 17 hours'),
  ('Préparation démo',             'Prépa de la présentation',       date_trunc('week',CURRENT_DATE)+interval '2 days 17 hours',   date_trunc('week',CURRENT_DATE)+interval '2 days 18 hours 30 minutes'),
  -- Jeudi : ~9 h (rouge, surchargé)
  ('Démo client',                  'Présentation au client',         date_trunc('week',CURRENT_DATE)+interval '3 days 9 hours',    date_trunc('week',CURRENT_DATE)+interval '3 days 10 hours 30 minutes'),
  ('Réunion partenaire',           'Cadrage partenariat',            date_trunc('week',CURRENT_DATE)+interval '3 days 11 hours',   date_trunc('week',CURRENT_DATE)+interval '3 days 13 hours'),
  ('Atelier technique',            'Architecture temps réel',        date_trunc('week',CURRENT_DATE)+interval '3 days 14 hours',   date_trunc('week',CURRENT_DATE)+interval '3 days 16 hours 30 minutes'),
  ('Point équipe',                 'Synchronisation',                date_trunc('week',CURRENT_DATE)+interval '3 days 16 hours 30 minutes', date_trunc('week',CURRENT_DATE)+interval '3 days 18 hours'),
  ('Bilan hebdomadaire',           'Rétro de la semaine',            date_trunc('week',CURRENT_DATE)+interval '3 days 18 hours',   date_trunc('week',CURRENT_DATE)+interval '3 days 19 hours 30 minutes'),
  -- Vendredi : ~2 h (vert)
  ('Rétrospective',                'Amélioration continue',          date_trunc('week',CURRENT_DATE)+interval '4 days 14 hours',   date_trunc('week',CURRENT_DATE)+interval '4 days 16 hours'),
  -- AUJOURD'HUI (quel que soit le jour, pour que la carte « Aujourd'hui » soit non nulle)
  ('Session de travail',           'Focus produit',                  CURRENT_DATE+interval '10 hours',                            CURRENT_DATE+interval '12 hours'),
  ('Suivi & e-mails',              'Traitement des demandes',        CURRENT_DATE+interval '14 hours',                            CURRENT_DATE+interval '15 hours 30 minutes')
) AS v(title, descr, start_ts, end_ts);

COMMIT;

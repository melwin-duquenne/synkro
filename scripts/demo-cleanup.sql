-- Retire proprement TOUT le jeu de données de démonstration (entreprises Nexora,
-- Zephyr, Demo + comptes @demo.synkro.ovh / @zephyr.demo + leurs rooms/contenu),
-- sans toucher aux données réelles ni aux Modules/RoomTemplates partagés.
-- Transaction : soit tout passe, soit rien.
BEGIN;

CREATE TEMP TABLE _ent   ON COMMIT DROP AS SELECT id FROM entreprise WHERE name IN ('Nexora','Zephyr','Demo');
CREATE TEMP TABLE _rooms ON COMMIT DROP AS SELECT id FROM room WHERE entreprise_id IN (SELECT id FROM _ent);
CREATE TEMP TABLE _users ON COMMIT DROP AS SELECT id FROM "user" WHERE email LIKE '%@demo.synkro.ovh' OR email LIKE '%@zephyr.demo';
CREATE TEMP TABLE _teams ON COMMIT DROP AS SELECT id FROM team WHERE entreprise_id IN (SELECT id FROM _ent);

DELETE FROM calendar_event_participant
  WHERE event_id IN (SELECT id FROM calendar_event WHERE room_id IN (SELECT id FROM _rooms))
     OR user_id  IN (SELECT id FROM _users);

DELETE FROM task
  WHERE room_id       IN (SELECT id FROM _rooms)
     OR assigned_to_id IN (SELECT id FROM _users)
     OR column_id     IN (SELECT id FROM kanban_column WHERE room_id IN (SELECT id FROM _rooms));

DELETE FROM kanban_column WHERE room_id IN (SELECT id FROM _rooms);

DELETE FROM calendar_event
  WHERE room_id       IN (SELECT id FROM _rooms)
     OR user_id       IN (SELECT id FROM _users)
     OR entreprise_id IN (SELECT id FROM _ent);

DELETE FROM message      WHERE room_id IN (SELECT id FROM _rooms) OR user_id IN (SELECT id FROM _users);
DELETE FROM document     WHERE room_id IN (SELECT id FROM _rooms);
DELETE FROM whiteboard   WHERE room_id IN (SELECT id FROM _rooms);
DELETE FROM file_resource WHERE room_id IN (SELECT id FROM _rooms) OR user_id IN (SELECT id FROM _users);
DELETE FROM module_room  WHERE room_id IN (SELECT id FROM _rooms);
DELETE FROM user_room_permission WHERE room_id IN (SELECT id FROM _rooms) OR user_id IN (SELECT id FROM _users);
DELETE FROM team_room_permission WHERE room_id IN (SELECT id FROM _rooms) OR team_id IN (SELECT id FROM _teams);
DELETE FROM invitation   WHERE entreprise_id IN (SELECT id FROM _ent) OR invited_by_id IN (SELECT id FROM _users);

DELETE FROM room WHERE id IN (SELECT id FROM _rooms);

UPDATE "user" SET team_id = NULL WHERE team_id IN (SELECT id FROM _teams);
DELETE FROM team WHERE id IN (SELECT id FROM _teams);

DELETE FROM user_entreprise WHERE user_id IN (SELECT id FROM _users) OR entreprise_id IN (SELECT id FROM _ent);
DELETE FROM "user" WHERE id IN (SELECT id FROM _users);
DELETE FROM entreprise WHERE id IN (SELECT id FROM _ent);

COMMIT;

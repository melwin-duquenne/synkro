--
-- PostgreSQL database dump
--

\restrict c0eED1WbdHE7bzSuLVLBJfzWeh6VM2NslP8ldC8BWoU96OgqCCzwBuxsPSsBmTD

-- Dumped from database version 16.13 (Debian 16.13-1.pgdg13+1)
-- Dumped by pg_dump version 16.13 (Debian 16.13-1.pgdg13+1)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: entreprise; Type: TABLE DATA; Schema: public; Owner: -
--

SET SESSION AUTHORIZATION DEFAULT;

ALTER TABLE public.entreprise DISABLE TRIGGER ALL;

INSERT INTO public.entreprise (id, name, domain, created_at, slug, ai_enabled, ai_provider, ai_api_key, ai_mode, ai_tokens_used, ai_tokens_limit, ai_tokens_reset_at) VALUES (12, 'Nexora', 'demo.synkro.ovh', '2026-07-12 12:17:16', 'nexora', false, NULL, NULL, 'byok', 0, NULL, NULL);
INSERT INTO public.entreprise (id, name, domain, created_at, slug, ai_enabled, ai_provider, ai_api_key, ai_mode, ai_tokens_used, ai_tokens_limit, ai_tokens_reset_at) VALUES (13, 'Zephyr', 'zephyr.demo', '2026-07-12 12:17:23', 'zephyr', false, NULL, NULL, 'byok', 0, NULL, NULL);


ALTER TABLE public.entreprise ENABLE TRIGGER ALL;

--
-- Data for Name: team; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.team DISABLE TRIGGER ALL;

INSERT INTO public.team (id, name, entreprise_id) VALUES (14, 'Produit', 12);
INSERT INTO public.team (id, name, entreprise_id) VALUES (15, 'Marketing', 12);
INSERT INTO public.team (id, name, entreprise_id) VALUES (16, 'Direction', 13);


ALTER TABLE public.team ENABLE TRIGGER ALL;

--
-- Data for Name: user; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public."user" DISABLE TRIGGER ALL;

INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (46, 'admin@demo.synkro.ovh', '$2y$13$DegkrIh1x.jBdgzQrfM9Gu1KuYoo2suXCZ/jstnL.Fz.JCZReQCWq', 'Admin Démo', 'admin', NULL, '2026-07-12 12:17:16', NULL, NULL, NULL, NULL, NULL, NULL, 'Admin', 'Démo');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (47, 'user@demo.synkro.ovh', '$2y$13$pzFv7ni5NJK12ROPsA8AxuQFXjgWXQ6/9hZJycCy6wO1eupiTVfN.', 'Camille Laurent', 'user', 14, '2026-07-12 12:17:17', NULL, NULL, NULL, NULL, NULL, NULL, 'Camille', 'Laurent');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (48, 'guest@demo.synkro.ovh', '$2y$13$zdGPT7CnBmAuaiWZU9.s3.EdqC1jJ6mQKlPPqiRBUhayGK7V82SQW', 'Théo Invité', 'user', 15, '2026-07-12 12:17:18', NULL, NULL, NULL, NULL, NULL, NULL, 'Théo', 'Invité');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (49, 'sophie.martin@demo.synkro.ovh', '$2y$13$zeb6NRzG1LDXsizRuc316.PQAwVfvVQpqwO49R5stHvizIc3Db.Sy', 'Sophie Martin', 'user', 15, '2026-07-12 12:17:19', NULL, NULL, NULL, NULL, NULL, NULL, 'Sophie', 'Martin');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (50, 'lucas.bernard@demo.synkro.ovh', '$2y$13$oSL0IWkMzGrb/vZDR.lWu.6khi2G6zO1njBwjgktrRd7ml988Awo2', 'Lucas Bernard', 'user', 14, '2026-07-12 12:17:19', NULL, NULL, NULL, NULL, NULL, NULL, 'Lucas', 'Bernard');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (51, 'emma.petit@demo.synkro.ovh', '$2y$13$ELwlsilxkC3r7IFWqpYeh.obLmiwbA4DC0tgjYgr76SZWfjTKxTFK', 'Emma Petit', 'user', 14, '2026-07-12 12:17:20', NULL, NULL, NULL, NULL, NULL, NULL, 'Emma', 'Petit');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (52, 'hugo.moreau@demo.synkro.ovh', '$2y$13$VxjdphsjklRrYvKi8xd7p.E6gLLHBp6YJCvrbRey0/nFDbfuDmEZ2', 'Hugo Moreau', 'user', 15, '2026-07-12 12:17:21', NULL, NULL, NULL, NULL, NULL, NULL, 'Hugo', 'Moreau');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (53, 'dir@zephyr.demo', '$2y$13$ScSeEKOW45Rmaq6mzP6tL.uK.dttjllUVzOlDP4BGoMz2LdO41LoG', 'Nadia Girard', 'user', 16, '2026-07-12 12:17:23', NULL, NULL, NULL, NULL, NULL, NULL, 'Nadia', 'Girard');
INSERT INTO public."user" (id, email, password, display_name, role, team_id, created_at, avatar_path, reset_password_token, reset_password_expires_at, delete_account_token, delete_account_expires_at, google_id, first_name, last_name) VALUES (54, 'dev@zephyr.demo', '$2y$13$hLi4.RBIJNV6sM9Xv6RT5u8bF9.LaKJ10V1S1OqidCIr9y0Oj.lEq', 'Victor Dubois', 'user', 16, '2026-07-12 12:17:23', NULL, NULL, NULL, NULL, NULL, NULL, 'Victor', 'Dubois');


ALTER TABLE public."user" ENABLE TRIGGER ALL;

--
-- Data for Name: room_template; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.room_template DISABLE TRIGGER ALL;

INSERT INTO public.room_template (id, name, description, is_default, creator_id, entreprise_id) VALUES (11, 'Brainstorm', 'Idéal pour les sessions de brainstorming avec tableau blanc et chat', true, NULL, NULL);
INSERT INTO public.room_template (id, name, description, is_default, creator_id, entreprise_id) VALUES (12, 'Rédaction', 'Espace d''écriture collaborative avec éditeur et chat', true, NULL, NULL);
INSERT INTO public.room_template (id, name, description, is_default, creator_id, entreprise_id) VALUES (13, 'Réunion', 'Pour les réunions d''équipe avec visio, chat et calendrier', true, NULL, NULL);
INSERT INTO public.room_template (id, name, description, is_default, creator_id, entreprise_id) VALUES (14, 'Projet', 'Gestion de projet complète avec documents, tâches et fichiers', true, NULL, NULL);
INSERT INTO public.room_template (id, name, description, is_default, creator_id, entreprise_id) VALUES (15, 'Complet', 'Accès à tous les modules disponibles', true, NULL, NULL);


ALTER TABLE public.room_template ENABLE TRIGGER ALL;

--
-- Data for Name: room; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.room DISABLE TRIGGER ALL;

INSERT INTO public.room (id, name, is_temporary, visibility, created_at, creator_id, entreprise_id, template_id, layout_type) VALUES (28, 'Réunion hebdo Produit', false, 'enterprise', '2026-07-12 12:17:22', 46, 12, 13, 'tabs');
INSERT INTO public.room (id, name, is_temporary, visibility, created_at, creator_id, entreprise_id, template_id, layout_type) VALUES (29, 'Rédaction - Plan marketing Q3', false, 'enterprise', '2026-07-12 12:17:22', 49, 12, 12, 'tabs');
INSERT INTO public.room (id, name, is_temporary, visibility, created_at, creator_id, entreprise_id, template_id, layout_type) VALUES (30, 'Brainstorm - Idées nouvelle fonctionnalité', false, 'enterprise', '2026-07-12 12:17:23', 47, 12, 11, 'tabs');
INSERT INTO public.room (id, name, is_temporary, visibility, created_at, creator_id, entreprise_id, template_id, layout_type) VALUES (31, 'Projet - Refonte site vitrine', false, 'private', '2026-07-12 12:17:23', 46, 12, 14, 'tabs');
INSERT INTO public.room (id, name, is_temporary, visibility, created_at, creator_id, entreprise_id, template_id, layout_type) VALUES (32, 'Espace équipe Nexora', false, 'enterprise', '2026-07-12 12:17:23', 46, 12, 15, 'tabs');
INSERT INTO public.room (id, name, is_temporary, visibility, created_at, creator_id, entreprise_id, template_id, layout_type) VALUES (33, 'Kickoff Zephyr', false, 'enterprise', '2026-07-12 12:17:24', 53, 13, 13, 'tabs');


ALTER TABLE public.room ENABLE TRIGGER ALL;

--
-- Data for Name: calendar_event; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.calendar_event DISABLE TRIGGER ALL;

INSERT INTO public.calendar_event (id, title, description, event_type, start_date, end_date, is_all_day, recurrence, color, location, is_private, created_at, room_id, user_id, entreprise_id) VALUES (17, 'Point hebdo Produit', 'Avancement sprint en cours, blocages, priorités de la semaine.', 'meeting', '2026-07-14 10:00:00', '2026-07-14 10:30:00', false, NULL, '#6366F1', 'Visio - Room Réunion hebdo Produit', false, '2026-07-12 12:17:22', 28, 46, 12);
INSERT INTO public.calendar_event (id, title, description, event_type, start_date, end_date, is_all_day, recurrence, color, location, is_private, created_at, room_id, user_id, entreprise_id) VALUES (18, 'Revue de sprint', 'Démo des fonctionnalités livrées + rétrospective.', 'meeting', '2026-07-21 14:00:00', '2026-07-21 15:00:00', false, NULL, '#6366F1', 'Visio - Room Réunion hebdo Produit', false, '2026-07-12 12:17:22', 28, 46, 12);
INSERT INTO public.calendar_event (id, title, description, event_type, start_date, end_date, is_all_day, recurrence, color, location, is_private, created_at, room_id, user_id, entreprise_id) VALUES (19, 'Café virtuel équipe', 'Pause informelle, ouvert à tous.', 'other', '2026-07-17 16:00:00', '2026-07-17 16:30:00', false, NULL, '#F59E0B', NULL, false, '2026-07-12 12:17:23', 32, 46, 12);


ALTER TABLE public.calendar_event ENABLE TRIGGER ALL;

--
-- Data for Name: calendar_event_participant; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.calendar_event_participant DISABLE TRIGGER ALL;

INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (42, 'accepted', '2026-07-12 12:17:22', 17, 46);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (43, 'accepted', '2026-07-12 12:17:22', 17, 47);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (44, 'pending', '2026-07-12 12:17:22', 17, 50);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (45, 'accepted', '2026-07-12 12:17:22', 17, 51);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (46, 'accepted', '2026-07-12 12:17:22', 18, 46);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (47, 'accepted', '2026-07-12 12:17:22', 18, 47);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (48, 'accepted', '2026-07-12 12:17:22', 18, 50);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (49, 'accepted', '2026-07-12 12:17:23', 19, 46);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (50, 'pending', '2026-07-12 12:17:23', 19, 47);
INSERT INTO public.calendar_event_participant (id, status, created_at, event_id, user_id) VALUES (51, 'accepted', '2026-07-12 12:17:23', 19, 49);


ALTER TABLE public.calendar_event_participant ENABLE TRIGGER ALL;

--
-- Data for Name: document; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.document DISABLE TRIGGER ALL;

INSERT INTO public.document (id, content_html, yjs_state, updated_at, room_id) VALUES (15, '<h1>Plan marketing Q3</h1><p>Objectif : augmenter la notoriété de Synkro auprès des équipes produit et accélérer l''acquisition sur le segment PME.</p><h2>1. Axes de communication</h2><ul><li>Mettre en avant la collaboration temps réel (chat, tableau blanc, éditeur partagé).</li><li>Témoignages clients sur le gain de temps en réunion.</li><li>Comparatif face aux outils concurrents.</li></ul><h2>2. Calendrier</h2><p>Semaine 1-2 : refonte de la landing page. Semaine 3-4 : campagne emailing. Semaine 5-6 : webinar de lancement.</p><p><em>Sophie</em> : je propose qu''on commence par la landing page, j''ajoute une section ce soir.</p><p><em>Hugo</em> : ok pour moi, je m''occupe des visuels réseaux sociaux en parallèle.</p>', NULL, '2026-07-12 12:17:22', 29);
INSERT INTO public.document (id, content_html, yjs_state, updated_at, room_id) VALUES (16, '<h1>Refonte du site vitrine</h1><p>Brief : nouveau site vitrine, design épuré, mise en avant des cas d''usage clients.</p><p>Livraison visée : fin du sprint 4.</p>', NULL, '2026-07-12 12:17:23', 31);
INSERT INTO public.document (id, content_html, yjs_state, updated_at, room_id) VALUES (17, '<h1>Notes d''équipe</h1><p>Espace libre pour toute information transverse à partager avec l''équipe Nexora.</p>', NULL, '2026-07-12 12:17:23', 32);


ALTER TABLE public.document ENABLE TRIGGER ALL;

--
-- Data for Name: file_resource; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.file_resource DISABLE TRIGGER ALL;

INSERT INTO public.file_resource (id, file_name, file_path, mime_type, size, created_at, room_id, user_id, is_folder, parent_id) VALUES (21, 'cahier-des-charges.pdf', '/demo/projet/cahier-des-charges.pdf', 'application/pdf', 245760, '2026-07-12 12:17:23', 31, 47, false, NULL);
INSERT INTO public.file_resource (id, file_name, file_path, mime_type, size, created_at, room_id, user_id, is_folder, parent_id) VALUES (22, 'maquette-accueil.png', '/demo/projet/maquette-accueil.png', 'image/png', 1887432, '2026-07-12 12:17:23', 31, 51, false, NULL);
INSERT INTO public.file_resource (id, file_name, file_path, mime_type, size, created_at, room_id, user_id, is_folder, parent_id) VALUES (23, 'reglement-interieur.pdf', '/demo/complet/reglement-interieur.pdf', 'application/pdf', 98304, '2026-07-12 12:17:23', 32, 46, false, NULL);


ALTER TABLE public.file_resource ENABLE TRIGGER ALL;

--
-- Data for Name: invitation; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.invitation DISABLE TRIGGER ALL;

INSERT INTO public.invitation (id, email, token, status, created_at, expires_at, entreprise_id, invited_by_id, role) VALUES (10, 'nouveau@exemple.fr', '3c13daeccf85b982756592f2c2f8b371', 'pending', '2026-07-12 12:17:23', '2026-07-19 12:17:22', 12, 46, 'user');


ALTER TABLE public.invitation ENABLE TRIGGER ALL;

--
-- Data for Name: kanban_column; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.kanban_column DISABLE TRIGGER ALL;

INSERT INTO public.kanban_column (id, name, color, "position", room_id) VALUES (35, 'À faire', 'bg-slate-500', 0, 31);
INSERT INTO public.kanban_column (id, name, color, "position", room_id) VALUES (36, 'En cours', 'bg-blue-500', 1, 31);
INSERT INTO public.kanban_column (id, name, color, "position", room_id) VALUES (37, 'Terminé', 'bg-green-500', 2, 31);
INSERT INTO public.kanban_column (id, name, color, "position", room_id) VALUES (38, 'À faire', 'bg-slate-500', 0, 32);
INSERT INTO public.kanban_column (id, name, color, "position", room_id) VALUES (39, 'En cours', 'bg-blue-500', 1, 32);
INSERT INTO public.kanban_column (id, name, color, "position", room_id) VALUES (40, 'Terminé', 'bg-green-500', 2, 32);


ALTER TABLE public.kanban_column ENABLE TRIGGER ALL;

--
-- Data for Name: message; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.message DISABLE TRIGGER ALL;

INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (77, 'Salut tout le monde, on est prêts pour le point hebdo ?', '2026-07-11 10:17:22', 28, 47);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (78, 'Oui, j''ai terminé l''intégration du header, je montrerai ça rapidement.', '2026-07-11 10:22:22', 28, 50);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (79, 'Parfait. On commence dans 5 minutes, le lien visio est dans le module Visio de la room.', '2026-07-11 10:27:22', 28, 46);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (80, 'Je serai un peu en retard (2-3 min), je finis une maquette.', '2026-07-11 10:29:22', 28, 51);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (81, 'Pas de souci Emma, on t''attend.', '2026-07-11 10:30:22', 28, 47);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (82, 'J''ai commencé le plan marketing Q3 dans le document, n''hésitez pas à compléter.', '2026-07-09 12:17:22', 29, 49);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (83, 'Top, je rajoute la partie visuels réseaux sociaux.', '2026-07-09 12:27:22', 29, 52);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (84, 'Merci ! On vise une validation vendredi.', '2026-07-09 12:42:22', 29, 49);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (85, 'Session brainstorm sur les prochaines fonctionnalités, allez-y avec le tableau blanc !', '2026-07-07 12:17:22', 30, 47);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (86, 'Je pose quelques idées de widgets pour le dashboard.', '2026-07-07 12:22:22', 30, 51);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (87, 'On pourrait aussi ajouter des réactions emoji sur les messages du chat.', '2026-07-07 12:29:22', 30, 50);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (88, 'Bonnes idées, je regroupe tout ça dans le tableau blanc.', '2026-07-07 12:37:22', 30, 47);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (89, 'Room créée pour piloter la refonte du site vitrine. Accès restreint à l''équipe Produit.', '2026-07-06 12:17:22', 31, 46);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (90, 'Cahier des charges déposé dans les fichiers, à valider avant vendredi.', '2026-07-08 12:17:22', 31, 47);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (91, 'Première version des maquettes ajoutée également.', '2026-07-10 12:17:22', 31, 51);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (92, 'Bienvenue dans l''espace d''équipe Nexora, tous les modules sont dispos ici !', '2026-07-05 12:17:22', 32, 46);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (93, 'Merci pour l''accès, tout est très clair.', '2026-07-05 12:47:22', 32, 48);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (94, 'Bienvenue sur Synkro ! On centralise nos échanges ici.', '2026-07-11 12:17:22', 33, 53);
INSERT INTO public.message (id, content, created_at, room_id, user_id) VALUES (95, 'Nickel, je regarde les modules disponibles.', '2026-07-11 12:22:22', 33, 54);


ALTER TABLE public.message ENABLE TRIGGER ALL;

--
-- Data for Name: module; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.module DISABLE TRIGGER ALL;

INSERT INTO public.module (id, name, code, description) VALUES (24, 'Éditeur', 'editor', 'Éditeur de texte collaboratif en temps réel avec TipTap et Yjs');
INSERT INTO public.module (id, name, code, description) VALUES (25, 'Tableau blanc', 'whiteboard', 'Espace de dessin collaboratif pour brainstorming et schémas');
INSERT INTO public.module (id, name, code, description) VALUES (26, 'Chat', 'chat', 'Messagerie instantanée en temps réel');
INSERT INTO public.module (id, name, code, description) VALUES (27, 'Visioconférence', 'video', 'Appels audio et vidéo via WebRTC');
INSERT INTO public.module (id, name, code, description) VALUES (28, 'Fichiers', 'files', 'Partage et gestion de fichiers');
INSERT INTO public.module (id, name, code, description) VALUES (29, 'Tâches', 'tasks', 'Tableau Kanban pour la gestion des tâches');
INSERT INTO public.module (id, name, code, description) VALUES (30, 'Calendrier', 'calendar', 'Agenda partagé avec réunions, absences et rappels');


ALTER TABLE public.module ENABLE TRIGGER ALL;

--
-- Data for Name: module_room; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.module_room DISABLE TRIGGER ALL;

INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (100, NULL, 28, 27, 0);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (101, NULL, 28, 26, 1);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (102, NULL, 28, 30, 2);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (103, NULL, 29, 24, 0);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (104, NULL, 29, 26, 1);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (105, NULL, 30, 25, 0);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (106, NULL, 30, 26, 1);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (107, NULL, 31, 24, 0);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (108, NULL, 31, 29, 1);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (109, NULL, 31, 28, 2);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (110, NULL, 31, 26, 3);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (111, NULL, 32, 24, 0);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (112, NULL, 32, 25, 1);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (113, NULL, 32, 26, 2);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (114, NULL, 32, 27, 3);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (115, NULL, 32, 28, 4);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (116, NULL, 32, 29, 5);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (117, NULL, 32, 30, 6);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (118, NULL, 33, 27, 0);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (119, NULL, 33, 26, 1);
INSERT INTO public.module_room (id, config_json, room_id, module_id, display_order) VALUES (120, NULL, 33, 30, 2);


ALTER TABLE public.module_room ENABLE TRIGGER ALL;

--
-- Data for Name: task; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.task DISABLE TRIGGER ALL;

INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (36, 'Rédiger le cahier des charges', 'Cadrage des besoins et périmètre fonctionnel.', 0, '2026-07-12 12:17:23', 31, 47, 5, 37, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (37, 'Maquettes UI de la page d''accueil', 'Wireframes + maquettes haute-fidélité sur Figma.', 0, '2026-07-12 12:17:23', 31, 51, 8, 36, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (38, 'Intégration du header responsive', 'Header sticky avec menu mobile.', 1, '2026-07-12 12:17:23', 31, 50, 3, 36, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (39, 'Mettre en place le monitoring', 'Uptime + alertes sur le futur site.', 0, '2026-07-12 12:17:23', 31, 52, 2, 35, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (40, 'Rédiger les tests d''acceptation', NULL, 1, '2026-07-12 12:17:23', 31, NULL, NULL, 35, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (41, 'Organiser le pot de départ de Théo', NULL, 0, '2026-07-12 12:17:23', 32, 49, 1, 38, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (42, 'Mettre à jour le trombinoscope', NULL, 0, '2026-07-12 12:17:23', 32, 52, 1, 39, 'active');
INSERT INTO public.task (id, title, description, "position", created_at, room_id, assigned_to_id, estimation, column_id, type) VALUES (43, 'Renouveler les licences logicielles', NULL, 0, '2026-07-12 12:17:23', 32, 46, 2, 40, 'active');


ALTER TABLE public.task ENABLE TRIGGER ALL;

--
-- Data for Name: team_room_permission; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.team_room_permission DISABLE TRIGGER ALL;

INSERT INTO public.team_room_permission (id, role, room_id, team_id) VALUES (5, 'editor', 31, 14);


ALTER TABLE public.team_room_permission ENABLE TRIGGER ALL;

--
-- Data for Name: template_module; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.template_module DISABLE TRIGGER ALL;

INSERT INTO public.template_module (id, template_id, module_id) VALUES (55, 11, 25);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (56, 11, 26);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (57, 12, 24);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (58, 12, 26);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (59, 13, 27);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (60, 13, 26);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (61, 13, 30);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (62, 14, 24);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (63, 14, 29);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (64, 14, 28);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (65, 14, 26);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (66, 15, 24);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (67, 15, 25);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (68, 15, 26);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (69, 15, 27);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (70, 15, 28);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (71, 15, 29);
INSERT INTO public.template_module (id, template_id, module_id) VALUES (72, 15, 30);


ALTER TABLE public.template_module ENABLE TRIGGER ALL;

--
-- Data for Name: user_entreprise; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.user_entreprise DISABLE TRIGGER ALL;

INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (46, 12, 'owner');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (47, 12, 'editor');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (48, 12, 'user');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (49, 12, 'editor');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (50, 12, 'editor');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (51, 12, 'user');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (52, 12, 'user');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (53, 13, 'owner');
INSERT INTO public.user_entreprise (user_id, entreprise_id, role) VALUES (54, 13, 'editor');


ALTER TABLE public.user_entreprise ENABLE TRIGGER ALL;

--
-- Data for Name: user_room_permission; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.user_room_permission DISABLE TRIGGER ALL;

INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (37, 28, 46);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (38, 29, 49);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (39, 30, 47);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (40, 31, 46);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (41, 31, 47);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (42, 31, 48);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (43, 32, 46);
INSERT INTO public.user_room_permission (id, room_id, user_id) VALUES (44, 33, 53);


ALTER TABLE public.user_room_permission ENABLE TRIGGER ALL;

--
-- Data for Name: whiteboard; Type: TABLE DATA; Schema: public; Owner: -
--

ALTER TABLE public.whiteboard DISABLE TRIGGER ALL;

INSERT INTO public.whiteboard (id, yjs_state, strokes, updated_at, room_id) VALUES (10, NULL, '[]', '2026-07-12 12:17:23', 30);
INSERT INTO public.whiteboard (id, yjs_state, strokes, updated_at, room_id) VALUES (11, NULL, '[]', '2026-07-12 12:17:23', 32);


ALTER TABLE public.whiteboard ENABLE TRIGGER ALL;

--
-- Name: calendar_event_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.calendar_event_id_seq', 19, true);


--
-- Name: calendar_event_participant_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.calendar_event_participant_id_seq', 51, true);


--
-- Name: document_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.document_id_seq', 17, true);


--
-- Name: entreprise_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.entreprise_id_seq', 13, true);


--
-- Name: file_resource_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.file_resource_id_seq', 23, true);


--
-- Name: invitation_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.invitation_id_seq', 10, true);


--
-- Name: kanban_column_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.kanban_column_id_seq', 40, true);


--
-- Name: message_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.message_id_seq', 95, true);


--
-- Name: module_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.module_id_seq', 30, true);


--
-- Name: module_room_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.module_room_id_seq', 120, true);


--
-- Name: room_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.room_id_seq', 33, true);


--
-- Name: room_template_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.room_template_id_seq', 15, true);


--
-- Name: task_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.task_id_seq', 43, true);


--
-- Name: team_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.team_id_seq', 16, true);


--
-- Name: team_room_permission_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.team_room_permission_id_seq', 5, true);


--
-- Name: template_module_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.template_module_id_seq', 72, true);


--
-- Name: user_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.user_id_seq', 54, true);


--
-- Name: user_room_permission_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.user_room_permission_id_seq', 44, true);


--
-- Name: whiteboard_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.whiteboard_id_seq', 11, true);


--
-- PostgreSQL database dump complete
--

\unrestrict c0eED1WbdHE7bzSuLVLBJfzWeh6VM2NslP8ldC8BWoU96OgqCCzwBuxsPSsBmTD


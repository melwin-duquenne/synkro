<?php

namespace App\Exception;

final class ErrorMessage
{
    // Auth
    const AUTH_REQUIRED = 'Vous devez être connecté pour effectuer cette action';
    const INVALID_CREDENTIALS = 'Email ou mot de passe incorrect';

    // Accès
    const ACCESS_DENIED = 'Vous n\'avez pas les droits pour effectuer cette action';
    const ADMIN_REQUIRED = 'Droits administrateur requis';
    const OWNER_OR_ADMIN_REQUIRED = 'Droits propriétaire ou administrateur requis';

    // Salon (Room)
    const ROOM_NOT_FOUND = 'Salon introuvable';
    const ROOM_CREATE_REQUIRES_ENTREPRISE = 'Vous devez être associé à une entreprise pour créer un salon';
    const ROOM_CREATE_REQUIRES_EDITOR = 'Vous devez avoir le rôle éditeur ou supérieur pour créer un salon';
    const ROOM_EDIT_DENIED = 'Vous n\'avez pas la permission de modifier ce salon';
    const ROOM_DELETE_DENIED = 'Vous n\'avez pas la permission de supprimer ce salon';
    const ROOM_ID_REQUIRED = 'Identifiant du salon requis';
    const ROOM_PRIVATE_MEMBERS_ONLY = 'Les membres ne peuvent être gérés que pour les salons privés';
    const ROOM_MANAGE_MEMBERS_DENIED = 'Vous n\'avez pas la permission de gérer les membres de ce salon';

    // Tâche / Kanban
    const TASK_NOT_FOUND = 'Tâche introuvable';
    const KANBAN_COLUMN_NOT_FOUND = 'Colonne introuvable';
    const KANBAN_COLUMN_INVALID = 'Cette colonne n\'appartient pas à ce salon';
    const KANBAN_NO_COLUMN = 'Ce salon n\'a pas encore de colonnes Kanban. Activez le module tâches.';
    const KANBAN_COLUMN_HAS_TASKS = 'Impossible de supprimer cette colonne : elle contient des tâches actives. Déplacez ou archivez-les d\'abord';

    // Calendrier
    const EVENT_NOT_FOUND = 'Événement introuvable';
    const EVENT_EDIT_DENIED = 'Vous ne pouvez modifier que vos propres événements';
    const EVENT_DELETE_DENIED = 'Vous ne pouvez supprimer que vos propres événements';
    const EVENT_PRIVATE = 'Cet événement est privé';
    const EVENT_DUPLICATE = 'Un événement identique existe déjà';
    const EVENT_SAVE_ERROR = 'Une erreur est survenue lors de la sauvegarde de l\'événement';
    const EVENT_REQUIRES_ENTREPRISE = 'Vous devez appartenir à une entreprise pour créer un événement';
    const ROOM_ACCESS_DENIED = 'Vous n\'avez pas accès à ce salon';

    // Invitation
    const INVITATION_NOT_FOUND = 'Invitation introuvable';
    const INVITATION_INVALID = 'Lien d\'invitation invalide ou expiré';
    const INVITATION_ALREADY_USED = 'Cette invitation a déjà été utilisée';
    const INVITATION_EXPIRED = 'Cette invitation a expiré';
    const INVITATION_DUPLICATE = 'Une invitation est déjà en attente pour cet email';
    const INVITATION_REQUIRES_ENTREPRISE = 'Vous devez appartenir à une entreprise pour envoyer des invitations';
    const INVITATION_USER_ALREADY_MEMBER = 'Cet utilisateur est déjà membre de votre entreprise';
    const INVITATION_WRONG_ENTREPRISE = 'Impossible de gérer les invitations d\'une autre entreprise';

    // Fichiers
    const FILE_NOT_FOUND = 'Fichier introuvable';
    const FILE_NOT_ON_DISK = 'Fichier introuvable sur le serveur';
    const FILE_CANNOT_DOWNLOAD_FOLDER = 'Impossible de télécharger un dossier';
    const FILE_CIRCULAR_MOVE = 'Impossible de déplacer un dossier dans lui-même ou dans un de ses sous-dossiers';
    const FILE_NAME_CONFLICT = 'Un fichier ou dossier portant ce nom existe déjà à cet emplacement';
    const FILE_INVALID_PARENT = 'Dossier parent introuvable';
    const FILE_NO_FILE_UPLOADED = 'Aucun fichier reçu';
    const FILE_TOO_LARGE = 'Fichier trop volumineux. Taille maximale : 100 Mo';
    const FILE_INVALID_TYPE = 'Type de fichier non autorisé';

    // Avatar
    const AVATAR_NO_FILE = 'Aucun fichier reçu';
    const AVATAR_TOO_LARGE = 'Fichier trop volumineux. Taille maximale : 2 Mo';
    const AVATAR_INVALID_TYPE = 'Type de fichier non autorisé. Types acceptés : JPEG, PNG, WebP, GIF';

    // Utilisateur / Compte
    const USER_NOT_FOUND = 'Utilisateur introuvable';
    const USER_EMAIL_TAKEN = 'Cet email est déjà utilisé';
    const USER_SELF_ROLE_CHANGE = 'Vous ne pouvez pas modifier votre propre rôle';
    const USER_SELF_DELETE = 'Vous ne pouvez pas supprimer votre propre compte depuis cette interface';
    const USER_ADMIN_DELETE_ADMIN_ONLY = 'Seuls les administrateurs peuvent supprimer d\'autres administrateurs';
    const USER_ROLE_NOT_ASSIGNABLE = 'Vous ne pouvez pas attribuer ce rôle';
    const USER_ADMIN_ROLE_ADMIN_ONLY = 'Seuls les administrateurs peuvent attribuer le rôle administrateur';
    const USER_WRONG_ENTREPRISE = 'Cet utilisateur n\'appartient pas à votre entreprise';
    const USER_INVALID_TEAM = 'Équipe introuvable';

    // Entreprise
    const ENTREPRISE_REQUIRED = 'Vous n\'appartenez à aucune entreprise';

    // Token (reset / suppression)
    const TOKEN_INVALID = 'Lien invalide ou expiré';
    const TOKEN_EXPIRED = 'Ce lien a expiré, veuillez en demander un nouveau';

    // Générique
    const INVALID_DATA = 'Données invalides';
    const SERVER_ERROR = 'Une erreur inattendue est survenue, veuillez réessayer';
}

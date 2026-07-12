<?php

namespace App\Security;

use App\Entity\Room;
use App\Entity\User;

class RoomAccessChecker
{
    /**
     * Check if user can access (view) the room
     */
    public function canAccess(User $user, Room $room): bool
    {
        // Must be a member of the room's enterprise
        if ($room->getEntreprise() === null || !$user->hasEntreprise($room->getEntreprise())) {
            return false;
        }

        // Creator always has access
        if ($room->getCreator() === $user) {
            return true;
        }

        // Admin can access all rooms in their enterprise
        if ($user->getRoleInEntreprise($room->getEntreprise()) === User::ROLE_ADMIN) {
            return true;
        }

        // Check enterprise visibility
        if ($room->getVisibility() === Room::VISIBILITY_ENTERPRISE) {
            return true;
        }

        // Check private room permissions
        foreach ($room->getUserPermissions() as $permission) {
            if ($permission->getUser() === $user) {
                return true;
            }
        }

        // Check team permissions
        if ($user->getTeam()) {
            foreach ($room->getTeamPermissions() as $permission) {
                if ($permission->getTeam() === $user->getTeam()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if user can edit the room (editor+ or creator)
     */
    public function canEdit(User $user, Room $room): bool
    {
        if (!$this->canAccess($user, $room)) {
            return false;
        }

        // Creator can always edit
        if ($room->getCreator() === $user) {
            return true;
        }

        // User with editor role or higher can edit
        if ($room->getEntreprise() !== null) {
            return $user->isAtLeastInEntreprise($room->getEntreprise(), User::ROLE_EDITOR);
        }
        return $user->isAtLeast(User::ROLE_EDITOR);
    }

    /**
     * Check if user can delete the room (editor+ or creator)
     */
    public function canDelete(User $user, Room $room): bool
    {
        // Creator can always delete
        if ($room->getCreator() === $user) {
            return true;
        }

        // User with editor role or higher can delete
        if ($room->getEntreprise() !== null) {
            return $user->isAtLeastInEntreprise($room->getEntreprise(), User::ROLE_EDITOR);
        }
        return $user->isAtLeast(User::ROLE_EDITOR);
    }

    /**
     * Check if user can manage members of a private room (editor+)
     */
    public function canManageMembers(User $user, Room $room): bool
    {
        if (!$this->canAccess($user, $room)) {
            return false;
        }

        // Only private rooms have manageable members
        if ($room->getVisibility() !== Room::VISIBILITY_PRIVATE) {
            return false;
        }

        // User with editor role or higher can manage members
        if ($room->getEntreprise() !== null) {
            return $user->isAtLeastInEntreprise($room->getEntreprise(), User::ROLE_EDITOR);
        }
        return $user->isAtLeast(User::ROLE_EDITOR);
    }

    /**
     * Check if user can create rooms (editor+) — requires enterprise context
     */
    public function canCreateRoom(User $user): bool
    {
        return $user->isAtLeast(User::ROLE_EDITOR);
    }
}

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
        // Must be in the same enterprise
        if ($user->getEntreprise() !== $room->getEntreprise()) {
            return false;
        }

        // Creator always has access
        if ($room->getCreator() === $user) {
            return true;
        }

        // Admin can access all rooms in their enterprise
        if ($user->getRole() === User::ROLE_ADMIN) {
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
        return $user->isAtLeast(User::ROLE_EDITOR);
    }

    /**
     * Check if user can create rooms (editor+)
     */
    public function canCreateRoom(User $user): bool
    {
        return $user->isAtLeast(User::ROLE_EDITOR);
    }
}

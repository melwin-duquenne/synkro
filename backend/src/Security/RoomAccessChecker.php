<?php

namespace App\Security;

use App\Entity\Room;
use App\Entity\User;
use App\Entity\UserRoomPermission;

class RoomAccessChecker
{
    public function canAccess(User $user, Room $room): bool
    {
        // Creator always has access
        if ($room->getCreator() === $user) {
            return true;
        }

        // Check enterprise visibility
        if ($room->getVisibility() === Room::VISIBILITY_ENTERPRISE) {
            return $user->getEntreprise() === $room->getEntreprise();
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

    public function canDelete(User $user, Room $room): bool
    {
        return $room->getCreator() === $user || $user->getRole() === 'admin';
    }

    public function getPermissionRole(User $user, Room $room): ?string
    {
        if ($room->getCreator() === $user) {
            return UserRoomPermission::ROLE_OWNER;
        }

        foreach ($room->getUserPermissions() as $permission) {
            if ($permission->getUser() === $user) {
                return $permission->getRole();
            }
        }

        if ($user->getTeam()) {
            foreach ($room->getTeamPermissions() as $permission) {
                if ($permission->getTeam() === $user->getTeam()) {
                    return $permission->getRole();
                }
            }
        }

        return null;
    }
}

<?php

namespace App\Dto\Room;

final class ManageRoomMembersInput
{
    /** @var int[] User IDs to add */
    public array $addUserIds = [];

    /** @var int[] User IDs to remove */
    public array $removeUserIds = [];
}

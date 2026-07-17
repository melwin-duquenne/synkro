<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useRoomsStore } from "@/stores/rooms";
import { useAuthStore } from "@/stores/auth";
import type { Room, RoomMember } from "@/types";
import { isAtLeast, canManageRoomMembers } from "@/utils/permissions";

const props = defineProps<{
  room: Room;
}>();

const emit = defineEmits<{
  close: [];
  updated: [room: Room];
}>();

const roomsStore = useRoomsStore();
const authStore = useAuthStore();

const activeTab = ref<"general" | "members">("general");
const loading = ref(false);
const saving = ref(false);

// General tab
const roomName = ref("");
const roomVisibility = ref<"enterprise" | "private">("enterprise");
const showVisibilityWarning = ref(false);

// Members tab
const members = ref<RoomMember[]>([]);
const memberSearch = ref("");
const showMemberDropdown = ref(false);

const isCreator = computed(() => authStore.user?.id === props.room.creator.id);

const canEdit = computed(() => {
  return isCreator.value || isAtLeast(authStore.user?.role || "user", "editor");
});

const canManageMembers = computed(() => {
  if (props.room.visibility !== "private") return false;
  return canManageRoomMembers(authStore.user?.role || "user");
});

const hasChanges = computed(() => {
  return (
    roomName.value !== props.room.name ||
    roomVisibility.value !== props.room.visibility
  );
});

const filteredUsers = computed(() => {
  const memberIds = members.value.map((m) => m.id);
  let users = roomsStore.enterpriseUsers.filter(
    (u) => u.id !== authStore.user?.id && !memberIds.includes(u.id),
  );
  if (memberSearch.value.trim()) {
    const search = memberSearch.value.toLowerCase();
    users = users.filter(
      (u) =>
        u.displayName.toLowerCase().includes(search) ||
        u.email.toLowerCase().includes(search),
    );
  }
  return users;
});

async function loadMembers() {
  if (props.room.visibility === "private") {
    loading.value = true;
    members.value = await roomsStore.fetchRoomMembers(props.room.id);
    loading.value = false;
  }
}

async function loadEnterpriseUsers() {
  if (roomsStore.enterpriseUsers.length === 0) {
    await roomsStore.fetchEnterpriseUsers();
  }
}

function handleVisibilityChange() {
  if (
    props.room.visibility === "enterprise" &&
    roomVisibility.value === "private"
  ) {
    showVisibilityWarning.value = true;
  } else if (
    props.room.visibility === "private" &&
    roomVisibility.value === "enterprise"
  ) {
    showVisibilityWarning.value = true;
  } else {
    showVisibilityWarning.value = false;
  }
}

async function saveGeneralSettings() {
  if (!hasChanges.value || !canEdit.value) return;

  saving.value = true;
  const updatedRoom = await roomsStore.updateRoom(props.room.id, {
    name: roomName.value,
    visibility: roomVisibility.value,
  });
  saving.value = false;

  if (updatedRoom) {
    showVisibilityWarning.value = false;
    emit("updated", updatedRoom);
  }
}

async function addMember(userId: number) {
  const result = await roomsStore.manageRoomMembers(props.room.id, {
    addUserIds: [userId],
  });
  if (result) {
    members.value = result;
  }
  memberSearch.value = "";
  showMemberDropdown.value = false;
}

function hideMemberDropdown() {
  setTimeout(() => (showMemberDropdown.value = false), 200);
}

async function removeMember(userId: number) {
  const member = members.value.find((m) => m.id === userId);
  if (!member || member.isCreator) return;

  const result = await roomsStore.manageRoomMembers(props.room.id, {
    removeUserIds: [userId],
  });
  if (result) {
    members.value = result;
  }
}

watch(() => roomVisibility.value, handleVisibilityChange);

onMounted(() => {
  roomName.value = props.room.name;
  roomVisibility.value = props.room.visibility as "enterprise" | "private";
  loadMembers();
  loadEnterpriseUsers();
});
</script>

<template>
  <dialog class="modal modal-open">
    <div class="modal-box max-w-2xl room-settings-modal">
      <div class="room-settings-header">
        <div class="room-settings-icon" aria-hidden="true">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-[#e7f0fa]">
            Paramètres de la room
          </h3>
          <p class="text-sm text-[#8ea4be]">
            Ajustez l'accès, le nom et les membres de la room
          </p>
        </div>
      </div>

      <!-- Tabs -->
      <div class="settings-tabs mb-6">
        <button
          class="settings-tab"
          :class="{ 'tab-active': activeTab === 'general' }"
          @click="activeTab = 'general'"
        >
          General
        </button>
        <button
          v-if="props.room.visibility === 'private'"
          class="settings-tab"
          :class="{ 'tab-active': activeTab === 'members' }"
          @click="activeTab = 'members'"
        >
          Membres
        </button>
      </div>

      <!-- General Tab -->
      <div v-if="activeTab === 'general'" class="space-y-5">
        <!-- Room Name -->
        <div class="section-card form-control">
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Nom de la room</span>
          </label>
          <input
            v-model="roomName"
            type="text"
            class="settings-input w-full"
            :disabled="!canEdit"
          />
        </div>

        <!-- Visibility -->
        <div class="section-card form-control">
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Visibilité</span>
          </label>
          <div class="visibility-grid">
            <label class="visibility-option cursor-pointer gap-2">
              <input
                v-model="roomVisibility"
                type="radio"
                name="visibility"
                value="enterprise"
                class="radio radio-primary"
                :disabled="!canEdit"
              />
              <div>
                <span class="block font-medium text-[#e7f0fa]">Entreprise</span>
                <span class="text-xs text-[#8ea4be]">Tous les membres</span>
              </div>
            </label>
            <label class="visibility-option cursor-pointer gap-2">
              <input
                v-model="roomVisibility"
                type="radio"
                name="visibility"
                value="private"
                class="radio radio-primary"
                :disabled="!canEdit"
              />
              <div>
                <span class="block font-medium text-[#e7f0fa]">Privée</span>
                <span class="text-xs text-[#8ea4be]">Invitation requise</span>
              </div>
            </label>
          </div>
        </div>

        <!-- Visibility Change Warning -->
        <div v-if="showVisibilityWarning" class="warning-box">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          <div>
            <p
              v-if="
                props.room.visibility === 'enterprise' &&
                roomVisibility === 'private'
              "
            >
              En passant en mode prive, seul vous (le createur) aurez acces a la
              room. Vous devrez ajouter les membres manuellement.
            </p>
            <p
              v-else-if="
                props.room.visibility === 'private' &&
                roomVisibility === 'enterprise'
              "
            >
              En passant en mode entreprise, tous les membres de l'entreprise
              auront acces a la room.
            </p>
          </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end pt-1">
          <button
            class="btn-primary-custom"
            :class="{ loading: saving }"
            :disabled="!hasChanges || !canEdit || saving"
            @click="saveGeneralSettings"
          >
            {{ saving ? "Enregistrement..." : "Enregistrer" }}
          </button>
        </div>
      </div>

      <!-- Members Tab -->
      <div v-if="activeTab === 'members'" class="space-y-5">
        <div v-if="loading" class="flex justify-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else>
          <!-- Current Members List -->
          <div class="section-card form-control">
            <label class="label px-0">
              <span class="label-text text-[#bfd1e5]">Membres actuels</span>
              <span class="label-text-alt text-[#8ea4be]"
                >{{ members.length }} membre(s)</span
              >
            </label>
            <div class="members-list max-h-64 overflow-y-auto">
              <div
                v-for="member in members"
                :key="member.id"
                class="member-row"
              >
                <div class="flex items-center gap-3">
                  <div class="avatar placeholder">
                    <div class="member-avatar rounded-full w-10">
                      <span>{{
                        member.displayName.charAt(0).toUpperCase()
                      }}</span>
                    </div>
                  </div>
                  <div>
                    <div class="font-medium text-[#e7f0fa]">
                      {{ member.displayName }}
                      <span v-if="member.isCreator" class="creator-pill ml-2"
                        >Créateur</span
                      >
                    </div>
                    <div v-if="member.email" class="text-xs text-[#8ea4be]">
                      {{ member.email }}
                    </div>
                  </div>
                </div>
                <button
                  v-if="!member.isCreator && canManageMembers"
                  class="remove-member-btn"
                  title="Retirer ce membre"
                  @click="removeMember(member.id)"
                >
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Add Members Section -->
          <div v-if="canManageMembers" class="section-card form-control">
            <label class="label px-0">
              <span class="label-text text-[#bfd1e5]">Ajouter des membres</span>
            </label>
            <div class="relative">
              <input
                v-model="memberSearch"
                type="text"
                placeholder="Rechercher un membre a ajouter..."
                class="settings-input w-full"
                @focus="showMemberDropdown = true"
                @blur="hideMemberDropdown"
              />

              <!-- Dropdown -->
              <div
                v-if="showMemberDropdown && filteredUsers.length > 0"
                class="members-dropdown absolute z-10 w-full mt-1 max-h-48 overflow-y-auto"
              >
                <button
                  v-for="user in filteredUsers"
                  :key="user.id"
                  type="button"
                  class="members-dropdown-item"
                  @mousedown.prevent="addMember(user.id)"
                >
                  <div>
                    <div class="font-medium text-[#e7f0fa]">
                      {{ user.displayName }}
                    </div>
                    <div class="text-xs text-[#8ea4be]">{{ user.email }}</div>
                  </div>
                  <svg
                    xmlns="http://www.w3.org/2000/svg"
                    class="h-5 w-5 text-[#7db8ea]"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 4v16m8-8H4"
                    />
                  </svg>
                </button>
              </div>

              <div
                v-if="
                  showMemberDropdown &&
                  filteredUsers.length === 0 &&
                  memberSearch
                "
                class="members-dropdown-empty absolute z-10 w-full mt-1 p-4 text-center"
              >
                Aucun membre trouve
              </div>
            </div>
          </div>

          <div v-if="!canManageMembers" class="info-box">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <span
              >Vous devez avoir le rôle éditeur ou supérieur pour gérer les
              membres.</span
            >
          </div>
        </template>
      </div>

      <!-- Modal Actions -->
      <div class="modal-action settings-footer">
        <button class="btn-soft" @click="emit('close')">Fermer</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="emit('close')">close</button>
    </form>
  </dialog>
</template>

<style scoped>
.room-settings-modal {
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background:
    radial-gradient(
      120% 120% at 0% 0%,
      rgba(64, 142, 214, 0.12),
      transparent 50%
    ),
    linear-gradient(165deg, rgba(10, 22, 40, 0.96), rgba(14, 29, 49, 0.98));
  color: #dbe6f2;
}

.room-settings-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}

.room-settings-icon {
  width: 2.6rem;
  height: 2.6rem;
  border-radius: 0.95rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #8bc2ef;
  background: rgba(64, 142, 214, 0.18);
  border: 1px solid rgba(91, 163, 232, 0.35);
  flex-shrink: 0;
}

.settings-tabs {
  display: inline-flex;
  gap: 0.5rem;
  padding: 0.35rem;
  border-radius: 999px;
  background: rgba(12, 26, 44, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.settings-tab {
  padding: 0.55rem 1rem;
  border-radius: 999px;
  color: #8ea4be;
  transition: 0.2s ease;
}

.settings-tab.tab-active {
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
  box-shadow: 0 8px 18px rgba(55, 125, 189, 0.24);
}

.section-card {
  padding: 1rem;
  border-radius: 16px;
  background: rgba(18, 38, 61, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.settings-input {
  width: 100%;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(10, 21, 36, 0.9);
  color: #e7f0fa;
  padding: 0.72rem 0.85rem;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.settings-input:focus {
  border-color: rgba(91, 163, 232, 0.75);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.18);
}

.visibility-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 0.75rem;
}

.visibility-option {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.85rem 0.95rem;
  border-radius: 14px;
  background: rgba(12, 26, 44, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.08);
}

.warning-box {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 0.9rem 1rem;
  border-radius: 14px;
  background: rgba(94, 66, 18, 0.2);
  color: #ffe4b3;
}

.btn-primary-custom {
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
  border-radius: 10px;
  padding: 0.65rem 1rem;
  transition: 0.2s ease;
}

.btn-primary-custom:hover:enabled {
  filter: brightness(1.06);
  transform: translateY(-1px);
}

.btn-primary-custom:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn-soft {
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(15, 31, 53, 0.78);
  color: #c8d8ea;
  padding: 0.55rem 0.95rem;
  transition: 0.2s ease;
}

.btn-soft:hover {
  color: #e7f0fa;
  border-color: rgba(91, 163, 232, 0.45);
}

.members-list {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.member-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.85rem 0.95rem;
  border-radius: 14px;
  background: rgba(12, 26, 44, 0.55);
}

.member-avatar {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #bfe0fa;
  background: rgba(64, 142, 214, 0.22);
  border: 1px solid rgba(91, 163, 232, 0.42);
}

.creator-pill {
  display: inline-flex;
  align-items: center;
  padding: 0.15rem 0.45rem;
  border-radius: 999px;
  background: rgba(91, 163, 232, 0.2);
  color: #cfe8ff;
  font-size: 0.7rem;
}

.remove-member-btn {
  width: 2.1rem;
  height: 2.1rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(127, 29, 29, 0.16);
  color: #ff8f9a;
  border: 1px solid rgba(248, 113, 113, 0.22);
  transition: 0.2s ease;
}

.remove-member-btn:hover {
  background: rgba(214, 62, 82, 0.22);
  color: #ffd3d8;
}

.members-dropdown,
.members-dropdown-empty {
  border-radius: 14px;
  background: rgba(12, 26, 44, 0.96);
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 14px 32px rgba(0, 0, 0, 0.28);
}

.members-dropdown-item {
  width: 100%;
  padding: 0.8rem 0.95rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-align: left;
  transition: background-color 0.15s ease;
}

.members-dropdown-item:hover {
  background: rgba(22, 46, 73, 0.8);
}

.info-box {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 0.9rem 1rem;
  border-radius: 14px;
  background: rgba(13, 39, 66, 0.55);
  color: #bfd1e5;
}

.settings-footer {
  margin-top: 0.25rem;
  padding-top: 0.5rem;
  justify-content: flex-end;
}

@media (max-width: 640px) {
  .visibility-grid {
    grid-template-columns: 1fr;
  }

  .room-settings-header {
    align-items: flex-start;
  }
}
</style>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useRoomsStore } from '@/stores/rooms'
import { useAuthStore } from '@/stores/auth'
import type { Room, RoomMember } from '@/types'
import { isAtLeast, canManageRoomMembers } from '@/utils/permissions'

const props = defineProps<{
  room: Room
}>()

const emit = defineEmits<{
  close: []
  updated: [room: Room]
}>()

const roomsStore = useRoomsStore()
const authStore = useAuthStore()

const activeTab = ref<'general' | 'members'>('general')
const loading = ref(false)
const saving = ref(false)

// General tab
const roomName = ref('')
const roomVisibility = ref<'enterprise' | 'private'>('enterprise')
const showVisibilityWarning = ref(false)

// Members tab
const members = ref<RoomMember[]>([])
const memberSearch = ref('')
const showMemberDropdown = ref(false)

const isCreator = computed(() => authStore.user?.id === props.room.creator.id)

const canEdit = computed(() => {
  return isCreator.value || isAtLeast(authStore.user?.role || 'user', 'editor')
})

const canManageMembers = computed(() => {
  if (props.room.visibility !== 'private') return false
  return canManageRoomMembers(authStore.user?.role || 'user')
})

const hasChanges = computed(() => {
  return roomName.value !== props.room.name || roomVisibility.value !== props.room.visibility
})

const filteredUsers = computed(() => {
  const memberIds = members.value.map(m => m.id)
  let users = roomsStore.enterpriseUsers.filter(
    u => u.id !== authStore.user?.id && !memberIds.includes(u.id)
  )
  if (memberSearch.value.trim()) {
    const search = memberSearch.value.toLowerCase()
    users = users.filter(
      u => u.displayName.toLowerCase().includes(search) || u.email.toLowerCase().includes(search)
    )
  }
  return users
})

async function loadMembers() {
  if (props.room.visibility === 'private') {
    loading.value = true
    members.value = await roomsStore.fetchRoomMembers(props.room.id)
    loading.value = false
  }
}

async function loadEnterpriseUsers() {
  if (roomsStore.enterpriseUsers.length === 0) {
    await roomsStore.fetchEnterpriseUsers()
  }
}

function handleVisibilityChange() {
  if (props.room.visibility === 'enterprise' && roomVisibility.value === 'private') {
    showVisibilityWarning.value = true
  } else if (props.room.visibility === 'private' && roomVisibility.value === 'enterprise') {
    showVisibilityWarning.value = true
  } else {
    showVisibilityWarning.value = false
  }
}

async function saveGeneralSettings() {
  if (!hasChanges.value || !canEdit.value) return

  saving.value = true
  const updatedRoom = await roomsStore.updateRoom(props.room.id, {
    name: roomName.value,
    visibility: roomVisibility.value
  })
  saving.value = false

  if (updatedRoom) {
    showVisibilityWarning.value = false
    emit('updated', updatedRoom)
  }
}

async function addMember(userId: number) {
  const result = await roomsStore.manageRoomMembers(props.room.id, { addUserIds: [userId] })
  if (result) {
    members.value = result
  }
  memberSearch.value = ''
  showMemberDropdown.value = false
}

function hideMemberDropdown() {
  setTimeout(() => showMemberDropdown.value = false, 200)
}

async function removeMember(userId: number) {
  const member = members.value.find(m => m.id === userId)
  if (!member || member.isCreator) return

  const result = await roomsStore.manageRoomMembers(props.room.id, { removeUserIds: [userId] })
  if (result) {
    members.value = result
  }
}

watch(() => roomVisibility.value, handleVisibilityChange)

onMounted(() => {
  roomName.value = props.room.name
  roomVisibility.value = props.room.visibility as 'enterprise' | 'private'
  loadMembers()
  loadEnterpriseUsers()
})
</script>

<template>
  <dialog class="modal modal-open">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">Parametres de la room</h3>

      <!-- Tabs -->
      <div class="tabs tabs-boxed mb-6">
        <button
          class="tab"
          :class="{ 'tab-active': activeTab === 'general' }"
          @click="activeTab = 'general'"
        >
          General
        </button>
        <button
          v-if="props.room.visibility === 'private'"
          class="tab"
          :class="{ 'tab-active': activeTab === 'members' }"
          @click="activeTab = 'members'"
        >
          Membres
        </button>
      </div>

      <!-- General Tab -->
      <div v-if="activeTab === 'general'" class="space-y-6">
        <!-- Room Name -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Nom de la room</span>
          </label>
          <input
            v-model="roomName"
            type="text"
            class="input input-bordered w-full"
            :disabled="!canEdit"
          />
        </div>

        <!-- Visibility -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Visibilite</span>
          </label>
          <div class="flex gap-4">
            <label class="label cursor-pointer gap-2">
              <input
                v-model="roomVisibility"
                type="radio"
                name="visibility"
                value="enterprise"
                class="radio radio-primary"
                :disabled="!canEdit"
              />
              <span>Entreprise</span>
              <span class="text-xs text-base-content/60">(Tous les membres)</span>
            </label>
            <label class="label cursor-pointer gap-2">
              <input
                v-model="roomVisibility"
                type="radio"
                name="visibility"
                value="private"
                class="radio radio-primary"
                :disabled="!canEdit"
              />
              <span>Privee</span>
              <span class="text-xs text-base-content/60">(Invitation requise)</span>
            </label>
          </div>
        </div>

        <!-- Visibility Change Warning -->
        <div v-if="showVisibilityWarning" class="alert alert-warning">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <div>
            <p v-if="props.room.visibility === 'enterprise' && roomVisibility === 'private'">
              En passant en mode prive, seul vous (le createur) aurez acces a la room. Vous devrez ajouter les membres manuellement.
            </p>
            <p v-else-if="props.room.visibility === 'private' && roomVisibility === 'enterprise'">
              En passant en mode entreprise, tous les membres de l'entreprise auront acces a la room.
            </p>
          </div>
        </div>

        <!-- Save Button -->
        <div class="flex justify-end">
          <button
            class="btn btn-primary"
            :class="{ 'loading': saving }"
            :disabled="!hasChanges || !canEdit || saving"
            @click="saveGeneralSettings"
          >
            {{ saving ? 'Enregistrement...' : 'Enregistrer' }}
          </button>
        </div>
      </div>

      <!-- Members Tab -->
      <div v-if="activeTab === 'members'" class="space-y-6">
        <div v-if="loading" class="flex justify-center py-8">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <template v-else>
          <!-- Current Members List -->
          <div class="form-control">
            <label class="label">
              <span class="label-text">Membres actuels</span>
              <span class="label-text-alt">{{ members.length }} membre(s)</span>
            </label>
            <div class="space-y-2 max-h-64 overflow-y-auto">
              <div
                v-for="member in members"
                :key="member.id"
                class="flex items-center justify-between p-3 bg-base-200 rounded-lg"
              >
                <div class="flex items-center gap-3">
                  <div class="avatar placeholder">
                    <div class="bg-neutral text-neutral-content rounded-full w-10">
                      <span>{{ member.displayName.charAt(0).toUpperCase() }}</span>
                    </div>
                  </div>
                  <div>
                    <div class="font-medium">
                      {{ member.displayName }}
                      <span v-if="member.isCreator" class="badge badge-sm badge-primary ml-2">Createur</span>
                    </div>
                    <div v-if="member.email" class="text-xs text-base-content/60">{{ member.email }}</div>
                  </div>
                </div>
                <button
                  v-if="!member.isCreator && canManageMembers"
                  class="btn btn-ghost btn-sm btn-circle text-error"
                  title="Retirer ce membre"
                  @click="removeMember(member.id)"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Add Members Section -->
          <div v-if="canManageMembers" class="form-control">
            <label class="label">
              <span class="label-text">Ajouter des membres</span>
            </label>
            <div class="relative">
              <input
                v-model="memberSearch"
                type="text"
                placeholder="Rechercher un membre a ajouter..."
                class="input input-bordered w-full"
                @focus="showMemberDropdown = true"
                @blur="hideMemberDropdown"
              />

              <!-- Dropdown -->
              <div
                v-if="showMemberDropdown && filteredUsers.length > 0"
                class="absolute z-10 w-full mt-1 bg-base-200 border border-base-300 rounded-lg shadow-lg max-h-48 overflow-y-auto"
              >
                <button
                  v-for="user in filteredUsers"
                  :key="user.id"
                  type="button"
                  class="w-full px-4 py-2 text-left hover:bg-base-300 flex items-center justify-between"
                  @mousedown.prevent="addMember(user.id)"
                >
                  <div>
                    <div class="font-medium">{{ user.displayName }}</div>
                    <div class="text-xs text-base-content/60">{{ user.email }}</div>
                  </div>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                </button>
              </div>

              <div
                v-if="showMemberDropdown && filteredUsers.length === 0 && memberSearch"
                class="absolute z-10 w-full mt-1 bg-base-200 border border-base-300 rounded-lg shadow-lg p-4 text-center text-base-content/60"
              >
                Aucun membre trouve
              </div>
            </div>
          </div>

          <div v-if="!canManageMembers" class="alert alert-info">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Vous devez avoir le role editeur ou superieur pour gerer les membres.</span>
          </div>
        </template>
      </div>

      <!-- Modal Actions -->
      <div class="modal-action">
        <button class="btn" @click="emit('close')">Fermer</button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="emit('close')">close</button>
    </form>
  </dialog>
</template>

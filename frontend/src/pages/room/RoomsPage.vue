<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoomsStore } from '@/stores/rooms'
import { useAuthStore } from '@/stores/auth'
import CreateRoomModal from '@/components/room/CreateRoomModal.vue'

const roomsStore = useRoomsStore()
const authStore = useAuthStore()
const showCreateModal = ref(false)
const setupLoading = ref(false)

const hasEntreprise = computed(() => !!authStore.user?.entreprise)

// Filter out invalid rooms (missing required data)
const validRooms = computed(() => {
  return roomsStore.rooms.filter(room => room && room.id && room.name)
})

onMounted(() => {
  if (hasEntreprise.value) {
    roomsStore.fetchRooms()
  }
})

async function handleDelete(id: number) {
  if (confirm('Êtes-vous sûr de vouloir supprimer cette room ?')) {
    await roomsStore.deleteRoom(id)
  }
}

async function handleSetupEntreprise() {
  setupLoading.value = true
  const success = await authStore.setupEntreprise()
  setupLoading.value = false
  if (success) {
    roomsStore.fetchRooms()
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold">Rooms</h1>
      <button
        class="btn btn-primary"
        @click="showCreateModal = true"
        :disabled="!hasEntreprise"
      >
        + Nouvelle Room
      </button>
    </div>

    <!-- No entreprise warning -->
    <div v-if="!hasEntreprise" class="card bg-warning/10 border border-warning shadow-xl">
      <div class="card-body text-center py-12">
        <h2 class="text-xl font-semibold mb-2">Configuration requise</h2>
        <p class="text-base-content/70 mb-4">
          Vous devez configurer votre entreprise pour créer et accéder aux rooms.
        </p>
        <div class="card-actions justify-center">
          <button
            class="btn btn-primary"
            @click="handleSetupEntreprise"
            :class="{ 'loading': setupLoading }"
            :disabled="setupLoading"
          >
            {{ setupLoading ? 'Configuration...' : 'Configurer mon entreprise' }}
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="roomsStore.loading" class="flex justify-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="validRooms.length === 0" class="card bg-base-100 shadow-xl">
      <div class="card-body text-center py-12">
        <h2 class="text-xl font-semibold mb-2">Aucune room</h2>
        <p class="text-base-content/70 mb-4">Créez votre première room pour commencer à collaborer</p>
        <div class="card-actions justify-center">
          <button class="btn btn-primary" @click="showCreateModal = true">Créer une room</button>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div
        v-for="room in validRooms"
        :key="room.id"
        class="card bg-base-100 shadow-xl hover:shadow-2xl transition-shadow"
      >
        <div class="card-body">
          <div class="flex justify-between items-start">
            <router-link :to="`/room/${room.id}`" class="card-title hover:text-primary">
              {{ room.name }}
            </router-link>
            <div class="dropdown dropdown-end">
              <div tabindex="0" role="button" class="btn btn-ghost btn-xs">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                </svg>
              </div>
              <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-32">
                <li><a @click="handleDelete(room.id)" class="text-error">Supprimer</a></li>
              </ul>
            </div>
          </div>

          <div class="flex items-center gap-2 mt-1">
            <span
              class="badge badge-sm"
              :class="room.visibility === 'private' ? 'badge-warning' : 'badge-success'"
            >
              {{ room.visibility === 'private' ? 'Privée' : 'Entreprise' }}
            </span>
            <span v-if="room.isTemporary" class="badge badge-sm badge-ghost">Temporaire</span>
          </div>

          <p class="text-sm text-base-content/60 mt-2">
            Par {{ room.creator?.displayName || 'Inconnu' }}
          </p>

          <div v-if="room.moduleRooms?.length" class="flex flex-wrap gap-1 mt-3">
            <span
              v-for="mr in room.moduleRooms"
              :key="mr.id"
              class="badge badge-outline badge-sm"
            >
              {{ mr.module?.name || mr.module?.code || 'Module' }}
            </span>
          </div>

          <div class="card-actions justify-end mt-4">
            <router-link :to="`/room/${room.id}`" class="btn btn-primary btn-sm">
              Ouvrir
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Room Modal -->
    <CreateRoomModal :open="showCreateModal" @close="showCreateModal = false" />
  </div>
</template>

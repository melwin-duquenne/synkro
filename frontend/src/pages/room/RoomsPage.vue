<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { useRoomsStore } from "@/stores/rooms";
import { useAuthStore } from "@/stores/auth";
import CreateRoomModal from "@/components/room/CreateRoomModal.vue";

const route = useRoute();
const roomsStore = useRoomsStore();
const authStore = useAuthStore();
const showCreateModal = ref(false);
const setupLoading = ref(false);
const entrepriseName = ref("");

const hasEntreprise = computed(
  () => (authStore.user?.entreprises?.length ?? 0) > 0,
);

// Filter out invalid rooms (missing required data)
const validRooms = computed(() => {
  return roomsStore.rooms.filter((room) => room && room.id && room.name);
});

const roomCount = computed(() => validRooms.value.length);
const privateCount = computed(
  () => validRooms.value.filter((room) => room.visibility === "private").length,
);
const sharedCount = computed(
  () => validRooms.value.filter((room) => room.visibility !== "private").length,
);

onMounted(() => {
  if (hasEntreprise.value) {
    roomsStore.fetchRooms();
  }

  // Ouvrir automatiquement le modal si on vient du dashboard
  if (route.query.create === "true") {
    showCreateModal.value = true;
  }
});

async function handleDelete(id: number) {
  if (confirm("Êtes-vous sûr de vouloir supprimer cette room ?")) {
    await roomsStore.deleteRoom(id);
  }
}

async function handleSetupEntreprise() {
  if (!entrepriseName.value.trim()) return;
  setupLoading.value = true;
  const success = await authStore.setupEntreprise(entrepriseName.value.trim());
  setupLoading.value = false;
  if (success) {
    await authStore.fetchUser();
    roomsStore.fetchRooms();
  }
}
</script>

<template>
  <div class="space-y-6 min-h-screen">
    <div class="dashboard-card page-header p-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1
            class="text-3xl md:text-4xl font-bold text-[#e0e0e0] tracking-tight"
          >
            Rooms
          </h1>
          <p class="text-sm text-[#9db0c6] mt-1">
            Gérez vos espaces de collaboration et accédez rapidement à vos
            projets.
          </p>
        </div>
        <button
          class="btn btn-primary"
          @click="showCreateModal = true"
          :disabled="!hasEntreprise"
        >
          + Nouvelle Room
        </button>
      </div>

      <div
        v-if="hasEntreprise"
        class="mt-5 grid grid-cols-2 md:grid-cols-3 gap-3"
      >
        <div class="stat-chip">
          <p class="stat-label">Total</p>
          <p class="stat-value">{{ roomCount }}</p>
        </div>
        <div class="stat-chip">
          <p class="stat-label">Privées</p>
          <p class="stat-value">{{ privateCount }}</p>
        </div>
        <div class="stat-chip">
          <p class="stat-label">Entreprise</p>
          <p class="stat-value">{{ sharedCount }}</p>
        </div>
      </div>
    </div>

    <!-- No entreprise warning -->
    <div
      v-if="!hasEntreprise"
      class="dashboard-card bg-[#0a1628] border border-[#4115df]/20 rounded-lg p-6"
    >
      <div class="text-center py-12">
        <h2 class="text-xl font-semibold mb-2 text-[#e0e0e0]">
          Configuration requise
        </h2>
        <p class="text-[#b0b0b0] mb-6">
          Vous devez configurer votre entreprise pour créer et accéder aux
          rooms.
        </p>
        <div class="flex flex-col items-center gap-3 w-full max-w-sm mx-auto">
          <input
            v-model="entrepriseName"
            type="text"
            placeholder="Nom de votre entreprise"
            class="input input-bordered w-full"
            required
            @keyup.enter="handleSetupEntreprise"
          />
          <button
            class="btn btn-primary w-full"
            @click="handleSetupEntreprise"
            :disabled="setupLoading || !entrepriseName.trim()"
          >
            <span
              v-if="setupLoading"
              class="loading loading-spinner loading-sm"
            ></span>
            {{
              setupLoading ? "Configuration..." : "Configurer mon entreprise"
            }}
          </button>
        </div>
      </div>
    </div>

    <div v-else-if="roomsStore.loading" class="flex justify-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div
      v-else-if="validRooms.length === 0"
      class="dashboard-card rounded-lg p-8"
    >
      <div class="text-center py-12">
        <h2 class="text-xl font-semibold mb-2 text-[#e0e0e0]">Aucune room</h2>
        <p class="text-[#b0b0b0] mb-6">
          Créez votre première room pour commencer à collaborer
        </p>
        <div class="flex justify-center">
          <button class="btn btn-primary" @click="showCreateModal = true">
            Créer une room
          </button>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article
        v-for="room in validRooms"
        :key="room.id"
        class="dashboard-card room-card rounded-lg p-6"
      >
        <div class="card-body p-0">
          <div class="flex justify-between items-start">
            <div class="min-w-0">
              <router-link
                :to="{
                  name: 'room',
                  params: {
                    entrepriseSlug: $route.params.entrepriseSlug,
                    id: room.id,
                  },
                }"
                class="card-title room-title"
              >
                {{ room.name }}
              </router-link>
              <p class="text-xs text-[#8ea4be] mt-1 truncate">
                {{ room.moduleRooms?.length || 0 }} module{{
                  (room.moduleRooms?.length || 0) > 1 ? "s" : ""
                }}
                activé{{ (room.moduleRooms?.length || 0) > 1 ? "s" : "" }}
              </p>
            </div>
            <div class="dropdown dropdown-end">
              <div
                tabindex="0"
                role="button"
                class="btn btn-ghost btn-sm rounded-lg"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  class="w-5 h-5 stroke-[#e0e0e0]"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"
                  ></path>
                </svg>
              </div>
              <ul
                tabindex="0"
                class="dropdown-content z-1 menu p-2 shadow-lg bg-[#0a1628] rounded-lg w-40 border border-gray-600"
              >
                <li>
                  <a
                    @click="handleDelete(room.id)"
                    class="text-red-400 hover:bg-red-400/10"
                    >Supprimer</a
                  >
                </li>
              </ul>
            </div>
          </div>

          <div class="flex items-center gap-2 flex-wrap">
            <span
              class="px-3 py-1 rounded-full text-xs font-medium"
              :class="
                room.visibility === 'private'
                  ? 'bg-[#4115df]/20 text-[#4115df]'
                  : 'bg-[#2d7a3f]/20 text-[#6fdd9f]'
              "
            >
              {{
                room.visibility === "private" ? "🔒 Privée" : "🌐 Entreprise"
              }}
            </span>
            <span
              v-if="room.isTemporary"
              class="px-3 py-1 rounded-full text-xs font-medium bg-[#1a3a52] text-[#8ab4f8]"
              >⏱️ Temporaire</span
            >
          </div>

          <p class="text-sm text-[#b0b0b0] mt-1">
            Créée par
            <span class="text-[#8fb6ff]">{{
              room.creator?.displayName || "Inconnu"
            }}</span>
          </p>

          <div
            v-if="room.moduleRooms?.length"
            class="flex flex-wrap gap-2 pt-2"
          >
            <span
              v-for="mr in room.moduleRooms"
              :key="mr.id"
              class="px-2 py-1 rounded-md text-xs bg-[#112338] text-[#9ec1ff] border border-white/10"
            >
              {{ mr.module?.name || mr.module?.code || "Module" }}
            </span>
          </div>

          <div class="card-actions justify-end mt-4">
            <router-link
              :to="{
                name: 'room',
                params: {
                  entrepriseSlug: $route.params.entrepriseSlug,
                  id: room.id,
                },
              }"
              class="btn btn-primary btn-sm"
            >
              Ouvrir
            </router-link>
          </div>
        </div>
      </article>
    </div>

    <!-- Create Room Modal -->
    <CreateRoomModal :open="showCreateModal" @close="showCreateModal = false" />
  </div>
</template>

<style scoped>
.dashboard-card {
  border: 1px solid rgba(65, 21, 223, 0.1);
  background-color: #0a1628;
  border-radius: 12px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}

.dashboard-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.page-header {
  background: linear-gradient(
    180deg,
    rgba(10, 22, 40, 1) 0%,
    rgba(10, 22, 40, 0.86) 100%
  );
  border-color: rgba(255, 255, 255, 0.08);
}

.stat-chip {
  background: rgba(17, 35, 56, 0.7);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 0.75rem;
  padding: 0.7rem 0.85rem;
}

.stat-label {
  font-size: 0.72rem;
  color: #90a7c2;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.stat-value {
  font-size: 1.2rem;
  line-height: 1.2;
  font-weight: 700;
  color: #e6eef8;
}

.room-card {
  border-color: rgba(255, 255, 255, 0.08);
  background: linear-gradient(
    180deg,
    rgba(10, 22, 40, 1) 0%,
    rgba(10, 22, 40, 0.93) 100%
  );
}

.room-card:hover {
  border-color: rgba(143, 182, 255, 0.28);
  background: linear-gradient(
    180deg,
    rgba(12, 28, 48, 1) 0%,
    rgba(12, 28, 48, 0.95) 100%
  );
}

.room-title {
  color: #e7eff8;
  text-decoration: none;
}

.room-title:hover {
  color: #ffffff;
}
</style>

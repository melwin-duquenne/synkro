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
  <div class="rooms-page space-y-6 min-h-screen">
    <div class="rooms-panel page-header p-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1
            class="text-3xl md:text-4xl font-bold text-[#e7f0fa] tracking-tight"
          >
            Rooms
          </h1>
          <p class="text-sm text-[#9db0c6] mt-1">
            Gérez vos espaces de collaboration et accédez rapidement à vos
            projets.
          </p>
        </div>
        <button
          class="rooms-primary-btn"
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
    <div v-if="!hasEntreprise" class="rooms-panel rounded-lg p-6">
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
            class="rooms-primary-btn w-full"
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

    <div v-else-if="validRooms.length === 0" class="rooms-panel rounded-lg p-8">
      <div class="text-center py-12">
        <h2 class="text-xl font-semibold mb-2 text-[#e0e0e0]">Aucune room</h2>
        <p class="text-[#b0b0b0] mb-6">
          Créez votre première room pour commencer à collaborer
        </p>
        <div class="flex justify-center">
          <button class="rooms-primary-btn" @click="showCreateModal = true">
            Créer une room
          </button>
        </div>
      </div>
    </div>

    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <article
        v-for="room in validRooms"
        :key="room.id"
        class="rooms-panel room-card rounded-lg p-6"
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
              <div tabindex="0" role="button" class="rooms-ghost-btn">
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
                class="dropdown-content z-1 menu p-2 shadow-lg rooms-dropdown-menu w-40"
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
              class="room-visibility-chip"
              :class="
                room.visibility === 'private'
                  ? 'room-visibility-chip--private'
                  : 'room-visibility-chip--shared'
              "
            >
              {{
                room.visibility === "private" ? "🔒 Privée" : "🌐 Entreprise"
              }}
            </span>
            <span v-if="room.isTemporary" class="room-temporary-chip"
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
              class="room-module-chip"
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
              class="rooms-primary-btn rooms-primary-btn--sm"
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
.rooms-page {
  padding: 0.2rem;
}

.rooms-panel {
  border: 1px solid rgba(255, 255, 255, 0.08);
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.14),
      transparent 34%
    ),
    linear-gradient(180deg, rgba(8, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  border-radius: 16px;
  box-shadow:
    0 22px 44px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
  transition:
    border-color 0.22s ease,
    transform 0.22s ease;
}

.page-header {
  position: relative;
  overflow: hidden;
}

.stat-chip {
  background: rgba(17, 35, 56, 0.58);
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
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.12),
      transparent 38%
    ),
    linear-gradient(180deg, rgba(10, 22, 40, 1), rgba(10, 22, 40, 0.93));
}

.room-card:hover {
  transform: translateY(-2px);
  border-color: rgba(143, 182, 255, 0.28);
  box-shadow: 0 18px 30px rgba(0, 0, 0, 0.3);
}

.room-title {
  color: #e7eff8;
  text-decoration: none;
}

.room-title:hover {
  color: #ffffff;
}

.rooms-primary-btn {
  border-radius: 10px;
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
  padding: 0.55rem 0.95rem;
  font-weight: 600;
  transition:
    transform 0.2s ease,
    filter 0.2s ease;
}

.rooms-primary-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.rooms-primary-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.rooms-primary-btn--sm {
  padding: 0.44rem 0.8rem;
  font-size: 0.82rem;
}

.rooms-ghost-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.03);
}

.rooms-ghost-btn:hover {
  border-color: rgba(91, 163, 232, 0.38);
  background: rgba(91, 163, 232, 0.1);
}

.rooms-dropdown-menu {
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  background: rgba(8, 18, 31, 0.95);
  backdrop-filter: blur(10px);
}

.room-visibility-chip,
.room-temporary-chip {
  padding: 0.28rem 0.65rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  border: 1px solid transparent;
}

.room-visibility-chip--private {
  color: #c8d9ff;
  background: rgba(91, 163, 232, 0.16);
  border-color: rgba(91, 163, 232, 0.34);
}

.room-visibility-chip--shared {
  color: #c9f0dd;
  background: rgba(82, 175, 126, 0.18);
  border-color: rgba(82, 175, 126, 0.34);
}

.room-temporary-chip {
  color: #9ec1ff;
  background: rgba(26, 58, 82, 0.6);
  border-color: rgba(143, 182, 255, 0.3);
}

.room-module-chip {
  padding: 0.24rem 0.5rem;
  border-radius: 8px;
  font-size: 0.72rem;
  color: #9ec1ff;
  background: rgba(17, 35, 56, 0.72);
  border: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
  .rooms-page {
    padding: 0;
  }

  .rooms-panel {
    border-radius: 14px;
  }
}
</style>

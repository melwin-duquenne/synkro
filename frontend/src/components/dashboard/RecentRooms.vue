<template>
  <div class="dashboard-card">
    <div>
      <h2 class="card-title text-white">
        <svg
          class="w-6 h-6"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
          />
        </svg>
        Rooms récentes
      </h2>

      <div
        v-if="!recentRooms || recentRooms.length === 0"
        class="text-center py-8 text-base-content/60"
      >
        <p v-if="error">{{ error }}</p>
        <p v-else>Aucune room récente disponible.</p>
        <p class="text-sm mt-2">
          Assurez-vous d'être connecté et d'avoir accès à des rooms.
        </p>
      </div>

      <div v-else class="space-y-3">
        <div
          v-for="room in recentRooms.slice(0, 5)"
          :key="room.id"
          class="p-4 rounded-lg border-none bg-[#0a1628] hover:shadow-lg transition-all shadow-md hover:shadow-2xl"
        >
          <div class="flex items-start justify-between mb-3">
            <div class="flex-1 min-w-0">
              <h3 class="font-semibold text-lg truncate mb-1">
                {{ room.name }}
              </h3>
              <div class="flex items-center gap-2 mb-2">
                <div
                  class="badge badge-sm"
                  :class="{
                    'badge-success': room.visibility === 'public',
                    'badge-warning': room.visibility === 'private',
                    'badge-info': room.visibility === 'team',
                  }"
                >
                  {{ getVisibilityLabel(room.visibility) }}
                </div>
                <span class="text-xs text-base-content/60">
                  {{ room.moduleCount }} modules
                </span>
              </div>

              <!-- Informations supplémentaires si disponibles -->
              <div
                class="flex items-center gap-4 text-xs text-base-content/70 mb-3"
              >
                <span class="flex items-center gap-1">
                  {{ getLayoutIcon(room.layoutType) }}
                  {{ getLayoutLabel(room.layoutType) }}
                </span>
                <span
                  v-if="room.creator?.displayName"
                  class="flex items-center gap-1"
                >
                  <svg
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                  {{ room.creator.displayName }}
                </span>
                <span v-if="room.createdAt" class="flex items-center gap-1">
                  <svg
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                  {{ formatDate(room.createdAt) }}
                </span>
              </div>
            </div>

            <!-- Bouton Entrer -->
            <button
              class="btn btn-primary btn-sm ml-3"
              @click.stop="navigateToRoom(room.id)"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
              Entrer
            </button>
          </div>
        </div>
      </div>

      <!-- Lien vers voir toutes les rooms -->
      <div
        v-if="recentRooms && recentRooms.length > 5"
        class="mt-4 text-center"
      >
        <router-link to="/rooms" class="btn btn-outline btn-sm">
          Voir toutes les rooms
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from "vue-router";

interface Room {
  id: number;
  name: string;
  visibility: string;
  moduleCount: number;
  layoutType: string;
  creator?: {
    displayName: string;
  };
  createdAt?: string;
}

defineProps<{
  recentRooms: Room[];
  error?: string;
}>();

const router = useRouter();

const navigateToRoom = (roomId: number) => {
  router.push(`/room/${roomId}`);
};

const getVisibilityLabel = (visibility: string): string => {
  const labels: Record<string, string> = {
    public: "Entreprise",
    private: "Privée",
    team: "Équipe",
  };
  return labels[visibility] || visibility;
};

const getLayoutIcon = (layout: string): string => {
  const icons: Record<string, string> = {
    tabs: "[=]",
    "grid-2x2": "[#]",
    "split-horizontal": "[-]",
    "split-vertical": "[|]",
    "sidebar-left": "[<]",
    "sidebar-right": "[>]",
    "main-sidebar": "[=]",
  };
  return icons[layout] || "[=]";
};

const getLayoutLabel = (layout: string): string => {
  const labels: Record<string, string> = {
    tabs: "Onglets",
    "grid-2x2": "Grille",
    "split-horizontal": "Split H",
    "split-vertical": "Split V",
    "sidebar-left": "Sidebar G",
    "sidebar-right": "Sidebar D",
    "main-sidebar": "Main+Side",
  };
  return labels[layout] || layout;
};

const formatDate = (dateString: string): string => {
  if (!dateString) return "";

  const date = new Date(dateString);
  const now = new Date();
  const diffTime = Math.abs(now.getTime() - date.getTime());
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (diffDays === 1) {
    return "Aujourd'hui";
  } else if (diffDays === 2) {
    return "Hier";
  } else if (diffDays <= 7) {
    return `Il y a ${diffDays - 1} jours`;
  } else {
    return date.toLocaleDateString("fr-FR", {
      day: "numeric",
      month: "short",
    });
  }
};
</script>

<style scoped>
.dashboard-card {
  background-color: #0a1628;
  border: none;
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dashboard-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  transform: translateY(-2px);
}
</style>

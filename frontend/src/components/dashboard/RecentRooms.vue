<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
      </svg>
      Rooms récentes
    </div>

    <div v-if="!recentRooms || recentRooms.length === 0" class="empty-state">
      <p v-if="error">{{ error }}</p>
      <p v-else>Aucune room récente.</p>
    </div>

    <div v-else class="rooms-list">
      <div v-for="room in recentRooms.slice(0, 5)" :key="room.id" class="room-row">
        <div class="room-info">
          <div class="room-name">{{ room.name }}</div>
          <div class="room-meta">
            <span class="vis-pill" :class="{
              'vis-public': room.visibility === 'public',
              'vis-private': room.visibility === 'private',
              'vis-team': room.visibility === 'team',
            }">{{ getVisibilityLabel(room.visibility) }}</span>
            <span class="meta-dot">·</span>
            <span class="meta-text">{{ room.moduleCount }} modules</span>
            <span class="meta-dot">·</span>
            <span class="meta-text">{{ getLayoutIcon(room.layoutType) }} {{ getLayoutLabel(room.layoutType) }}</span>
          </div>
          <div class="room-footer-meta">
            <span v-if="room.creator?.displayName" class="meta-text">
              <svg class="inline-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              {{ room.creator.displayName }}
            </span>
            <span v-if="room.createdAt" class="meta-text">
              <svg class="inline-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              {{ formatDate(room.createdAt) }}
            </span>
          </div>
        </div>
        <button class="enter-btn" @click.stop="navigateToRoom(room.id)">
          Entrer
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </button>
      </div>
    </div>

    <div v-if="recentRooms && recentRooms.length > 5" class="see-all">
      <router-link to="/rooms" class="see-all-link">Voir toutes les rooms →</router-link>
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
.card {
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 1.5rem;
  transition: border-color 0.2s;
}
.card:hover { border-color: rgba(255, 255, 255, 0.1); }
.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #c8daea;
  margin-bottom: 1.25rem;
}
.header-icon { width: 16px; height: 16px; color: #5ba3e8; flex-shrink: 0; }
.empty-state { text-align: center; padding: 2rem 0; color: #3d5060; font-size: 0.875rem; }
.rooms-list { display: flex; flex-direction: column; gap: 0.5rem; }
.room-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.875rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.055);
  border-radius: 10px;
  transition: border-color 0.15s;
}
.room-row:hover { border-color: rgba(255, 255, 255, 0.1); }
.room-info { flex: 1; min-width: 0; }
.room-name { font-size: 0.875rem; font-weight: 600; color: #c8daea; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 0.375rem; }
.room-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 0.375rem; margin-bottom: 0.25rem; }
.room-footer-meta { display: flex; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.25rem; }
.meta-dot { color: #2a3a48; }
.meta-text { font-size: 0.6875rem; color: #516070; display: inline-flex; align-items: center; gap: 0.25rem; }
.inline-icon { width: 11px; height: 11px; flex-shrink: 0; }
.vis-pill {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  text-transform: capitalize;
}
.vis-public { background: rgba(111,221,159,0.12); border: 1px solid rgba(111,221,159,0.25); color: #6fdd9f; }
.vis-private { background: rgba(240,162,62,0.12); border: 1px solid rgba(240,162,62,0.25); color: #f0a23e; }
.vis-team { background: rgba(91,163,232,0.12); border: 1px solid rgba(91,163,232,0.25); color: #5ba3e8; }
.enter-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.375rem 0.75rem;
  background: rgba(64, 142, 214, 0.1);
  border: 1px solid rgba(64, 142, 214, 0.25);
  border-radius: 7px;
  color: #5ba3e8;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.15s, border-color 0.15s;
}
.enter-btn:hover { background: rgba(64, 142, 214, 0.18); border-color: rgba(64, 142, 214, 0.4); }
.see-all { margin-top: 0.875rem; text-align: center; }
.see-all-link { font-size: 0.75rem; color: #5ba3e8; text-decoration: none; }
.see-all-link:hover { color: #7ec4f0; }
</style>

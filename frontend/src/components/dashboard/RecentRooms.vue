<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
        </svg>
        Rooms récentes
      </h2>
      
      <div v-if="!recentRooms || recentRooms.length === 0" class="text-center py-8 text-base-content/60">
        <p>Aucune room récente disponible.</p>
      </div>
      
      <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div 
          v-for="room in recentRooms" 
          :key="room.id"
          class="p-4 rounded-lg border-2 border-base-300 hover:border-primary hover:shadow-lg transition-all cursor-pointer"
          @click="navigateToRoom(room.id)"
        >
          <div class="flex items-start justify-between mb-2">
            <h3 class="font-semibold truncate flex-1">{{ room.name }}</h3>
            <div 
              class="badge badge-sm"
              :class="{
                'badge-success': room.visibility === 'public',
                'badge-warning': room.visibility === 'private',
                'badge-info': room.visibility === 'team'
              }"
            >
              {{ getVisibilityLabel(room.visibility) }}
            </div>
          </div>
          
          <div class="flex items-center gap-4 text-xs text-base-content/70">
            <span class="flex items-center gap-1">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
              </svg>
              {{ room.moduleCount }} modules
            </span>
            <span class="flex items-center gap-1">
              {{ getLayoutIcon(room.layoutType) }} {{ getLayoutLabel(room.layoutType) }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

interface Room {
  id: number
  name: string
  visibility: string
  moduleCount: number
  layoutType: string
}

defineProps<{
  recentRooms: Room[]
}>()

const router = useRouter()

const navigateToRoom = (roomId: number) => {
  router.push(`/rooms/${roomId}`)
}

const getVisibilityLabel = (visibility: string): string => {
  const labels: Record<string, string> = {
    public: 'Public',
    private: 'Privé',
    team: 'Équipe'
  }
  return labels[visibility] || visibility
}

const getLayoutIcon = (layout: string): string => {
  const icons: Record<string, string> = {
    tabs: '[=]',
    'grid-2x2': '[#]',
    'split-horizontal': '[-]',
    'split-vertical': '[|]',
    'sidebar-left': '[<]',
    'sidebar-right': '[>]',
    'main-sidebar': '[=]'
  }
  return icons[layout] || '[=]'
}

const getLayoutLabel = (layout: string): string => {
  const labels: Record<string, string> = {
    tabs: 'Onglets',
    'grid-2x2': 'Grille',
    'split-horizontal': 'Split H',
    'split-vertical': 'Split V',
    'sidebar-left': 'Sidebar G',
    'sidebar-right': 'Sidebar D',
    'main-sidebar': 'Main+Side'
  }
  return labels[layout] || layout
}
</script>

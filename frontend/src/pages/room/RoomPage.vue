<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import type { Room } from '@/types'
import EditorModule from '@/components/editor/EditorModule.vue'
import ChatModule from '@/components/chat/ChatModule.vue'
import TasksModule from '@/components/tasks/TasksModule.vue'
import CalendarModule from '@/components/calendar/CalendarModule.vue'

const route = useRoute()
const authStore = useAuthStore()
const room = ref<Room | null>(null)
const loading = ref(true)
const activeModule = ref<string | null>(null)

const roomId = computed(() => Number(route.params.id))

const availableModules = computed(() => {
  return room.value?.moduleRooms?.map(mr => mr.module.code) || []
})

onMounted(async () => {
  try {
    const response = await fetch(`/api/rooms/${route.params.id}`, {
      headers: authStore.getAuthHeaders()
    })
    if (response.ok) {
      room.value = await response.json()
      const firstModule = room.value?.moduleRooms?.[0]
      if (firstModule) {
        activeModule.value = firstModule.module.code
      }
    }
  } catch (error) {
    console.error('Failed to fetch room:', error)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="h-[calc(100vh-8rem)]">
    <div v-if="loading" class="flex justify-center items-center h-full">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="!room" class="flex justify-center items-center h-full">
      <div class="text-center">
        <h2 class="text-2xl font-bold mb-2">Room introuvable</h2>
        <router-link to="/rooms" class="btn btn-primary">Retour aux rooms</router-link>
      </div>
    </div>

    <div v-else class="flex flex-col h-full">
      <!-- Room Header -->
      <div class="flex items-center justify-between mb-4">
        <div>
          <h1 class="text-2xl font-bold">{{ room.name }}</h1>
          <p class="text-base-content/70">
            Créée par {{ room.creator?.displayName }}
          </p>
        </div>
        <div class="flex items-center gap-2">
          <span
            class="badge"
            :class="room.visibility === 'private' ? 'badge-warning' : 'badge-success'"
          >
            {{ room.visibility === 'private' ? 'Privée' : 'Entreprise' }}
          </span>
        </div>
      </div>

      <!-- Module Tabs -->
      <div class="tabs tabs-boxed mb-4">
        <a
          v-for="mr in room.moduleRooms"
          :key="mr.id"
          class="tab"
          :class="{ 'tab-active': activeModule === mr.module.code }"
          @click="activeModule = mr.module.code"
        >
          {{ mr.module.name }}
        </a>
      </div>

      <!-- Module Content -->
      <div class="flex-1 bg-base-100 rounded-lg overflow-hidden shadow-lg">
        <!-- Editor Module -->
        <EditorModule
          v-if="activeModule === 'editor' && availableModules.includes('editor')"
          :room-id="roomId"
          class="h-full"
        />

        <!-- Chat Module -->
        <ChatModule
          v-else-if="activeModule === 'chat' && availableModules.includes('chat')"
          :room-id="roomId"
          class="h-full"
        />

        <!-- Whiteboard (placeholder) -->
        <div v-else-if="activeModule === 'whiteboard'" class="h-full flex items-center justify-center">
          <div class="text-center py-12 text-base-content/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            <p class="text-lg font-medium">Tableau blanc collaboratif</p>
            <p class="text-sm">(Bientôt disponible)</p>
          </div>
        </div>

        <!-- Video (placeholder) -->
        <div v-else-if="activeModule === 'video'" class="h-full flex items-center justify-center">
          <div class="text-center py-12 text-base-content/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
            <p class="text-lg font-medium">Visioconférence WebRTC</p>
            <p class="text-sm">(Bientôt disponible)</p>
          </div>
        </div>

        <!-- Files (placeholder) -->
        <div v-else-if="activeModule === 'files'" class="h-full flex items-center justify-center">
          <div class="text-center py-12 text-base-content/50">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
            </svg>
            <p class="text-lg font-medium">Gestionnaire de fichiers</p>
            <p class="text-sm">(Bientôt disponible)</p>
          </div>
        </div>

        <!-- Tasks Module -->
        <TasksModule
          v-else-if="activeModule === 'tasks' && availableModules.includes('tasks')"
          :room-id="roomId"
          class="h-full"
        />

        <!-- Calendar Module -->
        <CalendarModule
          v-else-if="activeModule === 'calendar' && availableModules.includes('calendar')"
          :room-id="roomId"
          class="h-full"
        />

        <!-- Default / No module selected -->
        <div v-else class="h-full flex items-center justify-center">
          <div class="text-center py-12 text-base-content/50">
            <p>Sélectionnez un module pour commencer</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

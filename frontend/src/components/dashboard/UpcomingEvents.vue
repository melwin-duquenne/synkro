<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Événements
      </h2>

      <!-- État vide -->
      <div v-if="(!upcomingEvents || upcomingEvents.length === 0) && (!todayEvents || todayEvents.length === 0)" class="text-center py-8 text-base-content/60">
        <p>Aucun événement à venir.</p>
      </div>

      <template v-else>
      <!-- Timeline du jour -->
      <div v-if="todayEvents.length > 0" class="mb-6">
        <h3 class="text-sm font-semibold mb-3 text-base-content/70">Aujourd'hui</h3>
        <div class="space-y-2">
          <div 
            v-for="event in todayEvents" 
            :key="event.id"
            class="flex items-center gap-3 p-3 rounded-lg bg-base-200 hover:bg-base-300 transition-colors"
          >
            <div v-html="getEventIcon(event.eventType)"></div>
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ event.title }}</div>
              <div class="text-sm text-base-content/70">
                {{ formatTime(event.startDate) }} - {{ formatTime(event.endDate) }}
              </div>
            </div>
            <div 
              class="badge"
              :class="getEventBadgeClass(event.eventType)"
            >
              {{ event.eventType }}
            </div>
          </div>
        </div>
      </div>

      <!-- Prochains événements -->
      <div>
        <h3 class="text-sm font-semibold mb-3 text-base-content/70">
          À venir ({{ upcomingEvents.length }})
        </h3>
        <div v-if="upcomingEvents.length === 0" class="text-center py-8 text-base-content/50">
          Aucun événement à venir
        </div>
        <div v-else class="space-y-2">
          <div 
            v-for="event in upcomingEvents" 
            :key="event.id"
            class="flex items-start gap-3 p-3 rounded-lg hover:bg-base-200 transition-colors cursor-pointer"
            @click="navigateToRoom(event.room?.id)"
          >
            <div v-html="getEventIcon(event.eventType)"></div>
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ event.title }}</div>
              <div class="text-sm text-base-content/70">
                {{ formatDateTime(event.startDate) }}
              </div>
              <div v-if="event.room" class="text-xs text-primary mt-1 flex items-center gap-1">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ event.room.name }}
              </div>
            </div>
            <div 
              class="badge badge-sm"
              :class="getEventBadgeClass(event.eventType)"
            >
              {{ event.eventType }}
            </div>
          </div>
        </div>
      </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

interface Event {
  id: number
  title: string
  startDate: string
  endDate: string
  eventType: string
  room?: { id: number; name: string } | null
}

const props = defineProps<{
  upcomingEvents: Event[]
  todayEvents: Event[]
}>()

const router = useRouter()

const getEventIcon = (type: string): string => {
  const icons: Record<string, string> = {
    meeting: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    task: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`,
    absence: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>`,
    deadline: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    other: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>`
  }
  return icons[type] || icons.other
}

const getEventBadgeClass = (type: string): string => {
  const classes: Record<string, string> = {
    meeting: 'badge-primary',
    task: 'badge-success',
    absence: 'badge-warning',
    deadline: 'badge-error',
  }
  return classes[type] || 'badge-ghost'
}

const formatTime = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

const formatDateTime = (dateString: string): string => {
  const date = new Date(dateString)
  const now = new Date()
  const tomorrow = new Date(now)
  tomorrow.setDate(tomorrow.getDate() + 1)
  
  const isToday = date.toDateString() === now.toDateString()
  const isTomorrow = date.toDateString() === tomorrow.toDateString()
  
  if (isToday) {
    return `Aujourd'hui à ${formatTime(dateString)}`
  } else if (isTomorrow) {
    return `Demain à ${formatTime(dateString)}`
  } else {
    return date.toLocaleDateString('fr-FR', { 
      weekday: 'short', 
      day: 'numeric', 
      month: 'short',
      hour: '2-digit',
      minute: '2-digit'
    })
  }
}

const navigateToRoom = (roomId?: number) => {
  if (roomId) {
    router.push(`/rooms/${roomId}`)
  }
}
</script>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import type { CalendarEvent } from '@/types'

const authStore = useAuthStore()
const events = ref<CalendarEvent[]>([])
const loading = ref(true)
const currentDate = ref(new Date())

const currentMonth = computed(() => {
  return currentDate.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const daysInMonth = computed(() => {
  const year = currentDate.value.getFullYear()
  const month = currentDate.value.getMonth()
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)

  const days = []
  const startPadding = firstDay.getDay() === 0 ? 6 : firstDay.getDay() - 1

  for (let i = 0; i < startPadding; i++) {
    days.push({ day: null, events: [] })
  }

  for (let day = 1; day <= lastDay.getDate(); day++) {
    const date = new Date(year, month, day)
    const dayEvents = events.value.filter(e => {
      const eventDate = new Date(e.startDate)
      return eventDate.toDateString() === date.toDateString()
    })
    days.push({ day, date, events: dayEvents })
  }

  return days
})

function prevMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() - 1, 1)
}

function nextMonth() {
  currentDate.value = new Date(currentDate.value.getFullYear(), currentDate.value.getMonth() + 1, 1)
}

function getEventTypeColor(type: string): string {
  const colors: Record<string, string> = {
    meeting: 'bg-blue-500',
    absence: 'bg-red-500',
    blocked: 'bg-gray-500',
    reminder: 'bg-yellow-500',
    other: 'bg-purple-500'
  }
  return colors[type] || 'bg-primary'
}

onMounted(async () => {
  try {
    const response = await fetch('/api/calendar_events', {
      headers: authStore.getAuthHeaders()
    })
    if (response.ok) {
      const data = await response.json()
      events.value = data['hydra:member'] || data
    }
  } catch (error) {
    console.error('Failed to fetch events:', error)
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold">Calendrier</h1>
      <button class="btn btn-primary">+ Nouvel événement</button>
    </div>

    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <!-- Month Navigation -->
        <div class="flex items-center justify-between mb-4">
          <button class="btn btn-ghost" @click="prevMonth">&lt;</button>
          <h2 class="text-xl font-semibold capitalize">{{ currentMonth }}</h2>
          <button class="btn btn-ghost" @click="nextMonth">&gt;</button>
        </div>

        <!-- Calendar Grid -->
        <div class="grid grid-cols-7 gap-1">
          <!-- Day Headers -->
          <div v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']" :key="day" class="text-center font-semibold p-2 text-base-content/70">
            {{ day }}
          </div>

          <!-- Calendar Days -->
          <div
            v-for="(dayData, index) in daysInMonth"
            :key="index"
            class="min-h-24 p-1 border border-base-300 rounded"
            :class="{ 'bg-base-200': !dayData.day }"
          >
            <div v-if="dayData.day" class="h-full">
              <span class="text-sm font-medium">{{ dayData.day }}</span>
              <div class="space-y-1 mt-1">
                <div
                  v-for="event in dayData.events.slice(0, 2)"
                  :key="event.id"
                  class="text-xs p-1 rounded text-white truncate cursor-pointer"
                  :class="getEventTypeColor(event.eventType)"
                  :title="event.title"
                >
                  {{ event.title }}
                </div>
                <div v-if="dayData.events.length > 2" class="text-xs text-base-content/70">
                  +{{ dayData.events.length - 2 }} autres
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Event Types Legend -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h3 class="font-semibold mb-2">Légende</h3>
        <div class="flex flex-wrap gap-4">
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-blue-500"></span>
            <span>Réunion</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-red-500"></span>
            <span>Absence</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-gray-500"></span>
            <span>Bloqué</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-yellow-500"></span>
            <span>Rappel</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="w-3 h-3 rounded bg-purple-500"></span>
            <span>Autre</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

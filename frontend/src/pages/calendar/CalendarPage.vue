<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue'
import { useCalendarStore } from '@/stores/calendar'
import { useWorkload } from '@/composables/useWorkload'
import type { CalendarEvent, EventType } from '@/types'
import CalendarEventModal from '@/components/calendar/CalendarEventModal.vue'
import WorkloadIndicator from '@/components/calendar/WorkloadIndicator.vue'

const calendarStore = useCalendarStore()
const { 
  workload: dailyWorkload, 
  loading: dailyLoading, 
  fetchDailyWorkload 
} = useWorkload()

const { 
  workload: weeklyWorkload, 
  loading: weeklyLoading, 
  fetchWeeklyWorkload 
} = useWorkload()

const currentDate = ref(new Date())
const selectedDate = ref<Date | null>(null)
const showEventModal = ref(false)
const selectedEvent = ref<CalendarEvent | null>(null)
const showDayDetail = ref(false)
const dayDetailDate = ref<Date | null>(null)
const showWorkloadDetail = ref(false)

// Filters
const filterEventType = ref<EventType | null>(null)

const eventTypes: { value: EventType; label: string; color: string }[] = [
  { value: 'meeting', label: 'Réunion', color: 'bg-blue-500' },
  { value: 'absence', label: 'Absence', color: 'bg-orange-500' },
  { value: 'blocked', label: 'Bloqué', color: 'bg-red-500' },
  { value: 'reminder', label: 'Rappel', color: 'bg-purple-500' },
  { value: 'other', label: 'Autre', color: 'bg-gray-500' }
]

const currentMonth = computed(() => currentDate.value.getMonth())
const currentYear = computed(() => currentDate.value.getFullYear())

const monthName = computed(() => {
  return currentDate.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
})

const daysInMonth = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const days: { date: Date; isCurrentMonth: boolean }[] = []

  // Add days from previous month to fill the first week
  const firstDayOfWeek = firstDay.getDay() || 7
  for (let i = firstDayOfWeek - 1; i > 0; i--) {
    const date = new Date(year, month, 1 - i)
    days.push({ date, isCurrentMonth: false })
  }

  // Add days of current month
  for (let i = 1; i <= lastDay.getDate(); i++) {
    days.push({ date: new Date(year, month, i), isCurrentMonth: true })
  }

  // Add days from next month to complete the grid
  const remainingDays = 42 - days.length
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i)
    days.push({ date, isCurrentMonth: false })
  }

  return days
})

const filteredEvents = computed(() => {
  let events = calendarStore.events
  if (filterEventType.value) {
    events = events.filter(e => e.eventType === filterEventType.value)
  }
  return events
})

function formatLocalDate(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function getEventsForDate(date: Date): CalendarEvent[] {
  const dateStr = formatLocalDate(date)
  return filteredEvents.value.filter(event => {
    const startDate = new Date(event.startDate)
    const endDate = new Date(event.endDate)
    const start = formatLocalDate(startDate)
    const end = formatLocalDate(endDate)
    return dateStr >= start && dateStr <= end
  })
}

function getEventColor(event: CalendarEvent): string {
  if (event.color) return event.color
  const type = eventTypes.find(t => t.value === event.eventType)
  return type?.color || 'bg-gray-500'
}

function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1)
}

function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1)
}

function goToToday() {
  currentDate.value = new Date()
}

function openCreateModal(date: Date) {
  selectedDate.value = date
  selectedEvent.value = null
  showEventModal.value = true
}

function openEditModal(event: CalendarEvent) {
  selectedEvent.value = event
  selectedDate.value = null
  showDayDetail.value = false
  showEventModal.value = true
}

function openDayDetail(date: Date) {
  dayDetailDate.value = date
  showDayDetail.value = true
}

function formatTime(dateStr: string): string {
  const date = new Date(dateStr)
  return date.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
}

function formatDayDetailDate(date: Date): string {
  return date.toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
}

const dayDetailEvents = computed(() => {
  if (!dayDetailDate.value) return []
  return getEventsForDate(dayDetailDate.value)
})

function handleEventSaved() {
  showEventModal.value = false
  selectedEvent.value = null
  selectedDate.value = null
  fetchEvents()
  loadWorkload()
}

function handleEventDeleted() {
  showEventModal.value = false
  selectedEvent.value = null
  loadWorkload()
  fetchEvents()
}

function isToday(date: Date): boolean {
  const today = new Date()
  return date.toDateString() === today.toDateString()
}

async function fetchEvents() {
  const startOfMonth = new Date(currentYear.value, currentMonth.value, 1)
  const endOfMonth = new Date(currentYear.value, currentMonth.value + 1, 0)

  // Fetch personal events (no room)
  await calendarStore.fetchEvents({
    personal: true,
    startDate: startOfMonth.toISOString(),
    endDate: endOfMonth.toISOString()
  })
}

watch([currentMonth, currentYear], () => {
  fetchEvents()
})

async function loadWorkload() {
  await Promise.all([
    fetchDailyWorkload(),
    fetchWeeklyWorkload()
  ])
}

onMounted(() => {
  fetchEvents()
  loadWorkload()
})
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
      <div>
        <h1 class="text-3xl font-bold">Mon Calendrier</h1>
        <p class="text-base-content/70">Vos événements personnels</p>
      </div>
      
      <!-- Compact Workload Indicator -->
      <div class="flex items-center gap-4">
        <div v-if="(dailyWorkload || weeklyWorkload) && !dailyLoading && !weeklyLoading" class="flex items-center gap-3">
          <span class="text-sm font-medium text-base-content/70">Charge de travail :</span>
          
          <!-- Daily Badge -->
          <button 
            v-if="dailyWorkload"
            @click="showWorkloadDetail = true"
            :class="[
              'badge badge-lg gap-2 cursor-pointer hover:brightness-110 transition-all',
              dailyWorkload.status === 'normal' ? 'badge-success' : 
              dailyWorkload.status === 'busy' ? 'badge-warning' : 'badge-error'
            ]"
            :title="`Aujourd'hui: ${dailyWorkload.totalHours}h / ${dailyWorkload.standardHours}h`"
          >
            <span class="text-lg">
              {{ dailyWorkload.status === 'normal' ? '🟢' : dailyWorkload.status === 'busy' ? '🟡' : '🔴' }}
            </span>
            <span class="font-semibold">{{ dailyWorkload.percentage }}%</span>
            <span class="text-xs opacity-70">jour</span>
          </button>
          
          <!-- Weekly Badge -->
          <button 
            v-if="weeklyWorkload"
            @click="showWorkloadDetail = true"
            :class="[
              'badge badge-lg gap-2 cursor-pointer hover:brightness-110 transition-all',
              weeklyWorkload.status === 'normal' ? 'badge-success' : 
              weeklyWorkload.status === 'busy' ? 'badge-warning' : 'badge-error'
            ]"
            :title="`Cette semaine: ${weeklyWorkload.totalHours}h / ${weeklyWorkload.standardHours}h`"
          >
            <span class="text-lg">
              {{ weeklyWorkload.status === 'normal' ? '🟢' : weeklyWorkload.status === 'busy' ? '🟡' : '🔴' }}
            </span>
            <span class="font-semibold">{{ weeklyWorkload.percentage }}%</span>
            <span class="text-xs opacity-70">sem.</span>
          </button>
        </div>
        
        <div v-else-if="dailyLoading || weeklyLoading" class="flex items-center gap-3">
          <span class="text-sm font-medium text-base-content/70">Charge de travail</span>
          <div class="skeleton h-8 w-24"></div>
          <div class="skeleton h-8 w-24"></div>
        </div>
        
        <button class="btn btn-primary" @click="openCreateModal(new Date())">
          + Nouvel événement
        </button>
      </div>
    </div>

    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <!-- Month Navigation & Filters -->
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-4">
            <h2 class="text-xl font-semibold capitalize">{{ monthName }}</h2>
            <div class="flex gap-1">
              <button class="btn btn-ghost btn-sm btn-square" @click="previousMonth">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
              </button>
              <button class="btn btn-ghost btn-sm" @click="goToToday">Aujourd'hui</button>
              <button class="btn btn-ghost btn-sm btn-square" @click="nextMonth">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </button>
            </div>
          </div>

          <select v-model="filterEventType" class="select select-sm select-bordered">
            <option :value="null">Tous les types</option>
            <option v-for="type in eventTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- Loading -->
        <div v-if="calendarStore.loading" class="flex justify-center py-12">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <!-- Calendar Grid -->
        <div v-else>
          <!-- Day headers -->
          <div class="grid grid-cols-7 border-b border-base-300 mb-1">
            <div
              v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']"
              :key="day"
              class="p-2 text-center text-sm font-medium text-base-content/70"
            >
              {{ day }}
            </div>
          </div>

          <!-- Calendar days -->
          <div class="grid grid-cols-7 grid-rows-6">
            <div
              v-for="(day, index) in daysInMonth"
              :key="index"
              class="border border-base-300 p-1 min-h-[100px] cursor-pointer hover:bg-base-200 transition-colors"
              :class="{
                'bg-base-200/50': !day.isCurrentMonth,
                'bg-primary/10': isToday(day.date)
              }"
              @click="openCreateModal(day.date)"
            >
              <!-- Day number -->
              <div
                class="text-sm font-medium mb-1"
                :class="{
                  'text-base-content/50': !day.isCurrentMonth,
                  'text-primary font-bold': isToday(day.date)
                }"
              >
                {{ day.date.getDate() }}
              </div>

              <!-- Events for this day -->
              <div class="space-y-0.5 overflow-hidden">
                <div
                  v-for="event in getEventsForDate(day.date).slice(0, 3)"
                  :key="event.id"
                  class="text-xs px-1 py-0.5 rounded truncate text-white cursor-pointer hover:opacity-80"
                  :class="getEventColor(event)"
                  :style="event.color ? { backgroundColor: event.color } : {}"
                  @click.stop="openEditModal(event)"
                >
                  {{ event.title }}
                </div>
                <div
                  v-if="getEventsForDate(day.date).length > 3"
                  class="text-xs text-primary pl-1 cursor-pointer hover:underline"
                  @click.stop="openDayDetail(day.date)"
                >
                  +{{ getEventsForDate(day.date).length - 3 }} autres
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body py-4">
        <div class="flex items-center gap-6">
          <span class="font-medium">Types :</span>
          <div v-for="type in eventTypes" :key="type.value" class="flex items-center gap-2">
            <div :class="[type.color, 'w-3 h-3 rounded']"></div>
            <span class="text-sm text-base-content/70">{{ type.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Day Detail Modal -->
    <dialog class="modal" :class="{ 'modal-open': showDayDetail }">
      <div class="modal-box max-w-lg">
        <h3 class="font-bold text-lg mb-4 capitalize">
          {{ dayDetailDate ? formatDayDetailDate(dayDetailDate) : '' }}
        </h3>
        <div class="space-y-2">
          <div
            v-for="event in dayDetailEvents"
            :key="event.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200 cursor-pointer transition-colors"
            @click="openEditModal(event)"
          >
            <div class="w-3 h-3 rounded-full flex-shrink-0" :class="getEventColor(event)"></div>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm truncate">{{ event.title }}</div>
              <div class="text-xs text-base-content/60">
                <template v-if="event.isAllDay">Journée entière</template>
                <template v-else>{{ formatTime(event.startDate) }} - {{ formatTime(event.endDate) }}</template>
              </div>
            </div>
          </div>
          <div v-if="dayDetailEvents.length === 0" class="text-center text-base-content/50 py-4">
            Aucun événement
          </div>
        </div>
        <div class="modal-action">
          <button class="btn btn-sm btn-primary" @click="showDayDetail = false; openCreateModal(dayDetailDate!)">
            + Nouvel événement
          </button>
          <button class="btn btn-sm" @click="showDayDetail = false">Fermer</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showDayDetail = false">close</button>
      </form>
    </dialog>

    <!-- Personal Calendar Event Modal (no roomId) -->
    <CalendarEventModal
      :open="showEventModal"
      :room-id="0"
      :event="selectedEvent"
      :initial-date="selectedDate"
      @close="showEventModal = false; selectedEvent = null; selectedDate = null"
      @saved="handleEventSaved"
      @deleted="handleEventDeleted"
    />

    <!-- Workload Detail Modal -->
    <dialog :class="['modal', { 'modal-open': showWorkloadDetail }]">
      <div class="modal-box max-w-4xl">
        <h3 class="font-bold text-lg mb-4">📊 Charge de travail détaillée</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Daily Workload -->
          <div>
            <h4 class="font-semibold mb-2">Aujourd'hui</h4>
            <WorkloadIndicator 
              v-if="dailyWorkload"
              :workload="dailyWorkload" 
              :loading="dailyLoading"
              :show-details="true"
            />
          </div>
          
          <!-- Weekly Workload -->
          <div>
            <h4 class="font-semibold mb-2">Cette semaine</h4>
            <WorkloadIndicator 
              v-if="weeklyWorkload"
              :workload="weeklyWorkload" 
              :loading="weeklyLoading"
              :show-details="true"
            />
          </div>
        </div>
        
        <div class="modal-action">
          <button class="btn btn-sm" @click="showWorkloadDetail = false">Fermer</button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showWorkloadDetail = false">close</button>
      </form>
    </dialog>
  </div>
</template>

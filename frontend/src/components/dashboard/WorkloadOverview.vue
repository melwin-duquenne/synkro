<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <div class="flex items-center justify-between mb-4">
        <h2 class="card-title">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Charge de travail
        </h2>
      </div>

      <!-- Filtre Utilisateur (Admin uniquement) -->
      <div v-if="isAdmin" class="form-control mb-4">
        <label class="label">
          <span class="label-text font-semibold">Voir la charge de travail de :</span>
        </label>
        <select 
          v-model="selectedUserId" 
          class="select select-bordered w-full"
          @change="handleUserChange">
          <option :value="currentUserId">Moi ({{ currentUserName }})</option>
          <option v-if="users && users.length > 0" disabled>──────────</option>
          <option v-for="user in users" :key="user.id" :value="user.id">
            {{ user.displayName || user.email }}
          </option>
        </select>
      </div>

      <!-- État vide -->
      <div v-if="!workload || !workload.daily || workload.daily.length === 0" class="text-center py-8 text-base-content/60">
        <p>Aucune donnée de charge de travail disponible pour le moment.</p>
      </div>

      <template v-else>
        <!-- Alerte surcharge -->
        <div v-if="workload.hasAlert" class="alert alert-warning mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          <span>{{ workload.overloadDays }} jour(s) de surcharge cette semaine</span>
        </div>

        <!-- Statistiques globales -->
        <div class="grid grid-cols-2 gap-3 mb-4">
          <!-- Aujourd'hui -->
          <div class="stat bg-base-200 rounded-lg p-4">
            <div class="stat-title text-xs">Aujourd'hui</div>
            <div class="stat-value text-2xl" :class="{
              'text-success': todayWorkload.percentage < 80,
              'text-warning': todayWorkload.percentage >= 80 && todayWorkload.percentage <= 100,
              'text-error': todayWorkload.percentage > 100
            }">
              {{ todayWorkload.hoursUsed }}h
            </div>
            <div class="stat-desc">/ {{ todayWorkload.hoursAvailable }}h ({{ Math.round(todayWorkload.percentage) }}%)</div>
            <button 
              v-if="todayWorkload.tasks && todayWorkload.tasks.length > 0"
              class="btn btn-xs btn-ghost mt-2"
              @click="showTodayDetails = true">
              Voir détail
            </button>
          </div>

          <!-- Cette semaine -->
          <div class="stat bg-base-200 rounded-lg p-4">
            <div class="stat-title text-xs">Cette semaine</div>
            <div class="stat-value text-2xl" :class="{
              'text-success': weekWorkload.percentage < 80,
              'text-warning': weekWorkload.percentage >= 80 && weekWorkload.percentage <= 100,
              'text-error': weekWorkload.percentage > 100
            }">
              {{ weekWorkload.hoursUsed }}h
            </div>
            <div class="stat-desc">/ {{ weekWorkload.hoursAvailable }}h ({{ Math.round(weekWorkload.percentage) }}%)</div>
          </div>
        </div>

        <!-- Graphique par jour -->
        <div class="space-y-2">
          <h3 class="font-semibold text-sm mb-3">Charge par jour</h3>
          <div v-for="day in workload.daily" :key="day.date" 
               class="flex items-center gap-3 cursor-pointer hover:bg-base-200 p-2 rounded-lg transition-colors"
               @click="openDayDetails(day)">
            <div class="w-16 text-sm font-medium">{{ day.dayName }}</div>
            <div class="flex-1">
              <div class="relative h-8 bg-base-200 rounded-lg overflow-hidden">
                <div
                  class="absolute h-full transition-all duration-300 flex items-center justify-end px-2 text-xs font-bold"
                  :class="{
                    'bg-success': day.percentage < 80,
                    'bg-warning': day.percentage >= 80 && day.percentage <= 100,
                    'bg-error': day.percentage > 100
                  }"
                  :style="{ width: Math.min(day.percentage, 150) + '%' }"
                >
                  <span v-if="day.percentage > 10" class="text-white">{{ Math.round(day.percentage) }}%</span>
                </div>
              </div>
            </div>
            <div class="w-24 text-right text-xs flex items-center justify-end gap-1">
              <span :class="{ 'text-error font-bold': day.isOverloaded }">
                {{ Math.round((day.hoursUsed ?? (day.percentage * (day.hoursAvailable || 9) / 100)) * 10) / 10 }}h / {{ day.hoursAvailable || 9 }}h
              </span>
              <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
              </svg>
            </div>
          </div>
        </div>

        <!-- Légende -->
        <div class="flex gap-4 mt-4 text-xs border-t border-base-300 pt-3">
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-success rounded"></div>
            <span>&lt; 80%</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-warning rounded"></div>
            <span>80-100%</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="w-3 h-3 bg-error rounded"></div>
            <span>&gt; 100%</span>
          </div>
        </div>
      </template>
    </div>
  </div>

  <!-- Modale détails du jour -->
  <Teleport to="body">
    <div v-if="showTodayDetails" 
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
         style="background-color: rgba(0, 0, 0, 0.6);"
         @click.self="showTodayDetails = false">
      <div class="bg-base-100 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary to-primary-focus p-6 text-white">
          <div class="flex justify-between items-center">
            <h3 class="text-2xl font-bold">Détails - Aujourd'hui</h3>
            <button 
              class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors"
              @click="showTodayDetails = false">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <div class="mb-4">
            <div class="stat bg-base-200 rounded-lg">
              <div class="stat-title">Charge actuelle</div>
              <div class="stat-value" :class="{
                'text-success': todayWorkload.percentage < 80,
                'text-warning': todayWorkload.percentage >= 80 && todayWorkload.percentage <= 100,
                'text-error': todayWorkload.percentage > 100
              }">
                {{ Math.round(todayWorkload.percentage) }}%
              </div>
              <div class="stat-desc">{{ todayWorkload.hoursUsed }}h / {{ todayWorkload.hoursAvailable }}h</div>
            </div>
          </div>

          <h4 class="font-semibold mb-3">Tâches planifiées</h4>
          <div v-if="todayWorkload.tasks && todayWorkload.tasks.length > 0" class="space-y-2">
            <div v-for="task in todayWorkload.tasks" :key="task.id" 
                 class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
              <div class="flex-1">
                <div class="font-medium">{{ task.title }}</div>
                <div class="text-xs text-base-content/70 flex items-center gap-1 mt-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                  {{ task.roomName }}
                </div>
              </div>
              <div class="badge badge-lg badge-ghost">{{ task.estimatedHours }}h</div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-base-content/50">
            Aucune tâche planifiée pour aujourd'hui
          </div>
        </div>
      </div>
    </div>

    <!-- Modale détails d'un jour spécifique -->
    <div v-if="selectedDay" 
         class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
         style="background-color: rgba(0, 0, 0, 0.6);"
         @click.self="selectedDay = null">
      <div class="bg-base-100 rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
        <!-- Header -->
        <div class="bg-gradient-to-r from-primary to-primary-focus p-6 text-white">
          <div class="flex justify-between items-center">
            <h3 class="text-2xl font-bold">Détails - {{ selectedDay.dayName }}</h3>
            <button 
              class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-white/20 transition-colors"
              @click="selectedDay = null">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-6">
          <div class="mb-4">
            <div class="stat bg-base-200 rounded-lg">
              <div class="stat-title">Charge</div>
              <div class="stat-value" :class="{
                'text-success': selectedDay.percentage < 80,
                'text-warning': selectedDay.percentage >= 80 && selectedDay.percentage <= 100,
                'text-error': selectedDay.percentage > 100
              }">
                {{ Math.round(selectedDay.percentage) }}%
              </div>
              <div class="stat-desc">{{ selectedDay.hoursUsed ?? Math.round((selectedDay.percentage * (selectedDay.hoursAvailable || 9) / 100) * 10) / 10 }}h / {{ selectedDay.hoursAvailable || 9 }}h</div>
            </div>
          </div>

          <h4 class="font-semibold mb-3">Tâches planifiées</h4>
          <div v-if="selectedDay.tasks && selectedDay.tasks.length > 0" class="space-y-2">
            <div v-for="task in selectedDay.tasks" :key="task.id" 
                 class="flex items-center justify-between p-3 bg-base-200 rounded-lg">
              <div class="flex-1">
                <div class="font-medium">{{ task.title }}</div>
                <div class="text-xs text-base-content/70 flex items-center gap-1 mt-1">
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                  </svg>
                  {{ task.roomName }}
                </div>
              </div>
              <div class="badge badge-lg badge-ghost">{{ task.estimatedHours }}h</div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-base-content/50">
            Aucune tâche planifiée pour ce jour
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'

interface WorkloadData {
  daily: Array<{
    date: string
    dayName: string
    percentage: number
    hoursUsed: number
    hoursAvailable: number
    isOverloaded: boolean
    tasks?: Array<{
      id: number
      title: string
      roomName: string
      estimatedHours: number
    }>
  }>
  overloadDays: number
  hasAlert: boolean
}

interface User {
  id: number
  email: string
  displayName: string | null
}

const props = defineProps<{
  workload?: WorkloadData
  isAdmin?: boolean
  users?: User[]
  currentUserId?: number
  currentUserName?: string
}>()

const emit = defineEmits<{
  userChange: [userId: number | null]
}>()

const selectedUserId = ref<number | null>(props.currentUserId || null)
const showTodayDetails = ref(false)
const selectedDay = ref<any>(null)

// Synchroniser selectedUserId avec currentUserId quand il change
watch(() => props.currentUserId, (newId) => {
  if (newId && selectedUserId.value !== newId) {
    selectedUserId.value = newId
  }
}, { immediate: true })

// Calculer la charge d'aujourd'hui
const todayWorkload = computed(() => {
  if (!props.workload?.daily || props.workload.daily.length === 0) {
    return { hoursUsed: 0, hoursAvailable: 0, percentage: 0, tasks: [] }
  }
  
  // Obtenir la date d'aujourd'hui au format YYYY-MM-DD
  const today = new Date()
  const todayStr = today.toISOString().split('T')[0]
  
  console.log('[WorkloadOverview] Today date:', todayStr)
  console.log('[WorkloadOverview] Available dates:', props.workload.daily.map(d => d.date))
  
  // Chercher par date exacte
  const todayData = props.workload.daily.find(day => day.date === todayStr)
  
  console.log('[WorkloadOverview] Today data found:', todayData)
  
  const data = todayData || { hoursUsed: 0, hoursAvailable: 0, percentage: 0, tasks: [] }
  
  // Calculer les heures si elles ne sont pas fournies par le backend
  const hoursAvailable = data.hoursAvailable || 9
  const hoursUsed = data.hoursUsed ?? (data.percentage * hoursAvailable / 100)
  
  return {
    ...data,
    hoursUsed: Math.round(hoursUsed * 10) / 10,
    hoursAvailable,
    percentage: data.percentage
  }
})

// Calculer la charge de la semaine
const weekWorkload = computed(() => {
  if (!props.workload?.daily || props.workload.daily.length === 0) {
    return { hoursUsed: 0, hoursAvailable: 0, percentage: 0 }
  }
  
  // Semaine de travail = 45h (5 jours * 9h)
  const totalAvailable = 45
  
  const totalHours = props.workload.daily.reduce((sum, day) => {
    const hoursAvailable = day.hoursAvailable || 9
    const hoursUsed = day.hoursUsed ?? (day.percentage * hoursAvailable / 100)
    return sum + hoursUsed
  }, 0)
  
  const percentage = totalAvailable > 0 ? (totalHours / totalAvailable) * 100 : 0
  
  return {
    hoursUsed: Math.round(totalHours * 10) / 10,
    hoursAvailable: totalAvailable,
    percentage
  }
})

const handleUserChange = () => {
  console.log('[WorkloadOverview] User changed to:', selectedUserId.value)
  console.log('[WorkloadOverview] Current user ID:', props.currentUserId)
  console.log('[WorkloadOverview] Available users:', props.users?.map(u => ({ id: u.id, name: u.displayName || u.email })))
  emit('userChange', selectedUserId.value)
}

const openDayDetails = (day: any) => {
  selectedDay.value = day
}
</script>

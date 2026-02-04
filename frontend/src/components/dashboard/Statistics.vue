<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
        </svg>
        Statistiques de la semaine
      </h2>
      
      <!-- État vide -->
      <div v-if="!statistics" class="text-center py-8 text-base-content/60">
        <p>Aucune statistique disponible pour le moment.</p>
      </div>
      
      <div v-else class="grid grid-cols-2 gap-4">
        <!-- Productivité -->
        <div class="stat bg-base-200 rounded-lg p-4">
          <div class="stat-title text-xs">Productivité</div>
          <div class="stat-value text-2xl" :class="getProductivityClass(statistics.productivity)">
            {{ statistics.productivity }}%
          </div>
          <div class="stat-desc text-xs">
            {{ statistics.tasksCompleted }}/{{ statistics.tasksCreated }} tâches
          </div>
        </div>

        <!-- Réunions -->
        <div class="stat bg-base-200 rounded-lg p-4">
          <div class="stat-title text-xs">Réunions</div>
          <div class="stat-value text-2xl">{{ statistics.meetingsCount }}</div>
          <div class="stat-desc text-xs">
            {{ statistics.meetingsDurationFormatted }}
          </div>
        </div>

        <!-- Tâches complétées -->
        <div class="stat bg-base-200 rounded-lg p-4">
          <div class="stat-title text-xs">Tâches complétées</div>
          <div class="stat-value text-2xl text-success">
            {{ statistics.tasksCompleted }}
          </div>
          <div class="stat-desc text-xs">Cette semaine</div>
        </div>

        <!-- Durée moyenne réunion -->
        <div class="stat bg-base-200 rounded-lg p-4">
          <div class="stat-title text-xs">Moy. par réunion</div>
          <div class="stat-value text-2xl">
            {{ getAverageMeetingDuration() }}
          </div>
          <div class="stat-desc text-xs">
            {{ statistics.meetingsDuration }} min total
          </div>
        </div>
      </div>

      <!-- Graphique visuel productivité -->
      <div v-if="statistics" class="mt-4">
        <div class="text-sm font-medium mb-2">Objectif de productivité</div>
        <div class="w-full bg-base-200 rounded-full h-4">
          <div 
            class="h-4 rounded-full transition-all duration-500 flex items-center justify-center text-xs font-bold text-white"
            :class="{
              'bg-error': statistics.productivity < 50,
              'bg-warning': statistics.productivity >= 50 && statistics.productivity < 80,
              'bg-success': statistics.productivity >= 80
            }"
            :style="{ width: Math.min(statistics.productivity, 100) + '%' }"
          >
            <span v-if="statistics.productivity > 15">{{ statistics.productivity }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Statistics {
  tasksCompleted: number
  tasksCreated: number
  productivity: number
  meetingsCount: number
  meetingsDuration: number
  meetingsDurationFormatted: string
}

const props = defineProps<{
  statistics: Statistics
}>()

const getProductivityClass = (productivity: number): string => {
  if (productivity >= 80) return 'text-success'
  if (productivity >= 50) return 'text-warning'
  return 'text-error'
}

const getAverageMeetingDuration = (): string => {
  if (props.statistics.meetingsCount === 0) return '0min'
  
  const avgMinutes = Math.round(props.statistics.meetingsDuration / props.statistics.meetingsCount)
  const hours = Math.floor(avgMinutes / 60)
  const mins = avgMinutes % 60
  
  if (hours > 0) {
    return mins > 0 ? `${hours}h${mins}` : `${hours}h`
  }
  
  return `${mins}min`
}
</script>

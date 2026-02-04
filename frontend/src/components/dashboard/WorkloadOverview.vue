<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
        </svg>
        Charge de travail
      </h2>

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
        <span>Vous avez {{ workload.overloadDays }} jour(s) de surcharge cette semaine</span>
      </div>

      <!-- Graphique barres -->
      <div class="space-y-2">
        <div v-for="day in workload.daily" :key="day.date" class="flex items-center gap-3">
          <div class="w-12 text-sm font-medium">{{ day.dayName }}</div>
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
          <div class="w-16 text-right text-sm">
            <span :class="{ 'text-error font-bold': day.isOverloaded }">
              {{ Math.round(day.percentage) }}%
            </span>
          </div>
        </div>
      </div>

      <!-- Légende -->
      <div class="flex gap-4 mt-4 text-xs">
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
</template>

<script setup lang="ts">
interface WorkloadData {
  daily: Array<{
    date: string
    dayName: string
    percentage: number
    isOverloaded: boolean
  }>
  overloadDays: number
  hasAlert: boolean
}

defineProps<{
  workload: WorkloadData
}>()
</script>

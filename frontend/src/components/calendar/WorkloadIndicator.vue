<script setup lang="ts">
import { computed } from 'vue'
import type { WorkloadData } from '@/composables/useWorkload'

const props = defineProps<{
  workload: WorkloadData | null
  loading?: boolean
  showDetails?: boolean
}>()

const statusEmoji = computed(() => {
  if (!props.workload) return '📊'
  const emojis = {
    normal: '🟢',
    busy: '🟡',
    overloaded: '🔴'
  }
  return emojis[props.workload.status]
})

const statusColors = computed(() => {
  if (!props.workload) return {
    bg: 'bg-gray-100',
    text: 'text-gray-600',
    border: 'border-gray-300',
    progress: 'bg-gray-500'
  }

  const colors = {
    normal: {
      bg: 'bg-green-50',
      text: 'text-green-700',
      border: 'border-green-300',
      progress: 'bg-green-500'
    },
    busy: {
      bg: 'bg-orange-50',
      text: 'text-orange-700',
      border: 'border-orange-300',
      progress: 'bg-orange-500'
    },
    overloaded: {
      bg: 'bg-red-50',
      text: 'text-red-700',
      border: 'border-red-300',
      progress: 'bg-red-500'
    }
  }
  return colors[props.workload.status]
})

const progressWidth = computed(() => {
  if (!props.workload) return '0%'
  return `${Math.min(100, props.workload.percentage)}%`
})
</script>

<template>
  <div v-if="loading" class="animate-pulse">
    <div class="h-16 bg-gray-200 rounded-lg"></div>
  </div>
  
  <div 
    v-else-if="workload" 
    :class="[
      'rounded-lg border-2 p-4 transition-all',
      statusColors.bg,
      statusColors.border
    ]"
  >
    <!-- Header -->
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <span class="text-2xl">{{ statusEmoji }}</span>
        <div>
          <h3 :class="['font-semibold text-lg', statusColors.text]">
            {{ workload.statusLabel }}
          </h3>
          <p class="text-sm text-gray-600">
            {{ workload.totalHours }}h / {{ workload.standardHours }}h
            <span class="text-xs">({{ workload.period === 'day' ? 'aujourd\'hui' : 'cette semaine' }})</span>
          </p>
        </div>
      </div>
      <div :class="['text-3xl font-bold', statusColors.text]">
        {{ workload.percentage }}%
      </div>
    </div>

    <!-- Progress bar -->
    <div class="w-full bg-gray-200 rounded-full h-3 mb-3 overflow-hidden">
      <div 
        :class="['h-full rounded-full transition-all duration-500', statusColors.progress]"
        :style="{ width: progressWidth }"
      ></div>
    </div>

    <!-- Stats -->
    <div class="flex gap-4 text-sm text-gray-600">
      <div>
        <span class="font-medium">{{ workload.eventCount }}</span> événement{{ workload.eventCount > 1 ? 's' : '' }}
      </div>
      <div>
        <span class="font-medium">{{ workload.meetingCount }}</span> réunion{{ workload.meetingCount > 1 ? 's' : '' }}
      </div>
    </div>

    <!-- Warnings -->
    <div v-if="workload.modifiers.tooManyMeetings || workload.modifiers.shortBreaks" class="mt-3 pt-3 border-t border-gray-200">
      <p class="text-sm font-medium text-gray-700 mb-1">⚠️ Alertes :</p>
      <ul class="text-sm text-gray-600 space-y-1">
        <li v-if="workload.modifiers.tooManyMeetings">
          • Trop de réunions dans la journée
        </li>
        <li v-if="workload.modifiers.shortBreaks">
          • Pauses insuffisantes entre les événements
        </li>
      </ul>
    </div>

    <!-- Details -->
    <details v-if="showDetails && workload.events.length > 0" class="mt-3 pt-3 border-t border-gray-200">
      <summary class="text-sm font-medium text-gray-700 cursor-pointer hover:text-gray-900">
        Détails des événements
      </summary>
      <ul class="mt-2 space-y-2">
        <li 
          v-for="event in workload.events" 
          :key="event.id"
          class="text-sm text-gray-600 flex justify-between"
        >
          <span>{{ event.title }}</span>
          <span class="text-gray-500">{{ Math.round(event.duration / 60) }}h</span>
        </li>
      </ul>
    </details>
  </div>

  <div v-else class="text-gray-500 text-sm">
    Aucune donnée de charge de travail disponible
  </div>
</template>

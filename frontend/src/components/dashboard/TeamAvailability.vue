<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
        </svg>
        Disponibilité de l'équipe
      </h2>
      
      <!-- État vide -->
      <div v-if="!teamAvailability || teamAvailability.length === 0" class="text-center py-8 text-base-content/60">
        <p>Aucune information d'équipe disponible.</p>
      </div>
      
      <div v-else class="space-y-3">
        <div 
          v-for="member in teamAvailability" 
          :key="member.id"
          class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200 transition-colors"
        >
          <!-- Avatar -->
          <div class="avatar">
            <div class="w-10 h-10 rounded-full ring ring-offset-base-100 ring-offset-2"
                 :class="getStatusRingClass(member.status)">
              <img v-if="member.avatar" :src="`${API_URL}/uploads/avatars/${member.avatar}`" :alt="member.name" />
              <div v-else class="bg-primary text-primary-content flex items-center justify-center text-sm font-bold">
                {{ getInitials(member.name) }}
              </div>
            </div>
          </div>
          
          <!-- Info -->
          <div class="flex-1 min-w-0">
            <div class="font-medium truncate">{{ member.name }}</div>
            <div class="flex items-center gap-2 text-xs text-base-content/70">
              <span class="flex items-center gap-1">
                <span v-html="getStatusIcon(member.status)"></span>
                {{ getStatusLabel(member.status) }}
              </span>
              <span class="text-base-content/50">•</span>
              <span :class="getWorkloadColorClass(member.workload)">
                {{ Math.round(member.workload) }}% charge
              </span>
            </div>
          </div>
          
          <!-- Indicateur de charge -->
          <div class="radial-progress text-xs" 
               :class="getWorkloadProgressClass(member.workload)"
               :style="`--value:${Math.min(member.workload, 100)}; --size:3rem;`"
               role="progressbar">
            {{ Math.round(member.workload) }}%
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000'

interface TeamMember {
  id: number
  name: string
  email: string
  avatar: string | null
  workload: number
  status: 'available' | 'busy' | 'absent'
}

defineProps<{
  teamAvailability: TeamMember[]
}>()

const getInitials = (name: string): string => {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}

const getStatusIcon = (status: string): string => {
  const icons: Record<string, string> = {
    available: '<span class="inline-block w-2 h-2 rounded-full bg-success"></span>',
    busy: '<span class="inline-block w-2 h-2 rounded-full bg-error"></span>',
    absent: '<span class="inline-block w-2 h-2 rounded-full bg-base-300"></span>'
  }
  return icons[status] || '<span class="inline-block w-2 h-2 rounded-full bg-base-300"></span>'
}

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    available: 'Disponible',
    busy: 'Occupé',
    absent: 'Absent'
  }
  return labels[status] || status
}

const getStatusRingClass = (status: string): string => {
  const classes: Record<string, string> = {
    available: 'ring-success',
    busy: 'ring-error',
    absent: 'ring-base-300'
  }
  return classes[status] || 'ring-base-300'
}

const getWorkloadColorClass = (workload: number): string => {
  if (workload > 100) return 'text-error font-bold'
  if (workload >= 80) return 'text-warning'
  return 'text-success'
}

const getWorkloadProgressClass = (workload: number): string => {
  if (workload > 100) return 'text-error'
  if (workload >= 80) return 'text-warning'
  return 'text-success'
}
</script>

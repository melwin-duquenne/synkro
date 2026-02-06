<template>
  <div class="card bg-base-100 shadow-xl">
    <div class="card-body">
      <h2 class="card-title">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>
        Notifications
      </h2>
      
      <!-- État vide global -->
      <div v-if="!notifications || (!notifications.upcomingDeadlines || notifications.upcomingDeadlines.length === 0)" class="text-center py-8 text-base-content/60">
        <svg class="w-12 h-12 mx-auto mb-3 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <p>Aucune notification. Tout est à jour !</p>
      </div>
      
      <!-- Deadlines proches -->
      <div v-else-if="notifications.upcomingDeadlines.length > 0">
        <h3 class="text-sm font-semibold mb-3 flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <span>Deadlines proches</span>
          <span class="badge badge-error badge-sm">{{ notifications.upcomingDeadlines.length }}</span>
        </h3>
        <div class="space-y-2">
          <div 
            v-for="task in notifications.upcomingDeadlines" 
            :key="task.id"
            class="alert alert-warning py-3"
          >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <div class="flex-1 min-w-0">
              <div class="font-medium truncate">{{ task.title }}</div>
              <div class="text-xs">
                Échéance: {{ formatDeadline(task.dueDate) }}
              </div>
            </div>
            <div 
              class="badge badge-sm"
              :class="getPriorityClass(task.priority)"
            >
              {{ task.priority }}
            </div>
          </div>
        </div>
      </div>

      <!-- Message si aucune notification -->
      <div v-else class="text-center py-8 text-base-content/50">
        <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <div>Aucune notification</div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface TaskItem {
  id: number
  title: string
  description: string | null
  status: string
  priority: string
  dueDate: string | null
  room: { id: number; name: string } | null
}

interface Notifications {
  upcomingDeadlines: TaskItem[]
}

defineProps<{
  notifications?: Notifications
}>()

const getPriorityClass = (priority: string): string => {
  const classes: Record<string, string> = {
    high: 'badge-error',
    medium: 'badge-warning',
    low: 'badge-success',
  }
  return classes[priority] || 'badge-ghost'
}

const formatDeadline = (dateString: string | null): string => {
  if (!dateString) return ''
  
  const date = new Date(dateString)
  const now = new Date()
  const tomorrow = new Date(now)
  tomorrow.setDate(tomorrow.getDate() + 1)
  
  const diffTime = date.getTime() - now.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  
  if (date.toDateString() === now.toDateString()) {
    return "Aujourd'hui"
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return 'Demain'
  } else if (diffDays <= 7) {
    return `Dans ${diffDays} jour${diffDays > 1 ? 's' : ''}`
  } else {
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })
  }
}
</script>

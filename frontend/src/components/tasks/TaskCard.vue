<script setup lang="ts">
interface TaskUser {
  id: number
  displayName: string
}

interface Task {
  id: number
  title: string
  description: string | null
  columnId: number
  columnName: string
  columnColor: string
  type: 'active' | 'archived'
  position: number
  assignedTo: TaskUser | null
  estimation: number | null
  createdAt: string
}

defineProps<{
  task: Task
  draggable?: boolean
}>()

defineEmits<{
  click: []
  dragstart: []
  dragend: []
}>()

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2)
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'short'
  })
}
</script>

<template>
  <div
    class="task-card bg-base-100 rounded-lg p-3 shadow-sm hover:shadow-md transition-shadow cursor-pointer border border-base-300"
    :class="{ 'opacity-60': task.type === 'archived' }"
    :draggable="draggable"
    @click="$emit('click')"
    @dragstart="$emit('dragstart')"
    @dragend="$emit('dragend')"
  >
    <!-- Title -->
    <h5 class="font-medium text-sm mb-2 line-clamp-2">{{ task.title }}</h5>

    <!-- Description preview -->
    <p
      v-if="task.description"
      class="text-xs text-base-content/60 mb-3 line-clamp-2"
    >
      {{ task.description }}
    </p>

    <!-- Footer -->
    <div class="flex items-center justify-between">
      <!-- Assigned user -->
      <div v-if="task.assignedTo" class="flex items-center gap-1">
        <div class="avatar placeholder">
          <div class="bg-primary text-primary-content rounded-full w-6">
            <span class="text-xs">{{ getInitials(task.assignedTo.displayName) }}</span>
          </div>
        </div>
        <span class="text-xs text-base-content/70 truncate max-w-[80px]">{{ task.assignedTo.displayName }}</span>
      </div>
      <div v-else></div>

      <div class="flex items-center gap-2">
        <!-- Archived badge -->
        <span v-if="task.type === 'archived'" class="badge badge-sm badge-warning">
          Archivée
        </span>

        <!-- Estimation -->
        <span v-if="task.estimation" class="badge badge-sm badge-primary">
          {{ task.estimation }}pt
        </span>

        <!-- Date -->
        <span class="text-xs text-base-content/50">{{ formatDate(task.createdAt) }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-card {
  user-select: none;
}

.task-card:active {
  cursor: grabbing;
}

.task-card[draggable='true'] {
  cursor: grab;
}

.task-card[draggable='true']:active {
  opacity: 0.5;
}
</style>

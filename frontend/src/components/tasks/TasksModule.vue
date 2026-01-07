<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import TaskCard from './TaskCard.vue'
import TaskDetailModal from './TaskDetailModal.vue'
import CreateTaskModal from './CreateTaskModal.vue'

const props = defineProps<{
  roomId: number
}>()

interface TaskUser {
  id: number
  displayName: string
}

interface Task {
  id: number
  title: string
  description: string | null
  status: 'todo' | 'in_progress' | 'done'
  position: number
  assignedTo: TaskUser | null
  estimation: number | null
  createdAt: string
}

const authStore = useAuthStore()
const tasks = ref<Task[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const showCreateModal = ref(false)
const showDetailModal = ref(false)
const selectedTask = ref<Task | null>(null)
const createStatus = ref<'todo' | 'in_progress' | 'done'>('todo')

const draggedTask = ref<Task | null>(null)

const columns = [
  { id: 'todo', title: 'À faire', color: 'bg-slate-500' },
  { id: 'in_progress', title: 'En cours', color: 'bg-blue-500' },
  { id: 'done', title: 'Terminé', color: 'bg-green-500' }
] as const

const tasksByStatus = computed(() => {
  const grouped: Record<string, Task[]> = {
    todo: [],
    in_progress: [],
    done: []
  }

  tasks.value.forEach(task => {
    if (grouped[task.status]) {
      grouped[task.status].push(task)
    }
  })

  // Sort by position
  Object.keys(grouped).forEach(status => {
    grouped[status].sort((a, b) => a.position - b.position)
  })

  return grouped
})

async function fetchTasks() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks`, {
      headers: authStore.getAuthHeaders()
    })

    if (!response.ok) throw new Error('Failed to fetch tasks')

    const data = await response.json()
    // API Platform retourne les données dans hydra:member (format JSON-LD)
    tasks.value = data['hydra:member'] || data.member || (Array.isArray(data) ? data : [])
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to fetch tasks'
  } finally {
    loading.value = false
  }
}

function openCreateModal(status: 'todo' | 'in_progress' | 'done') {
  createStatus.value = status
  showCreateModal.value = true
}

function openDetailModal(task: Task) {
  selectedTask.value = task
  showDetailModal.value = true
}

function handleTaskCreated(task: Task) {
  tasks.value.push(task)
  showCreateModal.value = false
}

function handleTaskUpdated(updatedTask: Task) {
  const index = tasks.value.findIndex(t => t.id === updatedTask.id)
  if (index !== -1) {
    tasks.value[index] = updatedTask
  }
  showDetailModal.value = false
  selectedTask.value = null
}

function handleTaskDeleted(taskId: number) {
  tasks.value = tasks.value.filter(t => t.id !== taskId)
  showDetailModal.value = false
  selectedTask.value = null
}

// Drag and Drop
function onDragStart(task: Task) {
  draggedTask.value = task
}

function onDragEnd() {
  draggedTask.value = null
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
}

async function onDrop(e: DragEvent, newStatus: 'todo' | 'in_progress' | 'done') {
  e.preventDefault()

  if (!draggedTask.value) return

  const task = draggedTask.value
  const oldStatus = task.status

  if (oldStatus === newStatus) {
    draggedTask.value = null
    return
  }

  // Optimistic update
  task.status = newStatus
  task.position = tasksByStatus.value[newStatus].length

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks/${task.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify({
        status: newStatus,
        position: task.position
      })
    })

    if (!response.ok) {
      // Revert on error
      task.status = oldStatus
      throw new Error('Failed to update task')
    }
  } catch (e) {
    console.error('Failed to move task:', e)
  }

  draggedTask.value = null
}

onMounted(() => {
  fetchTasks()
})
</script>

<template>
  <div class="tasks-module h-full flex flex-col bg-base-200 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-lg">Tâches</h3>
      <button class="btn btn-primary btn-sm" @click="openCreateModal('todo')">
        + Nouvelle tâche
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center">
      <div class="alert alert-error">
        <span>{{ error }}</span>
        <button class="btn btn-sm" @click="fetchTasks">Réessayer</button>
      </div>
    </div>

    <!-- Kanban Board -->
    <div v-else class="flex-1 flex gap-4 overflow-x-auto">
      <div
        v-for="column in columns"
        :key="column.id"
        class="kanban-column flex-1 min-w-[280px] max-w-[350px] flex flex-col"
      >
        <!-- Column Header -->
        <div class="flex items-center gap-2 mb-3">
          <div :class="[column.color, 'w-3 h-3 rounded-full']"></div>
          <h4 class="font-medium">{{ column.title }}</h4>
          <span class="badge badge-sm badge-ghost">
            {{ tasksByStatus[column.id]?.length || 0 }}
          </span>
        </div>

        <!-- Column Content -->
        <div
          class="flex-1 bg-base-300 rounded-lg p-2 space-y-2 overflow-y-auto min-h-[200px]"
          @dragover="onDragOver"
          @drop="onDrop($event, column.id)"
        >
          <TaskCard
            v-for="task in tasksByStatus[column.id]"
            :key="task.id"
            :task="task"
            :draggable="true"
            @click="openDetailModal(task)"
            @dragstart="onDragStart(task)"
            @dragend="onDragEnd"
          />

          <!-- Empty state -->
          <div
            v-if="!tasksByStatus[column.id]?.length"
            class="text-center py-8 text-base-content/50"
          >
            <p class="text-sm">Aucune tâche</p>
          </div>

          <!-- Add task button -->
          <button
            class="btn btn-ghost btn-sm w-full justify-start opacity-50 hover:opacity-100"
            @click="openCreateModal(column.id)"
          >
            + Ajouter une tâche
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <CreateTaskModal
      :open="showCreateModal"
      :room-id="props.roomId"
      :initial-status="createStatus"
      @close="showCreateModal = false"
      @created="handleTaskCreated"
    />

    <TaskDetailModal
      v-if="selectedTask"
      :open="showDetailModal"
      :room-id="props.roomId"
      :task="selectedTask"
      @close="showDetailModal = false; selectedTask = null"
      @updated="handleTaskUpdated"
      @deleted="handleTaskDeleted"
    />
  </div>
</template>

<style scoped>
.kanban-column {
  transition: background-color 0.2s;
}

.tasks-module {
  min-height: 400px;
}
</style>

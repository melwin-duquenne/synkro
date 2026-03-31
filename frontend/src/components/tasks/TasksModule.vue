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

interface KanbanColumn {
  id: number
  name: string
  color: string
  position: number
  roomId: number
  taskCount: number
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

const authStore = useAuthStore()
const tasks = ref<Task[]>([])
const columns = ref<KanbanColumn[]>([])
const loading = ref(true)
const error = ref<string | null>(null)

const showCreateModal = ref(false)
const showDetailModal = ref(false)
const selectedTask = ref<Task | null>(null)
const createColumnId = ref<number | null>(null)
const showArchived = ref(false)


const draggedTask = ref<Task | null>(null)
const draggedColumn = ref<KanbanColumn | null>(null)
const dragOverColumnIndex = ref<number | null>(null)

// Filters
const filterSearch = ref('')
const filterAssignedTo = ref<number | null>(null)
const filterEstimation = ref<number | null>(null)

// Column management
const deleteColumnError = ref('')
const showAddColumn = ref(false)
const newColumnName = ref('')
const newColumnColor = ref('bg-slate-500')
const editingColumnId = ref<number | null>(null)
const editColumnName = ref('')
const editColumnColor = ref('')

const colorOptions = [
  { value: 'bg-slate-500', label: 'Gris' },
  { value: 'bg-blue-500', label: 'Bleu' },
  { value: 'bg-green-500', label: 'Vert' },
  { value: 'bg-red-500', label: 'Rouge' },
  { value: 'bg-yellow-500', label: 'Jaune' },
  { value: 'bg-purple-500', label: 'Violet' },
  { value: 'bg-pink-500', label: 'Rose' },
  { value: 'bg-orange-500', label: 'Orange' },
  { value: 'bg-teal-500', label: 'Turquoise' },
]

// Get unique assigned users for filter dropdown
const assignedUsers = computed(() => {
  const users = new Map<number, TaskUser>()
  tasks.value.forEach(task => {
    if (task.assignedTo) {
      users.set(task.assignedTo.id, task.assignedTo)
    }
  })
  return Array.from(users.values())
})

// Filter and group tasks by column
const tasksByColumn = computed(() => {
  const grouped: Record<number, Task[]> = {}

  columns.value.forEach(col => {
    grouped[col.id] = []
  })

  tasks.value
    .filter(task => {
      // Filter by active/archived
      if (!showArchived.value && task.type === 'archived') return false
      if (showArchived.value && task.type !== 'archived') return false

      // Filter by search
      if (filterSearch.value) {
        const search = filterSearch.value.toLowerCase()
        const matchTitle = task.title.toLowerCase().includes(search)
        const matchDesc = task.description?.toLowerCase().includes(search) || false
        if (!matchTitle && !matchDesc) return false
      }

      // Filter by assigned user
      if (filterAssignedTo.value !== null) {
        if (!task.assignedTo || task.assignedTo.id !== filterAssignedTo.value) return false
      }

      // Filter by estimation
      if (filterEstimation.value !== null) {
        if (task.estimation !== filterEstimation.value) return false
      }

      return true
    })
    .forEach(task => {
      if (grouped[task.columnId]) {
        grouped[task.columnId].push(task)
      }
    })

  // Sort by position
  Object.keys(grouped).forEach(colId => {
    grouped[Number(colId)].sort((a, b) => a.position - b.position)
  })

  return grouped
})

async function fetchColumns() {
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns`, {
      headers: authStore.getAuthHeaders()
    })

    if (!response.ok) throw new Error('Impossible de charger les colonnes')

    const data = await response.json()
    columns.value = data['hydra:member'] || data.member || (Array.isArray(data) ? data : [])
  } catch (e) {
    console.error('Impossible de charger les colonnes :', e)
  }
}

async function fetchTasks() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks`, {
      headers: authStore.getAuthHeaders()
    })

    if (!response.ok) throw new Error('Impossible de charger les tâches')

    const data = await response.json()
    tasks.value = data['hydra:member'] || data.member || (Array.isArray(data) ? data : [])
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Impossible de charger les tâches'
  } finally {
    loading.value = false
  }
}

function openCreateModal(columnId: number) {
  createColumnId.value = columnId
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


// Drag and Drop for Tasks (unchanged)
function onDragStart(task: Task) {
  draggedTask.value = task
}
function onDragEnd() {
  draggedTask.value = null
}
function onDragOver(e: DragEvent) {
  e.preventDefault()
}
async function onDrop(e: DragEvent, newColumnId: number) {
  e.preventDefault()
  if (!draggedTask.value) return
  const task = draggedTask.value
  const oldColumnId = task.columnId
  if (oldColumnId === newColumnId) {
    draggedTask.value = null
    return
  }
  // Optimistic update
  const oldColumn = columns.value.find(c => c.id === oldColumnId)
  const newColumn = columns.value.find(c => c.id === newColumnId)
  task.columnId = newColumnId
  task.columnName = newColumn?.name || ''
  task.columnColor = newColumn?.color || ''
  task.position = (tasksByColumn.value[newColumnId]?.length || 0)
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks/${task.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify({
        columnId: newColumnId,
        position: task.position
      })
    })
    if (!response.ok) {
      // Revert on error
      task.columnId = oldColumnId
      task.columnName = oldColumn?.name || ''
      task.columnColor = oldColumn?.color || ''
      throw new Error('Impossible de mettre à jour la tâche')
    }
  } catch (e) {
    console.error('Impossible de déplacer la tâche :', e)
  }
  draggedTask.value = null
}

// Drag and Drop for Columns
// eslint-disable-next-line @typescript-eslint/no-unused-vars
function onColumnDragStart(column: KanbanColumn, index: number) {
  draggedColumn.value = column
  dragOverColumnIndex.value = null
}
function onColumnDragOver(e: DragEvent, index: number) {
  e.preventDefault()
  dragOverColumnIndex.value = index
}
function onColumnDrop(e: DragEvent, index: number) {
  e.preventDefault()
  if (!draggedColumn.value) return
  const fromIndex = columns.value.findIndex(c => c.id === draggedColumn.value!.id)
  if (fromIndex === -1 || fromIndex === index) {
    draggedColumn.value = null
    dragOverColumnIndex.value = null
    return
  }
  // Move column in array
  const moved = columns.value.splice(fromIndex, 1)[0]
  if (!moved) return
  columns.value.splice(index, 0, moved)
  // Re-index positions
  columns.value.forEach((col, idx) => {
    col.position = idx
  })
  // Save order to backend
  saveColumnOrder()
  draggedColumn.value = null
  dragOverColumnIndex.value = null
}
function onColumnDragEnd() {
  draggedColumn.value = null
  dragOverColumnIndex.value = null
}

async function saveColumnOrder() {
  try {
    const payload = {
      columns: columns.value.map((col, idx) => ({ id: col.id, position: idx }))
    }
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns/reorder`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify(payload)
    })
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      console.error('Reorder error:', errorData)
      throw new Error('Erreur lors de la sauvegarde de l\'ordre des colonnes')
    }
  } catch (e) {
    console.error('Erreur lors de la sauvegarde de l\'ordre des colonnes:', e)
  }
}

// Column management
async function addColumn() {
  if (!newColumnName.value.trim()) return

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify({
        name: newColumnName.value,
        color: newColumnColor.value
      })
    })

    if (!response.ok) throw new Error('Impossible de créer la colonne')

    const column = await response.json()
    columns.value.push(column)
    newColumnName.value = ''
    newColumnColor.value = 'bg-slate-500'
    showAddColumn.value = false
  } catch (e) {
    console.error('Impossible de créer la colonne :', e)
  }
}

function startEditColumn(col: KanbanColumn) {
  editingColumnId.value = col.id
  editColumnName.value = col.name
  editColumnColor.value = col.color
}

async function saveColumnEdit(col: KanbanColumn) {
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns/${col.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify({
        name: editColumnName.value,
        color: editColumnColor.value
      })
    })

    if (!response.ok) throw new Error('Impossible de mettre à jour la colonne')

    const updated = await response.json()
    const index = columns.value.findIndex(c => c.id === col.id)
    if (index !== -1) {
      columns.value[index] = updated
    }

    // Update tasks that reference this column
    tasks.value.forEach(task => {
      if (task.columnId === col.id) {
        task.columnName = updated.name
        task.columnColor = updated.color
      }
    })

    editingColumnId.value = null
  } catch (e) {
    console.error('Impossible de mettre à jour la colonne :', e)
  }
}

async function deleteColumn(col: KanbanColumn) {
  if (!confirm(`Supprimer la colonne "${col.name}" ? Les tâches actives doivent être déplacées d'abord.`)) return

  deleteColumnError.value = ''

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns/${col.id}`, {
      method: 'DELETE',
      headers: authStore.getAuthHeaders()
    })

    if (!response.ok) {
      const data = await response.json()
      deleteColumnError.value = data.detail || data.message || 'Impossible de supprimer cette colonne'
      return
    }

    columns.value = columns.value.filter(c => c.id !== col.id)
  } catch (e) {
    console.error('Impossible de supprimer la colonne :', e)
    deleteColumnError.value = 'Impossible de supprimer la colonne'
  }
}

function clearFilters() {
  filterSearch.value = ''
  filterAssignedTo.value = null
  filterEstimation.value = null
}

onMounted(async () => {
  await fetchColumns()
  await fetchTasks()
})
</script>

<template>
  <div class="tasks-module h-full flex flex-col bg-base-200 p-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-lg">Tâches</h3>
      <div class="flex items-center gap-2">
        <button
          class="btn btn-sm"
          :class="showArchived ? 'btn-warning' : 'btn-ghost'"
          @click="showArchived = !showArchived"
        >
          {{ showArchived ? 'Voir actives' : 'Voir archivées' }}
        </button>
        <button class="btn btn-primary btn-sm" @click="columns[0] && openCreateModal(columns[0].id)">
          + Nouvelle tâche
        </button>
      </div>
    </div>

    <!-- Column delete error -->
    <div v-if="deleteColumnError" class="alert alert-error mb-2">
      <span>{{ deleteColumnError }}</span>
      <button class="btn btn-sm btn-ghost" @click="deleteColumnError = ''">✕</button>
    </div>

    <!-- Filters bar -->
    <div class="flex flex-wrap items-center gap-2 mb-4">
      <input
        v-model="filterSearch"
        type="text"
        class="input input-bordered input-sm w-48"
        placeholder="Rechercher..."
      />
      <select
        v-model="filterAssignedTo"
        class="select select-bordered select-sm"
      >
        <option :value="null">Tous les membres</option>
        <option v-for="user in assignedUsers" :key="user.id" :value="user.id">
          {{ user.displayName }}
        </option>
      </select>
      <select
        v-model="filterEstimation"
        class="select select-bordered select-sm"
      >
        <option :value="null">Toutes estimations</option>
        <option :value="1">1 pt</option>
        <option :value="2">2 pts</option>
        <option :value="3">3 pts</option>
        <option :value="5">5 pts</option>
        <option :value="8">8 pts</option>
        <option :value="13">13 pts</option>
        <option :value="21">21 pts</option>
      </select>
      <button
        v-if="filterSearch || filterAssignedTo !== null || filterEstimation !== null"
        class="btn btn-ghost btn-sm"
        @click="clearFilters"
      >
        Effacer filtres
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
        v-for="(column, colIdx) in columns"
        :key="column.id"
        class="kanban-column flex-1 min-w-[280px] max-w-[350px] flex flex-col"
        :draggable="!showArchived"
        @dragstart="!showArchived ? onColumnDragStart(column, colIdx) : undefined"
        @dragover="!showArchived ? onColumnDragOver($event, colIdx) : undefined"
        @drop="!showArchived ? onColumnDrop($event, colIdx) : undefined"
        @dragend="!showArchived ? onColumnDragEnd() : undefined"
        :style="dragOverColumnIndex === colIdx ? 'background: rgba(0,0,0,0.04);' : ''"
      >
        <!-- Column Header -->
        <div class="flex items-center gap-2 mb-3 group">
          <template v-if="editingColumnId === column.id">
            <input
              v-model="editColumnName"
              type="text"
              class="input input-bordered input-xs flex-1"
              @keyup.enter="saveColumnEdit(column)"
            />
            <select v-model="editColumnColor" class="select select-bordered select-xs">
              <option v-for="c in colorOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
            </select>
            <button class="btn btn-xs btn-success" @click="saveColumnEdit(column)">OK</button>
            <button class="btn btn-xs btn-ghost" @click="editingColumnId = null">X</button>
          </template>
          <template v-else>
            <div :class="[column.color, 'w-3 h-3 rounded-full']"></div>
            <h4 class="font-medium">{{ column.name }}</h4>
            <span class="badge badge-sm badge-ghost">
              {{ tasksByColumn[column.id]?.length || 0 }}
            </span>
            <div class="ml-auto hidden group-hover:flex items-center gap-1">
              <button class="btn btn-ghost btn-xs" @click="startEditColumn(column)" title="Modifier">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
              </button>
              <button class="btn btn-ghost btn-xs text-error" @click="deleteColumn(column)" title="Supprimer">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
              </button>
            </div>
          </template>
        </div>

        <!-- Column Content -->
        <div
          class="flex-1 bg-base-300 rounded-lg p-2 space-y-2 overflow-y-auto min-h-[200px]"
          @dragover="onDragOver"
          @drop="onDrop($event, column.id)"
        >
          <TaskCard
            v-for="task in tasksByColumn[column.id]"
            :key="task.id"
            :task="task"
            :draggable="!showArchived"
            @click="openDetailModal(task)"
            @dragstart="onDragStart(task)"
            @dragend="onDragEnd"
          />

          <!-- Empty state -->
          <div
            v-if="!tasksByColumn[column.id]?.length"
            class="text-center py-8 text-base-content/50"
          >
            <p class="text-sm">Aucune tâche</p>
          </div>

          <!-- Add task button -->
          <button
            v-if="!showArchived"
            class="btn btn-ghost btn-sm w-full justify-start opacity-50 hover:opacity-100"
            @click="openCreateModal(column.id)"
          >
            + Ajouter une tâche
          </button>
        </div>
      </div>

      <!-- Add column button -->
      <div class="min-w-[280px] max-w-[350px] flex flex-col" v-if="!showArchived">
        <div v-if="showAddColumn" class="bg-base-300 rounded-lg p-4 space-y-2">
          <input
            v-model="newColumnName"
            type="text"
            class="input input-bordered input-sm w-full"
            placeholder="Nom de la colonne"
            @keyup.enter="addColumn"
          />
          <select v-model="newColumnColor" class="select select-bordered select-sm w-full">
            <option v-for="c in colorOptions" :key="c.value" :value="c.value">{{ c.label }}</option>
          </select>
          <div class="flex gap-2">
            <button class="btn btn-sm btn-primary flex-1" @click="addColumn">Ajouter</button>
            <button class="btn btn-sm btn-ghost" @click="showAddColumn = false">Annuler</button>
          </div>
        </div>
        <button
          v-else
          class="btn btn-ghost btn-sm h-12 border-2 border-dashed border-base-content/20 hover:border-base-content/40 w-full"
          @click="showAddColumn = true"
        >
          + Ajouter une colonne
        </button>
      </div>
    </div>

    <!-- Modals -->
    <CreateTaskModal
      :open="showCreateModal"
      :room-id="props.roomId"
      :columns="columns"
      :initial-column-id="createColumnId ?? undefined"
      @close="showCreateModal = false"
      @created="handleTaskCreated"
    />

    <TaskDetailModal
      v-if="selectedTask"
      :open="showDetailModal"
      :room-id="props.roomId"
      :task="selectedTask"
      :columns="columns"
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

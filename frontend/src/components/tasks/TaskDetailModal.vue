<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

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

const props = defineProps<{
  open: boolean
  roomId: number
  task: Task
}>()

const emit = defineEmits<{
  close: []
  updated: [task: Task]
  deleted: [taskId: number]
}>()

const authStore = useAuthStore()

const isEditing = ref(false)
const loading = ref(false)
const deleteConfirm = ref(false)

const editTitle = ref('')
const editDescription = ref('')
const editStatus = ref<'todo' | 'in_progress' | 'done'>('todo')
const editAssignedToId = ref<number | null>(null)
const editEstimation = ref<number | null>(null)

const members = ref<TaskUser[]>([])
const loadingMembers = ref(false)

const statusOptions = [
  { value: 'todo', label: 'À faire', color: 'badge-ghost' },
  { value: 'in_progress', label: 'En cours', color: 'badge-info' },
  { value: 'done', label: 'Terminé', color: 'badge-success' }
]

const estimationOptions = [
  { value: null, label: 'Non estimé' },
  { value: 1, label: '1 point' },
  { value: 2, label: '2 points' },
  { value: 3, label: '3 points' },
  { value: 5, label: '5 points' },
  { value: 8, label: '8 points' },
  { value: 13, label: '13 points' },
  { value: 21, label: '21 points' }
]

async function fetchMembers() {
  loadingMembers.value = true
  try {
    const response = await fetch('/api/entreprise/members', {
      headers: authStore.getAuthHeaders()
    })
    if (response.ok) {
      const data = await response.json()
      members.value = data['hydra:member'] || data.member || (Array.isArray(data) ? data : [])
    }
  } catch (e) {
    console.error('Failed to fetch members:', e)
  } finally {
    loadingMembers.value = false
  }
}

watch(() => props.task, (task) => {
  editTitle.value = task.title
  editDescription.value = task.description || ''
  editStatus.value = task.status
  editAssignedToId.value = task.assignedTo?.id || null
  editEstimation.value = task.estimation
}, { immediate: true })

watch(() => props.open, (isOpen) => {
  if (isOpen && members.value.length === 0) {
    fetchMembers()
  }
})

onMounted(() => {
  fetchMembers()
})

function startEditing() {
  editTitle.value = props.task.title
  editDescription.value = props.task.description || ''
  editStatus.value = props.task.status
  editAssignedToId.value = props.task.assignedTo?.id || null
  editEstimation.value = props.task.estimation
  isEditing.value = true
}

function cancelEditing() {
  isEditing.value = false
  deleteConfirm.value = false
}

async function saveChanges() {
  if (!editTitle.value.trim()) return

  loading.value = true

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks/${props.task.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/ld+json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify({
        title: editTitle.value,
        description: editDescription.value || null,
        status: editStatus.value,
        assignedToId: editAssignedToId.value,
        estimation: editEstimation.value
      })
    })

    if (!response.ok) throw new Error('Failed to update task')

    const updatedTask = await response.json()
    emit('updated', updatedTask)
    isEditing.value = false
  } catch (e) {
    console.error('Failed to update task:', e)
  } finally {
    loading.value = false
  }
}

async function deleteTask() {
  loading.value = true

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks/${props.task.id}`, {
      method: 'DELETE',
      headers: authStore.getAuthHeaders()
    })

    if (!response.ok) throw new Error('Failed to delete task')

    emit('deleted', props.task.id)
  } catch (e) {
    console.error('Failed to delete task:', e)
  } finally {
    loading.value = false
    deleteConfirm.value = false
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: 'numeric',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

function handleClose() {
  isEditing.value = false
  deleteConfirm.value = false
  emit('close')
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box max-w-2xl">
      <!-- Header -->
      <div class="flex items-start justify-between mb-4">
        <div class="flex-1">
          <!-- Title - View mode -->
          <h3 v-if="!isEditing" class="font-bold text-xl">{{ task.title }}</h3>

          <!-- Title - Edit mode -->
          <input
            v-else
            v-model="editTitle"
            type="text"
            class="input input-bordered w-full text-xl font-bold"
            placeholder="Titre de la tâche"
          />
        </div>

        <button class="btn btn-sm btn-circle btn-ghost" @click="handleClose">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Status -->
      <div class="mb-4">
        <label class="label">
          <span class="label-text font-medium">Statut</span>
        </label>
        <div v-if="!isEditing" class="flex gap-2">
          <span
            class="badge"
            :class="statusOptions.find(s => s.value === task.status)?.color"
          >
            {{ statusOptions.find(s => s.value === task.status)?.label }}
          </span>
        </div>
        <select
          v-else
          v-model="editStatus"
          class="select select-bordered w-full max-w-xs"
        >
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>

      <!-- Description -->
      <div class="mb-4">
        <label class="label">
          <span class="label-text font-medium">Description</span>
        </label>

        <!-- View mode -->
        <div v-if="!isEditing">
          <p v-if="task.description" class="text-base-content/80 whitespace-pre-wrap">
            {{ task.description }}
          </p>
          <p v-else class="text-base-content/50 italic">Aucune description</p>
        </div>

        <!-- Edit mode -->
        <textarea
          v-else
          v-model="editDescription"
          class="textarea textarea-bordered w-full h-32"
          placeholder="Ajoutez une description..."
        ></textarea>
      </div>

      <!-- Assigned to -->
      <div class="mb-4">
        <label class="label">
          <span class="label-text font-medium">Assigné à</span>
        </label>

        <!-- View mode -->
        <div v-if="!isEditing">
          <div v-if="task.assignedTo" class="flex items-center gap-2">
            <div class="avatar placeholder">
              <div class="bg-primary text-primary-content rounded-full w-8">
                <span class="text-sm">
                  {{ task.assignedTo.displayName.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2) }}
                </span>
              </div>
            </div>
            <span>{{ task.assignedTo.displayName }}</span>
          </div>
          <p v-else class="text-base-content/50 italic">Non assigné</p>
        </div>

        <!-- Edit mode -->
        <select
          v-else
          v-model="editAssignedToId"
          class="select select-bordered w-full max-w-xs"
          :disabled="loadingMembers"
        >
          <option :value="null">Non assigné</option>
          <option v-for="member in members" :key="member.id" :value="member.id">
            {{ member.displayName }}
          </option>
        </select>
      </div>

      <!-- Estimation -->
      <div class="mb-4">
        <label class="label">
          <span class="label-text font-medium">Estimation</span>
        </label>

        <!-- View mode -->
        <div v-if="!isEditing">
          <span v-if="task.estimation" class="badge badge-primary">
            {{ task.estimation }} point{{ task.estimation > 1 ? 's' : '' }}
          </span>
          <p v-else class="text-base-content/50 italic">Non estimé</p>
        </div>

        <!-- Edit mode -->
        <select
          v-else
          v-model="editEstimation"
          class="select select-bordered w-full max-w-xs"
        >
          <option v-for="option in estimationOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
      </div>

      <!-- Meta info -->
      <div class="text-sm text-base-content/50 mb-6">
        Créée le {{ formatDate(task.createdAt) }}
      </div>

      <!-- Delete confirmation -->
      <div v-if="deleteConfirm" class="alert alert-warning mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span>Êtes-vous sûr de vouloir supprimer cette tâche ?</span>
        <div class="flex gap-2">
          <button class="btn btn-sm" @click="deleteConfirm = false">Annuler</button>
          <button
            class="btn btn-sm btn-error"
            :class="{ 'loading': loading }"
            @click="deleteTask"
          >
            Supprimer
          </button>
        </div>
      </div>

      <!-- Actions -->
      <div class="modal-action">
        <div v-if="!isEditing" class="flex gap-2 w-full justify-between">
          <button class="btn btn-error btn-outline" @click="deleteConfirm = true">
            Supprimer
          </button>
          <div class="flex gap-2">
            <button class="btn" @click="handleClose">Fermer</button>
            <button class="btn btn-primary" @click="startEditing">Modifier</button>
          </div>
        </div>

        <div v-else class="flex gap-2">
          <button class="btn" @click="cancelEditing">Annuler</button>
          <button
            class="btn btn-primary"
            :class="{ 'loading': loading }"
            :disabled="!editTitle.trim() || loading"
            @click="saveChanges"
          >
            Enregistrer
          </button>
        </div>
      </div>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button @click="handleClose">close</button>
    </form>
  </dialog>
</template>

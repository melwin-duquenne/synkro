<script setup lang="ts">
import { ref, watch } from 'vue'
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
  createdAt: string
}

const props = defineProps<{
  open: boolean
  roomId: number
  initialStatus?: 'todo' | 'in_progress' | 'done'
}>()

const emit = defineEmits<{
  close: []
  created: [task: Task]
}>()

const authStore = useAuthStore()

const title = ref('')
const description = ref('')
const status = ref<'todo' | 'in_progress' | 'done'>('todo')
const loading = ref(false)
const error = ref<string | null>(null)

const statusOptions = [
  { value: 'todo', label: 'À faire' },
  { value: 'in_progress', label: 'En cours' },
  { value: 'done', label: 'Terminé' }
]

watch(() => props.initialStatus, (newStatus) => {
  if (newStatus) {
    status.value = newStatus
  }
}, { immediate: true })

watch(() => props.open, (isOpen) => {
  if (isOpen && props.initialStatus) {
    status.value = props.initialStatus
  }
})

async function handleSubmit() {
  if (!title.value.trim()) return

  loading.value = true
  error.value = null

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...authStore.getAuthHeaders()
      },
      body: JSON.stringify({
        title: title.value,
        description: description.value || null,
        status: status.value
      })
    })

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.error || 'Failed to create task')
    }

    const task = await response.json()
    emit('created', task)
    resetForm()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Failed to create task'
  } finally {
    loading.value = false
  }
}

function resetForm() {
  title.value = ''
  description.value = ''
  status.value = props.initialStatus || 'todo'
  error.value = null
}

function handleClose() {
  resetForm()
  emit('close')
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">Nouvelle tâche</h3>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Title -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Titre *</span>
          </label>
          <input
            v-model="title"
            type="text"
            class="input input-bordered w-full"
            placeholder="Titre de la tâche"
            required
            autofocus
          />
        </div>

        <!-- Description -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Description</span>
            <span class="label-text-alt">Optionnel</span>
          </label>
          <textarea
            v-model="description"
            class="textarea textarea-bordered w-full h-24"
            placeholder="Décrivez la tâche..."
          ></textarea>
        </div>

        <!-- Status -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Statut</span>
          </label>
          <select v-model="status" class="select select-bordered w-full">
            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Error -->
        <div v-if="error" class="alert alert-error">
          <span>{{ error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button type="button" class="btn" @click="handleClose">Annuler</button>
          <button
            type="submit"
            class="btn btn-primary"
            :class="{ 'loading': loading }"
            :disabled="!title.trim() || loading"
          >
            {{ loading ? 'Création...' : 'Créer la tâche' }}
          </button>
        </div>
      </form>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button @click="handleClose">close</button>
    </form>
  </dialog>
</template>

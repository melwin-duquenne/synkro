<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import { useCalendarStore } from '@/stores/calendar'
import type { CalendarEvent, EventType } from '@/types'

const props = defineProps<{
  open: boolean
  roomId: number
  event: CalendarEvent | null
  initialDate: Date | null
}>()

const emit = defineEmits<{
  close: []
  saved: []
  deleted: []
}>()

const calendarStore = useCalendarStore()

const title = ref('')
const description = ref('')
const eventType = ref<EventType>('other')
const startDate = ref('')
const startTime = ref('09:00')
const endDate = ref('')
const endTime = ref('10:00')
const isAllDay = ref(false)
const location = ref('')
const color = ref('')
const isPrivate = ref(false)
const loading = ref(false)
const selectedParticipantIds = ref<number[]>([])
const participantSearch = ref('')

const isEditMode = computed(() => !!props.event)

// Only show participants for room events
const showParticipants = computed(() => props.roomId > 0)

const eventTypes: { value: EventType; label: string }[] = [
  { value: 'meeting', label: 'Réunion' },
  { value: 'absence', label: 'Absence' },
  { value: 'blocked', label: 'Bloqué' },
  { value: 'reminder', label: 'Rappel' },
  { value: 'other', label: 'Autre' }
]

const colorOptions = [
  { value: '', label: 'Par défaut' },
  { value: '#3b82f6', label: 'Bleu' },
  { value: '#10b981', label: 'Vert' },
  { value: '#f59e0b', label: 'Orange' },
  { value: '#ef4444', label: 'Rouge' },
  { value: '#8b5cf6', label: 'Violet' },
  { value: '#ec4899', label: 'Rose' }
]

const filteredUsers = computed(() => {
  const search = participantSearch.value.toLowerCase()
  return calendarStore.enterpriseUsers.filter(user =>
    user.displayName.toLowerCase().includes(search) ||
    user.email.toLowerCase().includes(search)
  )
})

const selectedParticipants = computed(() => {
  return calendarStore.enterpriseUsers.filter(u =>
    selectedParticipantIds.value.includes(u.id)
  )
})

function formatDateForInput(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatTimeForInput(date: Date): string {
  return date.toTimeString().slice(0, 5)
}

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    // Load users when modal opens
    if (showParticipants.value && calendarStore.enterpriseUsers.length === 0) {
      calendarStore.fetchEnterpriseUsers()
    }

    if (props.event) {
      // Edit mode
      title.value = props.event.title
      description.value = props.event.description || ''
      eventType.value = props.event.eventType
      isAllDay.value = props.event.isAllDay
      location.value = props.event.location || ''
      color.value = props.event.color || ''
      isPrivate.value = props.event.isPrivate
      selectedParticipantIds.value = props.event.participants?.map(p => p.userId) || []

      const start = new Date(props.event.startDate)
      const end = new Date(props.event.endDate)
      startDate.value = formatDateForInput(start)
      startTime.value = formatTimeForInput(start)
      endDate.value = formatDateForInput(end)
      endTime.value = formatTimeForInput(end)
    } else if (props.initialDate) {
      // Create mode with initial date
      resetForm()
      startDate.value = formatDateForInput(props.initialDate)
      endDate.value = formatDateForInput(props.initialDate)
    } else {
      resetForm()
    }
  }
})

function resetForm() {
  const today = new Date()
  title.value = ''
  description.value = ''
  eventType.value = 'other'
  startDate.value = formatDateForInput(today)
  startTime.value = '09:00'
  endDate.value = formatDateForInput(today)
  endTime.value = '10:00'
  isAllDay.value = false
  location.value = ''
  color.value = ''
  isPrivate.value = false
  selectedParticipantIds.value = []
  participantSearch.value = ''
}

function toggleParticipant(userId: number) {
  const index = selectedParticipantIds.value.indexOf(userId)
  if (index === -1) {
    selectedParticipantIds.value.push(userId)
  } else {
    selectedParticipantIds.value.splice(index, 1)
  }
}

function removeParticipant(userId: number) {
  selectedParticipantIds.value = selectedParticipantIds.value.filter(id => id !== userId)
}

async function handleSubmit() {
  if (!title.value.trim()) return

  loading.value = true

  const startDateTime = isAllDay.value
    ? `${startDate.value}T00:00:00`
    : `${startDate.value}T${startTime.value}:00`

  const endDateTime = isAllDay.value
    ? `${endDate.value}T23:59:59`
    : `${endDate.value}T${endTime.value}:00`

  const data = {
    title: title.value,
    description: description.value || undefined,
    eventType: eventType.value,
    startDate: startDateTime,
    endDate: endDateTime,
    isAllDay: isAllDay.value,
    location: location.value || undefined,
    color: color.value || undefined,
    isPrivate: isPrivate.value,
    roomId: props.roomId > 0 ? props.roomId : null,
    participantIds: showParticipants.value ? selectedParticipantIds.value : []
  }

  let success = false

  if (props.event) {
    const result = await calendarStore.updateEvent(props.event.id, data)
    success = !!result
  } else {
    const result = await calendarStore.createEvent(data)
    success = !!result
  }

  loading.value = false

  if (success) {
    emit('saved')
  }
}

async function handleDelete() {
  if (!props.event) return

  if (!confirm('Supprimer cet événement ?')) return

  loading.value = true
  const success = await calendarStore.deleteEvent(props.event.id)
  loading.value = false

  if (success) {
    emit('deleted')
  }
}

onMounted(() => {
  if (showParticipants.value) {
    calendarStore.fetchEnterpriseUsers()
  }
})
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box max-w-2xl max-h-[90vh] overflow-y-auto">
      <h3 class="font-bold text-lg mb-4">
        {{ isEditMode ? 'Modifier l\'événement' : 'Nouvel événement' }}
      </h3>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Title -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Titre *</span>
          </label>
          <input
            v-model="title"
            type="text"
            placeholder="Titre de l'événement"
            class="input input-bordered w-full"
            required
          />
        </div>

        <!-- Event Type -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Type</span>
          </label>
          <select v-model="eventType" class="select select-bordered w-full">
            <option v-for="type in eventTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- All Day Toggle -->
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-4">
            <input
              v-model="isAllDay"
              type="checkbox"
              class="checkbox checkbox-primary"
            />
            <span class="label-text">Journée entière</span>
          </label>
        </div>

        <!-- Date/Time -->
        <div class="grid grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Date de début *</span>
            </label>
            <input
              v-model="startDate"
              type="date"
              class="input input-bordered w-full"
              required
            />
          </div>
          <div v-if="!isAllDay" class="form-control">
            <label class="label">
              <span class="label-text">Heure de début</span>
            </label>
            <input
              v-model="startTime"
              type="time"
              class="input input-bordered w-full"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="form-control">
            <label class="label">
              <span class="label-text">Date de fin *</span>
            </label>
            <input
              v-model="endDate"
              type="date"
              class="input input-bordered w-full"
              required
            />
          </div>
          <div v-if="!isAllDay" class="form-control">
            <label class="label">
              <span class="label-text">Heure de fin</span>
            </label>
            <input
              v-model="endTime"
              type="time"
              class="input input-bordered w-full"
            />
          </div>
        </div>

        <!-- Participants (only for room events) -->
        <div v-if="showParticipants" class="form-control">
          <label class="label">
            <span class="label-text">Participants</span>
            <span class="label-text-alt">{{ selectedParticipantIds.length }} sélectionné(s)</span>
          </label>

          <!-- Selected participants -->
          <div v-if="selectedParticipants.length > 0" class="flex flex-wrap gap-2 mb-2">
            <div
              v-for="user in selectedParticipants"
              :key="user.id"
              class="badge badge-primary gap-2"
            >
              {{ user.displayName }}
              <button type="button" class="btn btn-ghost btn-xs p-0" @click="removeParticipant(user.id)">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <!-- Search users -->
          <input
            v-model="participantSearch"
            type="text"
            placeholder="Rechercher un utilisateur..."
            class="input input-bordered input-sm w-full mb-2"
          />

          <!-- Users list -->
          <div class="border border-base-300 rounded-lg max-h-40 overflow-y-auto">
            <div
              v-for="user in filteredUsers"
              :key="user.id"
              class="flex items-center gap-3 p-2 hover:bg-base-200 cursor-pointer"
              @click="toggleParticipant(user.id)"
            >
              <input
                type="checkbox"
                :checked="selectedParticipantIds.includes(user.id)"
                class="checkbox checkbox-sm checkbox-primary"
                @click.stop
                @change="toggleParticipant(user.id)"
              />
              <div class="flex-1">
                <div class="font-medium text-sm">{{ user.displayName }}</div>
                <div class="text-xs text-base-content/60">{{ user.email }}</div>
              </div>
            </div>
            <div v-if="filteredUsers.length === 0" class="p-4 text-center text-base-content/50 text-sm">
              Aucun utilisateur trouvé
            </div>
          </div>
        </div>

        <!-- Location -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Lieu</span>
          </label>
          <input
            v-model="location"
            type="text"
            placeholder="Salle de réunion, lien visio..."
            class="input input-bordered w-full"
          />
        </div>

        <!-- Description -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Description</span>
          </label>
          <textarea
            v-model="description"
            class="textarea textarea-bordered w-full"
            placeholder="Détails de l'événement..."
            rows="3"
          ></textarea>
        </div>

        <!-- Color -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Couleur</span>
          </label>
          <div class="flex gap-2">
            <button
              v-for="c in colorOptions"
              :key="c.value"
              type="button"
              class="w-8 h-8 rounded-full border-2 transition-transform hover:scale-110"
              :class="{
                'border-primary ring-2 ring-primary ring-offset-2': color === c.value,
                'border-base-300': color !== c.value
              }"
              :style="{ backgroundColor: c.value || 'var(--fallback-b2, oklch(var(--b2)))' }"
              @click="color = c.value"
            ></button>
          </div>
        </div>

        <!-- Private Toggle -->
        <div class="form-control">
          <label class="label cursor-pointer justify-start gap-4">
            <input
              v-model="isPrivate"
              type="checkbox"
              class="checkbox checkbox-primary"
            />
            <span class="label-text">Événement privé (visible uniquement par vous)</span>
          </label>
        </div>

        <!-- Error -->
        <div v-if="calendarStore.error" class="alert alert-error">
          <span>{{ calendarStore.error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button
            v-if="isEditMode"
            type="button"
            class="btn btn-error btn-outline"
            :disabled="loading"
            @click="handleDelete"
          >
            Supprimer
          </button>
          <div class="flex-1"></div>
          <button type="button" class="btn" @click="emit('close')">
            Annuler
          </button>
          <button
            type="submit"
            class="btn btn-primary"
            :class="{ 'loading': loading }"
            :disabled="loading || !title.trim()"
          >
            {{ isEditMode ? 'Enregistrer' : 'Créer' }}
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="emit('close')">close</button>
    </form>
  </dialog>
</template>

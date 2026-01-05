<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoomsStore } from '@/stores/rooms'
import { useRouter } from 'vue-router'

defineProps<{
  open: boolean
}>()

const emit = defineEmits<{
  close: []
}>()

const router = useRouter()
const roomsStore = useRoomsStore()

const name = ref('')
const selectedModules = ref<string[]>([])
const visibility = ref<'enterprise' | 'private'>('enterprise')
const selectedTemplate = ref<number | null>(null)

const isValid = computed(() => name.value.trim() && selectedModules.value.length > 0)

function applyTemplate(templateId: number) {
  const template = roomsStore.templates.find(t => t.id === templateId)
  if (template) {
    selectedTemplate.value = templateId
    selectedModules.value = template.modules.map(m => m.code)
  }
}

function toggleModule(code: string) {
  selectedTemplate.value = null // Clear template selection when manually toggling
  const index = selectedModules.value.indexOf(code)
  if (index === -1) {
    selectedModules.value.push(code)
  } else {
    selectedModules.value.splice(index, 1)
  }
}

async function handleSubmit() {
  if (!isValid.value) return

  const room = await roomsStore.createRoom({
    name: name.value,
    modules: selectedModules.value,
    visibility: visibility.value
  })

  if (room) {
    emit('close')
    router.push(`/room/${room.id}`)
  }
}

function handleClose() {
  name.value = ''
  selectedModules.value = []
  selectedTemplate.value = null
  visibility.value = 'enterprise'
  emit('close')
}

onMounted(() => {
  roomsStore.fetchModules()
  roomsStore.fetchTemplates()
})
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">Créer une nouvelle room</h3>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Room Name -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Nom de la room</span>
          </label>
          <input
            v-model="name"
            type="text"
            placeholder="Ma room collaborative"
            class="input input-bordered w-full"
            required
          />
        </div>

        <!-- Visibility -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Visibilité</span>
          </label>
          <div class="flex gap-4">
            <label class="label cursor-pointer gap-2">
              <input
                v-model="visibility"
                type="radio"
                name="visibility"
                value="enterprise"
                class="radio radio-primary"
              />
              <span>Entreprise</span>
              <span class="text-xs text-base-content/60">(Tous les membres)</span>
            </label>
            <label class="label cursor-pointer gap-2">
              <input
                v-model="visibility"
                type="radio"
                name="visibility"
                value="private"
                class="radio radio-primary"
              />
              <span>Privée</span>
              <span class="text-xs text-base-content/60">(Invitation requise)</span>
            </label>
          </div>
        </div>

        <!-- Templates -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Partir d'un template</span>
            <span class="label-text-alt text-base-content/60">Optionnel</span>
          </label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <button
              v-for="template in roomsStore.templates"
              :key="template.id"
              type="button"
              class="btn btn-sm"
              :class="selectedTemplate === template.id ? 'btn-primary' : 'btn-outline'"
              @click="applyTemplate(template.id)"
            >
              {{ template.name }}
            </button>
          </div>
        </div>

        <!-- Modules -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Modules</span>
            <span class="label-text-alt">{{ selectedModules.length }} sélectionné(s)</span>
          </label>
          <div class="grid grid-cols-2 gap-3">
            <label
              v-for="module in roomsStore.modules"
              :key="module.code"
              class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
              :class="selectedModules.includes(module.code) ? 'border-primary bg-primary/5' : 'border-base-300 hover:border-primary/50'"
            >
              <input
                type="checkbox"
                :checked="selectedModules.includes(module.code)"
                class="checkbox checkbox-primary mt-1"
                @change="toggleModule(module.code)"
              />
              <div class="flex-1">
                <div class="font-medium">{{ module.name }}</div>
                <div class="text-xs text-base-content/60">{{ module.description }}</div>
              </div>
            </label>
          </div>
        </div>

        <!-- Error -->
        <div v-if="roomsStore.error" class="alert alert-error">
          <span>{{ roomsStore.error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button type="button" class="btn" @click="handleClose">Annuler</button>
          <button
            type="submit"
            class="btn btn-primary"
            :class="{ 'loading': roomsStore.loading }"
            :disabled="!isValid || roomsStore.loading"
          >
            {{ roomsStore.loading ? 'Création...' : 'Créer la room' }}
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="handleClose">close</button>
    </form>
  </dialog>
</template>

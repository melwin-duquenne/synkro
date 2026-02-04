<script setup lang="ts">
import { ref } from 'vue'
import type { ModuleRoom } from '@/types'

const props = defineProps<{
  moduleRooms: ModuleRoom[]
}>()

const emit = defineEmits<{
  close: []
  save: [orderedModules: number[]]
}>()

const modules = ref([...props.moduleRooms].sort((a, b) => (a.displayOrder || 0) - (b.displayOrder || 0)))
const draggedIndex = ref<number | null>(null)

function startDrag(index: number) {
  draggedIndex.value = index
}

function drop(targetIndex: number) {
  if (draggedIndex.value === null) return

  const item = modules.value[draggedIndex.value]
  if (!item) return

  modules.value.splice(draggedIndex.value, 1)
  modules.value.splice(targetIndex, 0, item)

  draggedIndex.value = null
}

function moveUp(index: number) {
  if (index === 0) return
  const temp = modules.value[index]
  const prev = modules.value[index - 1]
  if (!temp || !prev) return

  modules.value[index] = prev
  modules.value[index - 1] = temp
}

function moveDown(index: number) {
  if (index === modules.value.length - 1) return
  const temp = modules.value[index]
  const next = modules.value[index + 1]
  if (!temp || !next) return

  modules.value[index] = next
  modules.value[index + 1] = temp
}

function save() {
  const orderedIds = modules.value.map(m => m.id)
  emit('save', orderedIds)
}
</script>

<template>
  <div class="modal modal-open">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">Réorganiser les modules</h3>

      <p class="text-sm text-base-content/70 mb-4">
        Glissez-déposez ou utilisez les flèches pour changer l'ordre d'affichage des modules
      </p>

      <div class="space-y-2">
        <div
          v-for="(mr, index) in modules"
          :key="mr.id"
          draggable="true"
          @dragstart="startDrag(index)"
          @dragover.prevent
          @drop="drop(index)"
          class="flex items-center gap-3 p-3 bg-base-200 rounded-lg cursor-move hover:bg-base-300 transition-colors"
          :class="{ 'opacity-50': draggedIndex === index }"
        >
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
          </svg>

          <div class="flex-1 font-medium">
            <span class="badge badge-sm badge-primary mr-2">{{ index + 1 }}</span>
            {{ mr.module.name }}
          </div>

          <div class="flex gap-1">
            <button
              class="btn btn-xs btn-ghost"
              :disabled="index === 0"
              @click="moveUp(index)"
              title="Monter"
            >
              ▲
            </button>
            <button
              class="btn btn-xs btn-ghost"
              :disabled="index === modules.length - 1"
              @click="moveDown(index)"
              title="Descendre"
            >
              ▼
            </button>
          </div>
        </div>
      </div>

      <div class="modal-action">
        <button class="btn" @click="emit('close')">Annuler</button>
        <button class="btn btn-primary" @click="save">Enregistrer</button>
      </div>
    </div>
    <div class="modal-backdrop" @click="emit('close')"></div>
  </div>
</template>

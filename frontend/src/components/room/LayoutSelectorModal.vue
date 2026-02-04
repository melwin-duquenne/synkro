<script setup lang="ts">
import { ref } from 'vue'

const emit = defineEmits<{
  close: []
  select: [layout: string]
}>()

const layouts = [
  {
    value: 'tabs',
    name: 'Onglets',
    description: 'Chaque module dans son onglet',
    icon: '📑'
  },
  {
    value: 'grid-2x2',
    name: 'Grille 2×2',
    description: '4 modules en grille',
    icon: '▦'
  },
  {
    value: 'split-horizontal',
    name: 'Division horizontale',
    description: 'Un module en haut, un en bas',
    icon: '⬒'
  },
  {
    value: 'split-vertical',
    name: 'Division verticale',
    description: 'Un module à gauche, un à droite',
    icon: '⬓'
  },
  {
    value: 'sidebar-left',
    name: 'Barre latérale gauche',
    description: 'Un module à gauche, deux à droite',
    icon: '◧'
  },
  {
    value: 'sidebar-right',
    name: 'Barre latérale droite',
    description: 'Deux modules à gauche, un à droite',
    icon: '◨'
  },
  {
    value: 'main-sidebar',
    name: 'Principal + Barre',
    description: 'Un module principal large avec barre latérale',
    icon: '▬'
  }
]

const selectedLayout = ref('tabs')

function selectLayout(layout: string) {
  selectedLayout.value = layout
  emit('select', layout)
  emit('close')
}
</script>

<template>
  <div class="modal modal-open">
    <div class="modal-box max-w-4xl">
      <h3 class="font-bold text-lg mb-4">Choisir la disposition des modules</h3>

      <div class="grid grid-cols-2 gap-4">
        <button
          v-for="layout in layouts"
          :key="layout.value"
          class="card bg-base-200 hover:bg-base-300 transition-colors cursor-pointer p-4 text-left"
          :class="{ 'ring-2 ring-primary': selectedLayout === layout.value }"
          @click="selectLayout(layout.value)"
        >
          <div class="flex items-start gap-3">
            <div class="text-4xl">{{ layout.icon }}</div>
            <div class="flex-1">
              <h4 class="font-semibold">{{ layout.name }}</h4>
              <p class="text-sm text-base-content/70">{{ layout.description }}</p>
            </div>
          </div>
        </button>
      </div>

      <div class="modal-action">
        <button class="btn" @click="emit('close')">Annuler</button>
      </div>
    </div>
    <div class="modal-backdrop" @click="emit('close')"></div>
  </div>
</template>

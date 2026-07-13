<script setup lang="ts">
import { ref } from "vue";

const emit = defineEmits<{
  close: [];
  select: [layout: string];
}>();

const layouts = [
  {
    value: "tabs",
    name: "Onglets",
    description: "Chaque module dans son onglet",
    icon: "📑",
  },
  {
    value: "grid-2x2",
    name: "Grille 2×2",
    description: "4 modules en grille",
    icon: "▦",
  },
  {
    value: "split-horizontal",
    name: "Division horizontale",
    description: "Un module en haut, un en bas",
    icon: "⬒",
  },
  {
    value: "split-vertical",
    name: "Division verticale",
    description: "Un module à gauche, un à droite",
    icon: "⬓",
  },
  {
    value: "sidebar-left",
    name: "Barre latérale gauche",
    description: "Un module à gauche, deux à droite",
    icon: "◧",
  },
  {
    value: "sidebar-right",
    name: "Barre latérale droite",
    description: "Deux modules à gauche, un à droite",
    icon: "◨",
  },
  {
    value: "main-sidebar",
    name: "Principal + Barre",
    description: "Un module principal large avec barre latérale",
    icon: "▬",
  },
];

const selectedLayout = ref("tabs");

function selectLayout(layout: string) {
  selectedLayout.value = layout;
  emit("select", layout);
  emit("close");
}
</script>

<template>
  <div class="modal modal-open">
    <div class="modal-box layout-selector-modal max-w-4xl">
      <div class="layout-selector-header">
        <div class="layout-selector-icon" aria-hidden="true">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 6a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2H6a2 2 0 01-2-2v-4zm10 0a2 2 0 012-2h4a2 2 0 012 2v4a2 2 0 01-2 2h-4a2 2 0 01-2-2v-4z"
            />
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-[#e7f0fa]">
            Choisir la disposition des modules
          </h3>
          <p class="text-sm text-[#8ea4be]">
            Sélectionnez la structure qui met le mieux en valeur les modules de
            la room
          </p>
        </div>
      </div>

      <div class="layout-grid grid grid-cols-2 gap-4">
        <button
          v-for="layout in layouts"
          :key="layout.value"
          class="layout-card text-left"
          :class="{ 'is-selected': selectedLayout === layout.value }"
          @click="selectLayout(layout.value)"
        >
          <div class="layout-card-top">
            <div class="layout-icon">{{ layout.icon }}</div>
            <div class="flex-1">
              <h4 class="layout-name">{{ layout.name }}</h4>
              <p class="layout-description">{{ layout.description }}</p>
            </div>
          </div>
          <div class="layout-preview" aria-hidden="true">
            <span></span>
            <span></span>
            <span></span>
          </div>
        </button>
      </div>

      <div class="modal-action layout-selector-footer">
        <button class="btn-soft" @click="emit('close')">Annuler</button>
      </div>
    </div>
    <div class="modal-backdrop" @click="emit('close')"></div>
  </div>
</template>

<style scoped>
.layout-selector-modal {
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background:
    radial-gradient(
      120% 120% at 0% 0%,
      rgba(64, 142, 214, 0.12),
      transparent 45%
    ),
    linear-gradient(165deg, rgba(10, 22, 40, 0.96), rgba(14, 29, 49, 0.98));
  color: #dbe6f2;
}

.layout-selector-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}

.layout-selector-icon {
  width: 2.6rem;
  height: 2.6rem;
  border-radius: 0.95rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #8bc2ef;
  background: rgba(64, 142, 214, 0.18);
  border: 1px solid rgba(91, 163, 232, 0.35);
  flex-shrink: 0;
}

.layout-grid {
  gap: 0.85rem;
}

.layout-card {
  position: relative;
  overflow: hidden;
  border-radius: 16px;
  padding: 1rem;
  background: rgba(12, 26, 44, 0.56);
  border: 1px solid rgba(255, 255, 255, 0.08);
  transition:
    transform 0.18s ease,
    border-color 0.18s ease,
    background-color 0.18s ease,
    box-shadow 0.18s ease;
}

.layout-card:hover {
  transform: translateY(-1px);
  background: rgba(17, 34, 55, 0.82);
  border-color: rgba(91, 163, 232, 0.3);
}

.layout-card.is-selected {
  border-color: rgba(91, 163, 232, 0.7);
  background: linear-gradient(
    165deg,
    rgba(34, 77, 117, 0.52),
    rgba(12, 26, 44, 0.94)
  );
  box-shadow:
    0 0 0 1px rgba(91, 163, 232, 0.18),
    0 16px 30px rgba(0, 0, 0, 0.24);
}

.layout-card-top {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
}

.layout-icon {
  width: 3rem;
  height: 3rem;
  border-radius: 1rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(64, 142, 214, 0.16);
  color: #d8ecff;
  font-size: 1.45rem;
  border: 1px solid rgba(91, 163, 232, 0.28);
  flex-shrink: 0;
}

.layout-name {
  color: #e7f0fa;
  font-weight: 700;
  margin-bottom: 0.15rem;
}

.layout-description {
  color: #8ea4be;
  font-size: 0.875rem;
  line-height: 1.35;
}

.layout-preview {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 0.35rem;
  margin-top: 0.95rem;
}

.layout-preview span {
  height: 0.45rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.07);
}

.layout-card.is-selected .layout-preview span {
  background: rgba(91, 163, 232, 0.62);
}

.layout-selector-footer {
  margin-top: 0.35rem;
  justify-content: flex-end;
}

.btn-soft {
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(15, 31, 53, 0.78);
  color: #c8d8ea;
  padding: 0.55rem 0.95rem;
  transition: 0.2s ease;
}

.btn-soft:hover {
  color: #e7f0fa;
  border-color: rgba(91, 163, 232, 0.45);
}

@media (max-width: 640px) {
  .layout-grid {
    grid-template-columns: 1fr;
  }

  .layout-selector-header {
    align-items: flex-start;
  }
}
</style>

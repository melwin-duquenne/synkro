<script setup lang="ts">
import { ref } from "vue";
import type { ModuleRoom } from "@/types";

const props = defineProps<{
  moduleRooms: ModuleRoom[];
}>();

const emit = defineEmits<{
  close: [];
  save: [orderedModules: number[]];
}>();

const modules = ref(
  [...props.moduleRooms].sort(
    (a, b) => (a.displayOrder || 0) - (b.displayOrder || 0),
  ),
);
const draggedIndex = ref<number | null>(null);

function startDrag(index: number) {
  draggedIndex.value = index;
}

function drop(targetIndex: number) {
  if (draggedIndex.value === null) return;

  const item = modules.value[draggedIndex.value];
  if (!item) return;

  modules.value.splice(draggedIndex.value, 1);
  modules.value.splice(targetIndex, 0, item);

  draggedIndex.value = null;
}

function moveUp(index: number) {
  if (index === 0) return;
  const temp = modules.value[index];
  const prev = modules.value[index - 1];
  if (!temp || !prev) return;

  modules.value[index] = prev;
  modules.value[index - 1] = temp;
}

function moveDown(index: number) {
  if (index === modules.value.length - 1) return;
  const temp = modules.value[index];
  const next = modules.value[index + 1];
  if (!temp || !next) return;

  modules.value[index] = next;
  modules.value[index + 1] = temp;
}

function save() {
  const orderedIds = modules.value.map((m) => m.id);
  emit("save", orderedIds);
}
</script>

<template>
  <div class="modal modal-open">
    <div class="modal-box module-order-modal">
      <div class="module-order-header">
        <div class="module-order-icon" aria-hidden="true">
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
              d="M4 8h16M4 16h16"
            />
          </svg>
        </div>
        <div>
          <h3 class="text-xl font-semibold text-[#e7f0fa]">
            Réorganiser les modules
          </h3>
          <p class="text-sm text-[#8ea4be]">
            Glissez-déposez ou utilisez les flèches pour changer l’ordre
            d’affichage
          </p>
        </div>
      </div>

      <div class="module-order-list space-y-2">
        <div
          v-for="(mr, index) in modules"
          :key="mr.id"
          draggable="true"
          @dragstart="startDrag(index)"
          @dragover.prevent
          @drop="drop(index)"
          class="module-order-item"
          :class="{ 'opacity-50': draggedIndex === index }"
        >
          <div class="module-order-grip" aria-hidden="true">
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
                d="M4 8h2M8 8h2M12 8h2M16 8h2M4 16h2M8 16h2M12 16h2M16 16h2"
              />
            </svg>
          </div>

          <div class="flex-1 min-w-0">
            <div class="module-order-meta">
              <span class="module-order-index">{{ index + 1 }}</span>
              <span class="module-order-name">{{ mr.module.name }}</span>
            </div>
            <p class="module-order-subtitle">
              Position {{ index + 1 }} sur {{ modules.length }}
            </p>
          </div>

          <div class="module-order-actions">
            <button
              class="module-order-arrow"
              :disabled="index === 0"
              @click="moveUp(index)"
              title="Monter"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 15l7-7 7 7"
                />
              </svg>
            </button>
            <button
              class="module-order-arrow"
              :disabled="index === modules.length - 1"
              @click="moveDown(index)"
              title="Descendre"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <div class="modal-action module-order-footer">
        <button class="btn-soft" @click="emit('close')">Annuler</button>
        <button class="btn-primary-custom" @click="save">Enregistrer</button>
      </div>
    </div>
    <div class="modal-backdrop" @click="emit('close')"></div>
  </div>
</template>

<style scoped>
.module-order-modal {
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background:
    radial-gradient(
      120% 120% at 100% 0%,
      rgba(64, 142, 214, 0.1),
      transparent 45%
    ),
    linear-gradient(165deg, rgba(10, 22, 40, 0.96), rgba(14, 29, 49, 0.98));
  color: #dbe6f2;
}

.module-order-header {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  margin-bottom: 1.25rem;
}

.module-order-icon {
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

.module-order-item {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  padding: 0.9rem 1rem;
  border-radius: 16px;
  background: rgba(12, 26, 44, 0.56);
  border: 1px solid rgba(255, 255, 255, 0.08);
  cursor: move;
  transition:
    transform 0.18s ease,
    border-color 0.18s ease,
    background-color 0.18s ease;
}

.module-order-item:hover {
  background: rgba(17, 34, 55, 0.82);
  border-color: rgba(91, 163, 232, 0.28);
  transform: translateY(-1px);
}

.module-order-grip {
  width: 2.3rem;
  height: 2.3rem;
  border-radius: 0.9rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #8ea4be;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.06);
  flex-shrink: 0;
}

.module-order-meta {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  min-width: 0;
}

.module-order-index {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.8rem;
  height: 1.8rem;
  padding: 0 0.45rem;
  border-radius: 999px;
  background: rgba(91, 163, 232, 0.18);
  border: 1px solid rgba(91, 163, 232, 0.35);
  color: #cfe8ff;
  font-size: 0.78rem;
  font-weight: 700;
  flex-shrink: 0;
}

.module-order-name {
  min-width: 0;
  font-weight: 600;
  color: #e7f0fa;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.module-order-subtitle {
  margin-top: 0.2rem;
  color: #8ea4be;
  font-size: 0.78rem;
}

.module-order-actions {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  flex-shrink: 0;
}

.module-order-arrow {
  width: 2.15rem;
  height: 2.15rem;
  border-radius: 0.75rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #dbe6f2;
  background: rgba(15, 31, 53, 0.78);
  border: 1px solid rgba(255, 255, 255, 0.1);
  transition: 0.18s ease;
}

.module-order-arrow:hover:enabled {
  background: rgba(64, 142, 214, 0.2);
  border-color: rgba(91, 163, 232, 0.4);
}

.module-order-arrow:disabled {
  opacity: 0.35;
  cursor: not-allowed;
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

.btn-primary-custom {
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
  border-radius: 10px;
  padding: 0.65rem 1rem;
  transition: 0.2s ease;
}

.btn-primary-custom:hover {
  filter: brightness(1.06);
  transform: translateY(-1px);
}

.module-order-footer {
  margin-top: 0.5rem;
}

@media (max-width: 640px) {
  .module-order-header {
    align-items: flex-start;
  }

  .module-order-item {
    align-items: flex-start;
  }

  .module-order-actions {
    flex-direction: column;
  }
}
</style>

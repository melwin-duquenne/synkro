<script setup lang="ts">
import { ref, watch } from "vue";

const props = defineProps<{
  show: boolean;
}>();

const emit = defineEmits<{
  close: [];
  create: [name: string];
}>();

const folderName = ref("");

watch(
  () => props.show,
  (val) => {
    if (val) folderName.value = "";
  },
);

function handleSubmit() {
  if (!folderName.value.trim()) return;
  emit("create", folderName.value.trim());
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': show }">
    <div class="modal-box create-folder-modal">
      <div class="modal-head">
        <div class="icon-wrap" aria-hidden="true">
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
              d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
            />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-lg text-[#e7f0fa]">Nouveau dossier</h3>
          <p class="text-xs text-[#8ea4be]">
            Organisez vos fichiers dans la room
          </p>
        </div>
      </div>

      <form @submit.prevent="handleSubmit">
        <div class="form-control mb-4">
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Nom du dossier</span>
          </label>
          <input
            v-model="folderName"
            type="text"
            class="folder-input"
            placeholder="Mon dossier"
            maxlength="255"
            autofocus
          />
          <p class="text-xs text-[#7f95ad] mt-2">
            Evitez les noms trop longs pour une navigation plus lisible.
          </p>
        </div>

        <div class="modal-action mt-0">
          <button type="button" class="btn-soft" @click="emit('close')">
            Annuler
          </button>
          <button
            type="submit"
            class="btn-primary-custom"
            :disabled="!folderName.trim()"
          >
            Creer
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop" @click="emit('close')">
      <button>close</button>
    </form>
  </dialog>
</template>

<style scoped>
.create-folder-modal {
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background:
    radial-gradient(
      120% 130% at 0% 0%,
      rgba(91, 163, 232, 0.12),
      transparent 55%
    ),
    linear-gradient(165deg, rgba(14, 29, 49, 0.96), rgba(11, 24, 41, 0.97));
  color: #dbe6f2;
}

.modal-head {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.1rem;
}

.icon-wrap {
  width: 2.2rem;
  height: 2.2rem;
  border-radius: 0.8rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #8bc2ef;
  border: 1px solid rgba(91, 163, 232, 0.4);
  background: rgba(64, 142, 214, 0.2);
}

.folder-input {
  width: 100%;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.13);
  background: rgba(10, 21, 36, 0.9);
  color: #e7f0fa;
  padding: 0.7rem 0.85rem;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.folder-input::placeholder {
  color: #7f95ad;
}

.folder-input:focus {
  border-color: rgba(91, 163, 232, 0.75);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.18);
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
  border-radius: 10px;
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
  padding: 0.55rem 0.95rem;
  transition:
    transform 0.2s ease,
    filter 0.2s ease;
}

.btn-primary-custom:hover:enabled {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.btn-primary-custom:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>

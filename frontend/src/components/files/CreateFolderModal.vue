<script setup lang="ts">
import { ref, watch } from 'vue'

const props = defineProps<{
  show: boolean
}>()

const emit = defineEmits<{
  close: []
  create: [name: string]
}>()

const folderName = ref('')

watch(() => props.show, (val) => {
  if (val) folderName.value = ''
})

function handleSubmit() {
  if (!folderName.value.trim()) return
  emit('create', folderName.value.trim())
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': show }">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">Nouveau dossier</h3>
      <form @submit.prevent="handleSubmit">
        <div class="form-control">
          <label class="label">
            <span class="label-text">Nom du dossier</span>
          </label>
          <input
            v-model="folderName"
            type="text"
            class="input input-bordered w-full"
            placeholder="Mon dossier"
            maxlength="255"
            autofocus
          />
        </div>
        <div class="modal-action">
          <button type="button" class="btn" @click="emit('close')">Annuler</button>
          <button type="submit" class="btn btn-primary" :disabled="!folderName.trim()">Creer</button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop" @click="emit('close')">
      <button>close</button>
    </form>
  </dialog>
</template>

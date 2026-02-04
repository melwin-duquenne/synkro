<script setup lang="ts">
import { ref, watch } from 'vue'
import type { FileResource } from '@/types'

const props = defineProps<{
  show: boolean
  file: FileResource | null
}>()

const emit = defineEmits<{
  close: []
  rename: [newName: string]
}>()

const newName = ref('')

watch(() => props.show, (val) => {
  if (val && props.file) {
    newName.value = props.file.fileName
  }
})

function handleSubmit() {
  if (!newName.value.trim()) return
  emit('rename', newName.value.trim())
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': show }">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">Renommer</h3>
      <form @submit.prevent="handleSubmit">
        <div class="form-control">
          <label class="label">
            <span class="label-text">Nouveau nom</span>
          </label>
          <input
            v-model="newName"
            type="text"
            class="input input-bordered w-full"
            maxlength="255"
            autofocus
          />
        </div>
        <div class="modal-action">
          <button type="button" class="btn" @click="emit('close')">Annuler</button>
          <button type="submit" class="btn btn-primary" :disabled="!newName.trim()">Renommer</button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop" @click="emit('close')">
      <button>close</button>
    </form>
  </dialog>
</template>

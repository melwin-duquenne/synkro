<script setup lang="ts">
import { computed } from 'vue'
import type { FileResource } from '@/types'

const props = defineProps<{
  show: boolean
  file: FileResource | null
  roomId: number
}>()

const emit = defineEmits<{
  close: []
  download: [file: FileResource]
}>()

const previewType = computed(() => {
  if (!props.file) return 'none'
  const mime = props.file.mimeType || ''
  if (mime.startsWith('image/')) return 'image'
  if (mime === 'application/pdf') return 'pdf'
  return 'unsupported'
})

const fileUrl = computed(() => {
  if (!props.file?.filePath) return ''
  return props.file.filePath
})
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': show }">
    <div class="modal-box max-w-4xl max-h-[90vh]">
      <div class="flex items-center justify-between mb-4">
        <h3 class="font-bold text-lg truncate pr-4">{{ file?.fileName }}</h3>
        <button class="btn btn-ghost btn-sm btn-square" @click="emit('close')">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <div v-if="previewType === 'image'" class="flex justify-center">
        <img
          :src="fileUrl"
          :alt="file?.fileName"
          class="max-w-full max-h-[70vh] object-contain rounded"
        />
      </div>

      <div v-else-if="previewType === 'pdf'" class="w-full" style="height: 70vh;">
        <iframe
          :src="fileUrl"
          class="w-full h-full rounded border"
          :title="file?.fileName"
        />
      </div>

      <div v-else class="text-center py-12 text-base-content/50">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
        <p class="text-lg font-medium mb-2">Apercu non disponible</p>
        <p class="text-sm mb-4">Ce type de fichier ne peut pas etre previsualise</p>
        <button v-if="file" class="btn btn-primary" @click="emit('download', file)">
          Telecharger
        </button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop" @click="emit('close')">
      <button>close</button>
    </form>
  </dialog>
</template>

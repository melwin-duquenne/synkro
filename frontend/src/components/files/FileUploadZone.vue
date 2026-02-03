<script setup lang="ts">
import { ref } from 'vue'

const ALLOWED_EXTENSIONS = [
  'pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg',
  'docx', 'xlsx', 'pptx', 'doc', 'xls', 'ppt',
  'zip', 'rar', '7z',
  'txt', 'csv', 'md'
]

const MAX_SIZE = 100 * 1024 * 1024 // 100 MB

const emit = defineEmits<{
  upload: [files: File[]]
}>()

const isDragging = ref(false)
const fileInput = ref<HTMLInputElement | null>(null)
const validationError = ref<string | null>(null)

function validateFiles(fileList: File[]): File[] {
  validationError.value = null
  const valid: File[] = []

  for (const file of fileList) {
    const ext = file.name.split('.').pop()?.toLowerCase() || ''
    if (!ALLOWED_EXTENSIONS.includes(ext)) {
      validationError.value = `Type non autorise: .${ext}`
      continue
    }
    if (file.size > MAX_SIZE) {
      validationError.value = `Fichier trop volumineux: ${file.name} (max 100 Mo)`
      continue
    }
    valid.push(file)
  }

  return valid
}

function handleDrop(e: DragEvent) {
  isDragging.value = false
  const droppedFiles = Array.from(e.dataTransfer?.files || [])
  const valid = validateFiles(droppedFiles)
  if (valid.length) emit('upload', valid)
}

function handleFileSelect(e: Event) {
  const input = e.target as HTMLInputElement
  const selectedFiles = Array.from(input.files || [])
  const valid = validateFiles(selectedFiles)
  if (valid.length) emit('upload', valid)
  input.value = ''
}

function triggerFileInput() {
  fileInput.value?.click()
}
</script>

<template>
  <div
    class="border-2 border-dashed rounded-lg p-6 text-center transition-colors"
    :class="isDragging ? 'border-primary bg-primary/10' : 'border-base-content/20 hover:border-base-content/40'"
    @dragenter.prevent="isDragging = true"
    @dragover.prevent="isDragging = true"
    @dragleave.prevent="isDragging = false"
    @drop.prevent="handleDrop"
  >
    <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto mb-2 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
    </svg>
    <p class="text-sm text-base-content/60 mb-2">
      Glissez-deposez vos fichiers ici ou
    </p>
    <button class="btn btn-sm btn-primary" @click="triggerFileInput">
      Parcourir
    </button>
    <input
      ref="fileInput"
      type="file"
      multiple
      class="hidden"
      @change="handleFileSelect"
    />
    <p v-if="validationError" class="text-error text-xs mt-2">{{ validationError }}</p>
    <p class="text-xs text-base-content/40 mt-2">
      Max 100 Mo. PDF, images, Office, archives, texte
    </p>
  </div>
</template>

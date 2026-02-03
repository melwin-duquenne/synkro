<script setup lang="ts">
import { computed, ref } from 'vue'
import type { FileResource } from '@/types'

const props = defineProps<{
  file: FileResource
}>()

const emit = defineEmits<{
  open: [file: FileResource]
  rename: [file: FileResource]
  move: [file: FileResource]
  download: [file: FileResource]
  delete: [file: FileResource]
  dropInto: [fileId: number, targetFolderId: number]
}>()

const isDragOverFolder = ref(false)

const iconClass = computed(() => {
  if (props.file.isFolder) return 'folder'
  const mime = props.file.mimeType || ''
  if (mime.startsWith('image/')) return 'image'
  if (mime === 'application/pdf') return 'pdf'
  if (mime.includes('word') || mime.includes('document')) return 'word'
  if (mime.includes('sheet') || mime.includes('excel')) return 'excel'
  if (mime.includes('presentation') || mime.includes('powerpoint')) return 'ppt'
  if (mime.includes('zip') || mime.includes('rar') || mime.includes('7z')) return 'archive'
  if (mime.startsWith('text/')) return 'text'
  return 'default'
})

const iconSvg = computed(() => {
  switch (iconClass.value) {
    case 'folder':
      return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />'
    case 'image':
      return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />'
    case 'pdf':
      return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />'
    case 'archive':
      return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />'
    default:
      return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />'
  }
})

const iconColor = computed(() => {
  switch (iconClass.value) {
    case 'folder': return 'text-warning'
    case 'image': return 'text-success'
    case 'pdf': return 'text-error'
    case 'word': return 'text-info'
    case 'excel': return 'text-success'
    case 'ppt': return 'text-warning'
    case 'archive': return 'text-secondary'
    case 'text': return 'text-base-content'
    default: return 'text-base-content/50'
  }
})

function formatSize(bytes: number): string {
  if (bytes === 0) return '-'
  const units = ['o', 'Ko', 'Mo', 'Go']
  let i = 0
  let size = bytes
  while (size >= 1024 && i < units.length - 1) {
    size /= 1024
    i++
  }
  return `${size.toFixed(i > 0 ? 1 : 0)} ${units[i]}`
}

function formatDate(dateStr: string): string {
  return new Date(dateStr).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

function truncateName(name: string, max = 28): string {
  if (name.length <= max) return name
  const ext = name.lastIndexOf('.')
  if (ext > 0 && name.length - ext <= 6) {
    const extension = name.slice(ext)
    return name.slice(0, max - extension.length - 3) + '...' + extension
  }
  return name.slice(0, max - 3) + '...'
}

// Drag source: any file/folder can be dragged
function onDragStart(e: DragEvent) {
  e.dataTransfer!.effectAllowed = 'move'
  e.dataTransfer!.setData('application/x-file-id', String(props.file.id))
}

// Drop target: only folders accept drops
function onDragOver(e: DragEvent) {
  if (!props.file.isFolder) return
  if (!e.dataTransfer?.types.includes('application/x-file-id')) return
  e.preventDefault()
  e.dataTransfer!.dropEffect = 'move'
  isDragOverFolder.value = true
}

function onDragLeave() {
  isDragOverFolder.value = false
}

function onDrop(e: DragEvent) {
  isDragOverFolder.value = false
  if (!props.file.isFolder) return
  const fileId = e.dataTransfer?.getData('application/x-file-id')
  if (!fileId) return
  e.preventDefault()
  e.stopPropagation()
  const draggedId = Number(fileId)
  if (draggedId === props.file.id) return
  emit('dropInto', draggedId, props.file.id)
}
</script>

<template>
  <div
    class="card bg-base-200 hover:bg-base-300 cursor-pointer transition-colors group"
    :class="{ 'ring-2 ring-primary bg-primary/10': isDragOverFolder }"
    draggable="true"
    @dragstart="onDragStart"
    @dragover="onDragOver"
    @dragleave="onDragLeave"
    @drop="onDrop"
    @dblclick="emit('open', file)"
  >
    <div class="card-body p-4">
      <div class="flex items-start justify-between">
        <div class="flex items-center gap-3 min-w-0 flex-1">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-10 w-10 shrink-0"
            :class="iconColor"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            v-html="iconSvg"
          />
          <div class="min-w-0">
            <p class="font-medium text-sm" :title="file.fileName">
              {{ truncateName(file.fileName) }}
            </p>
            <div class="flex items-center gap-2 text-xs text-base-content/50 mt-1">
              <span v-if="!file.isFolder">{{ formatSize(file.size) }}</span>
              <span v-if="file.isFolder">{{ file.childCount }} item{{ file.childCount !== 1 ? 's' : '' }}</span>
              <span>{{ formatDate(file.createdAt) }}</span>
            </div>
            <p v-if="file.user" class="text-xs text-base-content/40 mt-0.5">
              {{ file.user.displayName }}
            </p>
          </div>
        </div>

        <!-- Dropdown menu -->
        <div class="dropdown dropdown-end opacity-0 group-hover:opacity-100 transition-opacity">
          <label tabindex="0" class="btn btn-ghost btn-xs btn-square" @click.stop>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01" />
            </svg>
          </label>
          <ul tabindex="0" class="dropdown-content z-[1] menu p-2 shadow bg-base-100 rounded-box w-44">
            <li><a @click.stop="emit('rename', file)">Renommer</a></li>
            <li><a @click.stop="emit('move', file)">Deplacer</a></li>
            <li v-if="!file.isFolder"><a @click.stop="emit('download', file)">Telecharger</a></li>
            <li><a @click.stop="emit('delete', file)" class="text-error">Supprimer</a></li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

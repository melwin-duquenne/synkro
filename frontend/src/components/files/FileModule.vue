<script setup lang="ts">
import { ref, onMounted, onUnmounted, watch } from "vue";
import type { FileResource } from "@/types";
import { useFilesStore } from "@/stores/files";
import FileCard from "./FileCard.vue";
import FileUploadZone from "./FileUploadZone.vue";
import CreateFolderModal from "./CreateFolderModal.vue";
import RenameModal from "./RenameModal.vue";
import MoveModal from "./MoveModal.vue";
import FilePreviewModal from "./FilePreviewModal.vue";

const props = defineProps<{
  roomId: number;
}>();

const store = useFilesStore();

// Modal states
const showCreateFolder = ref(false);
const showRename = ref(false);
const showMove = ref(false);
const showPreview = ref(false);
const selectedFile = ref<FileResource | null>(null);

// Upload progress
const uploads = ref<{ name: string; progress: number }[]>([]);
const showUploadZone = ref(false);

// Drag overlay
const isDragOver = ref(false);
let dragCounter = 0;

// Search debounce
const searchInput = ref("");
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

onMounted(() => {
  store.resetNavigation();
  store.fetchFiles(props.roomId);
});

onUnmounted(() => {
  if (searchTimeout) clearTimeout(searchTimeout);
});

watch(searchInput, (val) => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    store.searchQuery = val;
    store.fetchFiles(props.roomId);
  }, 300);
});

// Sort
function setSort(field: string) {
  if (store.sortField === field) {
    store.sortOrder = store.sortOrder === "ASC" ? "DESC" : "ASC";
  } else {
    store.sortField = field;
    store.sortOrder = "ASC";
  }
  store.fetchFiles(props.roomId);
}

// Filter
function setFilter(type: string) {
  store.filterType = store.filterType === type ? "" : type;
  store.fetchFiles(props.roomId);
}

// Upload
async function handleUpload(files: File[]) {
  let hasError = false;
  for (const file of files) {
    const entry = { name: file.name, progress: 0 };
    uploads.value.push(entry);

    const ok = await store.uploadFile(props.roomId, file, (pct) => {
      entry.progress = pct;
    });

    if (!ok) hasError = true;

    // Remove from upload list after a short delay
    setTimeout(
      () => {
        const idx = uploads.value.indexOf(entry);
        if (idx >= 0) uploads.value.splice(idx, 1);
      },
      ok ? 1000 : 3000,
    );
  }
  // Save error before fetchFiles clears it
  const uploadError = store.error;
  await store.fetchFiles(props.roomId);
  if (hasError && uploadError) {
    store.error = uploadError;
  }
  showUploadZone.value = false;
}

// Drag & drop on entire module (external files from desktop)
function onDragEnter(e: DragEvent) {
  e.preventDefault();
  dragCounter++;
  // Only show upload overlay for external file drops, not internal moves
  if (
    e.dataTransfer?.types.includes("Files") &&
    !e.dataTransfer?.types.includes("application/x-file-id")
  ) {
    isDragOver.value = true;
  }
}

function onDragLeave(e: DragEvent) {
  e.preventDefault();
  dragCounter--;
  if (dragCounter <= 0) {
    isDragOver.value = false;
    dragCounter = 0;
  }
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  isDragOver.value = false;
  dragCounter = 0;
  // Only handle external file drops here (internal moves are handled by FileCard)
  if (e.dataTransfer?.types.includes("application/x-file-id")) return;
  const files = Array.from(e.dataTransfer?.files || []);
  if (files.length) handleUpload(files);
}

// Internal drag & drop: move a file into a folder
async function handleDropInto(fileId: number, targetFolderId: number) {
  const ok = await store.moveFile(props.roomId, fileId, targetFolderId);
  if (ok) {
    await store.fetchFiles(props.roomId);
  }
}

// File actions
function openFile(file: FileResource) {
  if (file.isFolder) {
    store.navigateToFolder(props.roomId, file);
    searchInput.value = "";
  } else {
    selectedFile.value = file;
    showPreview.value = true;
  }
}

async function handleCreateFolder(name: string) {
  const ok = await store.createFolder(props.roomId, name);
  showCreateFolder.value = false;
  if (ok) {
    await store.fetchFiles(props.roomId);
  }
}

function startRename(file: FileResource) {
  selectedFile.value = file;
  showRename.value = true;
}

async function handleRename(newName: string) {
  if (!selectedFile.value) return;
  const ok = await store.renameFile(
    props.roomId,
    selectedFile.value.id,
    newName,
  );
  if (ok) {
    showRename.value = false;
    store.fetchFiles(props.roomId);
  }
}

function startMove(file: FileResource) {
  selectedFile.value = file;
  showMove.value = true;
}

async function handleMove(targetFolderId: number | null) {
  if (!selectedFile.value) return;
  const ok = await store.moveFile(
    props.roomId,
    selectedFile.value.id,
    targetFolderId,
  );
  if (ok) {
    showMove.value = false;
    store.fetchFiles(props.roomId);
  }
}

async function handleDownload(file: FileResource) {
  await store.downloadFile(props.roomId, file);
}

async function handleDelete(file: FileResource) {
  const label = file.isFolder ? "ce dossier et tout son contenu" : "ce fichier";
  if (!confirm(`Supprimer ${label} ?`)) return;
  const ok = await store.deleteFile(props.roomId, file.id);
  if (ok) store.fetchFiles(props.roomId);
}

const sortIcon = (field: string) => {
  if (store.sortField !== field) return "";
  return store.sortOrder === "ASC" ? " ↑" : " ↓";
};
</script>

<template>
  <div
    class="flex flex-col h-full p-4 relative bg-[#0a1628]"
    @dragenter="onDragEnter"
    @dragover.prevent
    @dragleave="onDragLeave"
    @drop="onDrop"
  >
    <!-- Drag overlay -->
    <div
      v-if="isDragOver"
      class="absolute inset-0 z-50 bg-[#4115df]/20 border-4 border-dashed border-[#4115df] rounded-lg flex items-center justify-center pointer-events-none"
    >
      <div class="text-center">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-16 w-16 mx-auto mb-2 text-[#4115df]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
          />
        </svg>
        <p class="text-lg font-medium text-[#4115df]">
          Deposez vos fichiers ici
        </p>
      </div>
    </div>

    <!-- Header -->
    <div class="flex items-center justify-between mb-3 gap-2 flex-wrap">
      <h2 class="text-lg font-bold text-[#e0e0e0]">Fichiers</h2>
      <div class="flex items-center gap-2">
        <!-- Search -->
        <input
          v-model="searchInput"
          type="text"
          placeholder="Rechercher..."
          class="px-3 py-2 rounded-lg bg-[#1a3a52] border border-gray-600 text-[#e0e0e0] placeholder-[#b0b0b0] focus:outline-none focus:border-[#4115df] focus:ring-1 focus:ring-[#4115df]/30 w-48"
        />
        <!-- Create folder button -->
        <button
          class="p-2 rounded-lg bg-[#1a3a52] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0] transition-colors"
          @click="showCreateFolder = true"
          title="Nouveau dossier"
        >
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
        </button>
        <!-- Upload button -->
        <button
          class="px-4 py-2 rounded-lg bg-[#4115df] text-white hover:bg-[#6a3fe8] transition-colors flex items-center gap-1"
          @click="showUploadZone = !showUploadZone"
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
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"
            />
          </svg>
          Upload
        </button>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <div
      v-if="!store.searchQuery && store.breadcrumbs.length > 1"
      class="text-sm mb-2 py-0 flex items-center gap-1"
    >
      <div
        v-for="(bc, i) in store.breadcrumbs"
        :key="i"
        class="flex items-center gap-1"
      >
        <a
          class="cursor-pointer text-[#4115df] hover:text-[#6a3fe8] transition-colors"
          :class="{ 'font-bold': i === store.breadcrumbs.length - 1 }"
          @click="store.navigateToBreadcrumb(props.roomId, i)"
        >
          {{ bc.name }}
        </a>
        <span v-if="i < store.breadcrumbs.length - 1" class="text-[#666666]"
          >/</span
        >
      </div>
    </div>

    <!-- Search indicator -->
    <div v-if="store.searchQuery" class="mb-2">
      <span
        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-[#4115df]/20 text-[#4115df] border border-[#4115df]/30"
      >
        Recherche: "{{ store.searchQuery }}"
        <button
          class="ml-1 text-[#4115df] hover:text-[#6a3fe8]"
          @click="
            searchInput = '';
            store.searchQuery = '';
            store.fetchFiles(props.roomId);
          "
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-3 w-3"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </span>
    </div>

    <!-- Toolbar: sort & filter -->
    <div
      class="flex items-center gap-2 mb-3 flex-wrap text-xs bg-[#1a3a52] rounded-lg p-2"
    >
      <span class="text-[#b0b0b0]">Tri:</span>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.sortField === 'fileName',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.sortField !== 'fileName',
        }"
        @click="setSort('fileName')"
      >
        Nom{{ sortIcon("fileName") }}
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.sortField === 'createdAt',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.sortField !== 'createdAt',
        }"
        @click="setSort('createdAt')"
      >
        Date{{ sortIcon("createdAt") }}
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.sortField === 'size',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.sortField !== 'size',
        }"
        @click="setSort('size')"
      >
        Taille{{ sortIcon("size") }}
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.sortField === 'mimeType',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.sortField !== 'mimeType',
        }"
        @click="setSort('mimeType')"
      >
        Type{{ sortIcon("mimeType") }}
      </button>

      <span class="text-[#b0b0b0] ml-2">Filtre:</span>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.filterType === 'image',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.filterType !== 'image',
        }"
        @click="setFilter('image')"
      >
        Images
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.filterType === 'pdf',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.filterType !== 'pdf',
        }"
        @click="setFilter('pdf')"
      >
        PDF
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.filterType === 'office',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.filterType !== 'office',
        }"
        @click="setFilter('office')"
      >
        Office
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.filterType === 'archive',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.filterType !== 'archive',
        }"
        @click="setFilter('archive')"
      >
        Archives
      </button>
      <button
        class="px-2 py-1 rounded transition-colors"
        :class="{
          'bg-[#4115df] text-white': store.filterType === 'text',
          'bg-[#2a4a62] text-[#b0b0b0] hover:text-[#e0e0e0]':
            store.filterType !== 'text',
        }"
        @click="setFilter('text')"
      >
        Texte
      </button>
    </div>

    <!-- Upload zone -->
    <FileUploadZone v-if="showUploadZone" class="mb-3" @upload="handleUpload" />

    <!-- Upload progress -->
    <div v-if="uploads.length" class="mb-3 space-y-1">
      <div
        v-for="(u, i) in uploads"
        :key="i"
        class="flex items-center gap-2 text-sm"
      >
        <span class="truncate flex-1 text-[#e0e0e0]">{{ u.name }}</span>
        <progress
          class="w-24 h-2 rounded-lg bg-[#2a4a62]"
          :value="u.progress"
          max="100"
          style="accent-color: #4115df"
        ></progress>
        <span class="text-xs w-10 text-right text-[#b0b0b0]"
          >{{ u.progress }}%</span
        >
      </div>
    </div>

    <!-- Error -->
    <div
      v-if="store.error"
      class="bg-red-500/20 border border-red-500 text-red-400 px-3 py-2 rounded-lg mb-3 text-sm"
    >
      {{ store.error }}
    </div>

    <!-- Loading -->
    <div v-if="store.loading" class="flex justify-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Files grid -->
    <div v-else-if="store.files.length" class="flex-1 overflow-y-auto">
      <div
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3"
      >
        <FileCard
          v-for="file in store.files"
          :key="file.id"
          :file="file"
          @open="openFile"
          @rename="startRename"
          @move="startMove"
          @download="handleDownload"
          @delete="handleDelete"
          @drop-into="handleDropInto"
        />
      </div>
    </div>

    <!-- Empty state -->
    <div v-else class="flex-1 flex items-center justify-center">
      <div class="text-center py-12 text-[#b0b0b0]">
        <svg
          xmlns="http://www.w3.org/2000/svg"
          class="h-16 w-16 mx-auto mb-4 text-[#666666]"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
          />
        </svg>
        <p class="text-lg font-medium text-[#e0e0e0]">Aucun fichier</p>
        <p class="text-sm text-[#b0b0b0]">
          Glissez-deposez des fichiers ou utilisez le bouton Upload
        </p>
      </div>
    </div>

    <!-- Modals -->
    <CreateFolderModal
      :show="showCreateFolder"
      @close="showCreateFolder = false"
      @create="handleCreateFolder"
    />

    <RenameModal
      :show="showRename"
      :file="selectedFile"
      @close="showRename = false"
      @rename="handleRename"
    />

    <MoveModal
      :show="showMove"
      :file="selectedFile"
      :room-id="props.roomId"
      @close="showMove = false"
      @move="handleMove"
    />

    <FilePreviewModal
      :show="showPreview"
      :file="selectedFile"
      :room-id="props.roomId"
      @close="showPreview = false"
      @download="handleDownload"
    />
  </div>
</template>

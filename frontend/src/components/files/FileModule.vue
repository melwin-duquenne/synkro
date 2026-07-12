<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted, watch } from "vue";
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

const totalItems = computed(() => store.files.length);
const folderCount = computed(
  () => store.files.filter((file) => file.isFolder).length,
);
const regularCount = computed(() => totalItems.value - folderCount.value);
</script>

<template>
  <div
    class="files-shell flex flex-col h-full p-4 relative"
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
    <div class="section-panel p-3 sm:p-4 mb-3">
      <div class="flex items-center justify-between gap-3 flex-wrap">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <div
              class="w-9 h-9 rounded-xl bg-[#408ed6]/20 border border-[#408ed6]/30 flex items-center justify-center"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 text-[#7db8ea]"
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
            </div>
            <div>
              <h2 class="text-lg font-semibold text-[#e7f0fa]">Fichiers</h2>
              <p class="text-xs text-[#8ea4be]">Bibliotheque de la room</p>
            </div>
          </div>
        </div>
        <div class="stats-row">
          <span class="info-chip">{{ totalItems }} element(s)</span>
          <span class="info-chip">{{ folderCount }} dossier(s)</span>
          <span class="info-chip">{{ regularCount }} fichier(s)</span>
        </div>
      </div>

      <div class="flex items-center gap-2 flex-wrap mt-3">
        <!-- Search -->
        <label class="search-shell">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-4 w-4 text-[#8ea4be]"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-4.35-4.35m1.35-5.65a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
          <input
            v-model="searchInput"
            type="text"
            placeholder="Rechercher un fichier..."
            class="search-input"
          />
        </label>
        <!-- Create folder button -->
        <button
          class="soft-btn"
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
        <button class="primary-btn" @click="showUploadZone = !showUploadZone">
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
          Importer
        </button>
      </div>
    </div>

    <!-- Breadcrumbs -->
    <div
      v-if="!store.searchQuery && store.breadcrumbs.length > 1"
      class="text-sm mb-2 py-1 px-2 section-panel inline-flex items-center gap-1 max-w-full overflow-x-auto"
    >
      <div
        v-for="(bc, i) in store.breadcrumbs"
        :key="i"
        class="flex items-center gap-1"
      >
        <a
          class="cursor-pointer text-[#7db8ea] hover:text-[#e7f0fa] transition-colors whitespace-nowrap"
          :class="{ 'font-bold': i === store.breadcrumbs.length - 1 }"
          @click="store.navigateToBreadcrumb(props.roomId, i)"
        >
          {{ bc.name }}
        </a>
        <span
          v-if="i < store.breadcrumbs.length - 1"
          class="text-[#5f7692] whitespace-nowrap"
          >/</span
        >
      </div>
    </div>

    <!-- Search indicator -->
    <div v-if="store.searchQuery" class="mb-2">
      <span
        class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-[#408ed6]/15 text-[#8bc2ef] border border-[#408ed6]/35"
      >
        Recherche: "{{ store.searchQuery }}"
        <button
          class="ml-1 text-[#8bc2ef] hover:text-[#e7f0fa]"
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
    <div class="files-tabs-shell mb-3">
      <div class="files-tabs-shell__glow"></div>

      <div class="files-tabs-group">
        <span class="files-tabs-group__label">Tri</span>
        <div class="files-tabs-list">
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.sortField === 'fileName',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.sortField !== 'fileName',
            }"
            @click="setSort('fileName')"
          >
            Nom{{ sortIcon("fileName") }}
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.sortField === 'createdAt',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.sortField !== 'createdAt',
            }"
            @click="setSort('createdAt')"
          >
            Date{{ sortIcon("createdAt") }}
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.sortField === 'size',
              'text-[#b0b0b0] hover:text-[#e0e0e0]': store.sortField !== 'size',
            }"
            @click="setSort('size')"
          >
            Taille{{ sortIcon("size") }}
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.sortField === 'mimeType',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.sortField !== 'mimeType',
            }"
            @click="setSort('mimeType')"
          >
            Type{{ sortIcon("mimeType") }}
          </button>
        </div>
      </div>

      <div class="files-tabs-group">
        <span class="files-tabs-group__label">Filtre</span>
        <div class="files-tabs-list">
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.filterType === 'image',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.filterType !== 'image',
            }"
            @click="setFilter('image')"
          >
            Images
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.filterType === 'pdf',
              'text-[#b0b0b0] hover:text-[#e0e0e0]': store.filterType !== 'pdf',
            }"
            @click="setFilter('pdf')"
          >
            PDF
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.filterType === 'office',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.filterType !== 'office',
            }"
            @click="setFilter('office')"
          >
            Office
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.filterType === 'archive',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.filterType !== 'archive',
            }"
            @click="setFilter('archive')"
          >
            Archives
          </button>
          <button
            class="control-chip"
            :class="{
              'control-chip-active': store.filterType === 'text',
              'text-[#b0b0b0] hover:text-[#e0e0e0]':
                store.filterType !== 'text',
            }"
            @click="setFilter('text')"
          >
            Texte
          </button>
        </div>
      </div>
    </div>

    <!-- Upload zone -->
    <FileUploadZone v-if="showUploadZone" class="mb-3" @upload="handleUpload" />

    <!-- Upload progress -->
    <div v-if="uploads.length" class="mb-3 space-y-1">
      <div
        v-for="(u, i) in uploads"
        :key="i"
        class="section-panel px-3 py-2 flex items-center gap-2 text-sm"
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
      class="bg-red-500/20 border border-red-500/70 text-red-300 px-3 py-2 rounded-xl mb-3 text-sm"
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
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4"
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
      <div class="text-center py-12 text-[#b0b0b0] section-panel px-8">
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

<style scoped>
.files-shell {
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 16px;
  background:
    radial-gradient(
      120% 120% at 0% 0%,
      rgba(64, 142, 214, 0.1),
      transparent 55%
    ),
    linear-gradient(165deg, rgba(10, 22, 40, 0.92), rgba(14, 29, 49, 0.95));
}

.section-panel {
  background: rgba(22, 46, 73, 0.55);
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 14px;
  backdrop-filter: blur(8px);
}

.search-shell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  max-width: 340px;
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(14, 29, 49, 0.85);
}

.search-shell:focus-within {
  border-color: rgba(91, 163, 232, 0.65);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.2);
}

.search-input {
  width: 100%;
  background: transparent;
  color: #e7f0fa;
  outline: none;
  font-size: 0.9rem;
}

.search-input::placeholder {
  color: #8ea4be;
}

.soft-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.55rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(17, 35, 56, 0.75);
  color: #c8d8ea;
  transition: 0.2s ease;
}

.soft-btn:hover {
  border-color: rgba(91, 163, 232, 0.6);
  color: #e7f0fa;
}

.primary-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.55rem 0.9rem;
  border-radius: 12px;
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
  transition:
    transform 0.2s ease,
    filter 0.2s ease;
}

.primary-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.files-tabs-shell {
  position: relative;
  overflow: hidden;
  display: grid;
  gap: 0.45rem;
  padding: 0.6rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.18),
      transparent 30%
    ),
    linear-gradient(180deg, rgba(9, 18, 31, 0.94), rgba(7, 13, 24, 0.94));
  box-shadow:
    0 16px 32px rgba(0, 0, 0, 0.22),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.files-tabs-shell__glow {
  position: absolute;
  top: -3rem;
  right: -1.5rem;
  width: 9rem;
  height: 9rem;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    rgba(91, 163, 232, 0.22),
    transparent 70%
  );
  pointer-events: none;
}

.files-tabs-group {
  position: relative;
  z-index: 1;
  display: grid;
  gap: 0.3rem;
}

.files-tabs-group__label {
  color: #7f95ac;
  font-size: 0.58rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.files-tabs-list {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}

.control-chip {
  min-height: 1.75rem;
  padding: 0.28rem 0.55rem;
  border-radius: 11px;
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: rgba(255, 255, 255, 0.035);
  font-size: 0.76rem;
  line-height: 1;
  transition:
    border-color 0.22s ease,
    background-color 0.22s ease,
    transform 0.22s ease,
    box-shadow 0.22s ease;
}

.control-chip:hover {
  border-color: rgba(91, 163, 232, 0.45);
  background: rgba(91, 163, 232, 0.08);
  transform: translateY(-1px);
}

.control-chip-active {
  border-color: rgba(91, 163, 232, 0.38);
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.18),
    rgba(91, 163, 232, 0.06)
  );
  color: #e7f0fa;
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
}

.stats-row {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex-wrap: wrap;
}

.info-chip {
  padding: 0.3rem 0.6rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(12, 26, 44, 0.66);
  color: #a9c0d8;
  font-size: 0.75rem;
}

@media (max-width: 640px) {
  .files-tabs-shell {
    padding: 0.55rem;
  }

  .files-tabs-list {
    gap: 0.35rem;
  }

  .control-chip {
    min-height: 1.65rem;
    padding: 0.26rem 0.5rem;
    border-radius: 10px;
    font-size: 0.72rem;
  }
}
</style>

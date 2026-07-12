<script setup lang="ts">
import { ref, watch } from "vue";
import type { FileResource } from "@/types";
import { useAuthStore } from "@/stores/auth";

const API_URL = import.meta.env.VITE_API_URL || "/api";

const props = defineProps<{
  show: boolean;
  file: FileResource | null;
  roomId: number;
}>();

const emit = defineEmits<{
  close: [];
  move: [targetFolderId: number | null];
}>();

const authStore = useAuthStore();
const folders = ref<FileResource[]>([]);
const loading = ref(false);
const selectedFolderId = ref<number | null>(null);
const currentParentId = ref<number | null>(null);
const breadcrumbs = ref<{ id: number | null; name: string }[]>([
  { id: null, name: "Racine" },
]);

watch(
  () => props.show,
  async (val) => {
    if (val) {
      selectedFolderId.value = null;
      currentParentId.value = null;
      breadcrumbs.value = [{ id: null, name: "Racine" }];
      await loadFolders(null);
    }
  },
);

async function loadFolders(parentId: number | null) {
  loading.value = true;
  try {
    const params = new URLSearchParams({ type: "folder" });
    if (parentId) params.set("parent", String(parentId));

    const response = await fetch(
      `${API_URL}/rooms/${props.roomId}/files?${params}`,
      {
        headers: authStore.getAuthHeaders(),
      },
    );

    if (response.ok) {
      const data = await response.json();
      const all: FileResource[] = Array.isArray(data)
        ? data
        : data["hydra:member"] || data.member || [];
      // Exclude the file being moved (if it's a folder)
      folders.value = all.filter((f) => f.id !== props.file?.id);
    }
  } catch {
    // ignore
  } finally {
    loading.value = false;
  }
}

async function navigateInto(folder: FileResource) {
  currentParentId.value = folder.id;
  breadcrumbs.value.push({ id: folder.id, name: folder.fileName });
  selectedFolderId.value = null;
  await loadFolders(folder.id);
}

async function navigateBreadcrumb(index: number) {
  const item = breadcrumbs.value[index];
  if (!item) return;
  currentParentId.value = item.id;
  breadcrumbs.value = breadcrumbs.value.slice(0, index + 1);
  selectedFolderId.value = null;
  await loadFolders(item.id);
}

function handleMove() {
  // If a folder is selected, move into it; otherwise move to current browsed folder
  const target =
    selectedFolderId.value !== undefined
      ? selectedFolderId.value
      : currentParentId.value;
  emit("move", target);
}

function selectFolder(id: number | null) {
  selectedFolderId.value = id;
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': show }">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">
        Deplacer
        <span
          class="inline-block max-w-full align-bottom truncate"
          :title="file?.fileName"
        >
          "{{ file?.fileName }}"
        </span>
      </h3>

      <!-- Breadcrumbs -->
      <div class="text-sm breadcrumbs mb-3">
        <ul>
          <li v-for="(bc, i) in breadcrumbs" :key="i">
            <a
              @click="navigateBreadcrumb(i)"
              class="cursor-pointer inline-block max-w-40 truncate align-bottom"
              :title="bc.name"
            >
              {{ bc.name }}
            </a>
          </li>
        </ul>
      </div>

      <!-- Folder list -->
      <div class="border rounded-lg p-2 max-h-60 overflow-y-auto min-h-30">
        <div v-if="loading" class="flex justify-center py-8">
          <span class="loading loading-spinner"></span>
        </div>

        <!-- Option to place in current folder -->
        <div
          class="flex items-center gap-2 p-2 rounded cursor-pointer hover:bg-base-200 transition-colors"
          :class="{
            'bg-primary/10 border border-primary':
              selectedFolderId === currentParentId,
          }"
          @click="selectFolder(currentParentId)"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="h-5 w-5 text-base-content/50"
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
          <span class="text-sm italic text-base-content/60"
            >Ici (dossier actuel)</span
          >
        </div>

        <template v-if="!loading">
          <div
            v-for="folder in folders"
            :key="folder.id"
            class="flex items-center gap-2 p-2 rounded cursor-pointer hover:bg-base-200 transition-colors"
            :class="{
              'bg-primary/10 border border-primary':
                selectedFolderId === folder.id,
            }"
            @click="selectFolder(folder.id)"
            @dblclick="navigateInto(folder)"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5 text-warning"
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
            <span
              class="text-sm truncate flex-1 min-w-0"
              :title="folder.fileName"
            >
              {{ folder.fileName }}
            </span>
            <span class="text-xs text-base-content/40 ml-auto"
              >{{ folder.childCount }} items</span
            >
          </div>
          <p
            v-if="folders.length === 0 && !loading"
            class="text-sm text-base-content/50 text-center py-4"
          >
            Aucun sous-dossier
          </p>
        </template>
      </div>

      <div class="modal-action">
        <button class="btn" @click="emit('close')">Annuler</button>
        <button class="btn btn-primary" @click="handleMove">
          Deplacer ici
        </button>
      </div>
    </div>
    <form method="dialog" class="modal-backdrop" @click="emit('close')">
      <button>close</button>
    </form>
  </dialog>
</template>

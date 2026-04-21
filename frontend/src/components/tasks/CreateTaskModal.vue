<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";

interface TaskUser {
  id: number;
  displayName: string;
}

interface KanbanColumn {
  id: number;
  name: string;
  color: string;
  position: number;
  roomId: number;
  taskCount: number;
}

interface Task {
  id: number;
  title: string;
  description: string | null;
  columnId: number;
  columnName: string;
  columnColor: string;
  type: "active" | "archived";
  position: number;
  assignedTo: TaskUser | null;
  estimation: number | null;
  createdAt: string;
}

const props = defineProps<{
  open: boolean;
  roomId: number;
  columns?: KanbanColumn[];
  initialColumnId?: number;
}>();

const emit = defineEmits<{
  close: [];
  created: [task: Task];
}>();

const authStore = useAuthStore();

const title = ref("");
const description = ref("");
const columnId = ref<number | null>(null);
const assignedToId = ref<number | null>(null);
const estimation = ref<number | null>(null);
const loading = ref(false);
const error = ref<string | null>(null);

const members = ref<TaskUser[]>([]);
const loadingMembers = ref(false);
const availableColumns = ref<KanbanColumn[]>([]);
const loadingColumns = ref(false);

const estimationOptions = [
  { value: null, label: "Non estimé" },
  { value: 1, label: "1 point" },
  { value: 2, label: "2 points" },
  { value: 3, label: "3 points" },
  { value: 5, label: "5 points" },
  { value: 8, label: "8 points" },
  { value: 13, label: "13 points" },
  { value: 21, label: "21 points" },
];

async function fetchMembers() {
  loadingMembers.value = true;
  try {
    const response = await fetch("/api/entreprise/members", {
      headers: authStore.getAuthHeaders(),
    });
    if (response.ok) {
      const data = await response.json();
      members.value =
        data["hydra:member"] ||
        data.member ||
        (Array.isArray(data) ? data : []);
    }
  } catch (e) {
    console.error("Failed to fetch members:", e);
  } finally {
    loadingMembers.value = false;
  }
}

async function fetchColumns() {
  if (!props.roomId) return;
  loadingColumns.value = true;
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns`, {
      headers: authStore.getAuthHeaders(),
    });
    if (response.ok) {
      const data = await response.json();
      availableColumns.value =
        data["hydra:member"] ||
        data.member ||
        (Array.isArray(data) ? data : []);
    }
  } catch (e) {
    console.error("Failed to fetch columns:", e);
  } finally {
    loadingColumns.value = false;
  }
}

watch(
  () => props.initialColumnId,
  (newColumnId) => {
    if (newColumnId) {
      columnId.value = newColumnId;
    }
  },
  { immediate: true },
);

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    // Charger les colonnes si elles ne sont pas fournies
    if (!props.columns || props.columns.length === 0) {
      fetchColumns().then(() => {
        if (props.initialColumnId) {
          columnId.value = props.initialColumnId
        } else if (availableColumns.value.length > 0 && !columnId.value) {
          columnId.value = availableColumns.value[0]!.id
        }
      })
    } else {
      availableColumns.value = props.columns
      if (props.initialColumnId) {
        columnId.value = props.initialColumnId
      } else if (props.columns.length > 0 && !columnId.value) {
        columnId.value = props.columns[0]!.id
      }
    }
    if (members.value.length === 0) {
      fetchMembers()
    }
  }
})

onMounted(() => {
  fetchMembers();
});

async function handleSubmit() {
  if (!title.value.trim()) return;

  loading.value = true;
  error.value = null;

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks`, {
      method: "POST",
      headers: {
        "Content-Type": "application/ld+json",
        ...authStore.getAuthHeaders(),
      },
      body: JSON.stringify({
        title: title.value,
        description: description.value || null,
        columnId: columnId.value,
        assignedToId: assignedToId.value,
        estimation: estimation.value,
      }),
    });

    if (!response.ok) {
      const data = await response.json()
      throw new Error(data.error || 'Impossible de créer la tâche')
    }

    const task = await response.json();
    emit("created", task);
    resetForm();
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Impossible de créer la tâche'
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  title.value = ''
  description.value = ''
  columnId.value = props.initialColumnId || (availableColumns.value.length > 0 ? availableColumns.value[0]!.id : null)
  assignedToId.value = null
  estimation.value = null
  error.value = null
}

function handleClose() {
  resetForm();
  emit("close");
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box">
      <h3 class="font-bold text-lg mb-4">Nouvelle tâche</h3>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Title -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Titre *</span>
          </label>
          <input
            v-model="title"
            type="text"
            class="input input-bordered w-full"
            placeholder="Titre de la tâche"
            required
            autofocus
          />
        </div>

        <!-- Description -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Description</span>
            <span class="label-text-alt">Optionnel</span>
          </label>
          <textarea
            v-model="description"
            class="textarea textarea-bordered w-full h-24"
            placeholder="Décrivez la tâche..."
          ></textarea>
        </div>

        <!-- Column -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Colonne</span>
          </label>
          <select v-model="columnId" class="select select-bordered w-full">
            <option
              v-for="col in availableColumns"
              :key="col.id"
              :value="col.id"
            >
              {{ col.name }}
            </option>
          </select>
        </div>

        <!-- Assignation -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Assigné à</span>
            <span class="label-text-alt">Optionnel</span>
          </label>
          <select
            v-model="assignedToId"
            class="select select-bordered w-full"
            :disabled="loadingMembers"
          >
            <option :value="null">Non assigné</option>
            <option
              v-for="member in members"
              :key="member.id"
              :value="member.id"
            >
              {{ member.displayName }}
            </option>
          </select>
        </div>

        <!-- Estimation -->
        <div class="form-control">
          <label class="label">
            <span class="label-text">Estimation (points)</span>
            <span class="label-text-alt">Optionnel</span>
          </label>
          <select v-model="estimation" class="select select-bordered w-full">
            <option v-for="option in estimationOptions" :key="option.value ?? 'none'" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Error -->
        <div v-if="error" class="alert alert-error">
          <span>{{ error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button
            type="button"
            class="btn bg-white text-black border-0"
            @click="handleClose"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="btn btn-primary bg-white text-black border-0"
            :class="{ loading: loading }"
            :disabled="!title.trim() || loading"
          >
            {{ loading ? "Création..." : "Créer la tâche" }}
          </button>
        </div>
      </form>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button @click="handleClose">close</button>
    </form>
  </dialog>
</template>

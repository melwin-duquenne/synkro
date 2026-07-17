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

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      // Charger les colonnes si elles ne sont pas fournies
      if (!props.columns || props.columns.length === 0) {
        fetchColumns().then(() => {
          if (props.initialColumnId) {
            columnId.value = props.initialColumnId;
          } else if (availableColumns.value.length > 0 && !columnId.value) {
            columnId.value = availableColumns.value[0]!.id;
          }
        });
      } else {
        availableColumns.value = props.columns;
        if (props.initialColumnId) {
          columnId.value = props.initialColumnId;
        } else if (props.columns.length > 0 && !columnId.value) {
          columnId.value = props.columns[0]!.id;
        }
      }
      if (members.value.length === 0) {
        fetchMembers();
      }
    }
  },
);

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
      const data = await response.json();
      throw new Error(data.error || "Impossible de créer la tâche");
    }

    const task = await response.json();
    emit("created", task);
    resetForm();
  } catch (e) {
    error.value =
      e instanceof Error ? e.message : "Impossible de créer la tâche";
  } finally {
    loading.value = false;
  }
}

function resetForm() {
  title.value = "";
  description.value = "";
  columnId.value =
    props.initialColumnId ||
    (availableColumns.value.length > 0 ? availableColumns.value[0]!.id : null);
  assignedToId.value = null;
  estimation.value = null;
  error.value = null;
}

function handleClose() {
  resetForm();
  emit("close");
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box create-task-modal">
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
              d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
        </div>
        <div>
          <h3 class="font-semibold text-lg text-[#e7f0fa]">Nouvelle tâche</h3>
          <p class="text-xs text-[#8ea4be]">
            Planifiez une action dans le kanban
          </p>
        </div>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Title -->
        <div class="form-control">
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Titre *</span>
          </label>
          <input
            v-model="title"
            type="text"
            class="form-input"
            placeholder="Titre de la tâche"
            required
            autofocus
          />
        </div>

        <!-- Description -->
        <div class="form-control">
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Description</span>
            <span class="label-text-alt text-[#7f95ad]">Optionnel</span>
          </label>
          <textarea
            v-model="description"
            class="form-textarea"
            placeholder="Décrivez la tâche..."
          ></textarea>
        </div>

        <!-- Column -->
        <div class="form-control">
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Colonne</span>
          </label>
          <select v-model="columnId" class="form-select">
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
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Assigné à</span>
            <span class="label-text-alt text-[#7f95ad]">Optionnel</span>
          </label>
          <select
            v-model="assignedToId"
            class="form-select"
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
          <label class="label px-0">
            <span class="label-text text-[#bfd1e5]">Estimation (points)</span>
            <span class="label-text-alt text-[#7f95ad]">Optionnel</span>
          </label>
          <select v-model="estimation" class="form-select">
            <option
              v-for="option in estimationOptions"
              :key="option.value ?? 'none'"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- Error -->
        <div v-if="error" class="error-box">
          <span>{{ error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action">
          <button type="button" class="btn-soft" @click="handleClose">
            Annuler
          </button>
          <button
            type="submit"
            class="btn-primary-custom"
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

<style scoped>
.create-task-modal {
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
  margin-bottom: 1rem;
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

.form-input,
.form-select,
.form-textarea {
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

.form-input::placeholder,
.form-textarea::placeholder {
  color: #7f95ad;
}

.form-textarea {
  min-height: 6rem;
  resize: vertical;
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
  border-color: rgba(91, 163, 232, 0.75);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.18);
}

.error-box {
  border-radius: 12px;
  border: 1px solid rgba(248, 113, 113, 0.55);
  background: rgba(127, 29, 29, 0.25);
  color: #fecaca;
  padding: 0.65rem 0.8rem;
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

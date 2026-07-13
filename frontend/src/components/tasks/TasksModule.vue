<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import TaskCard from "./TaskCard.vue";
import TaskDetailModal from "./TaskDetailModal.vue";
import CreateTaskModal from "./CreateTaskModal.vue";

const props = defineProps<{
  roomId: number;
}>();

interface TaskUser {
  id: number;
  displayName: string;
  avatarUrl?: string | null;
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

const authStore = useAuthStore();
const tasks = ref<Task[]>([]);
const columns = ref<KanbanColumn[]>([]);
const members = ref<TaskUser[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedTask = ref<Task | null>(null);
const createColumnId = ref<number | null>(null);
const showArchived = ref(false);

const draggedTask = ref<Task | null>(null);
const draggedColumn = ref<KanbanColumn | null>(null);
const dragOverColumnIndex = ref<number | null>(null);

// Filters
const filterSearch = ref("");
const filterAssignedTo = ref<number | null>(null);
const filterEstimation = ref<number | null>(null);

// Column management
const deleteColumnError = ref("");
const showAddColumn = ref(false);
const newColumnName = ref("");
const newColumnColor = ref("bg-slate-500");
const editingColumnId = ref<number | null>(null);
const editColumnName = ref("");
const editColumnColor = ref("");

const colorOptions = [
  { value: "bg-slate-500", label: "Gris" },
  { value: "bg-blue-500", label: "Bleu" },
  { value: "bg-green-500", label: "Vert" },
  { value: "bg-red-500", label: "Rouge" },
  { value: "bg-yellow-500", label: "Jaune" },
  { value: "bg-purple-500", label: "Violet" },
  { value: "bg-pink-500", label: "Rose" },
  { value: "bg-orange-500", label: "Orange" },
  { value: "bg-teal-500", label: "Turquoise" },
];

// Get unique assigned users for filter dropdown
const assignedUsers = computed(() => {
  const users = new Map<number, TaskUser>();
  tasks.value.forEach((task) => {
    if (task.assignedTo) {
      users.set(task.assignedTo.id, task.assignedTo);
    }
  });
  return Array.from(users.values());
});

// Filter and group tasks by column
const tasksByColumn = computed(() => {
  const grouped: Record<number, Task[]> = {};

  columns.value.forEach((col) => {
    grouped[col.id] = [];
  });

  tasks.value
    .filter((task) => {
      // Filter by active/archived
      if (!showArchived.value && task.type === "archived") return false;
      if (showArchived.value && task.type !== "archived") return false;

      // Filter by search
      if (filterSearch.value) {
        const search = filterSearch.value.toLowerCase();
        const matchTitle = task.title.toLowerCase().includes(search);
        const matchDesc =
          task.description?.toLowerCase().includes(search) || false;
        if (!matchTitle && !matchDesc) return false;
      }

      // Filter by assigned user
      if (filterAssignedTo.value !== null) {
        if (!task.assignedTo || task.assignedTo.id !== filterAssignedTo.value)
          return false;
      }

      // Filter by estimation
      if (filterEstimation.value !== null) {
        if (task.estimation !== filterEstimation.value) return false;
      }

      return true;
    })
    .forEach((task) => {
      if (grouped[task.columnId] !== undefined) {
        grouped[task.columnId]!.push(task);
      }
    });

  // Sort by position
  Object.keys(grouped).forEach((colId) => {
    grouped[Number(colId)]?.sort((a, b) => a.position - b.position);
  });

  return grouped;
});

async function fetchColumns() {
  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns`, {
      headers: authStore.getAuthHeaders(),
    });

    if (!response.ok) throw new Error("Impossible de charger les colonnes");

    const data = await response.json();
    columns.value =
      data["hydra:member"] || data.member || (Array.isArray(data) ? data : []);
  } catch (e) {
    console.error("Impossible de charger les colonnes :", e);
  }
}

async function fetchMembers() {
  try {
    const response = await fetch("/api/entreprise/members", {
      headers: authStore.getAuthHeaders(),
    });

    if (!response.ok) throw new Error("Impossible de charger les membres");

    const data = await response.json();
    members.value =
      data["hydra:member"] || data.member || (Array.isArray(data) ? data : []);
  } catch (e) {
    console.error("Impossible de charger les membres :", e);
  }
}

async function fetchTasks() {
  loading.value = true;
  error.value = null;

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/tasks`, {
      headers: authStore.getAuthHeaders(),
    });

    if (!response.ok) throw new Error("Impossible de charger les tâches");

    const data = await response.json();
    tasks.value =
      data["hydra:member"] || data.member || (Array.isArray(data) ? data : []);
  } catch (e) {
    error.value =
      e instanceof Error ? e.message : "Impossible de charger les tâches";
  } finally {
    loading.value = false;
  }
}

function openCreateModal(columnId: number) {
  createColumnId.value = columnId;
  showCreateModal.value = true;
}

function openDetailModal(task: Task) {
  selectedTask.value = task;
  showDetailModal.value = true;
}

function handleTaskCreated(task: Task) {
  tasks.value.push(task);
  showCreateModal.value = false;
}

function handleTaskUpdated(updatedTask: Task) {
  const index = tasks.value.findIndex((t) => t.id === updatedTask.id);
  if (index !== -1) {
    tasks.value[index] = updatedTask;
  }
  showDetailModal.value = false;
  selectedTask.value = null;
}

function handleTaskDeleted(taskId: number) {
  tasks.value = tasks.value.filter((t) => t.id !== taskId);
  showDetailModal.value = false;
  selectedTask.value = null;
}

// Drag and Drop for Tasks (unchanged)
function onDragStart(task: Task) {
  draggedTask.value = task;
}
function onDragEnd() {
  draggedTask.value = null;
}
function onDragOver(e: DragEvent) {
  e.preventDefault();
}
async function onDrop(e: DragEvent, newColumnId: number) {
  e.preventDefault();
  if (!draggedTask.value) return;
  const task = draggedTask.value;
  const oldColumnId = task.columnId;
  if (oldColumnId === newColumnId) {
    draggedTask.value = null;
    return;
  }
  // Optimistic update
  const oldColumn = columns.value.find((c) => c.id === oldColumnId);
  const newColumn = columns.value.find((c) => c.id === newColumnId);
  task.columnId = newColumnId;
  task.columnName = newColumn?.name || "";
  task.columnColor = newColumn?.color || "";
  task.position = tasksByColumn.value[newColumnId]?.length || 0;
  try {
    const response = await fetch(
      `/api/rooms/${props.roomId}/tasks/${task.id}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/merge-patch+json",
          ...authStore.getAuthHeaders(),
        },
        body: JSON.stringify({
          columnId: newColumnId,
          position: task.position,
        }),
      },
    );
    if (!response.ok) {
      // Revert on error
      task.columnId = oldColumnId;
      task.columnName = oldColumn?.name || "";
      task.columnColor = oldColumn?.color || "";
      throw new Error("Impossible de mettre à jour la tâche");
    }
  } catch (e) {
    console.error("Impossible de déplacer la tâche :", e);
  }
  draggedTask.value = null;
}

// Drag and Drop for Columns
function onColumnDragStart(column: KanbanColumn) {
  draggedColumn.value = column;
  dragOverColumnIndex.value = null;
}
function onColumnDragOver(e: DragEvent, index: number) {
  e.preventDefault();
  dragOverColumnIndex.value = index;
}
function onColumnDrop(e: DragEvent, index: number) {
  e.preventDefault();
  if (!draggedColumn.value) return;
  const fromIndex = columns.value.findIndex(
    (c) => c.id === draggedColumn.value!.id,
  );
  if (fromIndex === -1 || fromIndex === index) {
    draggedColumn.value = null;
    dragOverColumnIndex.value = null;
    return;
  }
  // Move column in array
  const moved = columns.value.splice(fromIndex, 1)[0];
  if (!moved) return;
  columns.value.splice(index, 0, moved);
  // Re-index positions
  columns.value.forEach((col, idx) => {
    col.position = idx;
  });
  // Save order to backend
  saveColumnOrder();
  draggedColumn.value = null;
  dragOverColumnIndex.value = null;
}
function onColumnDragEnd() {
  draggedColumn.value = null;
  dragOverColumnIndex.value = null;
}

async function saveColumnOrder() {
  try {
    const payload = {
      columns: columns.value.map((col, idx) => ({ id: col.id, position: idx })),
    };
    const response = await fetch(
      `/api/rooms/${props.roomId}/kanban-columns/reorder`,
      {
        method: "POST",
        headers: {
          "Content-Type": "application/ld+json",
          ...authStore.getAuthHeaders(),
        },
        body: JSON.stringify(payload),
      },
    );
    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}));
      console.error("Reorder error:", errorData);
      throw new Error("Erreur lors de la sauvegarde de l'ordre des colonnes");
    }
  } catch (e) {
    console.error("Erreur lors de la sauvegarde de l'ordre des colonnes:", e);
  }
}

// Column management
async function addColumn() {
  if (!newColumnName.value.trim()) return;

  try {
    const response = await fetch(`/api/rooms/${props.roomId}/kanban-columns`, {
      method: "POST",
      headers: {
        "Content-Type": "application/ld+json",
        ...authStore.getAuthHeaders(),
      },
      body: JSON.stringify({
        name: newColumnName.value,
        color: newColumnColor.value,
      }),
    });

    if (!response.ok) throw new Error("Impossible de créer la colonne");

    const column = await response.json();
    columns.value.push(column);
    newColumnName.value = "";
    newColumnColor.value = "bg-slate-500";
    showAddColumn.value = false;
  } catch (e) {
    console.error("Impossible de créer la colonne :", e);
  }
}

function startEditColumn(col: KanbanColumn) {
  editingColumnId.value = col.id;
  editColumnName.value = col.name;
  editColumnColor.value = col.color;
}

async function saveColumnEdit(col: KanbanColumn) {
  try {
    const response = await fetch(
      `/api/rooms/${props.roomId}/kanban-columns/${col.id}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/merge-patch+json",
          ...authStore.getAuthHeaders(),
        },
        body: JSON.stringify({
          name: editColumnName.value,
          color: editColumnColor.value,
        }),
      },
    );

    if (!response.ok) throw new Error("Impossible de mettre à jour la colonne");

    const updated = await response.json();
    const index = columns.value.findIndex((c) => c.id === col.id);
    if (index !== -1) {
      columns.value[index] = updated;
    }

    // Update tasks that reference this column
    tasks.value.forEach((task) => {
      if (task.columnId === col.id) {
        task.columnName = updated.name;
        task.columnColor = updated.color;
      }
    });

    editingColumnId.value = null;
  } catch (e) {
    console.error("Impossible de mettre à jour la colonne :", e);
  }
}

async function deleteColumn(col: KanbanColumn) {
  if (
    !confirm(
      `Supprimer la colonne "${col.name}" ? Les tâches actives doivent être déplacées d'abord.`,
    )
  )
    return;

  deleteColumnError.value = "";

  try {
    const response = await fetch(
      `/api/rooms/${props.roomId}/kanban-columns/${col.id}`,
      {
        method: "DELETE",
        headers: authStore.getAuthHeaders(),
      },
    );

    if (!response.ok) {
      const data = await response.json();
      deleteColumnError.value =
        data.detail || data.message || "Impossible de supprimer cette colonne";
      return;
    }

    columns.value = columns.value.filter((c) => c.id !== col.id);
  } catch (e) {
    console.error("Impossible de supprimer la colonne :", e);
    deleteColumnError.value = "Impossible de supprimer la colonne";
  }
}

function clearFilters() {
  filterSearch.value = "";
  filterAssignedTo.value = null;
  filterEstimation.value = null;
}

onMounted(async () => {
  await fetchColumns();
  await fetchMembers();
  await fetchTasks();
});
</script>

<template>
  <div class="tasks-module h-full flex flex-col p-4">
    <!-- Header -->
    <div
      class="section-panel flex items-center justify-between mb-4 p-3 sm:p-4 gap-3 flex-wrap"
    >
      <div class="flex items-center gap-3">
        <div class="tasks-icon-wrap" aria-hidden="true">
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
          <h3 class="font-semibold text-lg text-[#e7f0fa]">Tâches</h3>
          <p class="text-xs text-[#8ea4be]">Tableau kanban de la room</p>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <button
          class="btn-soft"
          :class="showArchived ? 'btn-warning-alt' : ''"
          @click="showArchived = !showArchived"
        >
          {{ showArchived ? "Voir actives" : "Voir archivées" }}
        </button>
        <button
          class="btn-primary-custom"
          @click="columns[0] && openCreateModal(columns[0].id)"
        >
          + Nouvelle tâche
        </button>
      </div>
    </div>

    <!-- Column delete error -->
    <div
      v-if="deleteColumnError"
      class="bg-red-500/20 border border-red-500/70 text-red-300 px-3 py-2 rounded-xl mb-2 flex items-center justify-between"
    >
      <span>{{ deleteColumnError }}</span>
      <button
        class="text-red-400 hover:text-red-300"
        @click="deleteColumnError = ''"
      >
        ✕
      </button>
    </div>

    <!-- Filters bar -->
    <div
      class="section-panel flex flex-wrap items-center gap-2 mb-4 p-2 sm:p-3"
    >
      <label class="filter-search-shell">
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
          v-model="filterSearch"
          type="text"
          class="filter-input"
          placeholder="Rechercher..."
        />
      </label>
      <select v-model="filterAssignedTo" class="filter-select">
        <option :value="null">Tous les membres</option>
        <option v-for="user in assignedUsers" :key="user.id" :value="user.id">
          {{ user.displayName }}
        </option>
      </select>
      <select v-model="filterEstimation" class="filter-select">
        <option :value="null">Toutes estimations</option>
        <option :value="1">1 pt</option>
        <option :value="2">2 pts</option>
        <option :value="3">3 pts</option>
        <option :value="5">5 pts</option>
        <option :value="8">8 pts</option>
        <option :value="13">13 pts</option>
        <option :value="21">21 pts</option>
      </select>
      <button
        v-if="
          filterSearch || filterAssignedTo !== null || filterEstimation !== null
        "
        class="btn-soft"
        @click="clearFilters"
      >
        Effacer filtres
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex-1 flex items-center justify-center">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="flex-1 flex items-center justify-center">
      <div class="bg-red-500/20 border border-red-500 px-4 py-3 rounded-lg">
        <span class="text-red-400">{{ error }}</span>
        <button
          class="ml-3 px-3 py-1 rounded-lg bg-red-500 text-white hover:bg-red-600 transition-colors"
          @click="fetchTasks"
        >
          Réessayer
        </button>
      </div>
    </div>

    <!-- Kanban Board -->
    <div v-else class="kanban-board flex-1 flex gap-4 overflow-x-auto">
      <div
        v-for="(column, colIdx) in columns"
        :key="column.id"
        class="kanban-column flex-1 min-w-70 max-w-87.5 flex flex-col"
        :class="{
          'kanban-column--drop-target': dragOverColumnIndex === colIdx,
        }"
        :draggable="!showArchived"
        @dragstart="!showArchived ? onColumnDragStart(column) : undefined"
        @dragover="!showArchived ? onColumnDragOver($event, colIdx) : undefined"
        @drop="!showArchived ? onColumnDrop($event, colIdx) : undefined"
        @dragend="!showArchived ? onColumnDragEnd() : undefined"
      >
        <!-- Column Header -->
        <div class="column-header flex items-center gap-2 mb-3 group">
          <template v-if="editingColumnId === column.id">
            <input
              v-model="editColumnName"
              type="text"
              class="column-edit-input flex-1"
              @keyup.enter="saveColumnEdit(column)"
            />
            <select v-model="editColumnColor" class="column-edit-input">
              <option v-for="c in colorOptions" :key="c.value" :value="c.value">
                {{ c.label }}
              </option>
            </select>
            <button class="mini-btn-ok" @click="saveColumnEdit(column)">
              OK
            </button>
            <button class="mini-btn-soft" @click="editingColumnId = null">
              X
            </button>
          </template>
          <template v-else>
            <div :class="[column.color, 'w-3 h-3 rounded-full']"></div>
            <h4 class="font-medium text-[#e7f0fa]">{{ column.name }}</h4>
            <span class="count-pill">
              {{ tasksByColumn[column.id]?.length || 0 }}
            </span>
            <div class="ml-auto hidden group-hover:flex items-center gap-1">
              <button
                class="mini-btn-soft"
                @click="startEditColumn(column)"
                title="Modifier"
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
                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                  />
                </svg>
              </button>
              <button
                class="mini-btn-danger"
                @click="deleteColumn(column)"
                title="Supprimer"
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
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  />
                </svg>
              </button>
            </div>
          </template>
        </div>

        <!-- Column Content -->
        <div
          class="column-body flex-1 rounded-lg p-2 space-y-2 overflow-y-auto min-h-52"
          @dragover="onDragOver"
          @drop="onDrop($event, column.id)"
        >
          <TaskCard
            v-for="task in tasksByColumn[column.id]"
            :key="task.id"
            :task="task"
            :members="members"
            :draggable="!showArchived"
            @click="openDetailModal(task)"
            @dragstart="onDragStart(task)"
            @dragend="onDragEnd"
          />

          <!-- Empty state -->
          <div
            v-if="!tasksByColumn[column.id]?.length"
            class="empty-column-state"
          >
            <p class="text-sm">Aucune tâche</p>
          </div>

          <!-- Add task button -->
          <button
            v-if="!showArchived"
            class="add-task-btn w-full px-3 py-2 rounded-lg text-left"
            @click="openCreateModal(column.id)"
          >
            + Ajouter une tâche
          </button>
        </div>
      </div>

      <!-- Add column button -->
      <div class="min-w-70 max-w-87.5 flex flex-col" v-if="!showArchived">
        <div
          v-if="showAddColumn"
          class="section-panel rounded-lg p-4 space-y-2"
        >
          <input
            v-model="newColumnName"
            type="text"
            class="column-edit-input w-full"
            placeholder="Nom de la colonne"
            @keyup.enter="addColumn"
          />
          <select v-model="newColumnColor" class="column-edit-input w-full">
            <option v-for="c in colorOptions" :key="c.value" :value="c.value">
              {{ c.label }}
            </option>
          </select>
          <div class="flex gap-2">
            <button class="btn-primary-custom flex-1" @click="addColumn">
              Ajouter
            </button>
            <button class="btn-soft px-3 py-2" @click="showAddColumn = false">
              Annuler
            </button>
          </div>
        </div>
        <button
          v-else
          class="add-column-btn h-12 px-4 py-2 border-2 border-dashed rounded-lg w-full"
          @click="showAddColumn = true"
        >
          + Ajouter une colonne
        </button>
      </div>
    </div>

    <!-- Modals -->
    <CreateTaskModal
      :open="showCreateModal"
      :room-id="props.roomId"
      :columns="columns"
      :initial-column-id="createColumnId ?? undefined"
      @close="showCreateModal = false"
      @created="handleTaskCreated"
    />

    <TaskDetailModal
      v-if="selectedTask"
      :open="showDetailModal"
      :room-id="props.roomId"
      :task="selectedTask"
      :columns="columns"
      :members="members"
      @close="
        showDetailModal = false;
        selectedTask = null;
      "
      @updated="handleTaskUpdated"
      @deleted="handleTaskDeleted"
    />
  </div>
</template>

<style scoped>
.kanban-column {
  transition: transform 0.2s ease;
}

.kanban-column--drop-target {
  transform: translateY(-2px);
}

.tasks-module {
  min-height: 400px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.15),
      transparent 33%
    ),
    linear-gradient(180deg, rgba(8, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  box-shadow:
    0 22px 44px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.section-panel {
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.045),
    rgba(255, 255, 255, 0.02)
  );
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 14px;
  backdrop-filter: blur(10px);
}

.kanban-board {
  padding-bottom: 0.25rem;
}

.kanban-board::-webkit-scrollbar,
.column-body::-webkit-scrollbar {
  width: 10px;
  height: 10px;
}

.kanban-board::-webkit-scrollbar-track,
.column-body::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.04);
  border-radius: 999px;
}

.kanban-board::-webkit-scrollbar-thumb,
.column-body::-webkit-scrollbar-thumb {
  background: rgba(91, 163, 232, 0.35);
  border-radius: 999px;
}

.kanban-board::-webkit-scrollbar-thumb:hover,
.column-body::-webkit-scrollbar-thumb:hover {
  background: rgba(91, 163, 232, 0.5);
}

.tasks-icon-wrap {
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

.filter-search-shell {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  max-width: 320px;
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(14, 29, 49, 0.85);
}

.filter-input {
  width: 100%;
  background: transparent;
  color: #e7f0fa;
  outline: none;
  font-size: 0.9rem;
}

.filter-input::placeholder {
  color: #8ea4be;
}

.filter-search-shell:focus-within,
.filter-select:focus,
.column-edit-input:focus {
  border-color: rgba(91, 163, 232, 0.65);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.2);
}

.filter-select,
.column-edit-input {
  padding: 0.5rem 0.75rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(14, 29, 49, 0.85);
  color: #e7f0fa;
  outline: none;
}

.btn-soft {
  display: inline-flex;
  align-items: center;
  justify-content: center;
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

.btn-primary-custom:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.btn-warning-alt {
  background: linear-gradient(135deg, #c98d2d, #e6aa3d);
  color: #fff;
  border-color: rgba(255, 198, 96, 0.45);
}

.column-header {
  padding: 0.55rem 0.65rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: rgba(18, 38, 61, 0.58);
}

.column-body {
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: linear-gradient(
    180deg,
    rgba(19, 40, 64, 0.5),
    rgba(15, 31, 53, 0.62)
  );
  backdrop-filter: blur(6px);
}

.empty-column-state {
  text-align: center;
  padding: 1.7rem 0.4rem;
  color: #9db3ca;
  border: 1px dashed rgba(255, 255, 255, 0.14);
  border-radius: 12px;
  background: rgba(12, 26, 44, 0.33);
}

.count-pill {
  padding: 0.2rem 0.5rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(12, 26, 44, 0.7);
  color: #a9c0d8;
  font-size: 0.7rem;
}

.mini-btn-soft,
.mini-btn-danger,
.mini-btn-ok {
  padding: 0.3rem 0.5rem;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  transition: 0.2s ease;
}

.mini-btn-soft {
  background: rgba(15, 31, 53, 0.78);
  color: #c8d8ea;
}

.mini-btn-soft:hover {
  color: #e7f0fa;
  border-color: rgba(91, 163, 232, 0.45);
}

.mini-btn-danger {
  background: rgba(87, 24, 33, 0.5);
  color: #ff8f9a;
}

.mini-btn-danger:hover {
  background: rgba(214, 62, 82, 0.9);
  color: #fff;
}

.mini-btn-ok {
  background: rgba(36, 113, 86, 0.75);
  color: #d9ffe8;
}

.mini-btn-ok:hover {
  background: rgba(47, 145, 110, 0.95);
}

.add-task-btn {
  color: #a9c0d8;
  border: 1px dashed rgba(255, 255, 255, 0.18);
  background: rgba(12, 26, 44, 0.4);
  opacity: 0.72;
  transition: 0.2s ease;
}

.add-task-btn:hover {
  opacity: 1;
  color: #e7f0fa;
  border-color: rgba(91, 163, 232, 0.6);
  background: rgba(22, 46, 73, 0.55);
}

.add-column-btn {
  border-color: rgba(255, 255, 255, 0.2);
  color: #9fb5cc;
  background: rgba(11, 24, 41, 0.4);
  transition: 0.2s ease;
}

.add-column-btn:hover {
  border-color: rgba(91, 163, 232, 0.6);
  color: #e7f0fa;
  background: rgba(22, 46, 73, 0.4);
}

@media (max-width: 768px) {
  .tasks-module {
    padding: 0.7rem;
    border-radius: 16px;
  }

  .kanban-column {
    min-width: 18rem;
  }

  .section-panel {
    border-radius: 12px;
  }
}
</style>

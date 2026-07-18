<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";

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

const props = defineProps<{
  open: boolean;
  roomId: number;
  task: Task;
  columns: KanbanColumn[];
  members?: TaskUser[];
}>();

const emit = defineEmits<{
  close: [];
  updated: [task: Task];
  deleted: [taskId: number];
}>();

const authStore = useAuthStore();
const API_BASE = import.meta.env.VITE_API_URL?.replace("/api", "") || "";

const isEditing = ref(false);
const loading = ref(false);
const deleteConfirm = ref(false);

const editTitle = ref("");
const editDescription = ref("");
const editColumnId = ref<number | null>(null);
const editAssignedToId = ref<number | null>(null);
const editEstimation = ref<number | null>(null);

const localMembers = ref<TaskUser[]>([]);
const loadingMembers = ref(false);

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
      localMembers.value =
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

watch(
  () => props.task,
  (task) => {
    editTitle.value = task.title;
    editDescription.value = task.description || "";
    editColumnId.value = task.columnId;
    editAssignedToId.value = task.assignedTo?.id || null;
    editEstimation.value = task.estimation;
  },
  { immediate: true },
);

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen && localMembers.value.length === 0) {
      fetchMembers();
    }
  },
);

onMounted(() => {
  fetchMembers();
});

function startEditing() {
  editTitle.value = props.task.title;
  editDescription.value = props.task.description || "";
  editColumnId.value = props.task.columnId;
  editAssignedToId.value = props.task.assignedTo?.id || null;
  editEstimation.value = props.task.estimation;
  isEditing.value = true;
}

function cancelEditing() {
  isEditing.value = false;
  deleteConfirm.value = false;
}

async function saveChanges() {
  if (!editTitle.value.trim()) return;

  loading.value = true;

  try {
    const response = await fetch(
      `/api/rooms/${props.roomId}/tasks/${props.task.id}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/merge-patch+json",
          ...authStore.getAuthHeaders(),
        },
        body: JSON.stringify({
          title: editTitle.value,
          description: editDescription.value || null,
          columnId: editColumnId.value,
          assignedToId: editAssignedToId.value,
          estimation: editEstimation.value,
        }),
      },
    );

    if (!response.ok) throw new Error("Impossible de mettre à jour la tâche");

    const updatedTask = await response.json();
    emit("updated", updatedTask);
    isEditing.value = false;
  } catch (e) {
    console.error("Impossible de mettre à jour la tâche :", e);
  } finally {
    loading.value = false;
  }
}

async function toggleArchive() {
  loading.value = true;

  try {
    const newType = props.task.type === "active" ? "archived" : "active";
    const response = await fetch(
      `/api/rooms/${props.roomId}/tasks/${props.task.id}`,
      {
        method: "PATCH",
        headers: {
          "Content-Type": "application/merge-patch+json",
          ...authStore.getAuthHeaders(),
        },
        body: JSON.stringify({
          type: newType,
        }),
      },
    );

    if (!response.ok) throw new Error("Impossible de mettre à jour la tâche");

    const updatedTask = await response.json();
    emit("updated", updatedTask);
  } catch (e) {
    console.error("Impossible de modifier l'archivage de la tâche :", e);
  } finally {
    loading.value = false;
  }
}

async function deleteTask() {
  loading.value = true;

  try {
    const response = await fetch(
      `/api/rooms/${props.roomId}/tasks/${props.task.id}`,
      {
        method: "DELETE",
        headers: authStore.getAuthHeaders(),
      },
    );

    if (!response.ok) throw new Error("Impossible de supprimer la tâche");

    emit("deleted", props.task.id);
  } catch (e) {
    console.error("Impossible de supprimer la tâche :", e);
  } finally {
    loading.value = false;
    deleteConfirm.value = false;
  }
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "long",
    year: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  });
}

function getAvatarUrl(avatarUrl?: string | null): string | null {
  if (!avatarUrl) return null;
  return `${API_BASE}${avatarUrl}`;
}

function resolveAssignedAvatar(taskUser: TaskUser): string | null {
  if (taskUser.avatarUrl) return getAvatarUrl(taskUser.avatarUrl);
  const matchedMember = props.members?.find(
    (member) => member.id === taskUser.id,
  );
  return getAvatarUrl(matchedMember?.avatarUrl);
}

function handleClose() {
  isEditing.value = false;
  deleteConfirm.value = false;
  emit("close");
}
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div class="modal-box max-w-2xl task-detail-modal">
      <div class="modal-layout">
        <div class="header-row">
          <div class="title-block flex-1">
            <h3
              v-if="!isEditing"
              class="font-bold text-2xl text-[#e7f0fa] leading-tight"
            >
              {{ task.title }}
            </h3>
            <input
              v-else
              v-model="editTitle"
              type="text"
              class="field-input w-full text-2xl font-bold"
              placeholder="Titre de la tâche"
            />
            <div class="meta-line">
              <span
                class="status-pill"
                :class="
                  task.type === 'archived' ? 'status-archived' : 'status-active'
                "
              >
                {{ task.type === "archived" ? "Archivée" : "Active" }}
              </span>
              <span class="meta-chip"
                >Créée le {{ formatDate(task.createdAt) }}</span
              >
            </div>
          </div>

          <button class="icon-btn" @click="handleClose">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-6 w-6"
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
        </div>

        <div class="content-grid">
          <div class="main-panel">
            <div class="subsection">
              <div class="subsection-title">Description</div>
              <div v-if="!isEditing">
                <p
                  v-if="task.description"
                  class="text-[#d4e0ec] whitespace-pre-wrap leading-6"
                >
                  {{ task.description }}
                </p>
                <p v-else class="text-[#7f95ad] italic">Aucune description</p>
              </div>
              <textarea
                v-else
                v-model="editDescription"
                class="field-textarea w-full"
                placeholder="Ajoutez une description..."
              ></textarea>
            </div>

            <div class="subsection">
              <div class="subsection-title">Suppression</div>
              <button
                v-if="!deleteConfirm"
                class="btn-danger-outline"
                @click="deleteConfirm = true"
              >
                Supprimer la tâche
              </button>
              <div v-else class="delete-confirm-panel">
                <div class="delete-confirm-copy">
                  <div class="delete-icon" aria-hidden="true">
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
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                      />
                    </svg>
                  </div>
                  <div>
                    <p class="text-[#ffe4b3] font-medium">
                      Supprimer cette tâche ?
                    </p>
                    <p class="text-xs text-[#d9b96d] mt-1">
                      Cette action est définitive.
                    </p>
                  </div>
                </div>
                <div class="delete-confirm-actions">
                  <button
                    class="mini-btn-soft flex-1"
                    @click="deleteConfirm = false"
                  >
                    Annuler
                  </button>
                  <button
                    class="mini-btn-danger flex-1"
                    :class="{ loading: loading }"
                    @click="deleteTask"
                  >
                    Supprimer
                  </button>
                </div>
              </div>
            </div>
          </div>

          <aside class="side-panel">
            <div class="subsection">
              <div class="subsection-title">Colonne</div>
              <div v-if="!isEditing" class="flex items-center gap-2">
                <div :class="[task.columnColor, 'w-3 h-3 rounded-full']"></div>
                <span class="status-pill">{{ task.columnName }}</span>
              </div>
              <select v-else v-model="editColumnId" class="field-select w-full">
                <option v-for="col in columns" :key="col.id" :value="col.id">
                  {{ col.name }}
                </option>
              </select>
            </div>

            <div class="subsection">
              <div class="subsection-title">Assigné à</div>
              <div v-if="!isEditing">
                <div v-if="task.assignedTo" class="flex items-center gap-2">
                  <div class="avatar placeholder">
                    <div class="user-avatar rounded-full w-8 overflow-hidden">
                      <img
                        v-if="resolveAssignedAvatar(task.assignedTo)"
                        :src="
                          resolveAssignedAvatar(task.assignedTo) || undefined
                        "
                        :alt="task.assignedTo.displayName"
                        class="w-full h-full object-cover"
                      />
                      <span v-else class="text-sm">
                        {{
                          task.assignedTo.displayName
                            .split(" ")
                            .map((n) => n[0])
                            .join("")
                            .toUpperCase()
                            .slice(0, 2)
                        }}
                      </span>
                    </div>
                  </div>
                  <span class="text-[#d4e0ec]">{{
                    task.assignedTo.displayName
                  }}</span>
                </div>
                <p v-else class="text-[#7f95ad] italic">Non assigné</p>
              </div>
              <select
                v-else
                v-model="editAssignedToId"
                class="field-select w-full"
                :disabled="loadingMembers"
              >
                <option :value="null">Non assigné</option>
                <option
                  v-for="member in localMembers"
                  :key="member.id"
                  :value="member.id"
                >
                  {{ member.displayName }}
                </option>
              </select>
            </div>

            <div class="subsection">
              <div class="subsection-title">Estimation</div>
              <div v-if="!isEditing">
                <span v-if="task.estimation" class="status-pill status-points">
                  {{ task.estimation }} point{{
                    task.estimation > 1 ? "s" : ""
                  }}
                </span>
                <p v-else class="text-[#7f95ad] italic">Non estimé</p>
              </div>
              <select
                v-else
                v-model="editEstimation"
                class="field-select w-full"
              >
                <option
                  v-for="option in estimationOptions"
                  :key="option.value ?? 'none'"
                  :value="option.value"
                >
                  {{ option.label }}
                </option>
              </select>
            </div>

            <div class="subsection">
              <div class="subsection-title">Statut</div>
              <div class="flex items-center gap-2">
                <span
                  class="status-pill"
                  :class="
                    task.type === 'archived'
                      ? 'status-archived'
                      : 'status-active'
                  "
                >
                  {{ task.type === "archived" ? "Archivée" : "Active" }}
                </span>
                <button
                  class="mini-btn-soft"
                  :class="{ loading: loading }"
                  @click="toggleArchive"
                >
                  {{ task.type === "archived" ? "Désarchiver" : "Archiver" }}
                </button>
              </div>
            </div>
          </aside>
        </div>

        <div class="modal-action modal-footer-actions">
          <div
            v-if="!isEditing"
            class="flex gap-2 w-full justify-between items-center"
          >
            <button class="btn-soft" @click="handleClose">Fermer</button>
            <button class="btn-soft" @click="startEditing">Modifier</button>
          </div>

          <div v-else class="flex gap-2 justify-end w-full">
            <button class="btn-soft" @click="cancelEditing">Annuler</button>
            <button
              class="btn-primary-custom"
              :class="{ loading: loading }"
              :disabled="!editTitle.trim() || loading"
              @click="saveChanges"
            >
              Enregistrer
            </button>
          </div>
        </div>
      </div>
    </div>

    <form method="dialog" class="modal-backdrop">
      <button @click="handleClose">close</button>
    </form>
  </dialog>
</template>

<style scoped>
.task-detail-modal {
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

.modal-layout {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-height: 0;
}

.header-row {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.title-block {
  min-width: 0;
}

.meta-line {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 0.75rem;
}

.meta-chip {
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  background: rgba(12, 26, 44, 0.6);
  color: #8ea4be;
  font-size: 0.75rem;
}

.content-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.7fr) minmax(280px, 0.95fr);
  gap: 1rem;
}

.main-panel,
.side-panel {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.subsection {
  padding: 0.15rem 0;
}

.subsection-title {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #8ea4be;
  margin-bottom: 0.5rem;
}

.icon-btn {
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.15);
  background: rgba(11, 25, 43, 0.75);
  color: #c8d8ea;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-btn:hover {
  border-color: rgba(91, 163, 232, 0.55);
  color: #e7f0fa;
}

.field-input,
.field-select,
.field-textarea {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.13);
  background: rgba(10, 21, 36, 0.9);
  color: #e7f0fa;
  padding: 0.65rem 0.8rem;
  outline: none;
}

.field-textarea {
  min-height: 8rem;
  resize: vertical;
}

.field-input:focus,
.field-select:focus,
.field-textarea:focus {
  border-color: rgba(91, 163, 232, 0.75);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.18);
}

.status-pill {
  padding: 0.25rem 0.6rem;
  border-radius: 999px;
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(12, 26, 44, 0.7);
  color: #b9cde1;
  font-size: 0.75rem;
}

.status-active {
  color: #c9f3da;
  border-color: rgba(110, 210, 158, 0.45);
  background: rgba(38, 128, 93, 0.25);
}

.status-archived {
  color: #ffe3ab;
  border-color: rgba(240, 195, 109, 0.42);
  background: rgba(172, 120, 38, 0.25);
}

.status-points {
  color: #cfe8ff;
  border-color: rgba(91, 163, 232, 0.42);
  background: rgba(64, 142, 214, 0.22);
}

.user-avatar {
  background: rgba(64, 142, 214, 0.25);
  color: #bfe0fa;
  border: 1px solid rgba(91, 163, 232, 0.45);
}

.delete-confirm-panel {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  gap: 0.85rem;
  padding: 0.85rem 0.95rem;
  border-radius: 14px;
  background: rgba(94, 66, 18, 0.18);
}

.modal-footer-actions {
  margin-top: auto;
  padding-top: 0.5rem;
}

.delete-confirm-copy {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.delete-confirm-actions {
  display: flex;
  gap: 0.6rem;
}

.delete-icon {
  width: 2rem;
  height: 2rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #ffd98a;
  background: rgba(94, 66, 18, 0.32);
  flex-shrink: 0;
}

.btn-soft,
.mini-btn-soft,
.btn-primary-custom,
.btn-danger-outline,
.mini-btn-danger {
  border-radius: 10px;
  padding: 0.55rem 0.95rem;
  transition: 0.2s ease;
}

.btn-soft,
.mini-btn-soft {
  border: 1px solid rgba(255, 255, 255, 0.14);
  background: rgba(15, 31, 53, 0.78);
  color: #c8d8ea;
}

.btn-soft:hover,
.mini-btn-soft:hover {
  color: #e7f0fa;
  border-color: rgba(91, 163, 232, 0.45);
}

.btn-primary-custom {
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #ffffff;
}

.btn-primary-custom:hover:enabled {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.btn-primary-custom:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn-danger-outline {
  border: 1px solid rgba(248, 113, 113, 0.36);
  background: rgba(127, 29, 29, 0.14);
  color: #fda4af;
}

.btn-danger-outline:hover,
.mini-btn-danger:hover {
  background: rgba(214, 62, 82, 0.9);
  color: #fff;
}

.mini-btn-soft,
.mini-btn-danger {
  padding: 0.38rem 0.65rem;
}

.mini-btn-danger {
  border: 1px solid rgba(248, 113, 113, 0.5);
  background: rgba(87, 24, 33, 0.5);
  color: #ff8f9a;
}

@media (max-width: 768px) {
  .content-grid {
    grid-template-columns: 1fr;
  }

  .header-row {
    align-items: flex-start;
  }
}
</style>

<script setup lang="ts">
interface TaskUser {
  id: number;
  displayName: string;
  avatarUrl?: string | null;
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
  task: Task;
  members?: TaskUser[];
  draggable?: boolean;
}>();

defineEmits<{
  click: [];
  dragstart: [];
  dragend: [];
}>();

const API_BASE = import.meta.env.VITE_API_URL?.replace("/api", "") || "";

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

function getInitials(name: string): string {
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .slice(0, 2);
}

function formatDate(dateString: string): string {
  const date = new Date(dateString);
  return date.toLocaleDateString("fr-FR", {
    day: "numeric",
    month: "short",
  });
}
</script>

<template>
  <div
    class="task-card rounded-lg p-3 cursor-pointer"
    :class="{ 'opacity-60': task.type === 'archived' }"
    :draggable="draggable"
    @click="$emit('click')"
    @dragstart="$emit('dragstart')"
    @dragend="$emit('dragend')"
  >
    <!-- Title -->
    <h5 class="font-medium text-sm mb-2 line-clamp-2 text-[#e7f0fa]">
      {{ task.title }}
    </h5>

    <!-- Description preview -->
    <p v-if="task.description" class="text-xs text-[#8ea4be] mb-3 line-clamp-2">
      {{ task.description }}
    </p>

    <!-- Footer -->
    <div class="flex items-center justify-between">
      <!-- Assigned user -->
      <div v-if="task.assignedTo" class="flex items-center gap-1">
        <div class="avatar placeholder shrink-0">
          <div class="task-avatar rounded-full w-6 overflow-hidden">
            <img
              v-if="resolveAssignedAvatar(task.assignedTo)"
              :src="resolveAssignedAvatar(task.assignedTo) || undefined"
              :alt="task.assignedTo.displayName"
              class="w-full h-full object-cover"
            />
            <span v-else class="text-xs">{{
              getInitials(task.assignedTo.displayName)
            }}</span>
          </div>
        </div>
        <span class="text-xs text-[#a7bdd2] truncate max-w-20">{{
          task.assignedTo.displayName
        }}</span>
      </div>
      <div v-else></div>

      <div class="flex items-center gap-2">
        <!-- Archived badge -->
        <span v-if="task.type === 'archived'" class="badge-archived">
          Archivée
        </span>

        <!-- Estimation -->
        <span v-if="task.estimation" class="badge-estimation">
          {{ task.estimation }}pt
        </span>

        <!-- Date -->
        <span class="text-xs text-[#7a92ab]" data-testid="task-date">{{
          formatDate(task.createdAt)
        }}</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.task-card {
  user-select: none;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.12),
      transparent 42%
    ),
    linear-gradient(165deg, rgba(20, 41, 65, 0.86), rgba(13, 28, 47, 0.9));
  box-shadow:
    0 8px 16px rgba(3, 10, 20, 0.2),
    inset 0 0 0 1px rgba(255, 255, 255, 0.025);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    border-color 0.2s ease;
}

.task-card:hover {
  transform: translateY(-2px);
  border-color: rgba(91, 163, 232, 0.45);
  box-shadow: 0 14px 26px rgba(3, 10, 20, 0.35);
}

.task-card:active {
  cursor: grabbing;
}

.task-card[draggable="true"] {
  cursor: grab;
}

.task-card[draggable="true"]:active {
  opacity: 0.5;
}

.task-card.opacity-60 {
  border-style: dashed;
}

.task-avatar {
  background: rgba(64, 142, 214, 0.25);
  color: #bfe0fa;
  border: 1px solid rgba(91, 163, 232, 0.45);
}

.badge-archived,
.badge-estimation {
  font-size: 0.65rem;
  line-height: 1;
  padding: 0.25rem 0.45rem;
  border-radius: 999px;
  border: 1px solid transparent;
}

.badge-archived {
  color: #ffe3ab;
  background: rgba(172, 120, 38, 0.25);
  border-color: rgba(240, 195, 109, 0.42);
}

.badge-estimation {
  color: #cfe8ff;
  background: rgba(64, 142, 214, 0.22);
  border-color: rgba(91, 163, 232, 0.42);
}
</style>

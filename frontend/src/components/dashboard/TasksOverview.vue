<template>
  <div class="dashboard-card">
    <div>
      <div class="flex items-center justify-between mb-4">
        <h2 class="card-title text-white">
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
            />
          </svg>
          Tâches
        </h2>
      </div>

      <!-- Filtre Room -->
      <div v-if="availableRooms && availableRooms.length > 0" class="mb-4">
        <div class="form-control">
          <label class="label">
            <span class="label-text font-semibold">Room</span>
          </label>
          <select
            v-model="selectedRoomId"
            class="select select-bordered select-sm w-full"
          >
            <option :value="null">Toutes les rooms</option>
            <option
              v-for="room in availableRooms"
              :key="room.id"
              :value="room.id"
            >
              {{ room.name }}
            </option>
          </select>
        </div>
      </div>

      <!-- État vide -->
      <div
        v-if="!tasks || !tasks.today || !tasks.overdue || !tasks.roomProgress"
        class="text-center py-8 text-base-content/60"
      >
        <p>Aucune donnée de tâches disponible pour le moment.</p>
      </div>

      <template v-else>
        <!-- Tâches du jour -->
        <div v-if="tasks.today.length > 0" class="mb-4">
          <h3
            class="text-sm font-semibold mb-2 text-base-content/70 flex items-center gap-2"
          >
            <span>Aujourd'hui</span>
            <span class="badge badge-sm">{{ tasks.today.length }}</span>
          </h3>
          <div class="space-y-2">
            <div
              v-for="task in tasks.today"
              :key="task.id"
              class="flex items-start gap-2 p-2 rounded-lg hover:bg-[#1a3a52] transition-colors cursor-pointer"
            >
              <input type="checkbox" class="checkbox checkbox-sm mt-1" />
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ task.title }}</div>
                <div
                  v-if="task.room"
                  class="text-xs text-primary flex items-center gap-1"
                >
                  <svg
                    class="w-3 h-3"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                  </svg>
                  {{ task.room.name }}
                </div>
              </div>
              <div
                class="badge badge-sm"
                :class="getPriorityClass(task.priority)"
              >
                {{ task.priority }}
              </div>
            </div>
          </div>
        </div>

        <!-- Tâches en retard -->
        <div v-if="tasks.overdue.length > 0" class="mb-4">
          <h3
            class="text-sm font-semibold mb-2 text-error flex items-center gap-2"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
            <span>En retard</span>
            <span class="badge badge-error badge-sm">{{
              tasks.overdue.length
            }}</span>
          </h3>
          <div class="space-y-2">
            <div
              v-for="task in tasks.overdue"
              :key="task.id"
              class="flex items-start gap-2 p-2 rounded-lg bg-error/10 hover:bg-error/20 transition-colors cursor-pointer"
            >
              <input
                type="checkbox"
                class="checkbox checkbox-sm checkbox-error mt-1"
              />
              <div class="flex-1 min-w-0">
                <div class="font-medium truncate">{{ task.title }}</div>
                <div class="text-xs text-error">
                  Échéance: {{ formatDate(task.dueDate) }}
                </div>
              </div>
              <div
                class="badge badge-sm"
                :class="getPriorityClass(task.priority)"
              >
                {{ task.priority }}
              </div>
            </div>
          </div>
        </div>

        <!-- Progression par room -->
        <div v-if="tasks.roomProgress.length > 0">
          <h3 class="text-sm font-semibold mb-3 text-base-content/70">
            Progression des projets
          </h3>
          <div class="space-y-3">
            <div v-for="room in tasks.roomProgress" :key="room.roomId">
              <div class="flex justify-between items-center mb-1">
                <span class="text-sm font-medium truncate">{{
                  room.roomName
                }}</span>
                <span class="text-xs text-base-content/70">
                  {{ room.completed }}/{{ room.total }}
                </span>
              </div>
              <div class="w-full bg-[#1a3a52] rounded-full h-2">
                <div
                  class="h-2 rounded-full transition-all duration-300"
                  :class="{
                    'bg-success': room.percentage >= 80,
                    'bg-warning': room.percentage >= 50 && room.percentage < 80,
                    'bg-error': room.percentage < 50,
                  }"
                  :style="{ width: room.percentage + '%' }"
                ></div>
              </div>
              <div class="text-xs text-right text-base-content/70 mt-1">
                {{ room.percentage }}%
              </div>
            </div>
          </div>
        </div>

        <!-- Message si aucune tâche -->
        <div
          v-if="
            tasks.today.length === 0 &&
            tasks.overdue.length === 0 &&
            tasks.roomProgress.length === 0
          "
          class="text-center py-8 text-base-content/50"
        >
          Aucune tâche pour le moment
        </div>
      </template>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";

interface TaskItem {
  id: number;
  title: string;
  description: string | null;
  status: string;
  priority: string;
  dueDate: string | null;
  room: { id: number; name: string } | null;
}

interface TasksData {
  today: TaskItem[];
  overdue: TaskItem[];
  roomProgress: Array<{
    roomId: number;
    roomName: string;
    total: number;
    completed: number;
    percentage: number;
  }>;
}

interface Room {
  id: number;
  name: string;
}

defineProps<{
  tasks?: TasksData;
  availableRooms?: Room[];
}>();

const selectedRoomId = ref<number | null>(null);

const getPriorityClass = (priority: string): string => {
  const classes: Record<string, string> = {
    high: "badge-error",
    medium: "badge-warning",
    low: "badge-success",
  };
  return classes[priority] || "badge-ghost";
};

const formatDate = (dateString: string | null): string => {
  if (!dateString) return "";

  // Parser manuellement la date ISO pour éviter les conversions de timezone
  const match = dateString.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
  if (!match) {
    // Fallback si le format n'est pas ISO complet
    const date = new Date(dateString);
    return date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });
  }

  const [, year = "0", month = "1", day = "1", hour = "0", minute = "0"] =
    match ?? [];
  const date = new Date(
    parseInt(year),
    parseInt(month) - 1,
    parseInt(day),
    parseInt(hour),
    parseInt(minute),
  );
  return date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });
};
</script>

<style scoped>
.dashboard-card {
  background-color: #0a1628;
  border: none;
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dashboard-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  transform: translateY(-2px);
}
</style>

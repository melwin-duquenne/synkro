<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
      </svg>
      Tâches
    </div>

    <!-- Filtre Room -->
    <div v-if="availableRooms && availableRooms.length > 0" class="room-filter">
      <label class="filter-label">Room</label>
      <select v-model="selectedRoomId" class="filter-select">
        <option :value="null">Toutes les rooms</option>
        <option v-for="room in availableRooms" :key="room.id" :value="room.id">{{ room.name }}</option>
      </select>
    </div>

    <div v-if="!tasks || !tasks.today || !tasks.overdue || !tasks.roomProgress" class="empty-state">
      Aucune donnée de tâches disponible pour le moment.
    </div>

    <template v-else>
      <!-- Tâches du jour -->
      <div v-if="tasks.today.length > 0" class="tasks-section">
        <div class="section-label">
          Aujourd'hui
          <span class="count-pill">{{ tasks.today.length }}</span>
        </div>
        <div class="tasks-list">
          <div v-for="task in tasks.today" :key="task.id" class="task-row">
            <input type="checkbox" class="task-check" />
            <div class="task-content">
              <div class="task-title">{{ task.title }}</div>
              <div v-if="task.room" class="task-room">
                <svg class="inline-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ task.room.name }}
              </div>
            </div>
            <span class="priority-pill" :class="getPriorityClass(task.priority)">{{ task.priority }}</span>
          </div>
        </div>
      </div>

      <!-- En retard -->
      <div v-if="tasks.overdue.length > 0" class="tasks-section">
        <div class="section-label section-error">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
          </svg>
          En retard
          <span class="count-pill count-error">{{ tasks.overdue.length }}</span>
        </div>
        <div class="tasks-list">
          <div v-for="task in tasks.overdue" :key="task.id" class="task-row task-overdue">
            <input type="checkbox" class="task-check check-error" />
            <div class="task-content">
              <div class="task-title">{{ task.title }}</div>
              <div class="task-due">Échéance : {{ formatDate(task.dueDate) }}</div>
            </div>
            <span class="priority-pill" :class="getPriorityClass(task.priority)">{{ task.priority }}</span>
          </div>
        </div>
      </div>

      <!-- Progression par room -->
      <div v-if="tasks.roomProgress.length > 0" class="tasks-section">
        <div class="section-label">Progression des projets</div>
        <div class="progress-list">
          <div v-for="room in tasks.roomProgress" :key="room.roomId" class="progress-item">
            <div class="progress-header">
              <span class="progress-name">{{ room.roomName }}</span>
              <span class="progress-count">{{ room.completed }}/{{ room.total }}</span>
            </div>
            <div class="progress-track">
              <div
                class="progress-fill"
                :class="{
                  'fill-ok': room.percentage >= 80,
                  'fill-warn': room.percentage >= 50 && room.percentage < 80,
                  'fill-error': room.percentage < 50,
                }"
                :style="{ width: room.percentage + '%' }"
              ></div>
            </div>
            <div class="progress-pct">{{ room.percentage }}%</div>
          </div>
        </div>
      </div>

      <!-- Rien -->
      <div v-if="tasks.today.length === 0 && tasks.overdue.length === 0 && tasks.roomProgress.length === 0" class="empty-state">
        Aucune tâche pour le moment
      </div>
    </template>
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
  return classes[priority] || "pill-ghost";
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
.card {
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 1.5rem;
  transition: border-color 0.2s;
}
.card:hover { border-color: rgba(255, 255, 255, 0.1); }
.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #c8daea;
  margin-bottom: 1.25rem;
}
.header-icon { width: 16px; height: 16px; color: #5ba3e8; flex-shrink: 0; }
.empty-state { text-align: center; padding: 2rem 0; color: #3d5060; font-size: 0.875rem; }
.room-filter { margin-bottom: 1rem; }
.filter-label { display: block; font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #516070; margin-bottom: 0.375rem; }
.filter-select {
  width: 100%;
  padding: 0.5rem 0.75rem;
  background: rgba(255, 255, 255, 0.04);
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 8px;
  color: #8ea4be;
  font-size: 0.875rem;
  outline: none;
  cursor: pointer;
}
.filter-select:focus { border-color: rgba(91, 163, 232, 0.3); }
.filter-select option { background: #080f1c; color: #8ea4be; }
.tasks-section { margin-bottom: 1.25rem; }
.section-label {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #516070;
  margin-bottom: 0.5rem;
}
.section-error { color: #f87171; }
.count-pill {
  padding: 0.1rem 0.4rem;
  background: rgba(255,255,255,0.08);
  border-radius: 999px;
  font-size: 0.6rem;
}
.count-error { background: rgba(248,113,113,0.15); color: #f87171; }
.tasks-list { display: flex; flex-direction: column; gap: 0.375rem; }
.task-row {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  padding: 0.5rem 0.75rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}
.task-row:hover { background: rgba(255,255,255,0.05); }
.task-overdue { background: rgba(248,113,113,0.05); border-color: rgba(248,113,113,0.15); }
.task-overdue:hover { background: rgba(248,113,113,0.08); }
.task-check {
  width: 14px;
  height: 14px;
  margin-top: 2px;
  flex-shrink: 0;
  border-radius: 3px;
  accent-color: #5ba3e8;
}
.check-error { accent-color: #f87171; }
.task-content { flex: 1; min-width: 0; }
.task-title { font-size: 0.8125rem; font-weight: 500; color: #c8daea; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.task-room { font-size: 0.6875rem; color: #5ba3e8; display: flex; align-items: center; gap: 0.25rem; margin-top: 0.125rem; }
.task-due { font-size: 0.6875rem; color: #f87171; margin-top: 0.125rem; }
.inline-icon { width: 10px; height: 10px; flex-shrink: 0; }
.priority-pill {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  flex-shrink: 0;
  align-self: flex-start;
  text-transform: capitalize;
}
.pill-error { background: rgba(248,113,113,0.15); border: 1px solid rgba(248,113,113,0.25); color: #f87171; }
.pill-warn { background: rgba(240,162,62,0.12); border: 1px solid rgba(240,162,62,0.25); color: #f0a23e; }
.pill-ok { background: rgba(111,221,159,0.12); border: 1px solid rgba(111,221,159,0.25); color: #6fdd9f; }
.pill-ghost { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #6b8099; }
.progress-list { display: flex; flex-direction: column; gap: 0.75rem; }
.progress-item { }
.progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem; }
.progress-name { font-size: 0.8125rem; font-weight: 500; color: #8ea4be; }
.progress-count { font-size: 0.6875rem; color: #516070; }
.progress-track { height: 4px; background: rgba(255,255,255,0.06); border-radius: 999px; overflow: hidden; }
.progress-fill { height: 100%; border-radius: 999px; transition: width 0.4s ease; min-width: 2px; }
.fill-ok { background: #6fdd9f; }
.fill-warn { background: #f0a23e; }
.fill-error { background: #f87171; }
.progress-pct { font-size: 0.6875rem; color: #3d5060; text-align: right; margin-top: 0.125rem; }
</style>

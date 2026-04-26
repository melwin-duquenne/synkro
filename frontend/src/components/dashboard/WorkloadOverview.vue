<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
      </svg>
      Charge de travail
    </div>

    <div v-if="!workload || !workload.daily || workload.daily.length === 0" class="empty-state">
      Aucune donnée de charge de travail disponible pour le moment.
    </div>

    <template v-else>
      <!-- Alerte surcharge -->
      <div v-if="workload.hasAlert" class="overload-alert">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        {{ workload.overloadDays }} jour(s) de surcharge cette semaine
      </div>

      <!-- Résumé Aujourd'hui / Semaine -->
      <div class="summary-grid">
        <div class="summary-box">
          <div class="summary-label">Aujourd'hui</div>
          <div class="summary-value" :class="getHoursClass(todayWorkload.percentage)">{{ todayWorkload.hoursUsed }}h</div>
          <div class="summary-sub">/ {{ todayWorkload.hoursAvailable }}h ({{ Math.round(todayWorkload.percentage) }}%)</div>
          <button
            v-if="todayWorkload.tasks && todayWorkload.tasks.length > 0"
            class="detail-btn"
            @click="showTodayDetails = true"
          >Voir détail</button>
        </div>
        <div class="summary-box">
          <div class="summary-label">Cette semaine</div>
          <div class="summary-value" :class="getHoursClass(weekWorkload.percentage)">{{ weekWorkload.hoursUsed }}h</div>
          <div class="summary-sub">/ {{ weekWorkload.hoursAvailable }}h ({{ Math.round(weekWorkload.percentage) }}%)</div>
        </div>
      </div>

      <!-- Graphique par jour -->
      <div class="days-section">
        <div class="section-label">Charge par jour</div>
        <div class="days-list">
          <div
            v-for="day in workload.daily"
            :key="day.date"
            class="day-row"
            @click="openDayDetails(day)"
          >
            <div class="day-name">{{ day.dayName }}</div>
            <div class="bar-track">
              <div
                class="bar-fill"
                :class="{
                  'bar-ok': day.percentage < 80,
                  'bar-warn': day.percentage >= 80 && day.percentage <= 100,
                  'bar-error': day.percentage > 100,
                }"
                :style="{ width: Math.min(day.percentage, 150) + '%' }"
              >
                <span v-if="day.percentage > 10" class="bar-label">{{ Math.round(day.percentage) }}%</span>
              </div>
            </div>
            <div class="day-hours" :class="{ 'hours-error': day.isOverloaded }">
              {{ Math.round(((day.hoursUsed ?? (day.percentage * (day.hoursAvailable || 9)) / 100)) * 10) / 10 }}h / {{ day.hoursAvailable || 9 }}h
              <svg class="w-3.5 h-3.5 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
              </svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Légende -->
      <div class="legend">
        <span class="legend-item"><span class="legend-dot dot-ok"></span>&lt; 80%</span>
        <span class="legend-item"><span class="legend-dot dot-warn"></span>80-100%</span>
        <span class="legend-item"><span class="legend-dot dot-error"></span>&gt; 100%</span>
      </div>
    </template>
  </div>

  <!-- Modal : Aujourd'hui -->
  <Teleport to="body">
    <div
      v-if="showTodayDetails"
      class="modal-overlay"
      @click.self="showTodayDetails = false"
    >
      <div class="modal-panel">
        <div class="modal-header">
          <span class="modal-title">Détails — Aujourd'hui</span>
          <button class="modal-close" @click="showTodayDetails = false">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="modal-stat-row">
            <span class="modal-stat-label">Charge actuelle</span>
            <span class="modal-stat-value" :class="getHoursClass(todayWorkload.percentage)">{{ Math.round(todayWorkload.percentage) }}%</span>
            <span class="modal-stat-sub">{{ todayWorkload.hoursUsed }}h / {{ todayWorkload.hoursAvailable }}h</span>
          </div>
          <div class="section-label mt-4">Tâches planifiées</div>
          <div v-if="todayWorkload.tasks && todayWorkload.tasks.length > 0" class="modal-tasks">
            <div v-for="task in todayWorkload.tasks" :key="task.id" class="modal-task-row">
              <div class="modal-task-info">
                <div class="modal-task-title">{{ task.title }}</div>
                <div class="modal-task-room">{{ task.roomName }}</div>
              </div>
              <span class="hours-badge">{{ task.estimatedHours }}h</span>
            </div>
          </div>
          <div v-else class="empty-state">Aucune tâche planifiée pour aujourd'hui</div>
        </div>
      </div>
    </div>

    <!-- Modal : Jour spécifique -->
    <div
      v-if="selectedDay"
      class="modal-overlay"
      @click.self="selectedDay = null"
    >
      <div class="modal-panel">
        <div class="modal-header">
          <span class="modal-title">Détails — {{ selectedDay.dayName }}</span>
          <button class="modal-close" @click="selectedDay = null">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        <div class="modal-body">
          <div class="modal-stat-row">
            <span class="modal-stat-label">Charge</span>
            <span class="modal-stat-value" :class="getHoursClass(selectedDay.percentage)">{{ Math.round(selectedDay.percentage) }}%</span>
            <span class="modal-stat-sub">{{ selectedDay.hoursUsed ?? Math.round(((selectedDay.percentage * (selectedDay.hoursAvailable || 9)) / 100) * 10) / 10 }}h / {{ selectedDay.hoursAvailable || 9 }}h</span>
          </div>
          <div class="section-label mt-4">Tâches planifiées</div>
          <div v-if="selectedDay.tasks && selectedDay.tasks.length > 0" class="modal-tasks">
            <div v-for="task in selectedDay.tasks" :key="task.id" class="modal-task-row">
              <div class="modal-task-info">
                <div class="modal-task-title">{{ task.title }}</div>
                <div class="modal-task-room">{{ task.roomName }}</div>
              </div>
              <span class="hours-badge">{{ task.estimatedHours }}h</span>
            </div>
          </div>
          <div v-else class="empty-state">Aucune tâche planifiée pour ce jour</div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";

interface WorkloadData {
  daily: Array<{
    date: string;
    dayName: string;
    percentage: number;
    hoursUsed: number;
    hoursAvailable: number;
    isOverloaded: boolean;
    tasks?: Array<{
      id: number;
      title: string;
      roomName: string;
      estimatedHours: number;
    }>;
  }>;
  overloadDays: number;
  hasAlert: boolean;
}

const props = defineProps<{
  workload?: WorkloadData;
}>();

const showTodayDetails = ref(false);
const selectedDay = ref<WorkloadData["daily"][number] | null>(null);
// Calculer la charge d'aujourd'hui
const todayWorkload = computed(() => {
  if (!props.workload?.daily || props.workload.daily.length === 0) {
    return { hoursUsed: 0, hoursAvailable: 0, percentage: 0, tasks: [] };
  }

  // Obtenir la date d'aujourd'hui au format YYYY-MM-DD
  const today = new Date();
  const todayStr = today.toISOString().split("T")[0];

  console.log("[WorkloadOverview] Today date:", todayStr);
  console.log(
    "[WorkloadOverview] Available dates:",
    props.workload.daily.map((d) => d.date),
  );

  // Chercher par date exacte
  const todayData = props.workload.daily.find((day) => day.date === todayStr);

  console.log("[WorkloadOverview] Today data found:", todayData);

  const data = todayData || {
    hoursUsed: 0,
    hoursAvailable: 0,
    percentage: 0,
    tasks: [],
  };

  // Calculer les heures si elles ne sont pas fournies par le backend
  const hoursAvailable = data.hoursAvailable || 9;
  const hoursUsed = data.hoursUsed ?? (data.percentage * hoursAvailable) / 100;

  return {
    ...data,
    hoursUsed: Math.round(hoursUsed * 10) / 10,
    hoursAvailable,
    percentage: data.percentage,
  };
});

// Calculer la charge de la semaine
const weekWorkload = computed(() => {
  if (!props.workload?.daily || props.workload.daily.length === 0) {
    return { hoursUsed: 0, hoursAvailable: 0, percentage: 0 };
  }

  // Semaine de travail = 45h (5 jours * 9h)
  const totalAvailable = 45;

  const totalHours = props.workload.daily.reduce((sum, day) => {
    const hoursAvailable = day.hoursAvailable || 9;
    const hoursUsed = day.hoursUsed ?? (day.percentage * hoursAvailable) / 100;
    return sum + hoursUsed;
  }, 0);

  const percentage =
    totalAvailable > 0 ? (totalHours / totalAvailable) * 100 : 0;

  return {
    hoursUsed: Math.round(totalHours * 10) / 10,
    hoursAvailable: totalAvailable,
    percentage,
  };
});

const openDayDetails = (day: WorkloadData["daily"][number]) => {
  selectedDay.value = day;
};

const getHoursClass = (percentage: number): string => {
  if (percentage > 100) return 'color-error';
  if (percentage >= 80) return 'color-warn';
  return 'color-ok';
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
.overload-alert {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 0.875rem;
  background: rgba(240, 162, 62, 0.08);
  border: 1px solid rgba(240, 162, 62, 0.2);
  border-radius: 8px;
  color: #f0a23e;
  font-size: 0.8125rem;
  margin-bottom: 1rem;
}
.summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; margin-bottom: 1.25rem; }
.summary-box {
  padding: 0.875rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.055);
  border-radius: 10px;
}
.summary-label { font-size: 0.6875rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #516070; margin-bottom: 0.25rem; }
.summary-value { font-size: 1.5rem; font-weight: 700; line-height: 1.2; margin-bottom: 0.125rem; }
.color-ok { color: #6fdd9f; }
.color-warn { color: #f0a23e; }
.color-error { color: #f87171; }
.summary-sub { font-size: 0.6875rem; color: #3d5060; }
.detail-btn {
  margin-top: 0.5rem;
  padding: 0.25rem 0.625rem;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 6px;
  color: #6b8099;
  font-size: 0.6875rem;
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}
.detail-btn:hover { background: rgba(255,255,255,0.07); color: #c8daea; }
.days-section { margin-bottom: 1rem; }
.section-label {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #516070;
  margin-bottom: 0.5rem;
}
.mt-4 { margin-top: 1rem; }
.days-list { display: flex; flex-direction: column; gap: 0.375rem; }
.day-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.625rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s;
}
.day-row:hover { background: rgba(255,255,255,0.04); }
.day-name { width: 60px; font-size: 0.8125rem; font-weight: 500; color: #8ea4be; flex-shrink: 0; }
.bar-track { flex: 1; height: 8px; background: rgba(255,255,255,0.06); border-radius: 999px; overflow: hidden; }
.bar-fill { height: 100%; border-radius: 999px; transition: width 0.3s ease; display: flex; align-items: center; justify-content: flex-end; padding-right: 4px; min-width: 2px; }
.bar-ok { background: #6fdd9f; }
.bar-warn { background: #f0a23e; }
.bar-error { background: #f87171; }
.bar-label { font-size: 0.45rem; font-weight: 700; color: rgba(0,0,0,0.5); }
.day-hours { width: 90px; text-align: right; font-size: 0.6875rem; color: #516070; display: flex; align-items: center; justify-content: flex-end; gap: 0.25rem; flex-shrink: 0; }
.hours-error { color: #f87171; font-weight: 600; }
.legend { display: flex; gap: 1rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.05); }
.legend-item { display: flex; align-items: center; gap: 0.375rem; font-size: 0.6875rem; color: #3d5060; }
.legend-dot { width: 10px; height: 10px; border-radius: 3px; }
.dot-ok { background: #6fdd9f; }
.dot-warn { background: #f0a23e; }
.dot-error { background: #f87171; }
/* Modals */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.7);
  backdrop-filter: blur(4px);
}
.modal-panel {
  background: #060d18;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 16px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.6);
  max-width: 560px;
  width: 100%;
  max-height: 80vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1.25rem 1.5rem;
  border-bottom: 1px solid rgba(255,255,255,0.06);
}
.modal-title { font-size: 0.9375rem; font-weight: 600; color: #c8daea; }
.modal-close {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.07);
  border-radius: 6px;
  color: #6b8099;
  cursor: pointer;
  transition: color 0.15s, background 0.15s;
}
.modal-close:hover { background: rgba(255,255,255,0.08); color: #c8daea; }
.modal-body { flex: 1; overflow-y: auto; padding: 1.5rem; }
.modal-stat-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 0.875rem 1rem;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.055);
  border-radius: 10px;
  margin-bottom: 0.5rem;
}
.modal-stat-label { font-size: 0.75rem; font-weight: 600; color: #516070; }
.modal-stat-value { font-size: 1.5rem; font-weight: 700; }
.modal-stat-sub { font-size: 0.75rem; color: #3d5060; margin-left: auto; }
.modal-tasks { display: flex; flex-direction: column; gap: 0.5rem; }
.modal-task-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.625rem 0.75rem;
  background: rgba(255,255,255,0.03);
  border: 1px solid rgba(255,255,255,0.05);
  border-radius: 8px;
}
.modal-task-info { flex: 1; min-width: 0; }
.modal-task-title { font-size: 0.8125rem; font-weight: 500; color: #c8daea; }
.modal-task-room { font-size: 0.6875rem; color: #516070; margin-top: 0.125rem; }
.hours-badge {
  padding: 0.2rem 0.5rem;
  background: rgba(255,255,255,0.05);
  border: 1px solid rgba(255,255,255,0.1);
  border-radius: 999px;
  font-size: 0.6875rem;
  color: #8ea4be;
  flex-shrink: 0;
}
</style>

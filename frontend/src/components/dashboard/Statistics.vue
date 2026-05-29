<!-- eslint-disable vue/multi-word-component-names -->
<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
      </svg>
      Statistiques de la semaine
    </div>

    <div v-if="!statistics" class="empty-state">
      Aucune statistique disponible pour le moment.
    </div>

    <div v-else class="stats-grid">
      <div class="stat-box">
        <div class="stat-label">Productivité</div>
        <div class="stat-value" :class="getProductivityClass(statistics.productivity)">{{ statistics.productivity }}%</div>
        <div class="stat-sub">{{ statistics.tasksCompleted }}/{{ statistics.tasksCreated }} tâches</div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Réunions</div>
        <div class="stat-value color-blue">{{ statistics.meetingsCount }}</div>
        <div class="stat-sub">{{ statistics.meetingsDurationFormatted }}</div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Tâches complétées</div>
        <div class="stat-value color-green">{{ statistics.tasksCompleted }}</div>
        <div class="stat-sub">Cette semaine</div>
      </div>
      <div class="stat-box">
        <div class="stat-label">Moy. par réunion</div>
        <div class="stat-value color-blue">{{ getAverageMeetingDuration() }}</div>
        <div class="stat-sub">{{ statistics.meetingsDuration }} min total</div>
      </div>
    </div>

    <div v-if="statistics" class="prod-section">
      <div class="section-label">Objectif de productivité</div>
      <div class="progress-track">
        <div
          class="progress-fill"
          :class="{
            'fill-error': statistics.productivity < 50,
            'fill-warn': statistics.productivity >= 50 && statistics.productivity < 80,
            'fill-ok': statistics.productivity >= 80,
          }"
          :style="{ width: Math.min(statistics.productivity, 100) + '%' }"
        >
          <span v-if="statistics.productivity > 20" class="progress-label">{{ statistics.productivity }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Statistics {
  tasksCompleted: number;
  tasksCreated: number;
  productivity: number;
  meetingsCount: number;
  meetingsDuration: number;
  meetingsDurationFormatted: string;
}

const props = defineProps<{
  statistics?: Statistics;
}>();

const getProductivityClass = (productivity: number): string => {
  if (productivity >= 80) return "color-green";
  if (productivity >= 50) return "color-warn";
  return "color-error";
};

const getAverageMeetingDuration = (): string => {
  if (!props.statistics || props.statistics.meetingsCount === 0) return "0min";

  const avgMinutes = Math.round(
    props.statistics.meetingsDuration / props.statistics.meetingsCount,
  );
  const hours = Math.floor(avgMinutes / 60);
  const mins = avgMinutes % 60;

  if (hours > 0) {
    return mins > 0 ? `${hours}h${mins}` : `${hours}h`;
  }

  return `${mins}min`;
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
.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem; }
.stat-box {
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.055);
  border-radius: 10px;
  padding: 0.875rem 1rem;
}
.stat-label {
  font-size: 0.6875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #516070;
  margin-bottom: 0.25rem;
}
.stat-value {
  font-size: 1.625rem;
  font-weight: 700;
  color: #c8daea;
  line-height: 1.2;
  margin-bottom: 0.125rem;
}
.color-blue { color: #5ba3e8; }
.color-green { color: #6fdd9f; }
.color-warn { color: #f0a23e; }
.color-error { color: #f87171; }
.stat-sub { font-size: 0.6875rem; color: #3d5060; }
.prod-section { margin-top: 1.25rem; }
.section-label {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #516070;
  margin-bottom: 0.5rem;
}
.progress-track {
  height: 8px;
  background: rgba(255, 255, 255, 0.06);
  border-radius: 999px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: flex-end;
  padding-right: 6px;
  transition: width 0.5s ease;
  min-width: 2px;
}
.fill-ok { background: #6fdd9f; }
.fill-warn { background: #f0a23e; }
.fill-error { background: #f87171; }
.progress-label { font-size: 0.5rem; font-weight: 700; color: rgba(0,0,0,0.6); }
</style>

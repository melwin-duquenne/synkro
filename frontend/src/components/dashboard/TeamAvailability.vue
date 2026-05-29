<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
      </svg>
      Disponibilité de l'équipe
    </div>

    <div v-if="!teamAvailability || teamAvailability.length === 0" class="empty-state">
      Aucune information d'équipe disponible.
    </div>

    <div v-else class="members-list">
      <div v-for="member in teamAvailability" :key="member.id" class="member-row">
        <!-- Avatar -->
        <div class="avatar-wrap" :class="'ring-' + member.status">
          <img v-if="member.avatar" :src="`${API_BASE}${member.avatar}`" :alt="member.name" class="avatar-img" />
          <div v-else class="avatar-initials">{{ getInitials(member.name) }}</div>
        </div>

        <!-- Info -->
        <div class="member-info">
          <div class="member-name">{{ member.name }}</div>
          <div class="member-status">
            <span class="status-dot" :class="'dot-' + member.status"></span>
            {{ getStatusLabel(member.status) }}
            <span class="sep">·</span>
            <span :class="getWorkloadColorClass(member.workload)">{{ Math.round(member.workload) }}%</span>
          </div>
        </div>

        <!-- Workload ring -->
        <div class="workload-ring-wrap">
          <svg class="workload-ring" viewBox="0 0 36 36">
            <circle class="ring-track" cx="18" cy="18" r="15.9" fill="none" stroke-width="2.5" />
            <circle
              class="ring-fill"
              :class="getWorkloadProgressClass(member.workload)"
              cx="18" cy="18" r="15.9" fill="none" stroke-width="2.5"
              stroke-linecap="round"
              :stroke-dasharray="`${Math.min(member.workload, 100)} 100`"
              transform="rotate(-90 18 18)"
            />
          </svg>
          <span class="workload-value" :class="getWorkloadColorClass(member.workload)">{{ Math.round(member.workload) }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const API_BASE = import.meta.env.VITE_API_URL?.replace('/api', '') || ''

interface TeamMember {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  workload: number;
  status: "available" | "busy" | "absent";
}

defineProps<{
  teamAvailability?: TeamMember[];
}>();

const getInitials = (name: string): string => {
  return name
    .split(" ")
    .map((n) => n[0])
    .join("")
    .toUpperCase()
    .substring(0, 2);
};

const getStatusLabel = (status: string): string => {
  const labels: Record<string, string> = {
    available: "Disponible",
    busy: "Occupé",
    absent: "Absent",
  };
  return labels[status] || status;
};

const getWorkloadColorClass = (workload: number): string => {
  if (workload > 100) return "color-error";
  if (workload >= 80) return "color-warn";
  return "color-ok";
};

const getWorkloadProgressClass = (workload: number): string => {
  if (workload > 100) return "ring-error";
  if (workload >= 80) return "color-warn";
  return "color-ok";
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
.members-list { display: flex; flex-direction: column; gap: 0.375rem; }
.member-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.625rem 0.75rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  transition: border-color 0.15s;
}
.member-row:hover { border-color: rgba(255, 255, 255, 0.1); }
.avatar-wrap {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  overflow: hidden;
  flex-shrink: 0;
  border: 2px solid transparent;
}
.ring-available { border-color: rgba(111, 221, 159, 0.5); }
.ring-busy { border-color: rgba(248, 113, 113, 0.5); }
.ring-absent { border-color: rgba(255, 255, 255, 0.1); }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-initials {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(64, 142, 214, 0.2);
  color: #5ba3e8;
  font-size: 0.6875rem;
  font-weight: 700;
}
.member-info { flex: 1; min-width: 0; }
.member-name { font-size: 0.8125rem; font-weight: 500; color: #c8daea; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.member-status { display: flex; align-items: center; gap: 0.375rem; font-size: 0.6875rem; color: #6b8099; margin-top: 0.125rem; }
.status-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
.dot-available { background: #6fdd9f; }
.dot-busy { background: #f87171; }
.dot-absent { background: #3d5060; }
.sep { color: #2a3a48; }
.color-ok { color: #6fdd9f; }
.color-warn { color: #f0a23e; }
.color-error { color: #f87171; font-weight: 600; }
.workload-ring-wrap { position: relative; width: 36px; height: 36px; flex-shrink: 0; display: flex; align-items: center; justify-content: center; }
.workload-ring { width: 36px; height: 36px; position: absolute; }
.ring-track { stroke: rgba(255,255,255,0.07); }
.ring-ok { stroke: #6fdd9f; }
.ring-warn { stroke: #f0a23e; }
.ring-error { stroke: #f87171; }
.workload-value { font-size: 0.5rem; font-weight: 700; position: relative; z-index: 1; }
</style>

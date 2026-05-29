<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
      </svg>
      Notifications
    </div>

    <!-- Tout OK -->
    <div
      v-if="!notifications || !notifications.upcomingDeadlines || notifications.upcomingDeadlines.length === 0"
      class="empty-state"
    >
      <svg class="empty-icon color-green" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p>Tout est à jour !</p>
    </div>

    <!-- Deadlines proches -->
    <div v-else>
      <div class="section-header">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Deadlines proches
        <span class="count-badge">{{ notifications.upcomingDeadlines.length }}</span>
      </div>
      <div class="notif-list">
        <div
          v-for="task in notifications.upcomingDeadlines"
          :key="task.id"
          class="notif-item"
        >
          <div class="notif-icon-wrap">
            <svg class="w-3.5 h-3.5 color-warn" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
          <div class="notif-content">
            <div class="notif-title">{{ task.title }}</div>
            <div class="notif-sub">Échéance : {{ formatDeadline(task.dueDate) }}</div>
          </div>
          <span class="priority-pill" :class="getPriorityClass(task.priority)">{{ task.priority }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
interface TaskItem {
  id: number;
  title: string;
  description: string | null;
  status: string;
  priority: string;
  dueDate: string | null;
  room: { id: number; name: string } | null;
}

interface Notifications {
  upcomingDeadlines: TaskItem[];
}

defineProps<{
  notifications?: Notifications;
}>();

const getPriorityClass = (priority: string): string => {
  const classes: Record<string, string> = {
    high: "badge-error",
    medium: "badge-warning",
    low: "badge-success",
  };
  return classes[priority] || "pill-ghost";
};

const formatDeadline = (dateString: string | null): string => {
  if (!dateString) return "";

  const date = new Date(dateString);
  const now = new Date();
  const tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const diffTime = date.getTime() - now.getTime();
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

  if (date.toDateString() === now.toDateString()) {
    return "Aujourd'hui";
  } else if (date.toDateString() === tomorrow.toDateString()) {
    return "Demain";
  } else if (diffDays <= 7) {
    return `Dans ${diffDays} jour${diffDays > 1 ? "s" : ""}`;
  } else {
    return date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });
  }
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
.empty-state { text-align: center; padding: 1.5rem 0; color: #3d5060; font-size: 0.875rem; }
.empty-icon { width: 2.5rem; height: 2.5rem; margin: 0 auto 0.75rem; }
.color-green { color: #6fdd9f; }
.color-warn { color: #f0a23e; }
.section-header {
  display: flex;
  align-items: center;
  gap: 0.375rem;
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #516070;
  margin-bottom: 0.75rem;
}
.count-badge {
  margin-left: auto;
  padding: 0.1rem 0.45rem;
  background: rgba(248, 113, 113, 0.15);
  border: 1px solid rgba(248, 113, 113, 0.3);
  color: #f87171;
  border-radius: 999px;
  font-size: 0.6rem;
  font-weight: 700;
}
.notif-list { display: flex; flex-direction: column; gap: 0.5rem; }
.notif-item {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  padding: 0.75rem;
  background: rgba(240, 162, 62, 0.06);
  border: 1px solid rgba(240, 162, 62, 0.15);
  border-radius: 8px;
}
.notif-icon-wrap {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(240, 162, 62, 0.1);
  border-radius: 6px;
  flex-shrink: 0;
}
.notif-content { flex: 1; min-width: 0; }
.notif-title { font-size: 0.8125rem; font-weight: 500; color: #c8daea; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.notif-sub { font-size: 0.6875rem; color: #6b8099; margin-top: 0.125rem; }
.priority-pill {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  flex-shrink: 0;
  align-self: flex-start;
  text-transform: capitalize;
}
.pill-error { background: rgba(248,113,113,0.15); border: 1px solid rgba(248,113,113,0.3); color: #f87171; }
.pill-warn { background: rgba(240,162,62,0.15); border: 1px solid rgba(240,162,62,0.3); color: #f0a23e; }
.pill-ok { background: rgba(111,221,159,0.15); border: 1px solid rgba(111,221,159,0.3); color: #6fdd9f; }
.pill-ghost { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #6b8099; }
</style>

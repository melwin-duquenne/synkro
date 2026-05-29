<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
      Événements
    </div>

    <div
      v-if="(!upcomingEvents || upcomingEvents.length === 0) && (!todayEvents || todayEvents.length === 0)"
      class="empty-state"
    >
      Aucun événement à venir.
    </div>

    <template v-else>
      <!-- Aujourd'hui -->
      <div v-if="(todayEvents?.length ?? 0) > 0" class="events-section">
        <div class="section-label">Aujourd'hui</div>
        <div class="events-list">
          <div
            v-for="event in todayEvents"
            :key="event.id"
            class="event-row event-today"
            @click="$emit('event-click', event)"
          >
            <div class="event-icon-wrap" v-html="getEventIcon(event.eventType)"></div>
            <div class="event-content">
              <div class="event-title">{{ event.title }}</div>
              <div class="event-time">{{ formatTime(event.startDate) }} – {{ formatTime(event.endDate) }}</div>
            </div>
            <span class="event-badge" :class="getEventBadgeClass(event.eventType)">{{ event.eventType }}</span>
          </div>
        </div>
      </div>

      <!-- À venir -->
      <div class="events-section" :class="{ 'mt-4': (todayEvents?.length ?? 0) > 0 }">
        <div class="section-label">À venir</div>
        <div v-if="(upcomingEvents?.length ?? 0) === 0" class="empty-sub">Aucun événement à venir</div>
        <div v-else class="events-list">
          <div
            v-for="event in (upcomingEvents ?? []).slice(0, 2)"
            :key="event.id"
            class="event-row"
            @click="handleEventClick(event)"
          >
            <div class="event-icon-wrap" v-html="getEventIcon(event.eventType)"></div>
            <div class="event-content">
              <div class="event-title">{{ event.title }}</div>
              <div class="event-time">{{ formatDateTime(event.startDate) }}</div>
              <div v-if="event.room" class="event-room">
                <svg class="inline-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                {{ event.room.name }}
              </div>
            </div>
            <span class="event-badge" :class="getEventBadgeClass(event.eventType)">{{ event.eventType }}</span>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
interface Event {
  id: number;
  title: string;
  startDate: string;
  endDate: string;
  eventType: string;
  room: { id: number; name: string } | null;
}

defineProps<{
  upcomingEvents?: Event[];
  todayEvents?: Event[];
}>();

const emit = defineEmits<{
  "event-click": [event: Event];
}>();

const handleEventClick = (event: Event) => {
  emit("event-click", event);
};

const getEventIcon = (type: string): string => {
  const icons: Record<string, string> = {
    meeting: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    task: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>`,
    absence: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" /></svg>`,
    deadline: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    other: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" /></svg>`,
  };
  return icons[type] ?? icons.other ?? "";
};

const getEventBadgeClass = (type: string): string => {
  const classes: Record<string, string> = {
    meeting: "badge-primary",
    task: "badge-success",
    absence: "badge-warning",
    deadline: "badge-error",
  };
  return classes[type] || "badge-ghost";
};

const formatTime = (dateString: string): string => {
  // Parser manuellement la date ISO pour éviter les conversions de timezone
  const match = dateString.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
  if (!match) {
    const date = new Date(dateString);
    return date.toLocaleTimeString("fr-FR", {
      hour: "2-digit",
      minute: "2-digit",
    });
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
  return date.toLocaleTimeString("fr-FR", {
    hour: "2-digit",
    minute: "2-digit",
  });
};

const formatDateTime = (dateString: string): string => {
  // Parser manuellement la date ISO pour éviter les conversions de timezone
  const match = dateString.match(/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2})/);
  if (!match) {
    const date = new Date(dateString);
    const now = new Date();
    const tomorrow = new Date(now);
    tomorrow.setDate(tomorrow.getDate() + 1);

    const isToday = date.toDateString() === now.toDateString();
    const isTomorrow = date.toDateString() === tomorrow.toDateString();

    if (isToday) {
      return `Aujourd'hui à ${formatTime(dateString)}`;
    } else if (isTomorrow) {
      return `Demain à ${formatTime(dateString)}`;
    } else {
      return date.toLocaleDateString("fr-FR", {
        weekday: "short",
        day: "numeric",
        month: "short",
        hour: "2-digit",
        minute: "2-digit",
      });
    }
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
  const now = new Date();
  const tomorrow = new Date(now);
  tomorrow.setDate(tomorrow.getDate() + 1);

  const isToday = date.toDateString() === now.toDateString();
  const isTomorrow = date.toDateString() === tomorrow.toDateString();

  if (isToday) {
    return `Aujourd'hui à ${formatTime(dateString)}`;
  } else if (isTomorrow) {
    return `Demain à ${formatTime(dateString)}`;
  } else {
    return date.toLocaleDateString("fr-FR", {
      weekday: "short",
      day: "numeric",
      month: "short",
      hour: "2-digit",
      minute: "2-digit",
    });
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
.empty-state { text-align: center; padding: 2rem 0; color: #3d5060; font-size: 0.875rem; }
.empty-sub { padding: 0.75rem 0; color: #3d5060; font-size: 0.8125rem; }
.events-section { }
.section-label {
  font-size: 0.6875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #516070;
  margin-bottom: 0.5rem;
}
.mt-4 { margin-top: 1rem; }
.events-list { display: flex; flex-direction: column; gap: 0.375rem; }
.event-row {
  display: flex;
  align-items: flex-start;
  gap: 0.625rem;
  padding: 0.625rem 0.75rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.055);
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s;
}
.event-row:hover { background: rgba(255, 255, 255, 0.05); border-color: rgba(255, 255, 255, 0.1); }
.event-today { border-color: rgba(91, 163, 232, 0.15); background: rgba(64, 142, 214, 0.04); }
.event-icon-wrap { flex-shrink: 0; color: #516070; width: 18px; height: 18px; margin-top: 1px; }
.event-content { flex: 1; min-width: 0; }
.event-title { font-size: 0.8125rem; font-weight: 500; color: #c8daea; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.event-time { font-size: 0.6875rem; color: #6b8099; margin-top: 0.125rem; }
.event-room { font-size: 0.6875rem; color: #5ba3e8; margin-top: 0.125rem; display: flex; align-items: center; gap: 0.25rem; }
.inline-icon { width: 10px; height: 10px; flex-shrink: 0; }
.event-badge {
  font-size: 0.6rem;
  font-weight: 700;
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  flex-shrink: 0;
  align-self: flex-start;
  text-transform: capitalize;
}
.badge-blue { background: rgba(91,163,232,0.15); border: 1px solid rgba(91,163,232,0.25); color: #5ba3e8; }
.badge-green { background: rgba(111,221,159,0.12); border: 1px solid rgba(111,221,159,0.25); color: #6fdd9f; }
.badge-warn { background: rgba(240,162,62,0.12); border: 1px solid rgba(240,162,62,0.25); color: #f0a23e; }
.badge-error { background: rgba(248,113,113,0.12); border: 1px solid rgba(248,113,113,0.25); color: #f87171; }
.badge-ghost { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); color: #6b8099; }
</style>

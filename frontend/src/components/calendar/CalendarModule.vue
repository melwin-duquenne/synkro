<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useCalendarStore } from "@/stores/calendar";
import { useWorkload } from "@/composables/useWorkload";
import type { CalendarEvent, EventType } from "@/types";
import CalendarEventModal from "./CalendarEventModal.vue";
import WorkloadIndicator from "./WorkloadIndicator.vue";

const props = defineProps<{
  roomId: number;
}>();

const calendarStore = useCalendarStore();
const {
  workload: dailyWorkload,
  loading: dailyLoading,
  fetchDailyWorkload,
} = useWorkload();

const {
  workload: weeklyWorkload,
  loading: weeklyLoading,
  fetchWeeklyWorkload,
} = useWorkload();

const currentDate = ref(new Date());
const selectedDate = ref<Date | null>(null);
const showEventModal = ref(false);
const selectedEvent = ref<CalendarEvent | null>(null);
const showDayDetail = ref(false);
const dayDetailDate = ref<Date | null>(null);
const showWorkloadDetail = ref(false);

// Filters
const filterUserId = ref<number | null>(null);
const filterEventType = ref<EventType | null>(null);

const eventTypes: { value: EventType; label: string; color: string }[] = [
  { value: "meeting", label: "Réunion", color: "bg-blue-500" },
  { value: "absence", label: "Absence", color: "bg-orange-500" },
  { value: "blocked", label: "Bloqué", color: "bg-red-500" },
  { value: "reminder", label: "Rappel", color: "bg-purple-500" },
  { value: "other", label: "Autre", color: "bg-gray-500" },
];

const currentMonth = computed(() => currentDate.value.getMonth());
const currentYear = computed(() => currentDate.value.getFullYear());

const monthName = computed(() => {
  return currentDate.value.toLocaleDateString("fr-FR", {
    month: "long",
    year: "numeric",
  });
});

const daysInMonth = computed(() => {
  const year = currentYear.value;
  const month = currentMonth.value;
  const firstDay = new Date(year, month, 1);
  const lastDay = new Date(year, month + 1, 0);
  const days: { date: Date; isCurrentMonth: boolean }[] = [];

  // Add days from previous month to fill the first week
  const firstDayOfWeek = firstDay.getDay() || 7; // Convert Sunday (0) to 7
  for (let i = firstDayOfWeek - 1; i > 0; i--) {
    const date = new Date(year, month, 1 - i);
    days.push({ date, isCurrentMonth: false });
  }

  // Add days of current month
  for (let i = 1; i <= lastDay.getDate(); i++) {
    days.push({ date: new Date(year, month, i), isCurrentMonth: true });
  }

  // Add days from next month to complete the grid
  const remainingDays = 42 - days.length; // 6 rows * 7 days
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i);
    days.push({ date, isCurrentMonth: false });
  }

  return days;
});

const filteredEvents = computed(() => {
  let events = calendarStore.events;

  if (filterUserId.value) {
    events = events.filter((e) => e.user.id === filterUserId.value);
  }
  if (filterEventType.value) {
    events = events.filter((e) => e.eventType === filterEventType.value);
  }

  return events;
});

// Get unique users from events for filter
const eventUsers = computed(() => {
  const usersMap = new Map<number, { id: number; displayName: string }>();
  calendarStore.events.forEach((e) => {
    if (!usersMap.has(e.user.id)) {
      usersMap.set(e.user.id, e.user);
    }
  });
  return Array.from(usersMap.values());
});

function formatLocalDate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function getEventsForDate(date: Date): CalendarEvent[] {
  const dateStr = formatLocalDate(date);
  return filteredEvents.value.filter((event) => {
    // Parse event dates and convert to local date strings
    const startDate = new Date(event.startDate);
    const endDate = new Date(event.endDate);
    const start = formatLocalDate(startDate);
    const end = formatLocalDate(endDate);
    return dateStr >= start && dateStr <= end;
  });
}

function getEventColor(event: CalendarEvent): string {
  if (event.color) return event.color;
  const type = eventTypes.find((t) => t.value === event.eventType);
  return type?.color || "bg-gray-500";
}

function previousMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value - 1, 1);
}

function nextMonth() {
  currentDate.value = new Date(currentYear.value, currentMonth.value + 1, 1);
}

function goToToday() {
  currentDate.value = new Date();
}

function openCreateModal(date: Date) {
  selectedDate.value = date;
  selectedEvent.value = null;
  showEventModal.value = true;
}

function openEditModal(event: CalendarEvent) {
  selectedEvent.value = event;
  selectedDate.value = null;
  showDayDetail.value = false;
  showEventModal.value = true;
}

function openDayDetail(date: Date) {
  dayDetailDate.value = date;
  showDayDetail.value = true;
}

function formatTime(dateStr: string): string {
  const date = new Date(dateStr);
  return date.toLocaleTimeString("fr-FR", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatDayDetailDate(date: Date): string {
  return date.toLocaleDateString("fr-FR", {
    weekday: "long",
    day: "numeric",
    month: "long",
    year: "numeric",
  });
}

const dayDetailEvents = computed(() => {
  if (!dayDetailDate.value) return [];
  return getEventsForDate(dayDetailDate.value);
});

function handleEventSaved() {
  showEventModal.value = false;
  selectedEvent.value = null;
  selectedDate.value = null;
  fetchEvents();
  loadWorkload();
}

function handleEventDeleted() {
  showEventModal.value = false;
  selectedEvent.value = null;
  fetchEvents();
  loadWorkload();
}

function isToday(date: Date): boolean {
  const today = new Date();
  return date.toDateString() === today.toDateString();
}

async function fetchEvents() {
  const startOfMonth = new Date(currentYear.value, currentMonth.value, 1);
  const endOfMonth = new Date(currentYear.value, currentMonth.value + 1, 0);

  await calendarStore.fetchEvents({
    roomId: props.roomId,
    startDate: startOfMonth.toISOString(),
    endDate: endOfMonth.toISOString(),
  });
}

watch([currentMonth, currentYear], () => {
  fetchEvents();
});

// Watch for user filter changes to update workload
watch(filterUserId, () => {
  if (filterUserId.value) {
    loadWorkload();
  }
});

async function loadWorkload() {
  if (!filterUserId.value) return;

  await Promise.all([
    fetchDailyWorkload(undefined, filterUserId.value),
    fetchWeeklyWorkload(undefined, filterUserId.value),
  ]);
}

onMounted(() => {
  fetchEvents();
});
</script>

<template>
  <div class="calendar-module h-full flex flex-col p-4">
    <!-- Header -->
    <div class="calendar-header-wrap flex flex-col gap-4 mb-4">
      <div
        class="calendar-toolbar section-panel flex items-center justify-between"
      >
        <div class="flex items-center gap-4">
          <h3 class="font-semibold text-lg capitalize calendar-title">
            {{ monthName }}
          </h3>
          <div class="flex gap-1">
            <button class="calendar-nav-btn" @click="previousMonth">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
            <button class="calendar-nav-btn" @click="goToToday">
              Aujourd'hui
            </button>
            <button class="calendar-nav-btn" @click="nextMonth">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-4 w-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </div>

        <!-- Filters -->
        <div class="calendar-filters flex items-center gap-2">
          <select v-model="filterUserId" class="calendar-filter-select">
            <option :value="null">Tous les utilisateurs</option>
            <option v-for="user in eventUsers" :key="user.id" :value="user.id">
              {{ user.displayName }}
            </option>
          </select>

          <select v-model="filterEventType" class="calendar-filter-select">
            <option :value="null">Tous les types</option>
            <option
              v-for="type in eventTypes"
              :key="type.value"
              :value="type.value"
            >
              {{ type.label }}
            </option>
          </select>

          <button
            class="calendar-primary-btn"
            @click="openCreateModal(new Date())"
          >
            + Nouvel événement
          </button>
        </div>
      </div>

      <!-- Workload Indicator for Selected User -->
      <div
        v-if="filterUserId && (dailyWorkload || weeklyWorkload)"
        class="calendar-workload-strip section-panel flex items-center gap-3 px-2"
      >
        <span class="text-sm font-medium text-[#9fb5cc]"
          >Charge de travail</span
        >

        <!-- Daily Badge -->
        <button
          v-if="dailyWorkload"
          @click="showWorkloadDetail = true"
          :class="[
            'calendar-workload-badge',
            dailyWorkload.status === 'normal'
              ? 'calendar-workload-badge--normal'
              : dailyWorkload.status === 'busy'
                ? 'calendar-workload-badge--busy'
                : 'calendar-workload-badge--overload',
          ]"
          :title="`Aujourd'hui: ${dailyWorkload.totalHours}h / ${dailyWorkload.standardHours}h`"
        >
          <span class="text-lg">
            {{
              dailyWorkload.status === "normal"
                ? "🟢"
                : dailyWorkload.status === "busy"
                  ? "🟡"
                  : "🔴"
            }}
          </span>
          <span>{{ dailyWorkload.percentage }}%</span>
          <span class="text-xs opacity-70">jour</span>
        </button>

        <!-- Weekly Badge -->
        <button
          v-if="weeklyWorkload"
          @click="showWorkloadDetail = true"
          :class="[
            'calendar-workload-badge',
            weeklyWorkload.status === 'normal'
              ? 'calendar-workload-badge--normal'
              : weeklyWorkload.status === 'busy'
                ? 'calendar-workload-badge--busy'
                : 'calendar-workload-badge--overload',
          ]"
          :title="`Cette semaine: ${weeklyWorkload.totalHours}h / ${weeklyWorkload.standardHours}h`"
        >
          <span class="text-lg">
            {{
              weeklyWorkload.status === "normal"
                ? "🟢"
                : weeklyWorkload.status === "busy"
                  ? "🟡"
                  : "🔴"
            }}
          </span>
          <span>{{ weeklyWorkload.percentage }}%</span>
          <span class="text-xs opacity-70">sem.</span>
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div
      v-if="calendarStore.loading"
      class="flex-1 flex items-center justify-center"
    >
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Calendar Grid -->
    <div
      v-else
      class="calendar-grid-shell flex-1 flex flex-col overflow-hidden"
    >
      <!-- Day headers -->
      <div class="calendar-weekdays grid grid-cols-7">
        <div
          v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']"
          :key="day"
          class="calendar-weekday p-2 text-center text-sm font-medium"
        >
          {{ day }}
        </div>
      </div>

      <!-- Calendar days -->
      <div class="flex-1 grid grid-cols-7 grid-rows-6">
        <div
          v-for="(day, index) in daysInMonth"
          :key="index"
          class="calendar-day-cell border-b border-r p-1 min-h-20 cursor-pointer transition-colors"
          :class="{
            'calendar-day-cell--outside': !day.isCurrentMonth,
            'calendar-day-cell--today': isToday(day.date),
          }"
          @click="openCreateModal(day.date)"
        >
          <!-- Day number -->
          <div
            class="calendar-day-number text-sm font-medium mb-1"
            :class="{
              'calendar-day-number--outside': !day.isCurrentMonth,
              'calendar-day-number--today font-bold': isToday(day.date),
              'calendar-day-number--default':
                day.isCurrentMonth && !isToday(day.date),
            }"
          >
            {{ day.date.getDate() }}
          </div>

          <!-- Events for this day -->
          <div class="space-y-0.5 overflow-hidden">
            <div
              v-for="event in getEventsForDate(day.date).slice(0, 3)"
              :key="event.id"
              class="calendar-event-chip text-xs px-1 py-0.5 rounded truncate text-white cursor-pointer"
              :class="getEventColor(event)"
              :style="event.color ? { backgroundColor: event.color } : {}"
              @click.stop="openEditModal(event)"
            >
              {{ event.title }}
            </div>
            <div
              v-if="getEventsForDate(day.date).length > 3"
              class="calendar-more-events text-xs pl-1 cursor-pointer hover:underline"
              @click.stop="openDayDetail(day.date)"
            >
              +{{ getEventsForDate(day.date).length - 3 }} autres
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div
      class="calendar-legend section-panel flex items-center gap-4 mt-4 text-sm"
    >
      <span class="font-medium text-[#dce8f5]">Types :</span>
      <div
        v-for="type in eventTypes"
        :key="type.value"
        class="flex items-center gap-1"
      >
        <div :class="[type.color, 'w-3 h-3 rounded']"></div>
        <span class="text-[#9fb5cc]">{{ type.label }}</span>
      </div>
    </div>

    <!-- Day Detail Modal -->
    <dialog class="modal" :class="{ 'modal-open': showDayDetail }">
      <div class="modal-box max-w-lg calendar-modal-box">
        <h3 class="font-bold text-lg mb-4 capitalize text-[#e7f0fa]">
          {{ dayDetailDate ? formatDayDetailDate(dayDetailDate) : "" }}
        </h3>
        <div class="space-y-2">
          <div
            v-for="event in dayDetailEvents"
            :key="event.id"
            class="calendar-day-detail-item flex items-center gap-3 p-3 rounded-lg cursor-pointer transition-colors"
            @click="openEditModal(event)"
          >
            <div
              class="w-3 h-3 rounded-full shrink-0"
              :class="getEventColor(event)"
            ></div>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm truncate text-[#e7f0fa]">
                {{ event.title }}
              </div>
              <div class="text-xs text-[#9fb5cc]">
                <template v-if="event.isAllDay">Journée entière</template>
                <template v-else
                  >{{ formatTime(event.startDate) }} -
                  {{ formatTime(event.endDate) }}</template
                >
              </div>
            </div>
            <div class="text-xs text-[#8aa1ba]">
              {{ event.user?.displayName }}
            </div>
          </div>
          <div
            v-if="dayDetailEvents.length === 0"
            class="text-center text-[#9fb5cc] py-4"
          >
            Aucun événement
          </div>
        </div>
        <div class="modal-action">
          <button
            class="calendar-primary-btn"
            @click="
              showDayDetail = false;
              openCreateModal(dayDetailDate!);
            "
          >
            + Nouvel événement
          </button>
          <button class="calendar-soft-btn" @click="showDayDetail = false">
            Fermer
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showDayDetail = false">close</button>
      </form>
    </dialog>

    <!-- Event Modal -->
    <CalendarEventModal
      :open="showEventModal"
      :room-id="props.roomId"
      :event="selectedEvent"
      :initial-date="selectedDate"
      @close="
        showEventModal = false;
        selectedEvent = null;
        selectedDate = null;
      "
      @saved="handleEventSaved"
      @deleted="handleEventDeleted"
    />

    <!-- Workload Detail Modal -->
    <dialog :class="['modal', { 'modal-open': showWorkloadDetail }]">
      <div class="modal-box max-w-4xl calendar-modal-box">
        <h3 class="font-bold text-lg mb-4 text-[#e7f0fa]">
          📊 Charge de travail détaillée
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Daily Workload -->
          <div>
            <h4 class="font-semibold mb-2 text-[#e7f0fa]">Aujourd'hui</h4>
            <WorkloadIndicator
              v-if="dailyWorkload"
              :workload="dailyWorkload"
              :loading="dailyLoading"
              :show-details="true"
            />
          </div>

          <!-- Weekly Workload -->
          <div>
            <h4 class="font-semibold mb-2 text-[#e7f0fa]">Cette semaine</h4>
            <WorkloadIndicator
              v-if="weeklyWorkload"
              :workload="weeklyWorkload"
              :loading="weeklyLoading"
              :show-details="true"
            />
          </div>
        </div>

        <div class="modal-action">
          <button class="calendar-soft-btn" @click="showWorkloadDetail = false">
            Fermer
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showWorkloadDetail = false">close</button>
      </form>
    </dialog>
  </div>
</template>

<style scoped>
.calendar-module {
  min-height: 500px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.14),
      transparent 34%
    ),
    linear-gradient(180deg, rgba(8, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  box-shadow:
    0 22px 44px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.section-panel {
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 14px;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.045),
    rgba(255, 255, 255, 0.02)
  );
  backdrop-filter: blur(10px);
}

.calendar-toolbar {
  padding: 0.75rem 0.85rem;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.calendar-title {
  color: #e7f0fa;
}

.calendar-nav-btn,
.calendar-soft-btn,
.calendar-filter-select,
.calendar-primary-btn {
  border-radius: 11px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.calendar-nav-btn,
.calendar-soft-btn {
  padding: 0.5rem 0.75rem;
  color: #a9c0d8;
  background: rgba(15, 31, 53, 0.75);
}

.calendar-nav-btn:hover,
.calendar-soft-btn:hover {
  color: #edf6ff;
  background: rgba(91, 163, 232, 0.12);
  border-color: rgba(91, 163, 232, 0.38);
}

.calendar-filter-select {
  min-height: 2.2rem;
  padding: 0.45rem 0.7rem;
  background: rgba(14, 29, 49, 0.85);
  color: #e7f0fa;
}

.calendar-filter-select:focus {
  outline: none;
  border-color: rgba(91, 163, 232, 0.6);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.2);
}

.calendar-primary-btn {
  padding: 0.5rem 0.9rem;
  border-color: rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #fff;
}

.calendar-primary-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.calendar-workload-strip {
  padding: 0.42rem 0.6rem;
}

.calendar-workload-badge {
  padding: 0.3rem 0.6rem;
  border-radius: 10px;
  font-weight: 600;
  color: #fff;
  gap: 0.45rem;
  cursor: pointer;
  transition:
    transform 0.2s ease,
    filter 0.2s ease;
  display: inline-flex;
  align-items: center;
}

.calendar-workload-badge:hover {
  transform: translateY(-1px);
}

.calendar-workload-badge--normal {
  background: #2d9d6f;
}

.calendar-workload-badge--busy {
  background: #d39b34;
}

.calendar-workload-badge--overload {
  background: #c65566;
}

.calendar-grid-shell {
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 14px;
  background: rgba(9, 19, 34, 0.66);
}

.calendar-weekdays {
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(24, 50, 80, 0.5);
}

.calendar-weekday {
  color: #95abc2;
}

.calendar-day-cell {
  border-color: rgba(255, 255, 255, 0.08);
  background: rgba(7, 15, 27, 0.35);
}

.calendar-day-cell:hover {
  background: rgba(27, 54, 83, 0.55);
}

.calendar-day-cell--outside {
  background: rgba(5, 12, 22, 0.75);
}

.calendar-day-cell--today {
  background: rgba(91, 163, 232, 0.12);
  border-color: rgba(91, 163, 232, 0.35);
}

.calendar-day-number--outside {
  color: #62768d;
}

.calendar-day-number--today {
  color: #9dd1ff;
}

.calendar-day-number--default {
  color: #e7f0fa;
}

.calendar-event-chip {
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.calendar-event-chip:hover {
  opacity: 0.88;
  transform: translateX(1px);
}

.calendar-more-events {
  color: #9dd1ff;
}

.calendar-legend {
  padding: 0.6rem 0.8rem;
  flex-wrap: wrap;
}

.calendar-modal-box {
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: linear-gradient(
    180deg,
    rgba(9, 19, 34, 0.96),
    rgba(7, 13, 24, 0.96)
  );
  box-shadow: 0 24px 46px rgba(0, 0, 0, 0.32);
}

.calendar-day-detail-item:hover {
  background: rgba(91, 163, 232, 0.12);
}

@media (max-width: 768px) {
  .calendar-module {
    padding: 0.75rem;
    border-radius: 16px;
  }

  .calendar-toolbar {
    padding: 0.6rem;
  }

  .calendar-filters {
    width: 100%;
    flex-wrap: wrap;
  }

  .calendar-filter-select,
  .calendar-primary-btn {
    flex: 1 1 100%;
  }

  .calendar-workload-strip {
    flex-wrap: wrap;
    gap: 0.45rem;
  }
}
</style>

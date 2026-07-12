<script setup lang="ts">
import { ref, onMounted, computed, watch } from "vue";
import { useRoute } from "vue-router";
import { useCalendarStore } from "@/stores/calendar";
import { useWorkload } from "@/composables/useWorkload";
import type { CalendarEvent, EventType } from "@/types";
import CalendarEventModal from "@/components/calendar/CalendarEventModal.vue";
import WorkloadIndicator from "@/components/calendar/WorkloadIndicator.vue";

const route = useRoute();
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
  const firstDayOfWeek = firstDay.getDay() || 7;
  for (let i = firstDayOfWeek - 1; i > 0; i--) {
    const date = new Date(year, month, 1 - i);
    days.push({ date, isCurrentMonth: false });
  }

  // Add days of current month
  for (let i = 1; i <= lastDay.getDate(); i++) {
    days.push({ date: new Date(year, month, i), isCurrentMonth: true });
  }

  // Add days from next month to complete the grid
  const remainingDays = 42 - days.length;
  for (let i = 1; i <= remainingDays; i++) {
    const date = new Date(year, month + 1, i);
    days.push({ date, isCurrentMonth: false });
  }

  return days;
});

const filteredEvents = computed(() => {
  let events = calendarStore.events;
  if (filterEventType.value) {
    events = events.filter((e) => e.eventType === filterEventType.value);
  }
  return events;
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
  loadWorkload();
  fetchEvents();
}

function isToday(date: Date): boolean {
  const today = new Date();
  return date.toDateString() === today.toDateString();
}

async function fetchEvents() {
  const startOfMonth = new Date(currentYear.value, currentMonth.value, 1);
  const endOfMonth = new Date(currentYear.value, currentMonth.value + 1, 0);

  // Fetch personal events (no room)
  await calendarStore.fetchEvents({
    personal: true,
    startDate: startOfMonth.toISOString(),
    endDate: endOfMonth.toISOString(),
  });
}

watch([currentMonth, currentYear], () => {
  fetchEvents();
});

async function loadWorkload() {
  await Promise.all([fetchDailyWorkload(), fetchWeeklyWorkload()]);
}

onMounted(() => {
  fetchEvents();
  loadWorkload();

  // Ouvrir automatiquement le modal de création si on vient du dashboard
  if (route.query.create === "true") {
    openCreateModal(new Date());
  }
});
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div
      class="mb-6 flex flex-col lg:flex-row lg:items-center justify-between gap-4"
    >
      <div>
        <h1 class="text-3xl font-bold text-white mb-2">Mon Calendrier</h1>
        <p class="text-gray-400">Vos événements personnels</p>
      </div>

      <!-- Compact Workload Indicator -->
      <div class="flex items-center gap-4 flex-wrap">
        <div
          v-if="
            (dailyWorkload || weeklyWorkload) && !dailyLoading && !weeklyLoading
          "
          class="flex items-center gap-3"
        >
          <span class="text-sm font-medium text-gray-400"
            >Charge de travail :</span
          >

          <!-- Daily Badge -->
          <button
            v-if="dailyWorkload"
            @click="showWorkloadDetail = true"
            :class="[
              'group min-w-40 rounded-xl border px-3 py-2 text-left transition-all hover:bg-white/10',
              dailyWorkload.status === 'normal'
                ? 'bg-emerald-500/10 border-emerald-500/30'
                : dailyWorkload.status === 'busy'
                  ? 'bg-amber-500/10 border-amber-500/30'
                  : 'bg-red-500/10 border-red-500/30',
            ]"
            :title="`Aujourd'hui: ${dailyWorkload.totalHours}h / ${dailyWorkload.standardHours}h`"
          >
            <div class="flex items-center justify-between">
              <span class="text-[10px] uppercase tracking-wide text-gray-400"
                >Aujourd'hui</span
              >
              <span class="text-lg leading-none">
                {{
                  dailyWorkload.status === "normal"
                    ? "🟢"
                    : dailyWorkload.status === "busy"
                      ? "🟡"
                      : "🔴"
                }}
              </span>
            </div>
            <div class="mt-1 flex items-end justify-between gap-2">
              <span class="text-xl font-bold tabular-nums text-white"
                >{{ dailyWorkload.percentage }}%</span
              >
              <span class="text-[11px] text-gray-400"
                >{{ dailyWorkload.totalHours }}h /
                {{ dailyWorkload.standardHours }}h</span
              >
            </div>
          </button>

          <!-- Weekly Badge -->
          <button
            v-if="weeklyWorkload"
            @click="showWorkloadDetail = true"
            :class="[
              'group min-w-40 rounded-xl border px-3 py-2 text-left transition-all hover:bg-white/10',
              weeklyWorkload.status === 'normal'
                ? 'bg-emerald-500/10 border-emerald-500/30'
                : weeklyWorkload.status === 'busy'
                  ? 'bg-amber-500/10 border-amber-500/30'
                  : 'bg-red-500/10 border-red-500/30',
            ]"
            :title="`Cette semaine: ${weeklyWorkload.totalHours}h / ${weeklyWorkload.standardHours}h`"
          >
            <div class="flex items-center justify-between">
              <span class="text-[10px] uppercase tracking-wide text-gray-400"
                >Semaine</span
              >
              <span class="text-lg leading-none">
                {{
                  weeklyWorkload.status === "normal"
                    ? "🟢"
                    : weeklyWorkload.status === "busy"
                      ? "🟡"
                      : "🔴"
                }}
              </span>
            </div>
            <div class="mt-1 flex items-end justify-between gap-2">
              <span class="text-xl font-bold tabular-nums text-white"
                >{{ weeklyWorkload.percentage }}%</span
              >
              <span class="text-[11px] text-gray-400"
                >{{ weeklyWorkload.totalHours }}h /
                {{ weeklyWorkload.standardHours }}h</span
              >
            </div>
          </button>
        </div>

        <div
          v-else-if="dailyLoading || weeklyLoading"
          class="flex items-center gap-3"
        >
          <span class="text-sm font-medium text-gray-400"
            >Charge de travail</span
          >
          <div class="skeleton h-8 w-24"></div>
          <div class="skeleton h-8 w-24"></div>
        </div>

        <button
          class="btn btn-primary px-6"
          @click="openCreateModal(new Date())"
        >
          + Nouvel événement
        </button>
      </div>
    </div>

    <div class="dashboard-card">
      <div>
        <!-- Month Navigation & Filters -->
        <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
          <div class="flex items-center gap-4">
            <h2 class="text-xl font-semibold capitalize text-white">
              {{ monthName }}
            </h2>
            <div class="flex gap-1">
              <button
                class="btn btn-ghost btn-sm btn-square border border-white/10 hover:bg-white/10"
                @click="previousMonth"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4 text-[#e0e0e0]"
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
              <button
                class="btn btn-ghost btn-sm border border-white/10 text-[#e0e0e0] hover:bg-white/10"
                @click="goToToday"
              >
                Aujourd'hui
              </button>
              <button
                class="btn btn-ghost btn-sm btn-square border border-white/10 hover:bg-white/10"
                @click="nextMonth"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-4 w-4 text-[#e0e0e0]"
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

          <select
            v-model="filterEventType"
            class="select border border-white/10 bg-white/5 text-[#e0e0e0] hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-blue-500/50 rounded-lg"
          >
            <option :value="null" class="bg-[#0a1628] text-[#e0e0e0]">
              Tous les types
            </option>
            <option
              v-for="type in eventTypes"
              :key="type.value"
              :value="type.value"
              class="bg-[#0a1628] text-[#e0e0e0]"
            >
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- Loading -->
        <div v-if="calendarStore.loading" class="flex justify-center py-12">
          <span class="loading loading-spinner loading-lg"></span>
        </div>

        <!-- Calendar Grid -->
        <div v-else>
          <!-- Day headers -->
          <div class="grid grid-cols-7 border-b border-white/10 mb-2">
            <div
              v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']"
              :key="day"
              class="p-3 text-center text-sm font-semibold text-gray-400"
            >
              {{ day }}
            </div>
          </div>

          <!-- Calendar days -->
          <div
            class="grid grid-cols-7 grid-rows-6 gap-px bg-white/5 p-px rounded-lg overflow-hidden"
          >
            <div
              v-for="(day, index) in daysInMonth"
              :key="index"
              class="bg-[#1a2a3a] border border-white/5 p-2 min-h-24 cursor-pointer hover:bg-white/10 transition-colors"
              :class="{
                'bg-white/5 opacity-50': !day.isCurrentMonth,
                'bg-blue-500/20 border border-blue-500/50': isToday(day.date),
              }"
              @click="openCreateModal(day.date)"
            >
              <!-- Day number -->
              <div
                class="text-sm font-medium mb-2"
                :class="{
                  'text-gray-600': !day.isCurrentMonth,
                  'text-blue-400 font-bold': isToday(day.date),
                  'text-white': day.isCurrentMonth && !isToday(day.date),
                }"
              >
                {{ day.date.getDate() }}
              </div>

              <!-- Events for this day -->
              <div class="space-y-1 overflow-hidden">
                <div
                  v-for="event in getEventsForDate(day.date).slice(0, 3)"
                  :key="event.id"
                  class="text-xs px-2 py-1 rounded truncate text-white cursor-pointer hover:opacity-80 transition-opacity"
                  :class="getEventColor(event)"
                  :style="event.color ? { backgroundColor: event.color } : {}"
                  @click.stop="openEditModal(event)"
                >
                  {{ event.title }}
                </div>
                <div
                  v-if="getEventsForDate(day.date).length > 3"
                  class="text-xs text-blue-400 pl-1 cursor-pointer hover:underline font-medium"
                  @click.stop="openDayDetail(day.date)"
                >
                  +{{ getEventsForDate(day.date).length - 3 }} autres
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Legend -->
    <div class="dashboard-card">
      <div class="py-4">
        <div class="flex items-center gap-6 flex-wrap">
          <span class="font-medium text-white">Types :</span>
          <div
            v-for="type in eventTypes"
            :key="type.value"
            class="flex items-center gap-2"
          >
            <div :class="[type.color, 'w-3 h-3 rounded']"></div>
            <span class="text-sm text-gray-400">{{ type.label }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Day Detail Modal -->
    <dialog class="modal" :class="{ 'modal-open': showDayDetail }">
      <div
        class="modal-box max-w-lg bg-[#0a1628] border border-white/10 shadow-2xl rounded-xl"
      >
        <h3 class="font-bold text-lg mb-4 capitalize text-white">
          {{ dayDetailDate ? formatDayDetailDate(dayDetailDate) : "" }}
        </h3>
        <div class="space-y-2">
          <div
            v-for="event in dayDetailEvents"
            :key="event.id"
            class="flex items-center gap-3 p-3 rounded-lg hover:bg-white/10 cursor-pointer transition-colors border border-white/5"
            @click="openEditModal(event)"
          >
            <div
              class="w-3 h-3 rounded-full shrink-0"
              :class="getEventColor(event)"
            ></div>
            <div class="flex-1 min-w-0">
              <div class="font-medium text-sm truncate text-white">
                {{ event.title }}
              </div>
              <div class="text-xs text-gray-500">
                <template v-if="event.isAllDay">Journée entière</template>
                <template v-else
                  >{{ formatTime(event.startDate) }} -
                  {{ formatTime(event.endDate) }}</template
                >
              </div>
            </div>
          </div>
          <div
            v-if="dayDetailEvents.length === 0"
            class="text-center text-gray-500 py-4"
          >
            Aucun événement
          </div>
        </div>
        <div class="modal-action gap-3">
          <button
            class="btn btn-sm btn-primary"
            @click="
              showDayDetail = false;
              openCreateModal(dayDetailDate!);
            "
          >
            + Nouvel événement
          </button>
          <button
            class="btn btn-sm border border-white/10 text-[#e0e0e0] hover:bg-white/10"
            @click="showDayDetail = false"
          >
            Fermer
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button @click="showDayDetail = false">close</button>
      </form>
    </dialog>

    <!-- Personal Calendar Event Modal (no roomId) -->
    <CalendarEventModal
      :open="showEventModal"
      :room-id="0"
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
      <div
        class="modal-box max-w-4xl bg-[#0a1628] border border-white/10 shadow-2xl rounded-xl"
      >
        <h3 class="font-bold text-lg mb-4 text-white">
          📊 Charge de travail détaillée
        </h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <!-- Daily Workload -->
          <div>
            <h4 class="font-semibold mb-2 text-white">Aujourd'hui</h4>
            <WorkloadIndicator
              v-if="dailyWorkload"
              :workload="dailyWorkload"
              :loading="dailyLoading"
              :show-details="true"
            />
          </div>

          <!-- Weekly Workload -->
          <div>
            <h4 class="font-semibold mb-2 text-white">Cette semaine</h4>
            <WorkloadIndicator
              v-if="weeklyWorkload"
              :workload="weeklyWorkload"
              :loading="weeklyLoading"
              :show-details="true"
            />
          </div>
        </div>

        <div class="modal-action gap-3">
          <button
            class="btn btn-sm border border-white/10 text-[#e0e0e0] hover:bg-white/10"
            @click="showWorkloadDetail = false"
          >
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
.dashboard-card {
  background-color: #0a1628;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.5rem;
  color: #e0e0e0;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dashboard-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  transform: translateY(-2px);
}

.modal-box {
  background-color: #0a1628;
  color: #e0e0e0;
  border-radius: 12px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
}

.modal-box h3 {
  color: #ffffff;
}

.modal-box h4 {
  color: #ffffff;
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.6);
}
</style>

<template>
  <div class="dash-page">
    <!-- Header -->
    <div class="dash-header reveal reveal-1">
      <div class="dash-header-inner">
        <div>
          <h1 class="dash-title">
            <svg
              class="dash-title-icon"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 13a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"
              />
            </svg>
            Dashboard
          </h1>
          <p class="dash-subtitle">
            <template
              v-if="selectedUserId && selectedUserId !== authStore.user?.id"
            >
              Vue d'ensemble de {{ selectedUserName }}
            </template>
            <template v-else>Vue d'ensemble de votre activité</template>
          </p>
        </div>

        <!-- Sélecteur utilisateur global (admin/owner uniquement) -->
        <div
          v-if="isAdminOrOwner && teamUsers.length > 0"
          class="user-select-wrap"
        >
          <div class="user-select-control">
            <select
              v-model="selectedUserId"
              class="user-select"
              @change="handleUserChange"
            >
              <option :value="authStore.user?.id">
                Moi ({{ authStore.user?.displayName || authStore.user?.email }})
              </option>
              <option disabled>──────────</option>
              <option v-for="user in teamUsers" :key="user.id" :value="user.id">
                {{ user.name || user.email }}
              </option>
            </select>
            <svg
              class="user-select-chevron"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 9l-7 7-7-7"
              />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="loading-state reveal reveal-2">
      <div class="spinner"></div>
      <p class="loading-text">Chargement du tableau de bord...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="error-state reveal reveal-2">
      <svg
        class="w-5 h-5"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"
        />
      </svg>
      <span>{{ error }}</span>
    </div>

    <!-- Dashboard Grid -->
    <div v-else class="dashboard-grid reveal reveal-2">
      <!-- Colonne gauche (2 colonnes sur large) -->
      <div class="dashboard-col dashboard-col-wide">
        <!-- Charge de travail -->
        <WorkloadOverview
          class="dashboard-card"
          :workload="dashboardData?.workload"
        />

        <!-- Tâches -->
        <TasksOverview
          class="dashboard-card"
          :tasks="dashboardData?.tasks"
          :available-rooms="dashboardData?.recentRooms"
        />

        <!-- Rooms récentes -->
        <RecentRooms
          class="dashboard-card"
          :recent-rooms="dashboardData?.recentRooms || []"
          :error="
            error ||
            (!authStore.isAuthenticated
              ? 'Veuillez vous connecter pour voir vos rooms'
              : undefined)
          "
        />

        <!-- Statistiques -->
        <Statistics
          class="dashboard-card"
          :statistics="dashboardData?.statistics"
        />
      </div>

      <!-- Colonne droite (1 colonne sur large) -->
      <div class="dashboard-col">
        <!-- Actions rapides -->
        <QuickActions
          class="dashboard-card"
          :user-role="authStore.user?.role"
          @create-room="handleCreateRoom"
          @create-event="handleCreateEvent"
          @create-task="handleCreateTask"
          @invite-members="handleInviteMembers"
        />

        <!-- Événements -->
        <UpcomingEvents
          class="dashboard-card"
          :upcoming-events="dashboardData?.upcomingEvents"
          :today-events="dashboardData?.todayEvents"
          @event-click="handleEventClick"
        />

        <!-- Disponibilité équipe -->
        <TeamAvailability
          class="dashboard-card"
          :team-availability="dashboardData?.teamAvailability"
        />

        <!-- Notifications -->
        <NotificationCenter
          class="dashboard-card"
          :notifications="dashboardData?.notifications"
        />
      </div>
    </div>

    <!-- Modals -->
    <SelectRoomForTaskModal
      :open="showSelectRoomModal"
      @close="showSelectRoomModal = false"
      @select-room="handleRoomSelected"
    />
    <CreateTaskModal
      :open="showCreateTaskModal"
      :room-id="selectedRoomId || 0"
      @close="
        showCreateTaskModal = false;
        selectedRoomId = null;
      "
    />
    <EventDetailModal
      :open="showEventDetailModal"
      :event="selectedEvent"
      @close="
        showEventDetailModal = false;
        selectedEvent = null;
      "
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { useDashboard } from "@/composables/useDashboard";
import { isAtLeast } from "@/utils/permissions";
import WorkloadOverview from "@/components/dashboard/WorkloadOverview.vue";
import UpcomingEvents from "@/components/dashboard/UpcomingEvents.vue";
import TasksOverview from "@/components/dashboard/TasksOverview.vue";
import RecentRooms from "@/components/dashboard/RecentRooms.vue";
import TeamAvailability from "@/components/dashboard/TeamAvailability.vue";
import Statistics from "@/components/dashboard/Statistics.vue";
import QuickActions from "@/components/dashboard/QuickActions.vue";
import NotificationCenter from "@/components/dashboard/NotificationCenter.vue";
import CreateTaskModal from "@/components/tasks/CreateTaskModal.vue";
import SelectRoomForTaskModal from "@/components/dashboard/SelectRoomForTaskModal.vue";
import EventDetailModal from "@/components/dashboard/EventDetailModal.vue";

const router = useRouter();
const route = useRoute();
const entrepriseSlug = computed(() => route.params.entrepriseSlug as string);
const authStore = useAuthStore();
const { dashboardData, loading, error, fetchDashboardData } = useDashboard();

// User selection
const selectedUserId = ref<number | null>(authStore.user?.id || null);
const isAdminOrOwner = computed(() =>
  isAtLeast(authStore.user?.role || "user", "owner"),
);
const teamUsers = computed(() => dashboardData.value?.teamAvailability || []);
const selectedUserName = computed(() => {
  if (!selectedUserId.value) return "";
  const user = teamUsers.value.find(
    (u: { id: number; name?: string; email?: string }) =>
      u.id === selectedUserId.value,
  );
  return user?.name || user?.email || "";
});

// Modal states
const showSelectRoomModal = ref(false);
const showCreateTaskModal = ref(false);
const selectedRoomId = ref<number | null>(null);
const showEventDetailModal = ref(false);
interface DashboardEvent {
  id: number;
  title: string;
  startDate: string;
  endDate: string;
  eventType: string;
  description?: string;
  room: { id: number; name: string } | null;
}

const selectedEvent = ref<DashboardEvent | null>(null);

onMounted(() => {
  if (!authStore.isAuthenticated) {
    // Données fictives pour le développement
    dashboardData.value = {
      workload: { daily: [], overloadDays: 0, hasAlert: false },
      upcomingEvents: [],
      todayEvents: [],
      tasks: { today: [], overdue: [], roomProgress: [] },
      recentRooms: [
        {
          id: 1,
          name: "Test Room 1",
          visibility: "enterprise",
          moduleCount: 2,
          layoutType: "tabs",
          creator: { displayName: "Test User" },
          createdAt: new Date().toISOString(),
        },
        {
          id: 2,
          name: "Test Room 2",
          visibility: "private",
          moduleCount: 1,
          layoutType: "grid-2x2",
          creator: { displayName: "Test User" },
          createdAt: new Date().toISOString(),
        },
      ],
      teamAvailability: [],
      statistics: {
        tasksCompleted: 0,
        tasksCreated: 0,
        productivity: 0,
        meetingsCount: 0,
        meetingsDuration: 0,
        meetingsDurationFormatted: "0h",
      },
      notifications: { upcomingDeadlines: [] },
    };
    loading.value = false;
  } else {
    fetchDashboardData();
  }
});

const handleCreateRoom = () => {
  router.push({
    name: "rooms",
    params: { entrepriseSlug: entrepriseSlug.value },
    query: { create: "true" },
  });
};

const handleCreateEvent = () => {
  router.push({
    name: "calendar",
    params: { entrepriseSlug: entrepriseSlug.value },
    query: { create: "true" },
  });
};

const handleCreateTask = () => {
  showSelectRoomModal.value = true;
};

const handleRoomSelected = (room: { id: number }) => {
  selectedRoomId.value = room.id;
  showCreateTaskModal.value = true;
};

const handleInviteMembers = () => {
  router.push({
    name: "admin-users",
    params: { entrepriseSlug: entrepriseSlug.value },
  });
};

const handleEventClick = (event: DashboardEvent) => {
  selectedEvent.value = event;
  showEventDetailModal.value = true;
};

const handleUserChange = async () => {
  await fetchDashboardData(selectedUserId.value);
};
</script>

<style scoped>
.dash-page {
  min-height: 100vh;
}

.dash-header {
  margin-bottom: 1.75rem;
}

.dash-header-inner {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 1rem;
}

.dash-title {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  font-size: 1.62rem;
  font-weight: 700;
  color: #c8daea;
  letter-spacing: -0.02em;
  margin-bottom: 0.25rem;
}

.dash-title-icon {
  width: 24px;
  height: 24px;
  color: #5ba3e8;
  flex-shrink: 0;
}

.dash-subtitle {
  font-size: 0.9rem;
  color: #6f87a1;
}

.user-select-wrap {
  width: 100%;
  max-width: 18rem;
}

.user-select-control {
  position: relative;
}

.user-select {
  appearance: none;
  width: 100%;
  padding: 0.56rem 2rem 0.56rem 0.85rem;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.06) 0%,
    rgba(255, 255, 255, 0.03) 100%
  );
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  color: #bfd4e8;
  font-size: 0.84rem;
  font-weight: 500;
  outline: none;
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.user-select:hover {
  border-color: rgba(255, 255, 255, 0.22);
}

.user-select:focus {
  border-color: rgba(91, 163, 232, 0.45);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.16);
}

.user-select option {
  background: #080f1c;
  color: #bfd4e8;
}

.user-select-chevron {
  position: absolute;
  right: 0.65rem;
  top: 50%;
  width: 14px;
  height: 14px;
  color: #6f88a3;
  transform: translateY(-50%);
  pointer-events: none;
}

.loading-state {
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.7rem;
  height: 18rem;
}

.spinner {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 3px solid rgba(255, 255, 255, 0.06);
  border-top-color: #5ba3e8;
  animation: spin 0.8s linear infinite;
}

.loading-text {
  margin: 0;
  color: #6f87a1;
  font-size: 0.86rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-state {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  padding: 0.875rem 1rem;
  background: rgba(248, 113, 113, 0.08);
  border: 1px solid rgba(248, 113, 113, 0.2);
  border-radius: 10px;
  color: #f87171;
  font-size: 0.875rem;
}

.dashboard-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}

.dashboard-col {
  display: grid;
  gap: 1.5rem;
}

.dashboard-card {
  border-radius: 12px;
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.dashboard-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 24px rgba(0, 0, 0, 0.24);
}

.reveal {
  animation: fade-in-up 0.5s ease both;
}

.reveal-1 {
  animation-delay: 0.04s;
}

.reveal-2 {
  animation-delay: 0.1s;
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@media (min-width: 1024px) {
  .dashboard-grid {
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
  }

  .dashboard-col-wide {
    min-width: 0;
  }
}
</style>

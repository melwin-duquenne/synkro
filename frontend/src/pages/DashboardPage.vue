<template>
  <div class="min-h-screen">
    <!-- Header -->
    <div class="mb-6">
      <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
          <h1
            class="text-3xl font-bold mb-2 flex items-center gap-3 text-white"
          >
            <svg
              class="w-8 h-8"
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
          <p class="text-gray-300">
            <template
              v-if="selectedUserId && selectedUserId !== authStore.user?.id"
            >
              Vue d'ensemble de {{ selectedUserName }}
            </template>
            <template v-else> Vue d'ensemble de votre activité </template>
          </p>
        </div>

        <!-- Sélecteur utilisateur global (admin/owner uniquement) -->
        <div
          v-if="isAdminOrOwner && teamUsers.length > 0"
          class="form-control w-full sm:w-72"
        >
          <select
            v-model="selectedUserId"
            class="select select-bordered bg-[#001a3f] border-gray-500 text-white"
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
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-error">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        class="stroke-current shrink-0 h-6 w-6"
        fill="none"
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
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Colonne gauche (2 colonnes sur large) -->
      <div class="lg:col-span-2 space-y-6">
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
      <div class="space-y-6">
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
import { onMounted, ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useDashboard } from '@/composables/useDashboard'
import { isAtLeast } from '@/utils/permissions'
import WorkloadOverview from '@/components/dashboard/WorkloadOverview.vue'
import UpcomingEvents from '@/components/dashboard/UpcomingEvents.vue'
import TasksOverview from '@/components/dashboard/TasksOverview.vue'
import RecentRooms from '@/components/dashboard/RecentRooms.vue'
import TeamAvailability from '@/components/dashboard/TeamAvailability.vue'
import Statistics from '@/components/dashboard/Statistics.vue'
import QuickActions from '@/components/dashboard/QuickActions.vue'
import NotificationCenter from '@/components/dashboard/NotificationCenter.vue'
import CreateTaskModal from '@/components/tasks/CreateTaskModal.vue'
import SelectRoomForTaskModal from '@/components/dashboard/SelectRoomForTaskModal.vue'
import EventDetailModal from '@/components/dashboard/EventDetailModal.vue'

const router = useRouter()
const route = useRoute()
const entrepriseSlug = computed(() => route.params.entrepriseSlug as string)
const authStore = useAuthStore()
const { dashboardData, loading, error, fetchDashboardData } = useDashboard()

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
  router.push({ name: 'rooms', params: { entrepriseSlug: entrepriseSlug.value }, query: { create: 'true' } })
}

const handleCreateEvent = () => {
  router.push({ name: 'calendar', params: { entrepriseSlug: entrepriseSlug.value }, query: { create: 'true' } })
}

const handleCreateTask = () => {
  showSelectRoomModal.value = true;
};

const handleRoomSelected = (room: { id: number }) => {
  selectedRoomId.value = room.id;
  showCreateTaskModal.value = true;
};

const handleInviteMembers = () => {
  router.push({ name: 'admin-users', params: { entrepriseSlug: entrepriseSlug.value } })
}

const handleEventClick = (event: DashboardEvent) => {
  selectedEvent.value = event;
  showEventDetailModal.value = true;
};

const handleUserChange = async () => {
  await fetchDashboardData(selectedUserId.value);
};
</script>

<style scoped>
.dashboard-card {
  border: none;
  background-color: #0a1628;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  transition: all 0.3s ease;
}
</style>

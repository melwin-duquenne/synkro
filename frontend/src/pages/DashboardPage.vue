<template>
  <div class="min-h-screen bg-base-200 p-4 sm:p-6">
    <!-- Header -->
    <div class="mb-6">
      <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 13a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z" />
        </svg>
        Dashboard
      </h1>
      <p class="text-base-content/70">Vue d'ensemble de votre activité</p>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center h-64">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="alert alert-error">
      <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <span>{{ error }}</span>
    </div>

    <!-- Dashboard Grid -->
    <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Colonne gauche (2 colonnes sur large) -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Charge de travail -->
        <WorkloadOverview :workload="dashboardData?.workload" />
        
        <!-- Tâches -->
        <TasksOverview :tasks="dashboardData?.tasks" />
        
        <!-- Rooms récentes -->
        <RecentRooms :recent-rooms="dashboardData?.recentRooms" />
        
        <!-- Statistiques -->
        <Statistics :statistics="dashboardData?.statistics" />
      </div>

      <!-- Colonne droite (1 colonne sur large) -->
      <div class="space-y-6">
        <!-- Actions rapides -->
        <QuickActions 
          @create-room="handleCreateRoom"
          @create-event="handleCreateEvent"
          @create-task="handleCreateTask"
          @invite-members="handleInviteMembers"
        />
        
        <!-- Événements -->
        <UpcomingEvents 
          :upcoming-events="dashboardData?.upcomingEvents"
          :today-events="dashboardData?.todayEvents"
        />
        
        <!-- Disponibilité équipe -->
        <TeamAvailability :team-availability="dashboardData?.teamAvailability" />
        
        <!-- Notifications -->
        <NotificationCenter :notifications="dashboardData?.notifications" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useDashboard } from '@/composables/useDashboard'
import WorkloadOverview from '@/components/dashboard/WorkloadOverview.vue'
import UpcomingEvents from '@/components/dashboard/UpcomingEvents.vue'
import TasksOverview from '@/components/dashboard/TasksOverview.vue'
import RecentRooms from '@/components/dashboard/RecentRooms.vue'
import TeamAvailability from '@/components/dashboard/TeamAvailability.vue'
import Statistics from '@/components/dashboard/Statistics.vue'
import QuickActions from '@/components/dashboard/QuickActions.vue'
import NotificationCenter from '@/components/dashboard/NotificationCenter.vue'

const router = useRouter()
const { dashboardData, loading, error, fetchDashboardData } = useDashboard()

onMounted(() => {
  fetchDashboardData()
})

const handleCreateRoom = () => {
  router.push('/rooms/create')
}

const handleCreateEvent = () => {
  // TODO: Ouvrir modal de création d'événement
  console.log('Create event')
}

const handleCreateTask = () => {
  // TODO: Ouvrir modal de création de tâche
  console.log('Create task')
}

const handleInviteMembers = () => {
  // TODO: Ouvrir modal d'invitation
  console.log('Invite members')
}
</script>

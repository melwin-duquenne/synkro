<script setup lang="ts">
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-3xl font-bold">Dashboard</h1>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Welcome Card -->
      <div class="card bg-base-100 shadow-xl col-span-full">
        <div class="card-body">
          <h2 class="card-title">Bienvenue, {{ authStore.user?.displayName }} !</h2>
          <p>Accédez à vos rooms collaboratives et commencez à travailler en équipe.</p>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">Rooms</h2>
          <p class="text-base-content/70">Gérez vos espaces de travail collaboratifs</p>
          <div class="card-actions justify-end mt-4">
            <router-link to="/rooms" class="btn btn-primary">Voir les rooms</router-link>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">Calendrier</h2>
          <p class="text-base-content/70">Consultez vos événements et réunions</p>
          <div class="card-actions justify-end mt-4">
            <router-link to="/calendar" class="btn btn-primary">Ouvrir</router-link>
          </div>
        </div>
      </div>

      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h2 class="card-title">Nouvelle Room</h2>
          <p class="text-base-content/70">Créez un nouvel espace collaboratif</p>
          <div class="card-actions justify-end mt-4">
            <button class="btn btn-outline btn-primary">Créer</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Activity -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title">Activité récente</h2>
        <div class="py-8 text-center text-base-content/50">
          <p>Aucune activité récente</p>
        </div>
      </div>
    </div>
  </div>
</template>

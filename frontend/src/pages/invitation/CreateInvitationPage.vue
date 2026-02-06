<template>
  <div class="min-h-screen bg-base-200 p-4 sm:p-6">
    <div class="max-w-2xl mx-auto">
      <div class="card bg-base-100 shadow-xl">
        <div class="card-body">
          <h1 class="card-title text-2xl mb-6">Inviter des membres</h1>

          <div class="alert alert-info mb-6">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 h-6 w-6">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>Fonctionnalité en développement - Cette page sera bientôt disponible</span>
          </div>

          <div class="card-actions justify-end">
            <router-link to="/dashboard" class="btn btn-ghost">Retour au dashboard</router-link>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
// Page temporaire pour les invitations
</script>
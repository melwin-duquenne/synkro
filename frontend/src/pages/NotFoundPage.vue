<script setup lang="ts"></script>

<template>
  <div class="min-h-[60vh] flex items-center justify-center">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-primary">404</h1>
      <h2 class="text-2xl font-semibold mt-4 mb-2">Page introuvable</h2>
      <p class="text-base-content/70 mb-6">
        La page que vous recherchez n'existe pas.
      </p>
      <router-link to="/" class="btn btn-primary bg-white text-black border-0"
        >Retour à l'accueil</router-link
      >
    </div>
  </div>
</template>

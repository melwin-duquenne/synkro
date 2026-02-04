<script setup lang="ts">
import LandingHeader from '@/components/landing/LandingHeader.vue'
import LandingFooter from '@/components/landing/LandingFooter.vue'
</script>

<template>
  <div class="min-h-screen bg-base-100">
    <LandingHeader />
    <main>
      <slot />
    </main>
    <LandingFooter />
  </div>
</template>

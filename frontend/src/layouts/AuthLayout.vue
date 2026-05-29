<script setup lang="ts"></script>

<template>
  <div
    class="min-h-screen bg-linear-to-br from-[#0a1628] to-[#0f1f35] flex items-center justify-center p-4 relative overflow-hidden"
  >
    <!-- Background Decorative Elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <!-- Top-right accent -->
      <div
        class="absolute -top-40 -right-40 w-80 h-80 bg-[#408ed6]/20 rounded-full blur-3xl"
      ></div>
      <!-- Bottom-left accent -->
      <div
        class="absolute -bottom-40 -left-40 w-80 h-80 bg-[#2a5aa8]/20 rounded-full blur-3xl"
      ></div>
      <!-- Center accent -->
      <div
        class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-[#1f4b7c]/15 rounded-full blur-3xl"
      ></div>
      <!-- Top accent -->
      <div
        class="absolute -top-32 left-1/4 w-64 h-64 bg-[#408ed6]/15 rounded-full blur-2xl"
      ></div>
      <!-- Bottom-right accent -->
      <div
        class="absolute -bottom-32 right-1/4 w-72 h-72 bg-[#2a5aa8]/15 rounded-full blur-2xl"
      ></div>
    </div>

    <!-- Content -->
    <div class="w-full max-w-md relative z-10">
      <!-- Branding -->
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-[#e7f0fa] tracking-tight">Synkro</h1>
        <p class="text-[#8ea4be] mt-2 text-sm">
          Plateforme collaborative temps réel
        </p>
      </div>
      <slot />
    </div>
  </div>
</template>

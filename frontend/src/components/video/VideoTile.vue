<script setup lang="ts">
import { ref, watch, onMounted } from "vue";

const props = defineProps<{
  stream: MediaStream | null;
  displayName: string;
  isLocal: boolean;
  isAudioEnabled: boolean;
  isVideoEnabled: boolean;
  isScreenSharing: boolean;
}>();

const videoRef = ref<HTMLVideoElement | null>(null);

watch(
  () => props.stream,
  (stream) => {
    if (videoRef.value) {
      videoRef.value.srcObject = stream;
    }
  },
  { immediate: true },
);

onMounted(() => {
  if (videoRef.value && props.stream) {
    videoRef.value.srcObject = props.stream;
  }
});
</script>

<template>
  <div
    class="video-tile relative bg-[#0a1628] rounded-2xl overflow-hidden aspect-video border border-white/10 shadow-lg"
  >
    <!-- Video element -->
    <video
      ref="videoRef"
      autoplay
      :muted="isLocal"
      playsinline
      class="w-full h-full object-cover"
      :class="{ mirror: isLocal && !isScreenSharing }"
    />

    <!-- No video placeholder -->
    <div
      v-if="!isVideoEnabled || !stream"
      class="absolute inset-0 flex items-center justify-center bg-[#1a3a52]/60"
    >
      <div
        class="flex items-center justify-center w-20 h-20 rounded-full bg-[#408ed6]/30 text-[#408ed6] text-2xl font-bold"
      >
        <span>{{ displayName.charAt(0).toUpperCase() }}</span>
      </div>
    </div>

    <!-- Overlay info -->
    <div
      class="absolute bottom-0 left-0 right-0 bg-linear-to-t from-[#0a1628]/90 via-[#0a1628]/30 to-transparent p-3"
    >
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="text-[#e7f0fa] text-sm font-medium truncate max-w-37.5">
            {{ displayName }}
            <span v-if="isLocal" class="text-[#8ea4be] text-xs">(Vous)</span>
          </span>
        </div>

        <div class="flex items-center gap-2">
          <!-- Screen sharing indicator -->
          <div
            v-if="isScreenSharing"
            class="bg-[#6fdd9f]/20 text-[#6fdd9f] rounded-full p-1.5 shrink-0"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
              />
            </svg>
          </div>

          <!-- Audio muted indicator -->
          <div
            v-if="!isAudioEnabled"
            class="bg-red-500/20 text-red-400 rounded-full p-1.5 shrink-0"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"
              />
            </svg>
          </div>

          <!-- Video off indicator -->
          <div
            v-if="!isVideoEnabled"
            class="bg-red-500/20 text-red-400 rounded-full p-1.5 shrink-0"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 3l18 18"
              />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mirror {
  transform: scaleX(-1);
}

.video-tile video {
  background-color: #1f2937;
}
</style>

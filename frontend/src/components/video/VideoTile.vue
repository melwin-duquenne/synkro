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
  <div class="video-tile">
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
      class="video-tile__empty absolute inset-0 flex items-center justify-center"
    >
      <div class="video-tile__avatar-pill">
        <span>{{ displayName.charAt(0).toUpperCase() }}</span>
      </div>
    </div>

    <!-- Overlay info -->
    <div class="video-tile__overlay absolute bottom-0 left-0 right-0 p-3">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <span class="video-tile__name truncate max-w-37.5">
            {{ displayName }}
            <span v-if="isLocal" class="video-tile__you">(Vous)</span>
          </span>
        </div>

        <div class="flex items-center gap-2">
          <!-- Screen sharing indicator -->
          <div
            v-if="isScreenSharing"
            class="video-status-badge video-status-badge--screen"
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
            class="video-status-badge video-status-badge--muted"
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
            class="video-status-badge video-status-badge--muted"
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

.video-tile {
  position: relative;
  aspect-ratio: 16 / 9;
  overflow: hidden;
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.13),
      transparent 38%
    ),
    rgba(10, 22, 40, 0.9);
  box-shadow:
    0 14px 26px rgba(0, 0, 0, 0.26),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.video-tile video {
  background-color: #1f2937;
}

.video-tile__empty {
  background: linear-gradient(
    180deg,
    rgba(8, 18, 31, 0.72),
    rgba(8, 18, 31, 0.9)
  );
}

.video-tile__avatar-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 4.6rem;
  height: 4.6rem;
  border-radius: 999px;
  border: 1px solid rgba(91, 163, 232, 0.34);
  background: rgba(91, 163, 232, 0.2);
  color: #b4dcff;
  font-size: 1.35rem;
  font-weight: 700;
}

.video-tile__overlay {
  background: linear-gradient(
    180deg,
    rgba(10, 22, 40, 0),
    rgba(10, 22, 40, 0.78) 44%,
    rgba(10, 22, 40, 0.94)
  );
}

.video-tile__name {
  color: #e7f0fa;
  font-size: 0.88rem;
  font-weight: 600;
}

.video-tile__you {
  color: #8ea4be;
  font-size: 0.75rem;
}

.video-status-badge {
  border-radius: 999px;
  padding: 0.35rem;
  flex-shrink: 0;
  border: 1px solid transparent;
}

.video-status-badge--screen {
  color: #8ff2b7;
  background: rgba(111, 221, 159, 0.18);
  border-color: rgba(111, 221, 159, 0.32);
}

.video-status-badge--muted {
  color: #f9a8a8;
  background: rgba(248, 113, 113, 0.18);
  border-color: rgba(248, 113, 113, 0.32);
}
</style>

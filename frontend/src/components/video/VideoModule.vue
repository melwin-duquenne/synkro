<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { useWebRTC } from "@/composables/useWebRTC";
import { useAuthStore } from "@/stores/auth";
import VideoTile from "./VideoTile.vue";

const props = defineProps<{
  roomId: number;
}>();

const authStore = useAuthStore();

const {
  callState,
  localMedia,
  participants,
  error,
  joinCall,
  leaveCall,
  toggleAudio,
  toggleVideo,
  startScreenShare,
  stopScreenShare,
} = useWebRTC(props.roomId);

// Local video element ref for preview
const localVideoRef = ref<HTMLVideoElement | null>(null);

// Computed for grid layout
const participantCount = computed(() => participants.value.size + 1); // +1 for local

const gridClass = computed(() => {
  const count = participantCount.value;
  if (count === 1) return "grid-cols-1 max-w-2xl mx-auto";
  if (count === 2) return "grid-cols-2";
  if (count <= 4) return "grid-cols-2";
  if (count <= 6) return "grid-cols-3";
  return "grid-cols-4"; // 7-8 participants
});

// Watch for local stream changes (for preview)
watch(
  () => localMedia.value.videoStream,
  (stream) => {
    if (localVideoRef.value && stream) {
      localVideoRef.value.srcObject = stream;
    }
  },
  { immediate: true },
);

// Settings modal
const showSettings = ref(false);
const selectedAudioInput = ref<string>("");
const selectedVideoInput = ref<string>("");
const audioInputDevices = ref<MediaDeviceInfo[]>([]);
const videoInputDevices = ref<MediaDeviceInfo[]>([]);

async function loadDevices() {
  try {
    const devices = await navigator.mediaDevices.enumerateDevices();
    audioInputDevices.value = devices.filter((d) => d.kind === "audioinput");
    videoInputDevices.value = devices.filter((d) => d.kind === "videoinput");
  } catch (e) {
    console.error("Failed to enumerate devices:", e);
  }
}

// Preview media before joining
async function startPreview() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      audio: true,
      video: true,
    });
    if (localVideoRef.value) {
      localVideoRef.value.srcObject = stream;
    }
    // Store for later use
    localMedia.value.videoStream = stream;
    localMedia.value.audioStream = stream;
  } catch (e) {
    console.error("Failed to start preview:", e);
  }
}

function stopPreview() {
  if (localMedia.value.videoStream) {
    localMedia.value.videoStream.getTracks().forEach((track) => track.stop());
    localMedia.value.videoStream = null;
    localMedia.value.audioStream = null;
  }
}

onMounted(() => {
  loadDevices();
  startPreview();
});

// Cleanup preview if leaving without joining
function handleLeave() {
  stopPreview();
  leaveCall();
}
</script>

<template>
  <div class="video-module h-full flex flex-col">
    <!-- Pre-call lobby -->
    <div v-if="callState === 'idle'" class="video-lobby">
      <div class="text-center">
        <h2 class="video-lobby__title">Rejoindre la visioconference</h2>
        <p class="video-lobby__subtitle">
          Verifiez votre camera et microphone avant de rejoindre
        </p>
      </div>

      <!-- Preview -->
      <div
        class="video-preview-card relative w-80 aspect-video overflow-hidden"
      >
        <video
          ref="localVideoRef"
          autoplay
          muted
          playsinline
          class="w-full h-full object-cover mirror"
        />
        <div
          v-if="!localMedia.videoEnabled"
          class="video-preview-card__empty absolute inset-0 flex items-center justify-center"
        >
          <div class="video-avatar-pill">
            {{ authStore.user?.displayName?.charAt(0) }}
          </div>
        </div>
      </div>

      <!-- Controls preview -->
      <div class="video-control-row">
        <button
          class="video-circle-btn"
          :class="
            localMedia.audioEnabled
              ? 'video-circle-btn--on'
              : 'video-circle-btn--off'
          "
          @click="toggleAudio"
          title="Microphone"
        >
          <svg
            v-if="localMedia.audioEnabled"
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
            />
          </svg>
          <svg
            v-else
            class="w-6 h-6"
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
        </button>

        <button
          class="video-circle-btn"
          :class="
            localMedia.videoEnabled
              ? 'video-circle-btn--on'
              : 'video-circle-btn--off'
          "
          @click="toggleVideo"
          title="Camera"
        >
          <svg
            v-if="localMedia.videoEnabled"
            class="w-6 h-6"
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
          </svg>
          <svg
            v-else
            class="w-6 h-6"
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
              d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"
            />
          </svg>
        </button>

        <button
          class="video-circle-btn video-circle-btn--on"
          @click="showSettings = true"
          title="Parametres"
        >
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
          </svg>
        </button>
      </div>

      <button class="video-primary-btn" @click="joinCall">
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
            d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"
          />
        </svg>
        Rejoindre l'appel
      </button>

      <p v-if="error" class="text-red-400 text-sm">{{ error }}</p>
    </div>

    <!-- In-call view -->
    <template v-else-if="callState === 'connected'">
      <!-- Video grid -->
      <div class="video-stage">
        <div
          class="video-grid grid gap-4 h-full auto-rows-fr"
          :class="gridClass"
        >
          <!-- Local video -->
          <VideoTile
            :stream="
              localMedia.screenSharing
                ? localMedia.screenStream
                : localMedia.videoStream
            "
            :display-name="authStore.user?.displayName || 'Vous'"
            :is-local="true"
            :is-audio-enabled="localMedia.audioEnabled"
            :is-video-enabled="localMedia.videoEnabled"
            :is-screen-sharing="localMedia.screenSharing"
          />

          <!-- Remote participants -->
          <VideoTile
            v-for="[id, participant] in participants"
            :key="id"
            :stream="participant.stream"
            :display-name="participant.displayName"
            :is-local="false"
            :is-audio-enabled="participant.isAudioEnabled"
            :is-video-enabled="participant.isVideoEnabled"
            :is-screen-sharing="participant.isScreenSharing"
          />
        </div>
      </div>

      <!-- Call controls bar -->
      <div class="video-controls-bar">
        <div class="video-controls-bar__row">
          <!-- Microphone -->
          <button
            class="video-circle-btn video-circle-btn--sm"
            :class="
              localMedia.audioEnabled
                ? 'video-circle-btn--on'
                : 'video-circle-btn--off'
            "
            @click="toggleAudio"
            title="Microphone"
          >
            <svg
              v-if="localMedia.audioEnabled"
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"
              />
            </svg>
            <svg
              v-else
              class="w-5 h-5"
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
          </button>

          <!-- Camera -->
          <button
            class="video-circle-btn video-circle-btn--sm"
            :class="
              localMedia.videoEnabled
                ? 'video-circle-btn--on'
                : 'video-circle-btn--off'
            "
            @click="toggleVideo"
            title="Camera"
          >
            <svg
              v-if="localMedia.videoEnabled"
              class="w-6 h-6"
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
            </svg>
            <svg
              v-else
              class="w-6 h-6"
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
          </button>

          <!-- Screen share -->
          <button
            class="video-circle-btn video-circle-btn--sm"
            :class="
              localMedia.screenSharing
                ? 'video-circle-btn--screen'
                : 'video-circle-btn--on'
            "
            @click="
              localMedia.screenSharing ? stopScreenShare() : startScreenShare()
            "
            title="Partage d'ecran"
          >
            <svg
              class="w-6 h-6"
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
          </button>

          <!-- Settings -->
          <button
            class="video-circle-btn video-circle-btn--sm video-circle-btn--on"
            @click="showSettings = true"
            title="Parametres"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
              />
            </svg>
          </button>

          <!-- Leave call -->
          <button
            class="video-leave-btn"
            @click="handleLeave"
            title="Quitter l'appel"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M16 8l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2M5 3a2 2 0 00-2 2v1c0 8.284 6.716 15 15 15h1a2 2 0 002-2v-3.28a1 1 0 00-.684-.948l-4.493-1.498a1 1 0 00-1.21.502l-1.13 2.257a11.042 11.042 0 01-5.516-5.517l2.257-1.128a1 1 0 00.502-1.21L9.228 3.683A1 1 0 008.279 3H5z"
              />
            </svg>
          </button>
        </div>

        <!-- Participant count -->
        <div class="video-participant-count">
          {{ participantCount }} participant{{
            participantCount > 1 ? "s" : ""
          }}
        </div>
      </div>
    </template>

    <!-- Joining state -->
    <div v-else-if="callState === 'joining'" class="video-join-state">
      <div class="text-center">
        <span class="loading loading-spinner loading-lg"></span>
        <p class="mt-4 text-[#e0e0e0]">Connexion en cours...</p>
      </div>
    </div>

    <!-- Error state -->
    <div v-else-if="callState === 'error'" class="video-error-state">
      <div class="text-center text-red-400">
        <svg
          class="w-16 h-16 mx-auto mb-4"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <p class="text-lg font-medium">Erreur de connexion</p>
        <p class="text-sm">{{ error || "Une erreur est survenue" }}</p>
      </div>
      <button class="video-retry-btn" @click="joinCall">Reessayer</button>
    </div>

    <!-- Settings modal -->
    <dialog class="modal" :class="{ 'modal-open': showSettings }">
      <div class="modal-box video-settings-modal">
        <h3 class="video-settings-modal__title">Parametres audio/video</h3>

        <div class="video-settings-modal__field">
          <label class="video-settings-modal__label"> Microphone </label>
          <select
            v-model="selectedAudioInput"
            class="video-settings-modal__select"
          >
            <option
              v-for="device in audioInputDevices"
              :key="device.deviceId"
              :value="device.deviceId"
            >
              {{ device.label || `Microphone ${device.deviceId.slice(0, 5)}` }}
            </option>
          </select>
        </div>

        <div class="video-settings-modal__field">
          <label class="video-settings-modal__label"> Camera </label>
          <select
            v-model="selectedVideoInput"
            class="video-settings-modal__select"
          >
            <option
              v-for="device in videoInputDevices"
              :key="device.deviceId"
              :value="device.deviceId"
            >
              {{ device.label || `Camera ${device.deviceId.slice(0, 5)}` }}
            </option>
          </select>
        </div>

        <div class="video-settings-modal__actions">
          <button
            class="video-settings-modal__close"
            @click="showSettings = false"
          >
            Fermer
          </button>
        </div>
      </div>
      <form
        method="dialog"
        class="modal-backdrop"
        @click="showSettings = false"
      >
        <button>close</button>
      </form>
    </dialog>
  </div>
</template>

<style scoped>
.video-module {
  overflow: hidden;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.14),
      transparent 34%
    ),
    linear-gradient(180deg, rgba(8, 18, 31, 0.98), rgba(5, 10, 20, 0.98));
  box-shadow:
    0 22px 44px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.video-lobby {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 1.75rem;
  padding: 1.6rem;
}

.video-lobby__title {
  margin-bottom: 0.5rem;
  font-size: clamp(1.6rem, 1.8vw, 2rem);
  font-weight: 700;
  color: #eaf3fd;
}

.video-lobby__subtitle {
  color: #8ea4be;
  font-size: 0.95rem;
}

.video-preview-card {
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(26, 58, 82, 0.52);
  box-shadow:
    0 24px 40px rgba(0, 0, 0, 0.3),
    inset 0 0 0 1px rgba(255, 255, 255, 0.04);
}

.video-preview-card__empty {
  background: linear-gradient(
    180deg,
    rgba(8, 18, 31, 0.72),
    rgba(8, 18, 31, 0.9)
  );
}

.video-avatar-pill {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 5rem;
  height: 5rem;
  border-radius: 999px;
  border: 1px solid rgba(91, 163, 232, 0.34);
  background: rgba(91, 163, 232, 0.2);
  color: #b4dcff;
  font-size: 1.45rem;
  font-weight: 700;
}

.video-control-row {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.video-circle-btn {
  width: 3.4rem;
  height: 3.4rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.03);
  transition:
    transform 0.2s ease,
    border-color 0.2s ease,
    box-shadow 0.2s ease,
    background-color 0.2s ease,
    color 0.2s ease;
}

.video-circle-btn:hover {
  transform: translateY(-1px);
}

.video-circle-btn--sm {
  width: 3rem;
  height: 3rem;
}

.video-circle-btn--on {
  background: rgba(91, 163, 232, 0.12);
  border-color: rgba(91, 163, 232, 0.34);
  color: #9fd0ff;
}

.video-circle-btn--on:hover {
  background: rgba(91, 163, 232, 0.2);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.video-circle-btn--off {
  background: rgba(248, 113, 113, 0.12);
  border-color: rgba(248, 113, 113, 0.26);
  color: #f9a8a8;
}

.video-circle-btn--off:hover {
  background: rgba(248, 113, 113, 0.18);
}

.video-circle-btn--screen {
  background: rgba(111, 221, 159, 0.24);
  border-color: rgba(111, 221, 159, 0.45);
  color: #d8ffe8;
}

.video-circle-btn--screen:hover {
  background: rgba(111, 221, 159, 0.32);
}

.video-primary-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 0.78rem 1.45rem;
  border-radius: 12px;
  border: 1px solid rgba(91, 163, 232, 0.4);
  background: linear-gradient(135deg, rgba(71, 141, 210, 0.96), #5ba3e8);
  color: #ffffff;
  font-weight: 600;
  box-shadow: 0 14px 28px rgba(8, 19, 32, 0.35);
  transition:
    transform 0.2s ease,
    box-shadow 0.2s ease,
    filter 0.2s ease;
}

.video-primary-btn:hover {
  transform: translateY(-1px);
  filter: brightness(1.05);
  box-shadow: 0 18px 32px rgba(8, 19, 32, 0.4);
}

.video-stage {
  flex: 1;
  padding: 0.95rem;
  overflow: auto;
}

.video-grid {
  min-height: 100%;
}

.video-controls-bar {
  padding: 0.9rem;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.055),
    rgba(255, 255, 255, 0.02)
  );
  backdrop-filter: blur(10px);
}

.video-controls-bar__row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.7rem;
  flex-wrap: wrap;
}

.video-leave-btn {
  width: 3.3rem;
  height: 3.3rem;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid rgba(248, 113, 113, 0.32);
  background: rgba(248, 113, 113, 0.15);
  color: #fca5a5;
  transition:
    transform 0.2s ease,
    background-color 0.2s ease;
}

.video-leave-btn:hover {
  transform: translateY(-1px);
  background: rgba(248, 113, 113, 0.22);
}

.video-participant-count {
  margin-top: 0.55rem;
  text-align: center;
  font-size: 0.85rem;
  color: #8ea4be;
}

.video-join-state,
.video-error-state {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
}

.video-error-state {
  flex-direction: column;
  gap: 1rem;
}

.video-retry-btn {
  padding: 0.64rem 1.05rem;
  border-radius: 10px;
  border: 1px solid rgba(91, 163, 232, 0.34);
  background: rgba(91, 163, 232, 0.18);
  color: #e9f5ff;
  transition: background-color 0.2s ease;
}

.video-retry-btn:hover {
  background: rgba(91, 163, 232, 0.28);
}

.video-settings-modal {
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: linear-gradient(
    180deg,
    rgba(9, 19, 34, 0.96),
    rgba(7, 13, 24, 0.96)
  );
  box-shadow: 0 24px 46px rgba(0, 0, 0, 0.32);
}

.video-settings-modal__title {
  margin-bottom: 0.95rem;
  color: #e7f0fa;
  font-size: 1.05rem;
  font-weight: 700;
}

.video-settings-modal__field {
  margin-bottom: 0.85rem;
}

.video-settings-modal__label {
  display: block;
  margin-bottom: 0.45rem;
  color: #dbe6f2;
  font-size: 0.82rem;
  font-weight: 600;
}

.video-settings-modal__select {
  width: 100%;
  padding: 0.52rem 0.72rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.03);
  color: #e0e0e0;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.video-settings-modal__select:focus {
  outline: none;
  border-color: rgba(91, 163, 232, 0.55);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.2);
}

.video-settings-modal__actions {
  display: flex;
  justify-content: flex-end;
}

.video-settings-modal__close {
  padding: 0.48rem 0.85rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.04);
  color: #dbe6f2;
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease;
}

.video-settings-modal__close:hover {
  border-color: rgba(91, 163, 232, 0.45);
  background: rgba(91, 163, 232, 0.12);
}

.mirror {
  transform: scaleX(-1);
}

@media (max-width: 768px) {
  .video-lobby {
    padding: 1rem 0.8rem;
    gap: 1.1rem;
  }

  .video-preview-card {
    width: min(100%, 19rem);
  }

  .video-circle-btn {
    width: 3rem;
    height: 3rem;
  }

  .video-circle-btn--sm,
  .video-leave-btn {
    width: 2.75rem;
    height: 2.75rem;
  }

  .video-stage {
    padding: 0.65rem;
  }
}
</style>

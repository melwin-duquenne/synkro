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
  <div class="video-module h-full flex flex-col bg-[#0a1628]">
    <!-- Pre-call lobby -->
    <div
      v-if="callState === 'idle'"
      class="flex-1 flex flex-col items-center justify-center gap-6 p-8"
    >
      <div class="text-center">
        <h2 class="text-2xl font-bold mb-2 text-[#e0e0e0]">
          Rejoindre la visioconference
        </h2>
        <p class="text-[#b0b0b0]">
          Verifiez votre camera et microphone avant de rejoindre
        </p>
      </div>

      <!-- Preview -->
      <div
        class="relative w-80 aspect-video bg-[#1a3a52] rounded-xl overflow-hidden shadow-lg border border-gray-600"
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
          class="absolute inset-0 flex items-center justify-center bg-[#1a3a52]"
        >
          <div
            class="flex items-center justify-center w-20 h-20 rounded-full bg-gray-500 text-white text-2xl"
          >
            {{ authStore.user?.displayName?.charAt(0) }}
          </div>
        </div>
      </div>

      <!-- Controls preview -->
      <div class="flex gap-4">
        <button
          class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
          :class="
            localMedia.audioEnabled
              ? 'bg-[#1a3a52] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0]'
              : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
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
          class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
          :class="
            localMedia.videoEnabled
              ? 'bg-[#1a3a52] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0]'
              : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
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
          class="w-14 h-14 rounded-full flex items-center justify-center bg-[#1a3a52] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0] transition-colors"
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

      <button
        class="px-6 py-3 rounded-lg bg-[#4115df] text-white hover:bg-[#6a3fe8] transition-colors flex items-center gap-2"
        @click="joinCall"
      >
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
      <div class="flex-1 p-4 overflow-auto">
        <div class="grid gap-4 h-full auto-rows-fr" :class="gridClass">
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
      <div class="bg-[#1a3a52] p-4 border-t border-gray-600">
        <div class="flex items-center justify-center gap-4">
          <!-- Microphone -->
          <button
            class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
            :class="
              localMedia.audioEnabled
                ? 'bg-[#0a1628] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0]'
                : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
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

          <!-- Camera -->
          <button
            class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
            :class="
              localMedia.videoEnabled
                ? 'bg-[#0a1628] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0]'
                : 'bg-red-500/20 text-red-400 hover:bg-red-500/30'
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
            class="w-14 h-14 rounded-full flex items-center justify-center transition-colors"
            :class="
              localMedia.screenSharing
                ? 'bg-[#6fdd9f] text-black hover:bg-[#5ac88f]'
                : 'bg-[#0a1628] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0]'
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
            class="w-14 h-14 rounded-full flex items-center justify-center bg-[#0a1628] text-[#b0b0b0] hover:bg-[#2a4a62] hover:text-[#e0e0e0] transition-colors"
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
            class="w-14 h-14 rounded-full flex items-center justify-center bg-red-500/20 text-red-400 hover:bg-red-500/30 transition-colors"
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
        <div class="text-center mt-2 text-sm text-[#b0b0b0]">
          {{ participantCount }} participant{{
            participantCount > 1 ? "s" : ""
          }}
        </div>
      </div>
    </template>

    <!-- Joining state -->
    <div
      v-else-if="callState === 'joining'"
      class="flex-1 flex items-center justify-center"
    >
      <div class="text-center">
        <span class="loading loading-spinner loading-lg"></span>
        <p class="mt-4 text-[#e0e0e0]">Connexion en cours...</p>
      </div>
    </div>

    <!-- Error state -->
    <div
      v-else-if="callState === 'error'"
      class="flex-1 flex flex-col items-center justify-center gap-4"
    >
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
      <button
        class="px-6 py-3 rounded-lg bg-[#4115df] text-white hover:bg-[#6a3fe8] transition-colors"
        @click="joinCall"
      >
        Reessayer
      </button>
    </div>

    <!-- Settings modal -->
    <dialog class="modal" :class="{ 'modal-open': showSettings }">
      <div class="modal-box bg-[#0a1628] border border-gray-600">
        <h3 class="font-bold text-lg mb-4 text-[#e0e0e0]">
          Parametres audio/video
        </h3>

        <div class="mb-4">
          <label class="block text-[#e0e0e0] text-sm font-medium mb-2">
            Microphone
          </label>
          <select
            v-model="selectedAudioInput"
            class="w-full px-3 py-2 rounded-lg bg-[#1a3a52] border border-gray-600 text-[#e0e0e0] focus:outline-none focus:border-[#4115df] focus:ring-1 focus:ring-[#4115df]/30"
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

        <div class="mb-4">
          <label class="block text-[#e0e0e0] text-sm font-medium mb-2">
            Camera
          </label>
          <select
            v-model="selectedVideoInput"
            class="w-full px-3 py-2 rounded-lg bg-[#1a3a52] border border-gray-600 text-[#e0e0e0] focus:outline-none focus:border-[#4115df] focus:ring-1 focus:ring-[#4115df]/30"
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

        <div class="flex gap-2 justify-end">
          <button
            class="px-4 py-2 rounded-lg bg-[#1a3a52] text-[#b0b0b0] hover:bg-[#2a4a62] transition-colors"
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
.mirror {
  transform: scaleX(-1);
}
</style>

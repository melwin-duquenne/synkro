<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
  >
    <div class="modal-box max-w-2xl">
      <h3 class="font-bold text-lg mb-4">
        Sélectionner une room pour créer une tâche
      </h3>

      <div v-if="loading" class="flex justify-center py-8">
        <span class="loading loading-spinner loading-lg"></span>
      </div>

      <div v-else-if="availableRooms.length === 0" class="text-center py-8">
        <p class="text-base-content/70 mb-4">
          Aucune room avec le plugin Kanban n'est disponible.
        </p>
        <p class="text-sm text-base-content/50">
          Créez d'abord une room avec le module Kanban activé.
        </p>
      </div>

      <div v-else class="space-y-3 max-h-96 overflow-y-auto">
        <div
          v-for="room in availableRooms"
          :key="room.id"
          class="card bg-[#0a1628] border-none cursor-pointer hover:bg-[#1a3a52] transition-colors"
          @click="selectRoom(room)"
        >
          <div class="card-body p-4">
            <div class="flex items-center justify-between">
              <div>
                <h4 class="font-semibold">{{ room.name }}</h4>
                <p class="text-sm text-base-content/60">
                  {{ room.moduleRooms?.length || 0 }} module(s) •
                  {{ room.visibility === "private" ? "Privée" : "Entreprise" }}
                </p>
              </div>
              <svg
                class="w-5 h-5 text-primary"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                ></path>
              </svg>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-action">
        <button class="btn" @click="$emit('close')">Annuler</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { useRoomsStore } from "@/stores/rooms";

interface Room {
  id: number;
  name: string;
  visibility: "private" | "public" | "enterprise";
  moduleRooms?: Array<{
    id: number;
    module: {
      code: string;
      name: string;
    };
  }>;
}

const props = defineProps<{
  open: boolean;
}>();

const emit = defineEmits<{
  close: [];
  selectRoom: [room: Room];
}>();

const roomsStore = useRoomsStore();
const loading = ref(false);

const availableRooms = ref<Room[]>([]);

onMounted(async () => {
  if (props.open) {
    await loadAvailableRooms();
  }
});

// Watch for open prop changes
watch(
  () => props.open,
  async (newOpen) => {
    if (newOpen) {
      await loadAvailableRooms();
    }
  },
);

async function loadAvailableRooms() {
  loading.value = true;
  try {
    await roomsStore.fetchRooms();

    // Filter rooms that have the Kanban module
    availableRooms.value = roomsStore.rooms.filter((room) =>
      room.moduleRooms?.some((mr) => mr.module?.code === "kanban"),
    );
  } catch (error) {
    console.error("Error loading rooms:", error);
  } finally {
    loading.value = false;
  }
}

function selectRoom(room: Room) {
  emit("selectRoom", room);
  emit("close");
}
</script>

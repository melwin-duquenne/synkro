<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import type { Room } from "@/types";
import EditorModule from "@/components/editor/EditorModule.vue";
import ChatModule from "@/components/chat/ChatModule.vue";
import TasksModule from "@/components/tasks/TasksModule.vue";
import CalendarModule from "@/components/calendar/CalendarModule.vue";
import WhiteboardModule from "@/components/whiteboard/WhiteboardModule.vue";
import VideoModule from "@/components/video/VideoModule.vue";
import FileModule from "@/components/files/FileModule.vue";
import LayoutSelectorModal from "@/components/room/LayoutSelectorModal.vue";
import ModuleOrderModal from "@/components/room/ModuleOrderModal.vue";
import RoomSettingsModal from "@/components/room/RoomSettingsModal.vue";
import { isAtLeast } from "@/utils/permissions";

const route = useRoute();
const authStore = useAuthStore();
const room = ref<Room | null>(null);
const loading = ref(true);
const activeModule = ref<string | null>(null);
const showLayoutSelector = ref(false);
const showModuleOrder = ref(false);
const showSettings = ref(false);

const roomId = computed(() => Number(route.params.id));

const sortedModuleRooms = computed(() => {
  if (!room.value?.moduleRooms) return [];
  // Filtrer le chat pour ne pas l'afficher dans les layouts (il sera en mode flottant)
  return [...room.value.moduleRooms]
    .filter((mr) => mr.module.code !== "chat")
    .sort((a, b) => (a.displayOrder || 0) - (b.displayOrder || 0));
});

const hasChatModule = computed(() => {
  return (
    room.value?.moduleRooms?.some((mr) => mr.module.code === "chat") || false
  );
});

const layoutType = computed(() => room.value?.layoutType || "tabs");

const canEditRoom = computed(() => {
  if (!room.value || !authStore.user) return false;
  // Creator can always edit
  if (room.value.creator.id === authStore.user.id) return true;
  // User with editor role or higher can edit
  return isAtLeast(authStore.user.role, "editor");
});

function handleRoomUpdated(updatedRoom: Room) {
  room.value = updatedRoom;
}

function getModuleComponent(moduleCode: string | null) {
  if (!moduleCode) return null;

  const components: Record<
    string,
    ReturnType<(typeof import("vue"))["defineComponent"]>
  > = {
    editor: EditorModule,
    chat: ChatModule,
    tasks: TasksModule,
    calendar: CalendarModule,
    whiteboard: WhiteboardModule,
    video: VideoModule,
    files: FileModule,
  };

  return components[moduleCode] || null;
}

async function updateLayout(newLayout: string) {
  try {
    const response = await fetch(`/api/rooms/${route.params.id}`, {
      method: "PATCH",
      headers: {
        ...authStore.getAuthHeaders(),
        "Content-Type": "application/merge-patch+json",
      },
      body: JSON.stringify({ layoutType: newLayout }),
    });

    if (response.ok) {
      room.value = await response.json();
    }
  } catch (error) {
    console.error("Failed to update layout:", error);
  }
}

async function updateModuleOrder(orderedIds: number[]) {
  try {
    const response = await fetch(
      `/api/rooms/${route.params.id}/reorder-modules`,
      {
        method: "POST",
        headers: {
          ...authStore.getAuthHeaders(),
          "Content-Type": "application/ld+json",
        },
        body: JSON.stringify({ moduleOrder: orderedIds }),
      },
    );

    if (response.ok) {
      room.value = await response.json();
      showModuleOrder.value = false;
    }
  } catch (error) {
    console.error("Failed to update module order:", error);
  }
}

onMounted(async () => {
  try {
    const response = await fetch(`/api/rooms/${route.params.id}`, {
      headers: authStore.getAuthHeaders(),
    });
    if (response.ok) {
      room.value = await response.json();
      const firstModule = room.value?.moduleRooms?.[0];
      if (firstModule) {
        activeModule.value = firstModule.module.code;
      }
    }
  } catch (error) {
    console.error("Failed to fetch room:", error);
  } finally {
    loading.value = false;
  }
});
</script>

<template>
  <div class="h-[calc(100vh-8rem)]">
    <div v-if="loading" class="flex justify-center items-center h-full">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else-if="!room" class="flex justify-center items-center h-full">
      <div class="text-center">
        <h2 class="text-2xl font-bold mb-2">Room introuvable</h2>
        <router-link
          :to="{
            name: 'rooms',
            params: { entrepriseSlug: $route.params.entrepriseSlug },
          }"
          class="btn btn-primary"
          >Retour aux rooms</router-link
        >
      </div>
    </div>

    <div v-else class="flex flex-col h-full">
      <!-- Room Header -->
      <div
        class="flex items-center justify-between mb-4 px-4 pb-3 border-b border-white/10"
      >
        <div>
          <h1 class="text-3xl font-bold text-[#e7f0fa]">{{ room.name }}</h1>
          <p class="text-[#8ea4be] mt-1">
            Créée par
            <span class="text-[#408ed6]">{{ room.creator?.displayName }}</span>
          </p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <button
            v-if="canEditRoom"
            class="btn btn-sm bg-[#1a3a52]/50 border-white/10 text-[#dbe6f2] hover:bg-[#1a3a52] hover:border-[#408ed6]/50 rounded-lg"
            @click="showSettings = true"
            title="Parametres de la room"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
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
            <span class="hidden sm:inline">Parametres</span>
          </button>
          <button
            class="btn btn-sm bg-[#1a3a52]/50 border-white/10 text-[#dbe6f2] hover:bg-[#1a3a52] hover:border-[#408ed6]/50 rounded-lg"
            @click="showModuleOrder = true"
            title="Reorganiser les modules"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 8h16M4 16h16"
              />
            </svg>
            <span class="hidden sm:inline">Ordre</span>
          </button>
          <button
            class="btn btn-sm bg-[#1a3a52]/50 border-white/10 text-[#dbe6f2] hover:bg-[#1a3a52] hover:border-[#408ed6]/50 rounded-lg"
            @click="showLayoutSelector = true"
            title="Changer la disposition"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="h-5 w-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1H5a1 1 0 01-1-1v-3zM14 16a1 1 0 011-1h4a1 1 0 011 1v3a1 1 0 01-1 1h-4a1 1 0 01-1-1v-3z"
              />
            </svg>
            <span class="hidden sm:inline">Disposition</span>
          </button>
          <span
            class="px-3 py-1 rounded-full text-sm font-medium"
            :class="
              room.visibility === 'private'
                ? 'bg-[#4115df]/20 text-[#4115df]'
                : 'bg-[#2d7a3f]/20 text-[#6fdd9f]'
            "
          >
            {{ room.visibility === "private" ? "🔒 Privée" : "🌐 Entreprise" }}
          </span>
        </div>
      </div>

      <!-- Tabs Layout -->
      <template v-if="layoutType === 'tabs'">
        <div
          class="tabs tabs-boxed mb-4 overflow-x-auto bg-transparent border-b border-white/10"
          style="background-color: transparent"
        >
          <a
            v-for="mr in sortedModuleRooms"
            :key="mr.id"
            class="tab text-[#8ea4be] hover:text-[#e7f0fa] border-0 border-b-2 border-transparent hover:border-[#408ed6] transition-colors"
            :class="{
              'tab-active': activeModule === mr.module.code,
              '!border-b-[#408ed6] !text-[#408ed6]':
                activeModule === mr.module.code,
            }"
            @click="activeModule = mr.module.code"
          >
            {{ mr.module.name }}
          </a>
        </div>

        <div
          class="flex-1 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg border border-white/10"
        >
          <component
            :is="getModuleComponent(activeModule)"
            v-if="activeModule"
            :room-id="roomId"
            class="h-full"
          />
        </div>
      </template>

      <!-- Grid 2x2 Layout -->
      <div
        v-else-if="layoutType === 'grid-2x2'"
        class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 overflow-auto p-1"
      >
        <div
          v-for="mr in sortedModuleRooms"
          :key="mr.id"
          class="bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col h-[400px] sm:h-auto border border-white/10"
        >
          <div
            class="bg-[#1a3a52]/60 px-4 py-2 font-semibold border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
          >
            {{ mr.module.name }}
          </div>
          <div class="flex-1 overflow-auto">
            <component
              :is="getModuleComponent(mr.module.code)"
              :room-id="roomId"
              class="h-full"
            />
          </div>
        </div>
      </div>

      <!-- Split Horizontal Layout -->
      <div
        v-else-if="layoutType === 'split-horizontal'"
        class="flex-1 flex flex-col gap-3 sm:gap-4 overflow-hidden p-1"
      >
        <div
          v-for="mr in sortedModuleRooms"
          :key="mr.id"
          class="flex-1 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col border border-white/10"
          style="min-height: 0; flex-basis: 0"
        >
          <div
            class="bg-[#1a3a52]/60 px-4 py-2 font-semibold border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
          >
            {{ mr.module.name }}
          </div>
          <div class="flex-1 overflow-auto min-h-0">
            <component
              :is="getModuleComponent(mr.module.code)"
              :room-id="roomId"
              class="h-full"
            />
          </div>
        </div>
      </div>

      <!-- Split Vertical Layout -->
      <div
        v-else-if="layoutType === 'split-vertical'"
        class="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 overflow-hidden p-1"
      >
        <div
          v-for="mr in sortedModuleRooms"
          :key="mr.id"
          class="bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col h-[400px] sm:h-auto border border-white/10"
          style="min-height: 0; min-width: 0; flex-basis: 0"
        >
          <div
            class="bg-[#1a3a52]/60 px-4 py-2 font-semibold border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
          >
            {{ mr.module.name }}
          </div>
          <div class="flex-1 overflow-auto min-h-0">
            <component
              :is="getModuleComponent(mr.module.code)"
              :room-id="roomId"
              class="h-full"
            />
          </div>
        </div>
      </div>

      <!-- Sidebar Left Layout -->
      <div
        v-else-if="layoutType === 'sidebar-left'"
        class="flex-1 flex flex-col sm:flex-row gap-3 sm:gap-4 overflow-hidden p-1"
      >
        <div
          v-if="sortedModuleRooms[0]"
          class="flex-none w-full sm:w-1/3 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col min-h-[300px] sm:min-h-0 border border-white/10"
        >
          <div
            class="bg-[#1a3a52]/60 px-3 sm:px-4 py-2 font-semibold text-sm sm:text-base border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
          >
            {{ sortedModuleRooms[0].module.name }}
          </div>
          <div class="flex-1 overflow-auto min-h-0">
            <component
              :is="getModuleComponent(sortedModuleRooms[0].module.code)"
              :room-id="roomId"
              class="h-full"
            />
          </div>
        </div>
        <div
          class="flex-1 flex flex-col gap-3 sm:gap-4 overflow-hidden min-h-0"
          style="min-width: 0"
        >
          <div
            v-for="mr in sortedModuleRooms.slice(1, 3)"
            :key="mr.id"
            class="flex-1 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col border border-white/10"
            style="min-height: 0; flex-basis: 0"
          >
            <div
              class="bg-[#1a3a52]/60 px-4 py-2 font-semibold border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
            >
              {{ mr.module.name }}
            </div>
            <div class="flex-1 overflow-auto min-h-0">
              <component
                :is="getModuleComponent(mr.module.code)"
                :room-id="roomId"
                class="h-full"
              />
            </div>
          </div>
        </div>
      </div>

      <!-- Sidebar Right Layout -->
      <div
        v-else-if="layoutType === 'sidebar-right'"
        class="flex-1 flex flex-col sm:flex-row gap-3 sm:gap-4 overflow-hidden p-1"
      >
        <div
          class="flex-1 flex flex-col gap-3 sm:gap-4 overflow-hidden min-h-0"
          style="min-width: 0"
        >
          <div
            v-for="mr in sortedModuleRooms.slice(0, 2)"
            :key="mr.id"
            class="flex-1 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col border border-white/10"
            style="min-height: 0; flex-basis: 0"
          >
            <div
              class="bg-[#1a3a52]/60 px-4 py-2 font-semibold border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
            >
              {{ mr.module.name }}
            </div>
            <div class="flex-1 overflow-auto min-h-0">
              <component
                :is="getModuleComponent(mr.module.code)"
                :room-id="roomId"
                class="h-full"
              />
            </div>
          </div>
        </div>
        <div
          v-if="sortedModuleRooms[2]"
          class="flex-none w-full sm:w-1/3 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col min-h-[300px] sm:min-h-0 border border-white/10"
        >
          <div
            class="bg-[#1a3a52]/60 px-3 sm:px-4 py-2 font-semibold text-sm sm:text-base border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
          >
            {{ sortedModuleRooms[2].module.name }}
          </div>
          <div class="flex-1 overflow-auto min-h-0">
            <component
              :is="getModuleComponent(sortedModuleRooms[2].module.code)"
              :room-id="roomId"
              class="h-full"
            />
          </div>
        </div>
      </div>

      <!-- Main + Sidebar Layout -->
      <div
        v-else-if="layoutType === 'main-sidebar'"
        class="flex-1 flex flex-col lg:flex-row gap-3 sm:gap-4 overflow-hidden p-1"
      >
        <div
          v-if="sortedModuleRooms[0]"
          class="flex-1 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col min-h-[300px] lg:min-h-0 border border-white/10"
        >
          <div
            class="bg-[#1a3a52]/60 px-3 sm:px-4 py-2 font-semibold text-sm sm:text-base border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
          >
            {{ sortedModuleRooms[0].module.name }}
          </div>
          <div class="flex-1 overflow-auto min-h-0">
            <component
              :is="getModuleComponent(sortedModuleRooms[0].module.code)"
              :room-id="roomId"
              class="h-full"
            />
          </div>
        </div>
        <div
          class="flex-none w-full lg:w-80 flex flex-col gap-3 sm:gap-4 overflow-hidden min-h-0"
          style="min-width: 0"
        >
          <div
            v-for="mr in sortedModuleRooms.slice(1, 4)"
            :key="mr.id"
            class="flex-1 bg-[#0a1628] rounded-xl overflow-hidden shadow-lg flex flex-col border border-white/10"
            style="min-height: 0; flex-basis: 0"
          >
            <div
              class="bg-[#1a3a52]/60 px-4 py-2 font-semibold text-sm border-b border-white/10 flex-shrink-0 text-[#dbe6f2]"
            >
              {{ mr.module.name }}
            </div>
            <div class="flex-1 overflow-auto min-h-0">
              <component
                :is="getModuleComponent(mr.module.code)"
                :room-id="roomId"
                class="h-full"
              />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Layout Selector Modal -->
    <LayoutSelectorModal
      v-if="showLayoutSelector"
      @close="showLayoutSelector = false"
      @select="updateLayout"
    />

    <!-- Module Order Modal -->
    <ModuleOrderModal
      v-if="showModuleOrder && room"
      :module-rooms="room.moduleRooms"
      @close="showModuleOrder = false"
      @save="updateModuleOrder"
    />

    <!-- Room Settings Modal -->
    <RoomSettingsModal
      v-if="showSettings && room"
      :room="room"
      @close="showSettings = false"
      @updated="handleRoomUpdated"
    />

    <!-- Chat flottant -->
    <ChatModule
      v-if="room && hasChatModule"
      :room-id="roomId"
      :floating="true"
    />
  </div>
</template>

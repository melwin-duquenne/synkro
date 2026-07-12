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

function getModuleTabHint(moduleCode: string | null) {
  const hints: Record<string, string> = {
    editor: "Coedition",
    tasks: "Suivi",
    calendar: "Planning",
    whiteboard: "Canvas",
    video: "Visio",
    files: "Documents",
    chat: "Messages",
  };

  return hints[moduleCode ?? ""] || "Module";
}

function getModuleIconPath(moduleCode: string | null) {
  const icons: Record<string, string> = {
    editor:
      "M4 17.25V20h2.75L17.8 8.94l-2.75-2.75L4 17.25Zm14.71-9.04a1.003 1.003 0 0 0 0-1.42l-1.5-1.5a1.003 1.003 0 0 0-1.42 0l-1.17 1.17 2.75 2.75 1.34-1Z",
    tasks:
      "M9 5H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9m-6-4h6m0 0v6m0-6-7 7",
    calendar:
      "M8 2v3M16 2v3M3.5 9.5h17M5 5h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z",
    whiteboard: "M5 5h14v10H5zM9 19h6M12 15v4",
    video:
      "M15 10.5V6a2 2 0 0 0-2-2H5A2 2 0 0 0 3 4v10a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2v-4.5l6 4v-9l-6 4Z",
    files:
      "M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Zm0 0v5h5",
    chat: "M8 10h.01M12 10h.01M16 10h.01M5 5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2h-4l-4 4v-4H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z",
  };

  return icons[moduleCode ?? ""] || "M5 12h14";
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
      const firstModule = room.value?.moduleRooms?.find(
        (mr) => mr.module.code !== "chat",
      );
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
        <div class="room-header-actions">
          <button
            v-if="canEditRoom"
            class="room-toolbar-btn"
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
            <span>Parametres</span>
          </button>
          <button
            class="room-toolbar-btn"
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
            <span>Ordre</span>
          </button>
          <button
            class="room-toolbar-btn"
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
            <span>Disposition</span>
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
        <div class="room-tabs-shell mb-4">
          <div class="room-tabs-shell__glow"></div>
          <div class="room-tabs-shell__header">
            <div>
              <span class="room-tabs-shell__badge">Modules actifs</span>
              <p class="room-tabs-shell__title">Navigation rapide de la room</p>
            </div>
            <p class="room-tabs-shell__caption">
              Passe d'un espace de travail a l'autre sans perdre le contexte.
            </p>
          </div>

          <div class="room-tabs-list">
            <button
              v-for="mr in sortedModuleRooms"
              :key="mr.id"
              type="button"
              class="room-tab"
              :class="{
                'room-tab--active': activeModule === mr.module.code,
              }"
              @click="activeModule = mr.module.code"
            >
              <span class="room-tab__icon" aria-hidden="true">
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.8"
                    :d="getModuleIconPath(mr.module.code)"
                  />
                </svg>
              </span>
              <span class="room-tab__meta">
                <span class="room-tab__eyebrow">{{
                  getModuleTabHint(mr.module.code)
                }}</span>
                <span class="room-tab__label">{{ mr.module.name }}</span>
              </span>
            </button>
          </div>
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

<style scoped>
.room-header-actions {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}

.room-toolbar-btn {
  min-height: 2.35rem;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.09);
  border-radius: 14px;
  background: linear-gradient(
    180deg,
    rgba(255, 255, 255, 0.045),
    rgba(255, 255, 255, 0.02)
  );
  color: #dbe6f2;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.02);
  transition:
    border-color 0.22s ease,
    background-color 0.22s ease,
    transform 0.22s ease,
    box-shadow 0.22s ease;
}

.room-toolbar-btn:hover {
  border-color: rgba(91, 163, 232, 0.32);
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.14),
    rgba(91, 163, 232, 0.05)
  );
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.18);
}

.room-toolbar-btn svg {
  width: 1rem;
  height: 1rem;
  color: #d8ecff;
  flex: 0 0 auto;
}

.room-toolbar-btn span {
  font-size: 0.82rem;
  font-weight: 600;
  line-height: 1;
}

.room-tabs-shell {
  position: relative;
  overflow: hidden;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 0.75rem;
  background:
    radial-gradient(
      circle at top right,
      rgba(91, 163, 232, 0.2),
      transparent 30%
    ),
    linear-gradient(180deg, rgba(9, 18, 31, 0.96), rgba(7, 13, 24, 0.96));
  box-shadow:
    0 20px 40px rgba(0, 0, 0, 0.28),
    inset 0 0 0 1px rgba(255, 255, 255, 0.03);
}

.room-tabs-shell__glow {
  position: absolute;
  top: -4rem;
  right: -2rem;
  width: 12rem;
  height: 12rem;
  border-radius: 999px;
  background: radial-gradient(
    circle,
    rgba(91, 163, 232, 0.24),
    transparent 68%
  );
  pointer-events: none;
}

.room-tabs-shell__header {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.7rem;
}

.room-tabs-shell__badge {
  display: inline-flex;
  align-items: center;
  margin-bottom: 0.28rem;
  padding: 0.22rem 0.52rem;
  border: 1px solid rgba(148, 212, 255, 0.18);
  border-radius: 999px;
  background: rgba(91, 163, 232, 0.12);
  color: #d8ecff;
  font-size: 0.62rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.room-tabs-shell__title {
  margin: 0;
  color: #edf6ff;
  font-size: 0.92rem;
  font-weight: 700;
}

.room-tabs-shell__caption {
  max-width: 18rem;
  margin: 0;
  color: #8ea4be;
  font-size: 0.72rem;
  line-height: 1.35;
  text-align: right;
}

.room-tabs-list {
  position: relative;
  z-index: 1;
  display: flex;
  gap: 0.55rem;
  overflow-x: auto;
  padding-bottom: 0.1rem;
}

.room-tabs-list::-webkit-scrollbar {
  height: 0.38rem;
}

.room-tabs-list::-webkit-scrollbar-thumb {
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.12);
}

.room-tab {
  min-width: 9.75rem;
  display: inline-flex;
  align-items: center;
  gap: 0.65rem;
  padding: 0.58rem 0.72rem;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 15px;
  background: rgba(255, 255, 255, 0.035);
  color: #cfe0f1;
  text-align: left;
  transition:
    border-color 0.22s ease,
    background-color 0.22s ease,
    transform 0.22s ease,
    box-shadow 0.22s ease;
}

.room-tab:hover {
  border-color: rgba(91, 163, 232, 0.3);
  background: rgba(91, 163, 232, 0.08);
  transform: translateY(-1px);
}

.room-tab--active {
  border-color: rgba(91, 163, 232, 0.38);
  background: linear-gradient(
    135deg,
    rgba(91, 163, 232, 0.18),
    rgba(91, 163, 232, 0.06)
  );
  box-shadow: 0 14px 30px rgba(0, 0, 0, 0.22);
}

.room-tab__icon {
  width: 2.15rem;
  height: 2.15rem;
  flex: 0 0 2.15rem;
  display: grid;
  place-items: center;
  border-radius: 11px;
  border: 1px solid rgba(91, 163, 232, 0.18);
  background: rgba(91, 163, 232, 0.1);
  color: #d8ecff;
}

.room-tab__icon svg {
  width: 1rem;
  height: 1rem;
}

.room-tab__meta {
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.room-tab__eyebrow {
  color: #7f95ac;
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.room-tab__label {
  color: #edf6ff;
  font-size: 0.84rem;
  font-weight: 600;
  line-height: 1.2;
  white-space: nowrap;
}

@media (max-width: 768px) {
  .room-header-actions {
    gap: 0.5rem;
  }

  .room-toolbar-btn {
    min-height: 2.15rem;
    padding: 0.4rem 0.65rem;
    border-radius: 12px;
  }

  .room-toolbar-btn span {
    font-size: 0.76rem;
  }

  .room-tabs-shell {
    padding: 0.7rem;
  }

  .room-tabs-shell__header {
    flex-direction: column;
  }

  .room-tabs-shell__caption {
    max-width: none;
    text-align: left;
  }

  .room-tab {
    min-width: 9rem;
  }
}
</style>

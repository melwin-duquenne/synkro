<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { useRoomsStore, type EnterpriseUser } from "@/stores/rooms";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import { canCreateRoom } from "@/utils/permissions";

defineProps<{
  open: boolean;
}>();

const emit = defineEmits<{
  close: [];
}>();

const router = useRouter();
const roomsStore = useRoomsStore();
const authStore = useAuthStore();

const name = ref("");
const selectedModules = ref<string[]>([]);
const visibility = ref<"enterprise" | "private">("enterprise");
const selectedTemplate = ref<number | null>(null);
const selectedMembers = ref<number[]>([]);
const memberSearch = ref("");
const showMemberDropdown = ref(false);

const userCanCreateRoom = computed(() => {
  return authStore.user ? canCreateRoom(authStore.user.role) : false;
});

const isValid = computed(
  () => name.value.trim() && selectedModules.value.length > 0,
);

const filteredUsers = computed(() => {
  if (!memberSearch.value.trim()) {
    return roomsStore.enterpriseUsers.filter(
      (u) =>
        u.id !== authStore.user?.id && !selectedMembers.value.includes(u.id),
    );
  }
  const search = memberSearch.value.toLowerCase();
  return roomsStore.enterpriseUsers.filter(
    (u) =>
      u.id !== authStore.user?.id &&
      !selectedMembers.value.includes(u.id) &&
      (u.displayName.toLowerCase().includes(search) ||
        u.email.toLowerCase().includes(search)),
  );
});

const selectedMemberDetails = computed(() => {
  return selectedMembers.value
    .map((id) => roomsStore.enterpriseUsers.find((u) => u.id === id))
    .filter((u): u is EnterpriseUser => u !== undefined);
});

function applyTemplate(templateId: number) {
  const template = roomsStore.templates.find((t) => t.id === templateId);
  console.log("Applying template:", template);
  if (template && Array.isArray(template.templateModules)) {
    selectedTemplate.value = templateId;
    selectedModules.value = template.templateModules.map(
      (tm) => tm.module.code,
    );
  }
}

function toggleModule(code: string) {
  selectedTemplate.value = null; // Clear template selection when manually toggling
  const index = selectedModules.value.indexOf(code);
  if (index === -1) {
    selectedModules.value.push(code);
  } else {
    selectedModules.value.splice(index, 1);
  }
}

function addMember(userId: number) {
  if (!selectedMembers.value.includes(userId)) {
    selectedMembers.value.push(userId);
  }
  memberSearch.value = "";
  showMemberDropdown.value = false;
}

function removeMember(userId: number) {
  selectedMembers.value = selectedMembers.value.filter((id) => id !== userId);
}

async function handleSubmit() {
  if (!isValid.value) return;

  const room = await roomsStore.createRoom({
    name: name.value,
    modules: selectedModules.value,
    visibility: visibility.value,
    memberIds: visibility.value === "private" ? selectedMembers.value : [],
  });

  if (room) {
    emit("close");
    router.push(`/room/${room.id}`);
  }
}

function handleClose() {
  name.value = "";
  selectedModules.value = [];
  selectedTemplate.value = null;
  visibility.value = "enterprise";
  selectedMembers.value = [];
  memberSearch.value = "";
  emit("close");
}
function handleBlur() {
  setTimeout(() => (showMemberDropdown.value = false), 200);
}

// Fetch enterprise users when visibility changes to private
watch(visibility, async (newValue) => {
  if (newValue === "private" && roomsStore.enterpriseUsers.length === 0) {
    await roomsStore.fetchEnterpriseUsers();
  }
});

onMounted(() => {
  roomsStore.fetchModules();
  roomsStore.fetchTemplates();
});
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div
      class="modal-box max-w-3xl max-h-[90vh] bg-[#0a1628] border border-white/10 rounded-xl shadow-2xl p-0 overflow-y-auto text-[#e0e0e0]"
    >
      <div
        class="px-6 pt-6 pb-4 border-b border-white/10 bg-[linear-gradient(180deg,rgba(10,22,40,1)_0%,rgba(10,22,40,0.86)_100%)]"
      >
        <h3 class="font-bold text-xl text-white">Creer une nouvelle room</h3>
        <p class="text-sm text-[#97abc3] mt-1">
          Configurez votre espace, ses modules et ses membres en quelques clics.
        </p>
      </div>

      <!-- Permission warning -->
      <div
        v-if="!userCanCreateRoom"
        class="mx-6 mt-5 alert bg-amber-500/15 border border-amber-400/30 text-amber-200"
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
            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
          />
        </svg>
        <span
          >Vous devez avoir le role editeur ou superieur pour creer une
          room.</span
        >
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6 px-6 py-5">
        <!-- Room Name -->
        <div
          class="form-control"
        >
          <label class="label">
            <span class="label-text text-[#dbe6f2]">Nom de la room</span>
          </label>
          <input
            v-model="name"
            type="text"
            placeholder="Ma room collaborative"
            class="input w-full bg-[#112338] border border-white/10 text-[#e0e0e0] placeholder:text-[#7f95ad]"
            required
            :disabled="!userCanCreateRoom"
          />
        </div>

        <!-- Visibility -->
        <div
          class="form-control"
        >
          <label class="label">
            <span class="label-text text-[#dbe6f2]">Visibilite</span>
          </label>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label
              class="label cursor-pointer gap-2 px-3"
            >
              <input
                v-model="visibility"
                type="radio"
                name="visibility"
                value="enterprise"
                class="radio radio-primary"
                :disabled="!userCanCreateRoom"
              />
              <span class="text-[#e7f0fa]">Entreprise</span>
              <span class="text-xs text-[#8ea4be]">(Tous les membres)</span>
            </label>
            <label
              class="label cursor-pointer gap-2 px-3"
            >
              <input
                v-model="visibility"
                type="radio"
                name="visibility"
                value="private"
                class="radio radio-primary"
                :disabled="!userCanCreateRoom"
              />
              <span class="text-[#e7f0fa]">Privee</span>
              <span class="text-xs text-[#8ea4be]">(Invitation requise)</span>
            </label>
          </div>
        </div>

        <!-- Members Selection (only for private rooms) -->
        <div
          v-if="visibility === 'private'"
          class="form-control"
        >
          <label class="label">
            <span class="label-text text-[#dbe6f2]">Membres a inviter</span>
            <span class="label-text-alt text-[#8ea4be]"
              >{{ selectedMembers.length }} selectionne(s)</span
            >
          </label>

          <!-- Selected members badges -->
          <div
            v-if="selectedMemberDetails.length > 0"
            class="flex flex-wrap gap-2 mb-3"
          >
            <span
              v-for="member in selectedMemberDetails"
              :key="member.id"
              class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs bg-[#1a3a52] text-[#d9e8f8] border border-[#408ed6]/35"
            >
              {{ member.displayName }}
              <button
                type="button"
                class="btn btn-ghost btn-xs p-0 h-auto min-h-0 text-[#c4d8ef]"
                @click="removeMember(member.id)"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-3 w-3"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </span>
          </div>

          <!-- Member search -->
          <div class="relative">
            <input
              v-model="memberSearch"
              type="text"
              placeholder="Rechercher un membre..."
              class="input w-full bg-[#112338] border border-white/10 text-[#e0e0e0] placeholder:text-[#7f95ad]"
              @focus="showMemberDropdown = true"
              @blur="handleBlur"
            />

            <!-- Dropdown -->
            <div
              v-if="showMemberDropdown && filteredUsers.length > 0"
              class="absolute z-10 w-full mt-1 bg-[#0a1628] border border-white/10 rounded-lg shadow-lg max-h-48 overflow-y-auto"
            >
              <button
                v-for="user in filteredUsers"
                :key="user.id"
                type="button"
                class="w-full px-4 py-2 text-left hover:bg-[#1a3a52] flex items-center justify-between"
                @mousedown.prevent="addMember(user.id)"
              >
                <div>
                  <div class="font-medium text-[#e7f0fa]">
                    {{ user.displayName }}
                  </div>
                  <div class="text-xs text-[#8ea4be]">{{ user.email }}</div>
                </div>
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  class="h-5 w-5 text-primary"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
              </button>
            </div>

            <div
              v-if="
                showMemberDropdown && filteredUsers.length === 0 && memberSearch
              "
              class="absolute z-10 w-full mt-1 bg-[#0a1628] border border-white/10 rounded-lg shadow-lg p-4 text-center text-[#8ea4be]"
            >
              Aucun membre trouve
            </div>
          </div>

          <p class="text-xs text-[#8ea4be] mt-2">
            Vous serez automatiquement ajoute comme membre de la room.
          </p>
        </div>

        <!-- Templates -->
        <div
          class="form-control"
        >
          <label class="label">
            <span class="label-text text-[#dbe6f2]">Partir d'un template</span>
            <span class="label-text-alt text-[#8ea4be]">Optionnel</span>
          </label>
          <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
            <button
              v-for="template in roomsStore.templates"
              :key="template.id"
              type="button"
              class="btn btn-sm"
              :class="
                selectedTemplate === template.id ? 'btn-primary' : 'btn-ghost'
              "
              :disabled="!userCanCreateRoom"
              @click="applyTemplate(template.id)"
            >
              {{ template.name }}
            </button>
          </div>
        </div>

        <!-- Modules -->
        <div
          class="form-control"
        >
          <label class="label">
            <span class="label-text text-[#dbe6f2]">Modules</span>
            <span class="label-text-alt text-[#8ea4be]"
              >{{ selectedModules.length }} selectionne(s)</span
            >
          </label>
          <div class="grid grid-cols-2 gap-3">
            <label
              v-for="module in roomsStore.modules"
              :key="module.code"
              class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors"
              :class="[
                selectedModules.includes(module.code)
                  ? 'border-[#408ed6]/60 bg-[#1a3a52]/50'
                  : 'border-white/10 bg-[#112338] hover:border-[#408ed6]/40',
                !userCanCreateRoom ? 'opacity-50 cursor-not-allowed' : '',
              ]"
            >
              <input
                type="checkbox"
                :checked="selectedModules.includes(module.code)"
                class="checkbox checkbox-primary mt-1"
                :disabled="!userCanCreateRoom"
                @change="toggleModule(module.code)"
              />
              <div class="flex-1">
                <div class="font-medium text-[#e7f0fa]">{{ module.name }}</div>
                <div class="text-xs text-[#8ea4be]">
                  {{ module.description }}
                </div>
              </div>
            </label>
          </div>
        </div>

        <!-- Error -->
        <div
          v-if="roomsStore.error"
          class="alert bg-red-500/15 border border-red-400/30 text-red-200"
        >
          <span>{{ roomsStore.error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action mt-2 pt-4 border-t border-white/10">
          <button type="button" class="btn" @click="handleClose">
            Annuler
          </button>
          <button
            type="submit"
            class="btn btn-primary"
            :class="{ loading: roomsStore.loading }"
            :disabled="!isValid || roomsStore.loading || !userCanCreateRoom"
          >
            {{ roomsStore.loading ? "Creation..." : "Creer la room" }}
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="handleClose">close</button>
    </form>
  </dialog>
</template>

<script setup lang="ts">
import { ref, watch, computed, onMounted } from "vue";
import { useCalendarStore } from "@/stores/calendar";
import type { CalendarEvent, EventType } from "@/types";

const props = defineProps<{
  open: boolean;
  roomId: number;
  event: CalendarEvent | null;
  initialDate: Date | null;
}>();

const emit = defineEmits<{
  close: [];
  saved: [];
  deleted: [];
}>();

const calendarStore = useCalendarStore();

const title = ref("");
const description = ref("");
const eventType = ref<EventType>("other");
const startDate = ref("");
const startTime = ref("09:00");
const endDate = ref("");
const endTime = ref("10:00");
const isAllDay = ref(false);
const location = ref("");
const isPrivate = ref(false);
const loading = ref(false);
const selectedParticipantIds = ref<number[]>([]);
const participantSearch = ref("");

const isEditMode = computed(() => !!props.event);

// Only show participants for room events
const showParticipants = computed(() => props.roomId > 0);

const eventTypes: { value: EventType; label: string }[] = [
  { value: "meeting", label: "Réunion" },
  { value: "absence", label: "Absence" },
  { value: "blocked", label: "Bloqué" },
  { value: "reminder", label: "Rappel" },
  { value: "other", label: "Autre" },
];

const filteredUsers = computed(() => {
  const search = participantSearch.value.toLowerCase();
  return calendarStore.enterpriseUsers.filter(
    (user) =>
      user.displayName.toLowerCase().includes(search) ||
      user.email.toLowerCase().includes(search),
  );
});

const selectedParticipants = computed(() => {
  return calendarStore.enterpriseUsers.filter((u) =>
    selectedParticipantIds.value.includes(u.id),
  );
});

function formatDateForInput(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function parseDateTimeString(dateTimeStr: string): {
  date: string;
  time: string;
} {
  // Parse la chaîne ISO sans conversion de timezone
  const parts = dateTimeStr.split("T");
  const date = parts[0] || "";
  const time = parts[1] ? parts[1].substring(0, 5) : "00:00";
  return { date, time };
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      // Load users when modal opens
      if (
        showParticipants.value &&
        calendarStore.enterpriseUsers.length === 0
      ) {
        calendarStore.fetchEnterpriseUsers();
      }

      if (props.event) {
        // Edit mode
        title.value = props.event.title;
        description.value = props.event.description || "";
        eventType.value = props.event.eventType;
        isAllDay.value = props.event.isAllDay;
        location.value = props.event.location || "";
        isPrivate.value = props.event.isPrivate;
        selectedParticipantIds.value =
          props.event.participants?.map((p) => p.userId) || [];

        const startParsed = parseDateTimeString(props.event.startDate);
        const endParsed = parseDateTimeString(props.event.endDate);
        startDate.value = startParsed.date;
        startTime.value = startParsed.time;
        endDate.value = endParsed.date;
        endTime.value = endParsed.time;
      } else if (props.initialDate) {
        // Create mode with initial date
        resetForm();
        startDate.value = formatDateForInput(props.initialDate);
        endDate.value = formatDateForInput(props.initialDate);
      } else {
        resetForm();
      }
    }
  },
);

function resetForm() {
  const today = new Date();
  title.value = "";
  description.value = "";
  eventType.value = "other";
  startDate.value = formatDateForInput(today);
  startTime.value = "09:00";
  endDate.value = formatDateForInput(today);
  endTime.value = "10:00";
  isAllDay.value = false;
  location.value = "";
  isPrivate.value = false;
  selectedParticipantIds.value = [];
  participantSearch.value = "";
}

function toggleParticipant(userId: number) {
  const index = selectedParticipantIds.value.indexOf(userId);
  if (index === -1) {
    selectedParticipantIds.value.push(userId);
  } else {
    selectedParticipantIds.value.splice(index, 1);
  }
}

function removeParticipant(userId: number) {
  selectedParticipantIds.value = selectedParticipantIds.value.filter(
    (id) => id !== userId,
  );
}

async function handleSubmit() {
  if (!title.value.trim()) return;

  loading.value = true;

  const startDateTime = isAllDay.value
    ? `${startDate.value}T08:00:00`
    : `${startDate.value}T${startTime.value}:00`;

  const endDateTime = isAllDay.value
    ? `${endDate.value}T19:00:00`
    : `${endDate.value}T${endTime.value}:00`;

  const data = {
    title: title.value,
    description: description.value || undefined,
    eventType: eventType.value,
    startDate: startDateTime,
    endDate: endDateTime,
    isAllDay: isAllDay.value,
    location: location.value || undefined,
    isPrivate: isPrivate.value,
    roomId: props.roomId > 0 ? props.roomId : null,
    participantIds: showParticipants.value ? selectedParticipantIds.value : [],
  };

  let success: boolean;

  if (props.event) {
    const result = await calendarStore.updateEvent(props.event.id, data);
    success = !!result;
  } else {
    const result = await calendarStore.createEvent(data);
    success = !!result;
  }

  loading.value = false;

  if (success) {
    emit("saved");
  }
}

async function handleDelete() {
  if (!props.event) return;

  if (!confirm("Supprimer cet événement ?")) return;

  loading.value = true;
  const success = await calendarStore.deleteEvent(props.event.id);
  loading.value = false;

  if (success) {
    emit("deleted");
  }
}

onMounted(() => {
  if (showParticipants.value) {
    calendarStore.fetchEnterpriseUsers();
  }
});
</script>

<template>
  <dialog class="modal" :class="{ 'modal-open': open }">
    <div
      class="modal-box calendar-event-modal max-w-2xl max-h-[90vh] overflow-y-auto"
    >
      <h3 class="calendar-event-modal__title">
        {{ isEditMode ? "Modifier l'événement" : "Nouvel événement" }}
      </h3>

      <form @submit.prevent="handleSubmit" class="space-y-4">
        <!-- Title -->
        <div class="form-control calendar-field">
          <label class="label calendar-label">
            <span class="label-text">Titre *</span>
          </label>
          <input
            v-model="title"
            type="text"
            placeholder="Titre de l'événement"
            class="input input-bordered w-full calendar-input"
            required
          />
        </div>

        <!-- Event Type -->
        <div class="form-control calendar-field">
          <label class="label calendar-label">
            <span class="label-text">Type</span>
          </label>
          <select
            v-model="eventType"
            class="select select-bordered w-full calendar-input"
          >
            <option
              v-for="type in eventTypes"
              :key="type.value"
              :value="type.value"
            >
              {{ type.label }}
            </option>
          </select>
        </div>

        <!-- All Day Toggle -->
        <div class="form-control calendar-switch-row">
          <label class="label cursor-pointer justify-start gap-4 m-0">
            <input
              v-model="isAllDay"
              type="checkbox"
              class="checkbox checkbox-primary calendar-checkbox"
            />
            <span class="label-text">Journée entia8re</span>
          </label>
        </div>

        <!-- Date/Time -->
        <div class="grid grid-cols-2 gap-4">
          <div class="form-control calendar-field">
            <label class="label calendar-label">
              <span class="label-text">Date de début *</span>
            </label>
            <input
              v-model="startDate"
              type="date"
              class="input input-bordered w-full calendar-input"
              required
            />
          </div>
          <div v-if="!isAllDay" class="form-control calendar-field">
            <label class="label calendar-label">
              <span class="label-text">Heure de début</span>
            </label>
            <input
              v-model="startTime"
              type="time"
              class="input input-bordered w-full calendar-input"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="form-control calendar-field">
            <label class="label calendar-label">
              <span class="label-text">Date de fin *</span>
            </label>
            <input
              v-model="endDate"
              type="date"
              class="input input-bordered w-full calendar-input"
              required
            />
          </div>
          <div v-if="!isAllDay" class="form-control calendar-field">
            <label class="label calendar-label">
              <span class="label-text">Heure de fin</span>
            </label>
            <input
              v-model="endTime"
              type="time"
              class="input input-bordered w-full calendar-input"
            />
          </div>
        </div>

        <!-- Participants (only for room events) -->
        <div
          v-if="showParticipants"
          class="form-control calendar-participants-card"
        >
          <label class="label calendar-label">
            <span class="label-text">Participants</span>
            <span class="label-text-alt calendar-label-alt"
              >{{ selectedParticipantIds.length }} sélectionné(s)</span
            >
          </label>

          <!-- Selected participants -->
          <div
            v-if="selectedParticipants.length > 0"
            class="flex flex-wrap gap-2 mb-2"
          >
            <div
              v-for="user in selectedParticipants"
              :key="user.id"
              class="badge badge-primary gap-2"
            >
              {{ user.displayName }}
              <button
                type="button"
                class="btn btn-ghost btn-xs p-0"
                @click="removeParticipant(user.id)"
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
            </div>
          </div>

          <!-- Search users -->
          <input
            v-model="participantSearch"
            type="text"
            placeholder="Rechercher un utilisateur..."
            class="input input-bordered input-sm w-full mb-2 calendar-input"
          />

          <!-- Users list -->
          <div
            class="calendar-users-list border rounded-lg max-h-40 overflow-y-auto"
          >
            <div
              v-for="user in filteredUsers"
              :key="user.id"
              class="calendar-user-item flex items-center gap-3 p-2 cursor-pointer transition-colors"
              @click="toggleParticipant(user.id)"
            >
              <input
                type="checkbox"
                :checked="selectedParticipantIds.includes(user.id)"
                class="checkbox checkbox-sm checkbox-primary"
                @click.stop
                @change="toggleParticipant(user.id)"
              />
              <div class="flex-1">
                <div class="font-medium text-sm text-[#e7f0fa]">
                  {{ user.displayName }}
                </div>
                <div class="text-xs text-[#8ea4be]">{{ user.email }}</div>
              </div>
            </div>
            <div
              v-if="filteredUsers.length === 0"
              class="p-4 text-center text-[#7f95ac] text-sm"
            >
              Aucun utilisateur trouvé
            </div>
          </div>
        </div>

        <!-- Location -->
        <div class="form-control calendar-field">
          <label class="label calendar-label">
            <span class="label-text">Lieu</span>
          </label>
          <input
            v-model="location"
            type="text"
            placeholder="Salle de réunion, lien visio..."
            class="input input-bordered w-full calendar-input"
          />
        </div>

        <!-- Description -->
        <div class="form-control calendar-field">
          <label class="label calendar-label">
            <span class="label-text">Description</span>
          </label>
          <textarea
            v-model="description"
            class="textarea textarea-bordered w-full calendar-input"
            placeholder="Détails de l'événement..."
            rows="3"
          ></textarea>
        </div>

        <!-- Private Toggle -->
        <div class="form-control calendar-switch-row">
          <label class="label cursor-pointer justify-start gap-4 m-0">
            <input
              v-model="isPrivate"
              type="checkbox"
              class="checkbox checkbox-primary calendar-checkbox"
            />
            <span class="label-text"
              >Événement privé (visible uniquement par vous)</span
            >
          </label>
        </div>

        <!-- Error -->
        <div v-if="calendarStore.error" class="calendar-alert-error">
          <span>{{ calendarStore.error }}</span>
        </div>

        <!-- Actions -->
        <div class="modal-action calendar-actions">
          <button
            v-if="isEditMode"
            type="button"
            class="calendar-danger-btn"
            :disabled="loading"
            @click="handleDelete"
          >
            Supprimer
          </button>
          <div class="flex-1"></div>
          <button
            type="button"
            class="calendar-soft-btn"
            @click="emit('close')"
          >
            Annuler
          </button>
          <button
            type="submit"
            class="calendar-primary-btn"
            :class="{ loading: loading }"
            :disabled="loading || !title.trim()"
          >
            {{ isEditMode ? "Enregistrer" : "Créer" }}
          </button>
        </div>
      </form>
    </div>
    <form method="dialog" class="modal-backdrop">
      <button @click="emit('close')">close</button>
    </form>
  </dialog>
</template>

<style scoped>
.calendar-event-modal {
  border-radius: 18px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: linear-gradient(
    180deg,
    rgba(9, 19, 34, 0.96),
    rgba(7, 13, 24, 0.96)
  );
  color: #e0e0e0;
  box-shadow: 0 24px 46px rgba(0, 0, 0, 0.32);
}

.calendar-event-modal__title {
  margin-bottom: 1rem;
  color: #e7f0fa;
  font-size: 1.08rem;
  font-weight: 700;
}

.calendar-label .label-text {
  color: #dbe6f2;
  font-size: 0.82rem;
  font-weight: 600;
}

.calendar-label-alt {
  color: #8ea4be;
  font-size: 0.72rem;
}

.calendar-input {
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  background: rgba(255, 255, 255, 0.03);
  color: #e7f0fa;
}

.calendar-input::placeholder {
  color: #7f95ac;
}

.calendar-input:focus {
  outline: none;
  border-color: rgba(91, 163, 232, 0.6);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.2);
}

.calendar-switch-row {
  padding: 0.5rem 0.65rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.08);
  background: rgba(255, 255, 255, 0.02);
}

.calendar-checkbox {
  border-color: rgba(91, 163, 232, 0.45);
}

.calendar-participants-card {
  padding: 0.7rem;
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: rgba(255, 255, 255, 0.02);
}

.calendar-users-list {
  border-color: rgba(255, 255, 255, 0.12);
  background: rgba(8, 18, 31, 0.65);
}

.calendar-user-item:hover {
  background: rgba(91, 163, 232, 0.12);
}

.calendar-users-list::-webkit-scrollbar {
  width: 8px;
}

.calendar-users-list::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.04);
  border-radius: 999px;
}

.calendar-users-list::-webkit-scrollbar-thumb {
  background: rgba(91, 163, 232, 0.35);
  border-radius: 999px;
}

.calendar-alert-error {
  padding: 0.65rem 0.75rem;
  border-radius: 10px;
  border: 1px solid rgba(248, 113, 113, 0.36);
  background: rgba(248, 113, 113, 0.14);
  color: #fca5a5;
  font-size: 0.9rem;
}

.calendar-actions {
  margin-top: 0.5rem;
}

.calendar-soft-btn,
.calendar-primary-btn,
.calendar-danger-btn {
  border-radius: 10px;
  padding: 0.5rem 0.9rem;
  border: 1px solid rgba(255, 255, 255, 0.12);
  transition:
    border-color 0.2s ease,
    background-color 0.2s ease,
    color 0.2s ease,
    transform 0.2s ease;
}

.calendar-soft-btn {
  background: rgba(15, 31, 53, 0.75);
  color: #a9c0d8;
}

.calendar-soft-btn:hover {
  color: #edf6ff;
  background: rgba(91, 163, 232, 0.12);
  border-color: rgba(91, 163, 232, 0.38);
}

.calendar-primary-btn {
  border-color: rgba(91, 163, 232, 0.45);
  background: linear-gradient(135deg, #377dbd, #59a0e5);
  color: #fff;
}

.calendar-primary-btn:hover {
  filter: brightness(1.08);
  transform: translateY(-1px);
}

.calendar-danger-btn {
  border-color: rgba(248, 113, 113, 0.32);
  background: rgba(248, 113, 113, 0.14);
  color: #fca5a5;
}

.calendar-danger-btn:hover {
  background: rgba(248, 113, 113, 0.2);
}

.modal-backdrop {
  background-color: rgba(0, 0, 0, 0.6) !important;
}

@media (max-width: 768px) {
  .calendar-event-modal {
    padding: 1rem;
    border-radius: 14px;
  }

  .grid.grid-cols-2 {
    grid-template-columns: 1fr;
  }

  .calendar-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
}
</style>

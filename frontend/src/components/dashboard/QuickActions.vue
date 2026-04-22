<template>
  <div class="dashboard-card">
    <div>
      <h2 class="card-title">
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
            d="M13 10V3L4 14h7v7l9-11h-7z"
          />
        </svg>
        Actions rapides
      </h2>

      <div class="grid grid-cols-1 gap-3">
        <!-- Créer une room -->
        <button
          class="btn btn-primary quick-action-btn gap-3"
          @click="$emit('create-room')"
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
              d="M12 4v16m8-8H4"
            />
          </svg>
          <span>Créer une room</span>
        </button>

        <!-- Planifier un événement -->
        <button
          class="btn btn-ghost quick-action-btn gap-3"
          @click="$emit('create-event')"
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
              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <span>Planifier un événement</span>
        </button>

        <!-- Créer une tâche -->
        <button
          class="btn btn-ghost quick-action-btn gap-3"
          @click="$emit('create-task')"
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
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            />
          </svg>
          <span>Créer une tâche</span>
        </button>

        <!-- Inviter des membres / Gestion des utilisateurs (admin seulement) -->
        <button
          v-if="canManageUsers"
          class="btn btn-ghost quick-action-btn gap-3"
          @click="$emit('invite-members')"
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
              d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
            />
          </svg>
          <span>Gestion des utilisateurs</span>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import type { UserRole } from "@/types";
import { isAtLeast } from "@/utils/permissions";

const props = defineProps<{
  userRole?: UserRole;
}>();

const canManageUsers = computed(() =>
  props.userRole ? isAtLeast(props.userRole, "editor") : false,
);

defineEmits<{
  "create-room": [];
  "create-event": [];
  "create-task": [];
  "invite-members": [];
}>();
</script>

<style scoped>
.dashboard-card {
  background-color: #0a1628;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1.5rem;
  color: #e0e0e0;
  transition: all 0.3s ease;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.dashboard-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.dashboard-card h2 {
  color: #ffffff;
  margin-bottom: 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.dashboard-card .grid {
  gap: 0.75rem;
  margin-top: 0.5rem;
}

.quick-action-btn {
  width: 100%;
  justify-content: flex-start;
  padding: 0.8rem 1rem;
  border-color: rgba(255, 255, 255, 0.12) !important;
  background: rgba(5, 13, 26, 0.35) !important;
  color: #e0e0e0 !important;
}

.quick-action-btn:hover {
  background: rgba(26, 58, 82, 0.5) !important;
  color: #ffffff !important;
}

.quick-action-btn.btn-primary {
  background: rgba(26, 58, 82, 0.78) !important;
  border-color: rgba(64, 142, 214, 0.4) !important;
  color: #ffffff !important;
}

.quick-action-btn.btn-primary:hover {
  background: rgba(26, 58, 82, 0.95) !important;
}

.dashboard-card svg {
  flex-shrink: 0;
  opacity: 0.95;
}
</style>

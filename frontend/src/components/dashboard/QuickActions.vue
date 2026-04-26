<template>
  <div class="card">
    <div class="card-header">
      <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
      Actions rapides
    </div>

    <div class="actions-list">
      <button class="action-btn action-primary" @click="$emit('create-room')">
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Créer une room
      </button>
      <button class="action-btn" @click="$emit('create-event')">
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        Planifier un événement
      </button>
      <button class="action-btn" @click="$emit('create-task')">
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
        </svg>
        Créer une tâche
      </button>
      <button v-if="canManageUsers" class="action-btn" @click="$emit('invite-members')">
        <svg class="btn-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
        </svg>
        Gestion des utilisateurs
      </button>
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
.card {
  background: rgba(255, 255, 255, 0.025);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 12px;
  padding: 1.5rem;
  transition: border-color 0.2s;
}
.card:hover { border-color: rgba(255, 255, 255, 0.1); }
.card-header {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: #c8daea;
  margin-bottom: 1.25rem;
}
.header-icon { width: 16px; height: 16px; color: #5ba3e8; flex-shrink: 0; }
.actions-list { display: flex; flex-direction: column; gap: 0.5rem; }
.action-btn {
  display: flex;
  align-items: center;
  gap: 0.625rem;
  width: 100%;
  padding: 0.625rem 0.875rem;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.07);
  border-radius: 8px;
  color: #6b8099;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
  text-align: left;
}
.action-btn:hover {
  background: rgba(255, 255, 255, 0.06);
  border-color: rgba(255, 255, 255, 0.12);
  color: #c8daea;
}
.action-btn.action-primary {
  background: rgba(64, 142, 214, 0.12);
  border-color: rgba(64, 142, 214, 0.25);
  color: #5ba3e8;
}
.action-btn.action-primary:hover {
  background: rgba(64, 142, 214, 0.18);
  border-color: rgba(64, 142, 214, 0.4);
  color: #7ec4f0;
}
.btn-icon { width: 16px; height: 16px; flex-shrink: 0; }
</style>

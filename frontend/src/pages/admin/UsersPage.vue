<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAdminStore } from "@/stores/admin";
import { useAuthStore } from "@/stores/auth";
import { useInvitationStore } from "@/stores/invitation";
import { canAssignRole, isAtLeast } from "@/utils/permissions";
import type { UserRole } from "@/types";

const adminStore = useAdminStore();
const authStore = useAuthStore();
const invitationStore = useInvitationStore();

const roleLabels: Record<UserRole, string> = {
  user: "Utilisateur",
  editor: "Editeur",
  owner: "Proprietaire",
  admin: "Administrateur",
};

const allRoles: UserRole[] = ["user", "editor", "owner", "admin"];

function canChangeToRole(targetRole: UserRole): boolean {
  if (!authStore.user) return false;
  return canAssignRole(authStore.user.role, targetRole);
}

const API_BASE = import.meta.env.VITE_API_URL?.replace("/api", "") || "";

const deleteConfirmId = ref<number | null>(null);
const deleteConfirmName = ref("");
const success = ref<string | null>(null);

// Invitation form
const invitationEmail = ref("");
const invitationSuccess = ref<string | null>(null);

// Entreprise rename
const editingEntreprise = ref(false);
const entrepriseName = ref("");
const entrepriseSuccess = ref<string | null>(null);
const entrepriseError = ref<string | null>(null);

const sortedUsers = computed(() => {
  const roleOrder: Record<string, number> = {
    admin: 0,
    owner: 1,
    editor: 2,
    user: 3,
  };
  return [...adminStore.users].sort((a, b) => {
    const roleA = roleOrder[a.role] ?? 99;
    const roleB = roleOrder[b.role] ?? 99;
    if (roleA !== roleB) return roleA - roleB;
    return a.displayName.localeCompare(b.displayName);
  });
});

const pendingInvitations = computed(() => {
  return invitationStore.invitations.filter((i) => i.status === "pending");
});

onMounted(() => {
  adminStore.fetchUsers();
  invitationStore.fetchInvitations();
  if (authStore.currentEntreprise) {
    entrepriseName.value = authStore.currentEntreprise.name;
  }
});

async function handleRoleChange(userId: number, newRole: string) {
  success.value = null;
  const ok = await adminStore.updateUser(userId, { role: newRole });
  if (ok) {
    success.value = "Rôle mis à jour";
  }
}

function confirmDelete(userId: number, displayName: string) {
  deleteConfirmId.value = userId;
  deleteConfirmName.value = displayName;
  const modal = document.getElementById("delete-modal") as HTMLDialogElement;
  modal?.showModal();
}

async function handleDelete() {
  if (!deleteConfirmId.value) return;
  success.value = null;

  const ok = await adminStore.deleteUser(deleteConfirmId.value);
  if (ok) {
    success.value = `Utilisateur "${deleteConfirmName.value}" supprimé`;
  }

  deleteConfirmId.value = null;
  closeDeleteModal();
}

function closeDeleteModal() {
  const modal = document.getElementById("delete-modal") as HTMLDialogElement;
  modal?.close();
}

async function handleSendInvitation() {
  if (!invitationEmail.value.trim()) return;
  invitationSuccess.value = null;

  const ok = await invitationStore.sendInvitation(invitationEmail.value.trim());
  if (ok) {
    invitationSuccess.value = `Invitation envoyée à ${invitationEmail.value}`;
    invitationEmail.value = "";
  }
}

async function handleCancelInvitation(id: number) {
  invitationSuccess.value = null;
  const ok = await invitationStore.cancelInvitation(id);
  if (ok) {
    invitationSuccess.value = "Invitation annulée";
  }
}

function startEditEntreprise() {
  editingEntreprise.value = true;
  entrepriseName.value = authStore.currentEntreprise?.name || "";
}

function cancelEditEntreprise() {
  editingEntreprise.value = false;
  entrepriseName.value = authStore.currentEntreprise?.name || "";
  entrepriseError.value = null;
}

async function handleRenameEntreprise() {
  if (!entrepriseName.value.trim()) return;
  entrepriseSuccess.value = null;
  entrepriseError.value = null;

  const ok = await authStore.updateEntrepriseName(entrepriseName.value.trim());
  if (ok) {
    entrepriseSuccess.value = "Nom de l'entreprise mis à jour";
    editingEntreprise.value = false;
  } else {
    entrepriseError.value = authStore.error || "Erreur lors de la mise à jour";
  }
}

function getInitials(name: string): string {
  return name.charAt(0).toUpperCase();
}
</script>

<template>
  <div class="enterprise-page">
    <header class="page-header">
      <h1 class="page-title">Gestion de l'entreprise</h1>
      <p class="page-subtitle">
        Pilotez les membres, invitations et parametres de votre organisation.
      </p>
    </header>

    <div v-if="entrepriseSuccess" class="status-banner status-success">
      <span>{{ entrepriseSuccess }}</span>
    </div>
    <div v-if="entrepriseError" class="status-banner status-error">
      <span>{{ entrepriseError }}</span>
    </div>
    <div v-if="invitationSuccess" class="status-banner status-success">
      <span>{{ invitationSuccess }}</span>
    </div>
    <div v-if="invitationStore.error" class="status-banner status-error">
      <span>{{ invitationStore.error }}</span>
    </div>
    <div v-if="success" class="status-banner status-success">
      <span>{{ success }}</span>
    </div>
    <div v-if="adminStore.error" class="status-banner status-error">
      <span>{{ adminStore.error }}</span>
    </div>

    <div class="top-grid">
      <section v-if="authStore.currentEntreprise" class="card section-block">
        <div class="section-head">
          <h2 class="section-title">Nom de l'entreprise</h2>
        </div>

        <div v-if="!editingEntreprise" class="rename-row">
          <span class="company-name">{{
            authStore.currentEntreprise?.name
          }}</span>
          <button class="btn btn-primary" @click="startEditEntreprise">
            Renommer
          </button>
        </div>

        <form
          v-else
          @submit.prevent="handleRenameEntreprise"
          class="rename-form"
        >
          <input
            v-model="entrepriseName"
            type="text"
            class="field-input"
            placeholder="Nouveau nom"
            required
            maxlength="255"
          />
          <div class="btn-inline-actions">
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="authStore.loading"
            >
              <span v-if="authStore.loading" class="inline-spinner"></span>
              <span>Enregistrer</span>
            </button>
            <button
              type="button"
              class="btn btn-ghost"
              @click="cancelEditEntreprise"
            >
              Annuler
            </button>
          </div>
        </form>
      </section>

      <section class="card section-block">
        <div class="section-head">
          <h2 class="section-title">Inviter un utilisateur</h2>
        </div>

        <form @submit.prevent="handleSendInvitation" class="invite-form">
          <input
            v-model="invitationEmail"
            type="email"
            class="field-input"
            placeholder="adresse@email.com"
            required
          />
          <button
            type="submit"
            class="btn btn-primary"
            :disabled="invitationStore.loading"
          >
            <span v-if="invitationStore.loading" class="inline-spinner"></span>
            <span>Inviter</span>
          </button>
        </form>

        <div v-if="pendingInvitations.length > 0" class="pending-block">
          <p class="subheading">Invitations en attente</p>
          <div class="pending-list">
            <article
              v-for="inv in pendingInvitations"
              :key="inv.id"
              class="pending-item"
            >
              <div class="pending-main">
                <p class="pending-email">{{ inv.email }}</p>
                <p class="pending-meta">
                  Envoyee par {{ inv.invitedBy.displayName }} • Expire le
                  {{ new Date(inv.expiresAt).toLocaleDateString("fr-FR") }}
                </p>
              </div>
              <button
                class="btn btn-danger-ghost btn-sm"
                @click="handleCancelInvitation(inv.id)"
                :disabled="invitationStore.loading"
              >
                Annuler
              </button>
            </article>
          </div>
        </div>
      </section>
    </div>

    <section class="card section-block users-block">
      <div class="section-head users-head">
        <h2 class="section-title">Utilisateurs</h2>
        <span class="users-count">{{ sortedUsers.length }} membre(s)</span>
      </div>

      <div
        v-if="adminStore.loading && adminStore.users.length === 0"
        class="loading-state"
      >
        <span class="inline-spinner big"></span>
      </div>

      <div v-else class="users-table-wrap">
        <table class="users-table">
          <thead>
            <tr>
              <th>Utilisateur</th>
              <th>Email</th>
              <th>Equipe</th>
              <th>Role</th>
              <th>Inscrit le</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in sortedUsers" :key="u.id">
              <td>
                <div class="user-cell">
                  <div class="user-avatar">
                    <img
                      v-if="u.avatarUrl"
                      :src="`${API_BASE}${u.avatarUrl}`"
                      :alt="u.displayName"
                    />
                    <div v-else class="avatar-fallback">
                      {{ getInitials(u.displayName) }}
                    </div>
                  </div>
                  <div class="user-main">
                    <p class="user-name">{{ u.displayName }}</p>
                    <span v-if="u.id === authStore.user?.id" class="you-badge"
                      >Vous</span
                    >
                  </div>
                </div>
              </td>
              <td class="email-cell">{{ u.email }}</td>
              <td>{{ u.team?.name || "-" }}</td>
              <td>
                <select
                  class="role-select"
                  :value="u.role"
                  @change="
                    handleRoleChange(
                      u.id,
                      ($event.target as HTMLSelectElement).value,
                    )
                  "
                  :disabled="
                    u.id === authStore.user?.id ||
                    !isAtLeast(authStore.user?.role || 'user', 'owner')
                  "
                >
                  <option
                    v-for="role in allRoles"
                    :key="role"
                    :value="role"
                    :disabled="!canChangeToRole(role)"
                  >
                    {{ roleLabels[role] }}
                  </option>
                </select>
              </td>
              <td>{{ new Date(u.createdAt).toLocaleDateString("fr-FR") }}</td>
              <td>
                <button
                  class="btn btn-danger-ghost btn-sm"
                  @click="confirmDelete(u.id, u.displayName)"
                  :disabled="u.id === authStore.user?.id"
                  title="Supprimer"
                >
                  Supprimer
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div
          v-if="sortedUsers.length === 0 && !adminStore.loading"
          class="empty-state"
        >
          Aucun utilisateur trouve.
        </div>
      </div>
    </section>

    <dialog id="delete-modal" class="custom-dialog">
      <div class="dialog-panel">
        <h3 class="dialog-title">Confirmer la suppression</h3>
        <p class="dialog-text">
          Etes-vous sur de vouloir supprimer l'utilisateur
          <strong>{{ deleteConfirmName }}</strong> ? Cette action est
          irreversible.
        </p>
        <div class="dialog-actions">
          <button
            class="btn btn-ghost"
            @click="closeDeleteModal"
          >
            Annuler
          </button>
          <button
            class="btn btn-danger"
            @click="handleDelete"
            :disabled="adminStore.loading"
          >
            <span v-if="adminStore.loading" class="inline-spinner"></span>
            <span>Supprimer</span>
          </button>
        </div>
      </div>
      <form method="dialog" class="dialog-backdrop">
        <button class="visually-hidden">close</button>
      </form>
    </dialog>
  </div>
</template>

<style scoped>
.enterprise-page {
  max-width: 1200px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 1rem;
}

.page-title {
  margin: 0;
  color: #c8daea;
  font-size: 1.55rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.page-subtitle {
  margin: 0.35rem 0 0;
  color: #6f87a1;
  font-size: 0.9rem;
}

.status-banner {
  border-radius: 10px;
  border: 1px solid;
  padding: 0.65rem 0.85rem;
  font-size: 0.84rem;
  margin-bottom: 0.65rem;
}

.status-success {
  color: #7be3ad;
  background: rgba(111, 221, 159, 0.1);
  border-color: rgba(111, 221, 159, 0.3);
}

.status-error {
  color: #fca5a5;
  background: rgba(248, 113, 113, 0.1);
  border-color: rgba(248, 113, 113, 0.3);
}

.top-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 1rem;
  margin: 1rem 0;
}

.card {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.025);
  padding: 1rem;
}

.section-block {
  display: flex;
  flex-direction: column;
  gap: 0.9rem;
}

.section-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.section-title {
  margin: 0;
  color: #d8e8f7;
  font-size: 1rem;
  font-weight: 600;
}

.company-name {
  color: #d6e7f7;
  font-size: 1.03rem;
  font-weight: 600;
}

.rename-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.75rem;
}

.rename-form,
.invite-form {
  display: flex;
  gap: 0.6rem;
}

.btn-inline-actions {
  display: flex;
  gap: 0.5rem;
}

.field-input,
.role-select {
  width: 100%;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
  color: #d6e8f9;
  font-size: 0.85rem;
  padding: 0.58rem 0.72rem;
  outline: none;
}

.field-input:focus,
.role-select:focus {
  border-color: rgba(91, 163, 232, 0.48);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.16);
}

.pending-block {
  border-top: 1px solid rgba(255, 255, 255, 0.06);
  padding-top: 0.8rem;
}

.subheading {
  margin: 0 0 0.6rem;
  color: #88a2bd;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.pending-list {
  display: grid;
  gap: 0.5rem;
}

.pending-item {
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.055);
  background: rgba(255, 255, 255, 0.03);
  padding: 0.62rem 0.72rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.7rem;
}

.pending-email {
  margin: 0;
  color: #d7e8f8;
  font-size: 0.85rem;
  font-weight: 600;
}

.pending-meta {
  margin: 0.2rem 0 0;
  color: #7f98b1;
  font-size: 0.74rem;
}

.users-block {
  margin-top: 0.2rem;
}

.users-head {
  margin-bottom: 0.2rem;
}

.users-count {
  color: #7e96b2;
  font-size: 0.8rem;
}

.users-table-wrap {
  overflow-x: auto;
}

.users-table {
  width: 100%;
  border-collapse: collapse;
}

.users-table th,
.users-table td {
  padding: 0.7rem 0.55rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  font-size: 0.82rem;
  color: #9bb0c6;
  text-align: left;
  white-space: nowrap;
}

.users-table th {
  color: #6f87a2;
  font-size: 0.7rem;
  letter-spacing: 0.09em;
  text-transform: uppercase;
}

.users-table tbody tr:hover {
  background: rgba(255, 255, 255, 0.02);
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

.user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  overflow: hidden;
  border: 1px solid rgba(91, 163, 232, 0.35);
  background: rgba(91, 163, 232, 0.1);
}

.user-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-fallback {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #d9ecfd;
  font-size: 0.82rem;
  font-weight: 700;
}

.user-main {
  min-width: 0;
}

.user-name {
  margin: 0;
  color: #d4e6f8;
  font-size: 0.85rem;
  font-weight: 600;
}

.you-badge {
  display: inline-flex;
  margin-top: 0.18rem;
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 999px;
  padding: 0.1rem 0.45rem;
  color: #8ba2ba;
  font-size: 0.66rem;
}

.email-cell {
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
}

.loading-state {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 2.8rem 0;
}

.empty-state {
  text-align: center;
  color: #7088a1;
  font-size: 0.85rem;
  padding: 1.3rem 0;
}

.btn {
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.03);
  color: #b2c8dc;
  font-size: 0.8rem;
  font-weight: 600;
  padding: 0.48rem 0.72rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  cursor: pointer;
  transition: 0.18s ease;
}

.btn:hover {
  border-color: rgba(255, 255, 255, 0.2);
  color: #d6e8fa;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn-primary {
  border-color: rgba(91, 163, 232, 0.35);
  background: rgba(91, 163, 232, 0.14);
  color: #d9ecff;
}

.btn-primary:hover {
  border-color: rgba(91, 163, 232, 0.56);
  background: rgba(91, 163, 232, 0.2);
}

.btn-ghost {
  background: transparent;
}

.btn-danger,
.btn-danger-ghost {
  border-color: rgba(248, 113, 113, 0.33);
  color: #f8b4b4;
}

.btn-danger {
  background: rgba(248, 113, 113, 0.14);
}

.btn-danger-ghost {
  background: transparent;
}

.btn-danger:hover,
.btn-danger-ghost:hover {
  border-color: rgba(248, 113, 113, 0.5);
  color: #ffd0d0;
}

.btn-sm {
  font-size: 0.73rem;
  padding: 0.36rem 0.6rem;
}

.custom-dialog {
  border: none;
  background: transparent;
  padding: 0;
  max-width: 92vw;
}

.custom-dialog::backdrop {
  background: rgba(3, 7, 14, 0.72);
  backdrop-filter: blur(3px);
}

.dialog-panel {
  width: min(430px, 92vw);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: #08111f;
  padding: 1rem;
  box-shadow: 0 24px 44px rgba(0, 0, 0, 0.45);
}

.dialog-title {
  margin: 0;
  color: #d9e8f8;
  font-size: 1rem;
}

.dialog-text {
  margin: 0.65rem 0 0;
  color: #8ca4be;
  font-size: 0.86rem;
  line-height: 1.45;
}

.dialog-actions {
  margin-top: 0.95rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.55rem;
}

.dialog-backdrop {
  position: fixed;
  inset: 0;
}

.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}

.inline-spinner {
  width: 14px;
  height: 14px;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, 0.2);
  border-top-color: currentColor;
  animation: spin 0.7s linear infinite;
}

.inline-spinner.big {
  width: 26px;
  height: 26px;
  border-width: 3px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 980px) {
  .top-grid {
    grid-template-columns: 1fr;
  }

  .rename-form,
  .invite-form {
    flex-direction: column;
  }

  .btn-inline-actions {
    width: 100%;
    flex-direction: column;
  }

  .btn,
  .role-select {
    width: 100%;
  }

  .users-count {
    display: none;
  }
}
</style>

<script setup lang="ts">
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter, useRoute } from "vue-router";

const authStore = useAuthStore();
const router = useRouter();
const route = useRoute();
const currentSlug = computed(
  () => route.params.entrepriseSlug as string | undefined,
);

const API_BASE = import.meta.env.VITE_API_URL?.replace("/api", "") || "";

const displayName = ref(authStore.user?.displayName || "");
const email = ref(authStore.user?.email || "");
const editing = ref(false);
const success = ref<string | null>(null);
const avatarInput = ref<HTMLInputElement | null>(null);
const deleteRequested = ref(false);

// Create entreprise modal
const newEntrepriseName = ref("");
const newEntrepriseDomain = ref("");
const creatingEntreprise = ref(false);

function openCreateEntrepriseModal() {
  newEntrepriseName.value = "";
  newEntrepriseDomain.value = "";
  (
    document.getElementById("create-entreprise-modal") as HTMLDialogElement
  )?.showModal();
}

function closeCreateEntrepriseModal() {
  (
    document.getElementById("create-entreprise-modal") as HTMLDialogElement
  )?.close();
}

async function handleCreateEntreprise() {
  if (!newEntrepriseName.value.trim()) return;
  creatingEntreprise.value = true;
  const slug = await authStore.createEntreprise(
    newEntrepriseName.value.trim(),
    newEntrepriseDomain.value.trim() || undefined,
  );
  creatingEntreprise.value = false;
  if (slug) {
    closeCreateEntrepriseModal();
    router.push({ name: "dashboard", params: { entrepriseSlug: slug } });
  }
}

function handleSwitchEntreprise(slug: string) {
  if (slug === currentSlug.value) return;
  router.push({ name: "dashboard", params: { entrepriseSlug: slug } });
}

function openDeleteModal() {
  (
    document.getElementById("delete-account-modal") as HTMLDialogElement
  )?.showModal();
}

function closeDeleteModal() {
  (
    document.getElementById("delete-account-modal") as HTMLDialogElement
  )?.close();
}

const avatarUrl = computed(() => {
  if (authStore.user?.avatarUrl) {
    return `${API_BASE}${authStore.user.avatarUrl}`;
  }
  return null;
});

const initials = computed(() => {
  return authStore.user?.displayName?.charAt(0)?.toUpperCase() || "U";
});

const roleLabel = computed(() => {
  if (authStore.user?.role === "admin") return "Administrateur";
  if (authStore.user?.role === "owner") return "Proprietaire";
  if (authStore.user?.role === "manager") return "Manager";
  return "Utilisateur";
});

const roleBadgeClass = computed(() => {
  if (authStore.user?.role === "admin") return "badge-admin";
  if (authStore.user?.role === "owner") return "badge-owner";
  if (authStore.user?.role === "manager") return "badge-manager";
  return "badge-user";
});

function startEditing() {
  displayName.value = authStore.user?.displayName || "";
  email.value = authStore.user?.email || "";
  editing.value = true;
  success.value = null;
  authStore.error = null;
}

function cancelEditing() {
  editing.value = false;
  authStore.error = null;
}

async function saveProfile() {
  success.value = null;
  const data: Record<string, string> = {};

  if (displayName.value !== authStore.user?.displayName) {
    data.displayName = displayName.value;
  }
  if (email.value !== authStore.user?.email) {
    data.email = email.value;
  }

  if (Object.keys(data).length === 0) {
    editing.value = false;
    return;
  }

  const ok = await authStore.updateProfile(data);
  if (ok) {
    editing.value = false;
    success.value = "Profil mis à jour avec succès";
  }
}

function triggerAvatarUpload() {
  avatarInput.value?.click();
}

async function handleAvatarChange(event: Event) {
  const input = event.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;

  success.value = null;
  const ok = await authStore.uploadAvatar(file);
  if (ok) {
    success.value = "Avatar mis à jour";
  }

  // Reset input
  input.value = "";
}

async function handleRequestDelete() {
  success.value = null;
  const ok = await authStore.requestDeleteAccount();
  if (ok) {
    deleteRequested.value = true;
    success.value =
      "Un email de confirmation a été envoyé. Vérifiez votre boîte de réception.";
  }
}

async function handleDeleteAvatar() {
  success.value = null;
  const ok = await authStore.deleteAvatar();
  if (ok) {
    success.value = "Avatar supprimé";
  }
}
</script>

<template>
  <div class="profile-page">
    <header class="profile-header reveal reveal-1">
      <h1 class="profile-title">Mon profil</h1>
      <p class="profile-subtitle">
        Gerez votre identite, vos entreprises et la securite de votre compte.
      </p>
    </header>

    <div v-if="success" class="status-banner status-success reveal reveal-2">
      <span>{{ success }}</span>
    </div>

    <div
      v-if="authStore.error"
      class="status-banner status-error reveal reveal-2"
    >
      <span>{{ authStore.error }}</span>
    </div>

    <div class="profile-grid">
      <section class="card panel-identity reveal reveal-2">
        <div class="avatar-shell">
          <img
            v-if="avatarUrl"
            :src="avatarUrl"
            alt="Avatar"
            class="avatar-image-large"
          />
          <div v-else class="avatar-fallback-large">{{ initials }}</div>
        </div>

        <p class="identity-name">
          {{ authStore.user?.displayName || "Utilisateur" }}
        </p>
        <p class="identity-email">{{ authStore.user?.email }}</p>

        <div class="identity-meta">
          <span class="role-badge" :class="roleBadgeClass">{{
            roleLabel
          }}</span>
          <span v-if="authStore.currentEntreprise" class="meta-chip">{{
            authStore.currentEntreprise.name
          }}</span>
          <span v-if="authStore.user?.team" class="meta-chip"
            >Equipe: {{ authStore.user.team.name }}</span
          >
        </div>

        <div class="avatar-actions">
          <button
            class="btn btn-primary"
            @click="triggerAvatarUpload"
            :disabled="authStore.loading"
          >
            {{ avatarUrl ? "Changer l'avatar" : "Ajouter un avatar" }}
          </button>
          <button
            v-if="avatarUrl"
            class="btn btn-danger-ghost"
            @click="handleDeleteAvatar"
            :disabled="authStore.loading"
          >
            Supprimer
          </button>
        </div>

        <input
          ref="avatarInput"
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          class="visually-hidden"
          @change="handleAvatarChange"
        />

        <p class="identity-help">
          Formats supportes: JPEG, PNG, WebP, GIF. Max 2 Mo.
        </p>
      </section>

      <section class="card panel-account reveal reveal-3">
        <div class="panel-head">
          <h2 class="panel-title">Informations du compte</h2>
          <button v-if="!editing" class="btn btn-primary" @click="startEditing">
            Modifier
          </button>
        </div>

        <div v-if="!editing" class="info-grid">
          <div class="info-item">
            <p class="info-label">Nom d'affichage</p>
            <p class="info-value">{{ authStore.user?.displayName || "-" }}</p>
          </div>
          <div class="info-item">
            <p class="info-label">Email</p>
            <p class="info-value">{{ authStore.user?.email || "-" }}</p>
          </div>
          <div class="info-item">
            <p class="info-label">Role</p>
            <p class="info-value">{{ roleLabel }}</p>
          </div>
          <div class="info-item" v-if="authStore.currentEntreprise">
            <p class="info-label">Entreprise active</p>
            <p class="info-value">{{ authStore.currentEntreprise.name }}</p>
          </div>
        </div>

        <form v-else @submit.prevent="saveProfile" class="form-grid">
          <label class="field">
            <span class="field-label">Nom d'affichage</span>
            <input
              v-model="displayName"
              type="text"
              class="field-input"
              maxlength="255"
              required
            />
          </label>

          <label class="field">
            <span class="field-label">Email</span>
            <input v-model="email" type="email" class="field-input" required />
          </label>

          <div class="form-actions">
            <button type="button" class="btn btn-ghost" @click="cancelEditing">
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="authStore.loading"
            >
              <span v-if="authStore.loading" class="inline-spinner"></span>
              <span>Sauvegarder</span>
            </button>
          </div>
        </form>
      </section>

      <section class="card panel-enterprises reveal reveal-4">
        <div class="panel-head">
          <h2 class="panel-title">Mes entreprises</h2>
          <button class="btn btn-primary" @click="openCreateEntrepriseModal">
            Creer une entreprise
          </button>
        </div>

        <div v-if="!authStore.user?.entreprises?.length" class="empty-state">
          Vous n'appartenez a aucune entreprise.
        </div>

        <ul v-else class="entreprise-list">
          <li
            v-for="ue in authStore.user.entreprises"
            :key="ue.id"
            class="entreprise-row"
          >
            <div class="entreprise-meta">
              <p class="entreprise-name">{{ ue.name }}</p>
              <p class="entreprise-role">{{ ue.role }}</p>
            </div>
            <div class="entreprise-actions">
              <span v-if="ue.slug === currentSlug" class="active-chip"
                >Active</span
              >
              <button
                v-else
                class="btn btn-row"
                @click="handleSwitchEntreprise(ue.slug)"
              >
                Ouvrir
              </button>
            </div>
          </li>
        </ul>
      </section>

      <section class="card panel-danger reveal reveal-5">
        <h2 class="panel-title danger-title">Zone dangereuse</h2>

        <div v-if="deleteRequested" class="status-banner status-info">
          <span>
            Un email de confirmation a ete envoye a votre adresse. Cliquez sur
            le lien recu pour finaliser la suppression.
          </span>
        </div>

        <div v-else>
          <p class="danger-text">
            La suppression de votre compte est irreversible. Toutes vos donnees
            seront supprimees apres validation par email.
          </p>
          <button class="btn btn-danger-ghost" @click="openDeleteModal()">
            Supprimer mon compte
          </button>
        </div>
      </section>
    </div>

    <dialog id="create-entreprise-modal" class="profile-dialog">
      <div class="dialog-panel">
        <h3 class="dialog-title">Creer une entreprise</h3>
        <form @submit.prevent="handleCreateEntreprise" class="form-grid">
          <label class="field">
            <span class="field-label">Nom de l'entreprise *</span>
            <input
              v-model="newEntrepriseName"
              type="text"
              class="field-input"
              placeholder="Acme Corp"
              minlength="2"
              maxlength="255"
              required
            />
          </label>

          <label class="field">
            <span class="field-label">Domaine (optionnel)</span>
            <input
              v-model="newEntrepriseDomain"
              type="text"
              class="field-input"
              placeholder="company.com"
              maxlength="255"
            />
          </label>

          <div class="form-actions">
            <button
              type="button"
              class="btn btn-ghost"
              @click="closeCreateEntrepriseModal"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="creatingEntreprise || !newEntrepriseName.trim()"
            >
              <span v-if="creatingEntreprise" class="inline-spinner"></span>
              <span>Creer</span>
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="dialog-close-zone">
        <button class="visually-hidden">close</button>
      </form>
    </dialog>

    <dialog id="delete-account-modal" class="profile-dialog">
      <div class="dialog-panel">
        <h3 class="dialog-title danger-title">Supprimer votre compte</h3>
        <p class="dialog-text">
          Vous allez recevoir un email de confirmation. Vous devrez cliquer sur
          le lien dans cet email pour finaliser la suppression de votre compte.
        </p>

        <div class="form-actions">
          <button type="button" class="btn btn-ghost" @click="closeDeleteModal">
            Annuler
          </button>
          <button
            class="btn btn-danger"
            :disabled="authStore.loading"
            @click="
              handleRequestDelete();
              closeDeleteModal();
            "
          >
            <span v-if="authStore.loading" class="inline-spinner"></span>
            <span>Envoyer l'email de confirmation</span>
          </button>
        </div>
      </div>
      <form method="dialog" class="dialog-close-zone">
        <button class="visually-hidden">close</button>
      </form>
    </dialog>
  </div>
</template>

<style scoped>
.profile-page {
  max-width: 1200px;
  margin: 0 auto;
}

.profile-header {
  margin-bottom: 1.25rem;
}

.profile-title {
  margin: 0;
  color: #c8daea;
  font-size: 1.55rem;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.profile-subtitle {
  margin: 0.35rem 0 0;
  color: #6f87a1;
  font-size: 0.9rem;
}

.status-banner {
  border-radius: 10px;
  border: 1px solid;
  padding: 0.7rem 0.85rem;
  font-size: 0.86rem;
  margin-bottom: 1rem;
  animation: fade-in-up 0.45s ease both;
}

.status-success {
  color: #7be3ad;
  background: rgba(111, 221, 159, 0.1);
  border-color: rgba(111, 221, 159, 0.28);
}

.status-error {
  color: #fca5a5;
  background: rgba(248, 113, 113, 0.1);
  border-color: rgba(248, 113, 113, 0.3);
}

.status-info {
  color: #9fd0ff;
  background: rgba(91, 163, 232, 0.1);
  border-color: rgba(91, 163, 232, 0.32);
  margin: 0;
}

.profile-grid {
  display: grid;
  grid-template-columns: minmax(260px, 320px) minmax(0, 1fr);
  gap: 1rem;
}

.card {
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.025);
  padding: 1rem;
  transition:
    transform 0.22s ease,
    border-color 0.22s ease,
    box-shadow 0.22s ease;
}

.card:hover {
  transform: translateY(-2px);
  border-color: rgba(91, 163, 232, 0.2);
  box-shadow: 0 10px 28px rgba(0, 0, 0, 0.25);
}

.panel-identity {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.avatar-shell {
  width: 94px;
  height: 94px;
  border-radius: 50%;
  border: 1px solid rgba(91, 163, 232, 0.45);
  background: rgba(91, 163, 232, 0.12);
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.avatar-image-large {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.avatar-fallback-large {
  color: #d9ebfb;
  font-size: 2rem;
  font-weight: 700;
}

.identity-name {
  margin: 0.8rem 0 0;
  color: #d8e8f8;
  font-size: 1rem;
  font-weight: 600;
}

.identity-email {
  margin: 0.25rem 0 0;
  color: #7e96b1;
  font-size: 0.8rem;
  word-break: break-all;
}

.identity-meta {
  width: 100%;
  margin-top: 0.8rem;
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 0.45rem;
}

.role-badge,
.meta-chip,
.active-chip {
  border-radius: 999px;
  padding: 0.2rem 0.6rem;
  font-size: 0.7rem;
  border: 1px solid;
}

.badge-admin {
  color: #9fd0ff;
  border-color: rgba(91, 163, 232, 0.4);
  background: rgba(91, 163, 232, 0.12);
}

.badge-owner {
  color: #c9b4ff;
  border-color: rgba(192, 132, 252, 0.35);
  background: rgba(192, 132, 252, 0.14);
}

.badge-manager {
  color: #f7c27a;
  border-color: rgba(240, 162, 62, 0.35);
  background: rgba(240, 162, 62, 0.12);
}

.badge-user {
  color: #a8bfd5;
  border-color: rgba(142, 164, 190, 0.28);
  background: rgba(142, 164, 190, 0.12);
}

.meta-chip {
  color: #9ab2cb;
  border-color: rgba(255, 255, 255, 0.1);
  background: rgba(255, 255, 255, 0.03);
}

.avatar-actions {
  margin-top: 0.9rem;
  width: 100%;
  display: grid;
  gap: 0.45rem;
}

.identity-help {
  margin: 0.65rem 0 0;
  color: #5f748a;
  font-size: 0.72rem;
}

.panel-account,
.panel-enterprises,
.panel-danger {
  display: flex;
  flex-direction: column;
}

.panel-enterprises,
.panel-danger {
  grid-column: 1 / -1;
}

.panel-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.7rem;
  margin-bottom: 0.9rem;
}

.panel-title {
  margin: 0;
  color: #d8e8f7;
  font-size: 1rem;
  font-weight: 600;
}

.danger-title {
  color: #fca5a5;
}

.info-grid {
  display: grid;
  gap: 0.65rem;
}

.info-item {
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.055);
  background: rgba(255, 255, 255, 0.03);
  padding: 0.7rem 0.8rem;
}

.info-label {
  margin: 0;
  color: #6f87a1;
  font-size: 0.72rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.info-value {
  margin: 0.25rem 0 0;
  color: #d5e6f7;
  font-size: 0.92rem;
  font-weight: 500;
  word-break: break-word;
}

.form-grid {
  display: grid;
  gap: 0.75rem;
}

.field {
  display: grid;
  gap: 0.35rem;
}

.field-label {
  color: #85a0bc;
  font-size: 0.76rem;
}

.field-input {
  width: 100%;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: rgba(255, 255, 255, 0.03);
  color: #d7e8f9;
  font-size: 0.88rem;
  padding: 0.58rem 0.72rem;
  outline: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.field-input:focus {
  border-color: rgba(91, 163, 232, 0.45);
  box-shadow: 0 0 0 3px rgba(91, 163, 232, 0.15);
}

.form-actions {
  margin-top: 0.15rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.55rem;
  flex-wrap: wrap;
}

.entreprise-list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 0.55rem;
}

.entreprise-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 0.8rem;
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.06);
  background: rgba(255, 255, 255, 0.03);
  padding: 0.65rem 0.8rem;
}

.entreprise-meta {
  min-width: 0;
}

.entreprise-name {
  margin: 0;
  color: #d3e5f7;
  font-size: 0.9rem;
  font-weight: 600;
}

.entreprise-role {
  margin: 0.18rem 0 0;
  color: #7f98b3;
  font-size: 0.75rem;
}

.entreprise-actions {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.active-chip {
  color: #7be3ad;
  border-color: rgba(111, 221, 159, 0.35);
  background: rgba(111, 221, 159, 0.1);
}

.empty-state {
  border-radius: 10px;
  border: 1px dashed rgba(255, 255, 255, 0.12);
  color: #7790ab;
  font-size: 0.85rem;
  padding: 0.8rem;
}

.danger-text {
  margin: 0;
  color: #95abc3;
  font-size: 0.9rem;
  line-height: 1.5;
}

.btn {
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 9px;
  background: rgba(255, 255, 255, 0.03);
  color: #b2c8dc;
  font-size: 0.82rem;
  font-weight: 600;
  padding: 0.5rem 0.75rem;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.38rem;
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
  border-color: rgba(91, 163, 232, 0.58);
  background: rgba(91, 163, 232, 0.2);
}

.btn-ghost {
  background: transparent;
}

.btn-row {
  padding: 0.4rem 0.62rem;
  font-size: 0.75rem;
}

.btn-danger,
.btn-danger-ghost {
  border-color: rgba(248, 113, 113, 0.32);
  color: #f8b4b4;
}

.btn-danger {
  background: rgba(248, 113, 113, 0.13);
}

.btn-danger-ghost {
  background: transparent;
}

.btn-danger:hover,
.btn-danger-ghost:hover {
  border-color: rgba(248, 113, 113, 0.5);
  color: #ffd0d0;
}

.profile-dialog {
  border: none;
  background: transparent;
  padding: 0;
  max-width: 92vw;
}

.profile-dialog::backdrop {
  background: rgba(3, 7, 14, 0.72);
  backdrop-filter: blur(3px);
}

.dialog-panel {
  width: min(440px, 92vw);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.09);
  background: #08111f;
  padding: 1rem;
  box-shadow: 0 28px 50px rgba(0, 0, 0, 0.45);
}

.profile-dialog[open] .dialog-panel {
  animation: dialog-pop 0.22s ease-out;
}

.dialog-title {
  margin: 0 0 0.8rem;
  color: #d8e8f8;
  font-size: 1rem;
  font-weight: 600;
}

.dialog-text {
  margin: 0;
  color: #8ca4be;
  font-size: 0.86rem;
  line-height: 1.5;
}

.dialog-close-zone {
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

.reveal {
  animation: fade-in-up 0.5s ease both;
}

.reveal-1 {
  animation-delay: 0.04s;
}

.reveal-2 {
  animation-delay: 0.1s;
}

.reveal-3 {
  animation-delay: 0.16s;
}

.reveal-4 {
  animation-delay: 0.22s;
}

.reveal-5 {
  animation-delay: 0.28s;
}

@keyframes fade-in-up {
  from {
    opacity: 0;
    transform: translateY(8px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes dialog-pop {
  from {
    opacity: 0;
    transform: translateY(8px) scale(0.985);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 960px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }

  .panel-enterprises,
  .panel-danger {
    grid-column: auto;
  }

  .panel-head {
    flex-direction: column;
    align-items: flex-start;
  }

  .btn {
    width: 100%;
  }

  .form-actions {
    width: 100%;
  }
}
</style>

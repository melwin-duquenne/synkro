<script setup lang="ts">
import { ref, computed } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { useRouter, useRoute } from 'vue-router'

const authStore = useAuthStore()
const router = useRouter()
const route = useRoute()
const currentSlug = computed(() => route.params.entrepriseSlug as string | undefined)

const API_BASE = import.meta.env.VITE_API_URL?.replace("/api", "") || "";

const displayName = ref(authStore.user?.displayName || '')
const email = ref(authStore.user?.email || '')
const editing = ref(false)
const success = ref<string | null>(null)
const avatarInput = ref<HTMLInputElement | null>(null)
const deleteRequested = ref(false)

// Create entreprise modal
const newEntrepriseName = ref('')
const newEntrepriseDomain = ref('')
const creatingEntreprise = ref(false)

function openCreateEntrepriseModal() {
  newEntrepriseName.value = ''
  newEntrepriseDomain.value = ''
  ;(document.getElementById('create-entreprise-modal') as HTMLDialogElement)?.showModal()
}

function closeCreateEntrepriseModal() {
  ;(document.getElementById('create-entreprise-modal') as HTMLDialogElement)?.close()
}

async function handleCreateEntreprise() {
  if (!newEntrepriseName.value.trim()) return
  creatingEntreprise.value = true
  const slug = await authStore.createEntreprise(newEntrepriseName.value.trim(), newEntrepriseDomain.value.trim() || undefined)
  creatingEntreprise.value = false
  if (slug) {
    closeCreateEntrepriseModal()
    router.push({ name: 'dashboard', params: { entrepriseSlug: slug } })
  }
}

function handleSwitchEntreprise(slug: string) {
  if (slug === currentSlug.value) return
  router.push({ name: 'dashboard', params: { entrepriseSlug: slug } })
}

function openDeleteModal() {
  ;(document.getElementById('delete-account-modal') as HTMLDialogElement)?.showModal()
}

function closeDeleteModal() {
  ;(document.getElementById('delete-account-modal') as HTMLDialogElement)?.close()
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
  <div class="max-w-2xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Mon profil</h1>

    <div v-if="success" class="alert alert-success mb-4">
      <span>{{ success }}</span>
    </div>

    <div v-if="authStore.error" class="alert alert-error mb-4">
      <span>{{ authStore.error }}</span>
    </div>

    <!-- Avatar Section -->
    <div class="card bg-base-100 shadow-xl mb-6">
      <div class="card-body items-center text-center">
        <div class="avatar mb-4">
          <div
            class="w-24 rounded-full ring ring-primary ring-offset-base-100 ring-offset-2"
          >
            <img v-if="avatarUrl" :src="avatarUrl" alt="Avatar" />
            <div
              v-else
              class="bg-primary text-primary-content w-full h-full flex items-center justify-center text-3xl"
            >
              {{ initials }}
            </div>
          </div>
        </div>

        <div class="flex gap-2">
          <button
            class="btn btn-primary btn-sm bg-white text-black border-0"
            @click="triggerAvatarUpload"
            :disabled="authStore.loading"
          >
            {{ avatarUrl ? "Changer l'avatar" : "Ajouter un avatar" }}
          </button>
          <button
            v-if="avatarUrl"
            class="btn btn-error btn-sm btn-outline bg-white text-black border-0"
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
          class="hidden"
          @change="handleAvatarChange"
        />

        <p class="text-xs text-base-content/50 mt-2">
          JPEG, PNG, WebP ou GIF. Max 2 Mo.
        </p>
      </div>
    </div>

    <!-- Profile Info Section -->
    <div class="card bg-base-100 shadow-xl">
      <div class="card-body">
        <h2 class="card-title mb-4">Informations</h2>

        <div v-if="!editing" class="space-y-4">
          <div>
            <label class="label"
              ><span class="label-text font-semibold"
                >Nom d'affichage</span
              ></label
            >
            <p class="text-lg">{{ authStore.user?.displayName }}</p>
          </div>

          <div>
            <label class="label"
              ><span class="label-text font-semibold">Email</span></label
            >
            <p class="text-lg">{{ authStore.user?.email }}</p>
          </div>

          <div>
            <label class="label"
              ><span class="label-text font-semibold">Rôle</span></label
            >
            <span
              class="badge"
              :class="
                authStore.user?.role === 'admin'
                  ? 'badge-primary'
                  : 'badge-ghost'
              "
            >
              {{
                authStore.user?.role === "admin"
                  ? "Administrateur"
                  : "Utilisateur"
              }}
            </span>
          </div>

          <div v-if="authStore.currentEntreprise">
            <label class="label"><span class="label-text font-semibold">Entreprise active</span></label>
            <p class="text-lg">{{ authStore.currentEntreprise.name }}</p>
          </div>

          <div v-if="authStore.user?.team">
            <label class="label"
              ><span class="label-text font-semibold">Équipe</span></label
            >
            <p class="text-lg">{{ authStore.user.team.name }}</p>
          </div>

          <div class="card-actions justify-end mt-4">
            <button
              class="btn btn-primary bg-white text-black border-0"
              @click="startEditing"
            >
              Modifier
            </button>
          </div>
        </div>

        <form v-else @submit.prevent="saveProfile" class="space-y-4">
          <div class="form-control">
            <label class="label"
              ><span class="label-text">Nom d'affichage</span></label
            >
            <input
              v-model="displayName"
              type="text"
              class="input input-bordered w-full"
              maxlength="255"
              required
            />
          </div>

          <div class="form-control">
            <label class="label"><span class="label-text">Email</span></label>
            <input
              v-model="email"
              type="email"
              class="input input-bordered w-full"
              required
            />
          </div>

          <div class="card-actions justify-end mt-4 gap-2">
            <button
              type="button"
              class="btn btn-ghost bg-white text-black border-0"
              @click="cancelEditing"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="btn btn-primary"
              :disabled="authStore.loading"
            >
              <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
              Sauvegarder
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Mes entreprises -->
    <div class="card bg-base-100 shadow-xl mt-6">
      <div class="card-body">
        <div class="flex justify-between items-center mb-4">
          <h2 class="card-title">Mes entreprises</h2>
          <button class="btn btn-primary btn-sm" @click="openCreateEntrepriseModal">+ Créer une entreprise</button>
        </div>

        <div v-if="!authStore.user?.entreprises?.length" class="text-base-content/60 text-sm">
          Vous n'appartenez à aucune entreprise.
        </div>

        <ul v-else class="space-y-2">
          <li
            v-for="ue in authStore.user.entreprises"
            :key="ue.id"
            class="flex items-center justify-between p-3 rounded-lg bg-base-200"
          >
            <div class="flex items-center gap-3">
              <span class="font-medium">{{ ue.name }}</span>
              <span class="badge badge-sm badge-ghost">{{ ue.role }}</span>
              <span v-if="ue.slug === currentSlug" class="badge badge-sm badge-success">Active</span>
            </div>
            <button
              v-if="ue.slug !== currentSlug"
              class="btn btn-xs btn-outline"
              @click="handleSwitchEntreprise(ue.slug)"
            >
              Aller
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Create Entreprise Modal -->
    <dialog id="create-entreprise-modal" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg mb-4">Créer une entreprise</h3>
        <form @submit.prevent="handleCreateEntreprise" class="space-y-4">
          <div class="form-control">
            <label class="label"><span class="label-text">Nom de l'entreprise <span class="text-error">*</span></span></label>
            <input
              v-model="newEntrepriseName"
              type="text"
              class="input input-bordered w-full"
              placeholder="Acme Corp"
              minlength="2"
              maxlength="255"
              required
            />
          </div>
          <div class="form-control">
            <label class="label">
              <span class="label-text">Domaine</span>
              <span class="label-text-alt text-base-content/50">optionnel</span>
            </label>
            <input
              v-model="newEntrepriseDomain"
              type="text"
              class="input input-bordered w-full"
              placeholder="company.com"
              maxlength="255"
            />
          </div>
          <div class="modal-action">
            <button type="button" class="btn btn-ghost" @click="closeCreateEntrepriseModal">Annuler</button>
            <button type="submit" class="btn btn-primary" :disabled="creatingEntreprise || !newEntrepriseName.trim()">
              <span v-if="creatingEntreprise" class="loading loading-spinner loading-sm"></span>
              Créer
            </button>
          </div>
        </form>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button>close</button>
      </form>
    </dialog>

    <!-- Danger Zone -->
    <div class="card bg-base-100 shadow-xl mt-6 border border-error/30">
      <div class="card-body">
        <h2 class="card-title text-error mb-4">Zone dangereuse</h2>

        <div v-if="deleteRequested" class="alert alert-info">
          <span
            >Un email de confirmation a été envoyé à votre adresse. Cliquez sur
            le lien dans l'email pour confirmer la suppression.</span
          >
        </div>

        <div v-else>
          <p class="text-base-content/70 mb-4">
            La suppression de votre compte est irréversible. Toutes vos données
            seront définitivement supprimées. Un email de confirmation vous sera
            envoyé pour vérifier votre identité.
          </p>

          <button
            class="btn btn-error btn-outline"
            @click="openDeleteModal()"
          >
            Supprimer mon compte
          </button>
        </div>
      </div>
    </div>

    <!-- Delete Account Modal -->
    <dialog id="delete-account-modal" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg text-error">Supprimer votre compte</h3>
        <p class="py-4">
          Vous allez recevoir un email de confirmation. Vous devrez cliquer sur
          le lien dans cet email pour finaliser la suppression de votre compte.
        </p>
        <div class="modal-action">
          <form method="dialog">
            <button class="btn btn-ghost bg-white text-black border-0">
              Annuler
            </button>
          </form>
          <button
            class="btn btn-error"
            :disabled="authStore.loading"
            @click="handleRequestDelete(); closeDeleteModal()"
          >
            <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
            Envoyer l'email de confirmation
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button>close</button>
      </form>
    </dialog>
  </div>
</template>

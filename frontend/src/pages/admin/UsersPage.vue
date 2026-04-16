<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { useAuthStore } from '@/stores/auth'
import { useInvitationStore } from '@/stores/invitation'
import { canAssignRole, isAtLeast } from '@/utils/permissions'
import type { UserRole } from '@/types'

const adminStore = useAdminStore()
const authStore = useAuthStore()
const invitationStore = useInvitationStore()

const roleLabels: Record<UserRole, string> = {
  user: 'Utilisateur',
  editor: 'Editeur',
  owner: 'Proprietaire',
  admin: 'Administrateur'
}

const allRoles: UserRole[] = ['user', 'editor', 'owner', 'admin']

function canChangeToRole(targetRole: UserRole): boolean {
  if (!authStore.user) return false
  return canAssignRole(authStore.user.role, targetRole)
}

const API_BASE = import.meta.env.VITE_API_URL?.replace('/api', '') || ''

const deleteConfirmId = ref<number | null>(null)
const deleteConfirmName = ref('')
const success = ref<string | null>(null)

// Invitation form
const invitationEmail = ref('')
const invitationSuccess = ref<string | null>(null)

// Entreprise rename
const editingEntreprise = ref(false)
const entrepriseName = ref('')
const entrepriseSuccess = ref<string | null>(null)
const entrepriseError = ref<string | null>(null)

const sortedUsers = computed(() => {
  const roleOrder: Record<string, number> = { admin: 0, owner: 1, editor: 2, user: 3 }
  return [...adminStore.users].sort((a, b) => {
    const roleA = roleOrder[a.role] ?? 99
    const roleB = roleOrder[b.role] ?? 99
    if (roleA !== roleB) return roleA - roleB
    return a.displayName.localeCompare(b.displayName)
  })
})

const pendingInvitations = computed(() => {
  return invitationStore.invitations.filter(i => i.status === 'pending')
})

onMounted(() => {
  adminStore.fetchUsers()
  invitationStore.fetchInvitations()
  if (authStore.currentEntreprise) {
    entrepriseName.value = authStore.currentEntreprise.name
  }
})

async function handleRoleChange(userId: number, newRole: string) {
  success.value = null
  const ok = await adminStore.updateUser(userId, { role: newRole })
  if (ok) {
    success.value = 'Rôle mis à jour'
  }
}

function confirmDelete(userId: number, displayName: string) {
  deleteConfirmId.value = userId
  deleteConfirmName.value = displayName
  const modal = document.getElementById('delete-modal') as HTMLDialogElement
  modal?.showModal()
}

async function handleDelete() {
  if (!deleteConfirmId.value) return
  success.value = null

  const ok = await adminStore.deleteUser(deleteConfirmId.value)
  if (ok) {
    success.value = `Utilisateur "${deleteConfirmName.value}" supprimé`
  }

  deleteConfirmId.value = null
  const modal = document.getElementById('delete-modal') as HTMLDialogElement
  modal?.close()
}

async function handleSendInvitation() {
  if (!invitationEmail.value.trim()) return
  invitationSuccess.value = null

  const ok = await invitationStore.sendInvitation(invitationEmail.value.trim())
  if (ok) {
    invitationSuccess.value = `Invitation envoyée à ${invitationEmail.value}`
    invitationEmail.value = ''
  }
}

async function handleCancelInvitation(id: number) {
  invitationSuccess.value = null
  const ok = await invitationStore.cancelInvitation(id)
  if (ok) {
    invitationSuccess.value = 'Invitation annulée'
  }
}

function startEditEntreprise() {
  editingEntreprise.value = true
  entrepriseName.value = authStore.currentEntreprise?.name || ''
}

function cancelEditEntreprise() {
  editingEntreprise.value = false
  entrepriseName.value = authStore.currentEntreprise?.name || ''
  entrepriseError.value = null
}

async function handleRenameEntreprise() {
  if (!entrepriseName.value.trim()) return
  entrepriseSuccess.value = null
  entrepriseError.value = null

  const ok = await authStore.updateEntrepriseName(entrepriseName.value.trim())
  if (ok) {
    entrepriseSuccess.value = 'Nom de l\'entreprise mis à jour'
    editingEntreprise.value = false
  } else {
    entrepriseError.value = authStore.error || 'Erreur lors de la mise à jour'
  }
}

function getInitials(name: string): string {
  return name.charAt(0).toUpperCase()
}
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Gestion de l'entreprise</h1>

    <!-- Entreprise name section -->
    <div v-if="authStore.currentEntreprise" class="card bg-base-100 shadow-xl mb-6">
      <div class="card-body">
        <h2 class="card-title text-lg">Nom de l'entreprise</h2>

        <div v-if="entrepriseSuccess" class="alert alert-success mt-2">
          <span>{{ entrepriseSuccess }}</span>
        </div>
        <div v-if="entrepriseError" class="alert alert-error mt-2">
          <span>{{ entrepriseError }}</span>
        </div>

        <div v-if="!editingEntreprise" class="flex items-center gap-4 mt-2">
          <span class="text-lg font-semibold">{{ authStore.currentEntreprise?.name }}</span>
          <button class="btn btn-sm btn-outline" @click="startEditEntreprise">
            Renommer
          </button>
        </div>

        <form v-else @submit.prevent="handleRenameEntreprise" class="flex items-end gap-3 mt-2">
          <div class="form-control flex-1">
            <input
              v-model="entrepriseName"
              type="text"
              class="input input-bordered"
              placeholder="Nouveau nom"
              required
              maxlength="255"
            />
          </div>
          <button type="submit" class="btn btn-primary" :disabled="authStore.loading">
            <span v-if="authStore.loading" class="loading loading-spinner loading-sm"></span>
            Enregistrer
          </button>
          <button type="button" class="btn btn-ghost" @click="cancelEditEntreprise">
            Annuler
          </button>
        </form>
      </div>
    </div>

    <!-- Invitations section -->
    <div class="card bg-base-100 shadow-xl mb-6">
      <div class="card-body">
        <h2 class="card-title text-lg">Inviter un utilisateur</h2>

        <div v-if="invitationSuccess" class="alert alert-success mt-2">
          <span>{{ invitationSuccess }}</span>
        </div>
        <div v-if="invitationStore.error" class="alert alert-error mt-2">
          <span>{{ invitationStore.error }}</span>
        </div>

        <form @submit.prevent="handleSendInvitation" class="flex items-end gap-3 mt-2">
          <div class="form-control flex-1">
            <input
              v-model="invitationEmail"
              type="email"
              class="input input-bordered"
              placeholder="adresse@email.com"
              required
            />
          </div>
          <button type="submit" class="btn btn-primary" :disabled="invitationStore.loading">
            <span v-if="invitationStore.loading" class="loading loading-spinner loading-sm"></span>
            Inviter
          </button>
        </form>

        <!-- Pending invitations list -->
        <div v-if="pendingInvitations.length > 0" class="mt-4">
          <h3 class="font-semibold mb-2">Invitations en attente</h3>
          <div class="overflow-x-auto">
            <table class="table table-sm">
              <thead>
                <tr>
                  <th>Email</th>
                  <th>Envoyée par</th>
                  <th>Expire le</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="inv in pendingInvitations" :key="inv.id">
                  <td>{{ inv.email }}</td>
                  <td>{{ inv.invitedBy.displayName }}</td>
                  <td>{{ new Date(inv.expiresAt).toLocaleDateString('fr-FR') }}</td>
                  <td>
                    <button
                      class="btn btn-error btn-xs btn-outline"
                      @click="handleCancelInvitation(inv.id)"
                      :disabled="invitationStore.loading"
                    >
                      Annuler
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Users section -->
    <h2 class="text-xl font-bold mb-4">Utilisateurs</h2>

    <div v-if="success" class="alert alert-success mb-4">
      <span>{{ success }}</span>
    </div>

    <div v-if="adminStore.error" class="alert alert-error mb-4">
      <span>{{ adminStore.error }}</span>
    </div>

    <div v-if="adminStore.loading && adminStore.users.length === 0" class="flex justify-center py-12">
      <span class="loading loading-spinner loading-lg"></span>
    </div>

    <div v-else class="card bg-base-100 shadow-xl">
      <div class="card-body p-0">
        <div class="overflow-x-auto">
          <table class="table">
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
              <tr v-for="u in sortedUsers" :key="u.id" class="hover">
                <td>
                  <div class="flex items-center gap-3">
                    <div class="avatar">
                      <div class="w-10 rounded-full">
                        <img v-if="u.avatarUrl" :src="`${API_BASE}${u.avatarUrl}`" :alt="u.displayName" />
                        <div v-else class="bg-primary text-primary-content w-full h-full flex items-center justify-center">
                          {{ getInitials(u.displayName) }}
                        </div>
                      </div>
                    </div>
                    <div>
                      <div class="font-bold">{{ u.displayName }}</div>
                      <span v-if="u.id === authStore.user?.id" class="badge badge-ghost badge-xs">Vous</span>
                    </div>
                  </div>
                </td>
                <td>{{ u.email }}</td>
                <td>{{ u.team?.name || '—' }}</td>
                <td>
                  <select
                    class="select select-bordered select-sm"
                    :value="u.role"
                    @change="handleRoleChange(u.id, ($event.target as HTMLSelectElement).value)"
                    :disabled="u.id === authStore.user?.id || !isAtLeast(authStore.user?.role || 'user', 'owner')"
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
                <td>{{ new Date(u.createdAt).toLocaleDateString('fr-FR') }}</td>
                <td>
                  <button
                    class="btn btn-error btn-sm btn-outline"
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
        </div>

        <div v-if="sortedUsers.length === 0 && !adminStore.loading" class="text-center py-8 text-base-content/50">
          Aucun utilisateur trouve
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <dialog id="delete-modal" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">Confirmer la suppression</h3>
        <p class="py-4">
          Etes-vous sur de vouloir supprimer l'utilisateur <strong>{{ deleteConfirmName }}</strong> ?
          Cette action est irreversible.
        </p>
        <div class="modal-action">
          <form method="dialog">
            <button class="btn btn-ghost">Annuler</button>
          </form>
          <button
            class="btn btn-error"
            @click="handleDelete"
            :disabled="adminStore.loading"
          >
            <span v-if="adminStore.loading" class="loading loading-spinner loading-sm"></span>
            Supprimer
          </button>
        </div>
      </div>
      <form method="dialog" class="modal-backdrop">
        <button>close</button>
      </form>
    </dialog>
  </div>
</template>

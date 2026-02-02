<script setup lang="ts">
import { ref, onMounted, computed } from 'vue'
import { useAdminStore } from '@/stores/admin'
import { useAuthStore } from '@/stores/auth'

const adminStore = useAdminStore()
const authStore = useAuthStore()

const API_BASE = import.meta.env.VITE_API_URL?.replace('/api', '') || ''

const deleteConfirmId = ref<number | null>(null)
const deleteConfirmName = ref('')
const success = ref<string | null>(null)

const sortedUsers = computed(() => {
  return [...adminStore.users].sort((a, b) => {
    // Admins first, then by name
    if (a.role !== b.role) return a.role === 'admin' ? -1 : 1
    return a.displayName.localeCompare(b.displayName)
  })
})

onMounted(() => {
  adminStore.fetchUsers()
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

function getInitials(name: string): string {
  return name.charAt(0).toUpperCase()
}
</script>

<template>
  <div class="max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold mb-6">Gestion des utilisateurs</h1>

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
                <th>Équipe</th>
                <th>Rôle</th>
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
                    :disabled="u.id === authStore.user?.id"
                  >
                    <option value="user">Utilisateur</option>
                    <option value="admin">Administrateur</option>
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
          Aucun utilisateur trouvé
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <dialog id="delete-modal" class="modal">
      <div class="modal-box">
        <h3 class="font-bold text-lg">Confirmer la suppression</h3>
        <p class="py-4">
          Êtes-vous sûr de vouloir supprimer l'utilisateur <strong>{{ deleteConfirmName }}</strong> ?
          Cette action est irréversible.
        </p>
        <div class="modal-action">
          <form method="dialog">
            <button class="btn btn-ghost">Annuler</button>
          </form>
          <button
            class="btn btn-error"
            @click="handleDelete"
            :class="{ 'loading': adminStore.loading }"
            :disabled="adminStore.loading"
          >
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

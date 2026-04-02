import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, LoginCredentials, RegisterData, UpdateProfileData } from '@/types'
import { extractApiError } from '@/utils/apiError'

const API_URL = import.meta.env.VITE_API_URL || '/api'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem('token'))
  const user = ref<User | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const isAuthenticated = computed(() => !!token.value)

  async function login(credentials: LoginCredentials): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/auth/login`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        body: JSON.stringify(credentials)
      })

      if (!response.ok) {
        const data = await response.json()
        throw new Error(extractApiError(data, 'Connexion échouée. Vérifiez vos identifiants'))
      }

      const data = await response.json()
      token.value = data.token
      localStorage.setItem('token', data.token)

      await fetchUser()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Connexion échouée. Vérifiez vos identifiants'
      return false
    } finally {
      loading.value = false
    }
  }

  async function register(data: RegisterData): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/auth/register`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        body: JSON.stringify(data)
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(responseData.error || 'Inscription échouée. Vérifiez les informations saisies')
      }

      // Auto login after registration
      return await login({ email: data.email, password: data.password })
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Inscription échouée. Vérifiez les informations saisies'
      return false
    } finally {
      loading.value = false
    }
  }

  async function fetchUser(): Promise<void> {
    if (!token.value) return

    try {
      const response = await fetch(`${API_URL}/auth/me`, {
        headers: { 'Authorization': `Bearer ${token.value}` }
      })

      if (!response.ok) {
        throw new Error('Failed to fetch user')
      }

      user.value = await response.json()
    } catch {
      logout()
    }
  }

  function loginWithGoogle(): void {
    // Ne pas utiliser API_URL car la route OAuth n'est pas sous /api
    const backendUrl = import.meta.env.VITE_BACKEND_URL || 'http://localhost:8000'
    window.location.href = `${backendUrl}/auth/google`
  }

  function logout(): void {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
  }

  function getAuthHeaders(): Record<string, string> {
    return token.value ? { 'Authorization': `Bearer ${token.value}` } : {}
  }

  async function setupEntreprise(companyName?: string): Promise<boolean> {
    if (!token.value) return false

    try {
      const response = await fetch(`${API_URL}/auth/setup-entreprise`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          'Authorization': `Bearer ${token.value}`
        },
        body: JSON.stringify({ companyName })
      })

      if (!response.ok) {
        throw new Error('Impossible de configurer l\'entreprise')
      }

      // Refresh user data
      await fetchUser()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de configurer l\'entreprise'
      return false
    }
  }

  async function updateEntrepriseName(name: string): Promise<boolean> {
    if (!token.value) return false
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/entreprise`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          'Authorization': `Bearer ${token.value}`
        },
        body: JSON.stringify({ name })
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de mettre à jour le nom de l\'entreprise'))
      }

      await fetchUser()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de mettre à jour le nom de l\'entreprise'
      return false
    } finally {
      loading.value = false
    }
  }

  async function updateProfile(data: UpdateProfileData): Promise<boolean> {
    if (!token.value) return false
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/profile`, {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/merge-patch+json',
          'Authorization': `Bearer ${token.value}`
        },
        body: JSON.stringify(data)
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de mettre à jour le profil'))
      }

      const updatedProfile = await response.json()
      if (user.value) {
        user.value.displayName = updatedProfile.displayName
        user.value.email = updatedProfile.email
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de mettre à jour le profil'
      return false
    } finally {
      loading.value = false
    }
  }

  async function uploadAvatar(file: File): Promise<boolean> {
    if (!token.value) return false
    loading.value = true
    error.value = null

    try {
      const formData = new FormData()
      formData.append('avatar', file)

      const response = await fetch(`${API_URL}/account/avatar`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token.value}`
        },
        body: formData
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de mettre à jour la photo de profil'))
      }

      const updatedProfile = await response.json()
      if (user.value) {
        user.value.avatarUrl = updatedProfile.avatarUrl
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de mettre à jour la photo de profil'
      return false
    } finally {
      loading.value = false
    }
  }

  async function deleteAvatar(): Promise<boolean> {
    if (!token.value) return false
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/avatar`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token.value}`
        }
      })

      if (!response.ok) {
        throw new Error('Impossible de supprimer la photo de profil')
      }

      if (user.value) {
        user.value.avatarUrl = null
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de supprimer la photo de profil'
      return false
    } finally {
      loading.value = false
    }
  }

  async function requestResetPassword(email: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/reset-password/request`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        body: JSON.stringify({ email })
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible d\'envoyer l\'email de réinitialisation'))
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible d\'envoyer l\'email de réinitialisation'
      return false
    } finally {
      loading.value = false
    }
  }

  async function confirmResetPassword(resetToken: string, password: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/reset-password/confirm`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        body: JSON.stringify({ token: resetToken, password })
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de réinitialiser le mot de passe'))
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de réinitialiser le mot de passe'
      return false
    } finally {
      loading.value = false
    }
  }

  async function requestDeleteAccount(): Promise<boolean> {
    if (!token.value) return false
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/delete/request`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/ld+json',
          'Authorization': `Bearer ${token.value}`
        }
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de demander la suppression du compte'))
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de demander la suppression du compte'
      return false
    } finally {
      loading.value = false
    }
  }

  async function confirmDeleteAccount(deleteToken: string): Promise<boolean> {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(`${API_URL}/account/delete/confirm`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/ld+json' },
        body: JSON.stringify({ token: deleteToken })
      })

      if (!response.ok) {
        const responseData = await response.json()
        throw new Error(extractApiError(responseData, 'Impossible de supprimer le compte'))
      }

      logout()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Impossible de supprimer le compte'
      return false
    } finally {
      loading.value = false
    }
  }

  // Initialize user on store creation
  if (token.value) {
    fetchUser()
  }

  return {
    token,
    user,
    loading,
    error,
    isAuthenticated,
    login,
    loginWithGoogle,
    register,
    fetchUser,
    logout,
    getAuthHeaders,
    setupEntreprise,
    updateEntrepriseName,
    updateProfile,
    uploadAvatar,
    deleteAvatar,
    requestResetPassword,
    confirmResetPassword,
    requestDeleteAccount,
    confirmDeleteAccount
  }
})

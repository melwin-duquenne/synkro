import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { User, LoginCredentials, RegisterData, UpdateProfileData } from '@/types'

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
        throw new Error(data.message || 'Login failed')
      }

      const data = await response.json()
      token.value = data.token
      localStorage.setItem('token', data.token)

      await fetchUser()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Login failed'
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
        throw new Error(responseData.error || 'Registration failed')
      }

      // Auto login after registration
      return await login({ email: data.email, password: data.password })
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Registration failed'
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
    } catch (e) {
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
        throw new Error('Failed to setup entreprise')
      }

      // Refresh user data
      await fetchUser()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to setup entreprise'
      return false
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
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to update profile')
      }

      const updatedProfile = await response.json()
      if (user.value) {
        user.value.displayName = updatedProfile.displayName
        user.value.email = updatedProfile.email
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to update profile'
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
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to upload avatar')
      }

      const updatedProfile = await response.json()
      if (user.value) {
        user.value.avatarUrl = updatedProfile.avatarUrl
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to upload avatar'
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
        throw new Error('Failed to delete avatar')
      }

      if (user.value) {
        user.value.avatarUrl = null
      }
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete avatar'
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
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to send reset email')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to send reset email'
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
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to reset password')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to reset password'
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
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to request account deletion')
      }

      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to request account deletion'
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
        throw new Error(responseData['hydra:description'] || responseData.detail || 'Failed to delete account')
      }

      logout()
      return true
    } catch (e) {
      error.value = e instanceof Error ? e.message : 'Failed to delete account'
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
    updateProfile,
    uploadAvatar,
    deleteAvatar,
    requestResetPassword,
    confirmResetPassword,
    requestDeleteAccount,
    confirmDeleteAccount
  }
})

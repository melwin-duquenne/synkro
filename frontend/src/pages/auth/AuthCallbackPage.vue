<template>
  <div class="auth-callback">
    <div class="loading-container">
      <div class="spinner"></div>
      <p>Connexion en cours...</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

onMounted(async () => {
  const urlParams = new URLSearchParams(window.location.search)
  const token = urlParams.get('token')
  const error = urlParams.get('error')

  if (error) {
    console.error('OAuth error:', error)
    router.push('/login?error=oauth_failed')
    return
  }

  if (token) {
    // Stocker le token JWT
    authStore.token = token
    localStorage.setItem('token', token)

    try {
      // Récupérer les informations utilisateur
      await authStore.fetchUser()
      // Rediriger vers le dashboard
      router.push('/dashboard')
    } catch (e) {
      console.error('Failed to fetch user:', e)
      router.push('/login?error=fetch_user_failed')
    }
  } else {
    router.push('/login?error=no_token')
  }
})
</script>

<style scoped>
.auth-callback {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 100vh;
  background: #f5f5f5;
}

.loading-container {
  text-align: center;
  background: white;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #f3f3f3;
  border-top: 4px solid #4285f4;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 1rem;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

p {
  margin: 0;
  color: #666;
  font-size: 1.1rem;
}
</style>
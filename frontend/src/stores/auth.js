import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { authApi } from '../api/auth'
import api from '../api/axios'
import { useWorkspaceStore } from './workspace'
import router from '../router'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(localStorage.getItem('orbita_token') || null)

  const isAuthenticated = computed(() => !!token.value)

  async function login(email, password) {
    const response = await authApi.login({ email, password })
    token.value = response.data.data.token
    user.value = response.data.data.user
    localStorage.setItem('orbita_token', token.value)
    return response.data.data
  }

  async function register(data) {
    const response = await authApi.register(data)
    token.value = response.data.data.token
    user.value = response.data.data.user
    localStorage.setItem('orbita_token', token.value)
    return response.data.data
  }

  async function loginWithGoogle() {
    try {
      const response = await authApi.googleRedirect()
      const redirectUrl = response.data.data.url
      window.location.href = redirectUrl
    } catch {
      throw new Error('Erro ao redirecionar para Google')
    }
  }

  async function handleGoogleCallback(callbackToken) {
    token.value = callbackToken
    localStorage.setItem('orbita_token', callbackToken)
    await fetchMe()
  }

  async function logout() {
    const oldToken = token.value
    token.value = null
    user.value = null
    localStorage.removeItem('orbita_token')
    const workspaceStore = useWorkspaceStore()
    workspaceStore.clearWorkspace()
    router.push('/login')
    // Best-effort server-side token invalidation
    if (oldToken) {
      try {
        await api.post('/auth/logout', null, {
          headers: { Authorization: `Bearer ${oldToken}` },
        })
      } catch {}
    }
  }

  async function refresh() {
    const response = await authApi.refresh()
    token.value = response.data.data.token
    localStorage.setItem('orbita_token', token.value)
  }

  async function fetchMe() {
    const response = await authApi.me()
    user.value = response.data.data
  }

  return {
    user, token, isAuthenticated,
    login, register, loginWithGoogle, handleGoogleCallback,
    logout, refresh, fetchMe,
  }
})

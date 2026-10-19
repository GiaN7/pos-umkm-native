import { defineStore } from 'pinia'
import { ref } from 'vue'
import api from '@/lib/api'

export const useAuthStore = defineStore('auth', () => {
  const user = ref<User | null>(null)
  const token = ref<string | null>(localStorage.getItem('token'))
  const loading = ref(false)
  const error = ref<string | null>(null)

  interface User {
    id: number
    name: string
    email: string
    role: 'owner' | 'kasir'
    status: boolean
  }

  async function login(email: string, password: string) {
    loading.value = true
    error.value = null

    try {
      // Get CSRF cookie first
      await api.get('/sanctum/csrf-cookie')

      const response = await api.post('/login', { email, password })

      token.value = response.data.token
      user.value = response.data.user

      localStorage.setItem('token', response.data.token)

      return response.data
    } catch (err: any) {
      error.value = err.response?.data?.message || 'Login failed'
      throw err
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    try {
      await api.post('/logout')
    } finally {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
    }
  }

  async function fetchUser() {
    if (!token.value) return

    try {
      const response = await api.get('/user')
      user.value = response.data
    } catch (err) {
      user.value = null
      token.value = null
      localStorage.removeItem('token')
    }
  }

  function isAuthenticated(): boolean {
    return !!token.value
  }

  function isOwner(): boolean {
    return user.value?.role === 'owner'
  }

  return {
    user,
    token,
    loading,
    error,
    login,
    logout,
    fetchUser,
    isAuthenticated,
    isOwner,
  }
})

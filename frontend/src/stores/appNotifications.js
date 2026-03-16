import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { notificationsApi } from '../api/notifications'

export const useAppNotificationsStore = defineStore('appNotifications', () => {
  const notifications = ref([])
  const unreadCount = ref(0)
  const loading = ref(false)

  async function fetch() {
    loading.value = true
    try {
      const res = await notificationsApi.list()
      notifications.value = res.data.data
      unreadCount.value = res.data.unread_count
    } catch {
      // ignore
    } finally {
      loading.value = false
    }
  }

  async function markRead(id) {
    try {
      await notificationsApi.markRead(id)
      const n = notifications.value.find(n => n.id === id)
      if (n) n.read_at = new Date().toISOString()
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    } catch {
      // ignore
    }
  }

  async function markAllRead() {
    try {
      await notificationsApi.markAllRead()
      notifications.value.forEach(n => {
        if (!n.read_at) n.read_at = new Date().toISOString()
      })
      unreadCount.value = 0
    } catch {
      // ignore
    }
  }

  return { notifications, unreadCount, loading, fetch, markRead, markAllRead }
})

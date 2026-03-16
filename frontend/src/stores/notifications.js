import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useNotificationsStore = defineStore('notifications', () => {
  const notifications = ref([])
  let nextId = 0

  function add(message, type = 'info') {
    const id = nextId++
    notifications.value.push({ id, message, type })
    setTimeout(() => remove(id), 5000)
  }

  function remove(id) {
    notifications.value = notifications.value.filter(n => n.id !== id)
  }

  return { notifications, add, remove }
})

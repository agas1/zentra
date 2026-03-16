import { computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useWorkspaceStore } from '../stores/workspace'

export function useAuth() {
  const authStore = useAuthStore()
  const workspaceStore = useWorkspaceStore()

  const isOwner = computed(() => workspaceStore.isOwner)
  const isAdmin = computed(() => workspaceStore.isAdmin)

  function hasRole(...roles) {
    return roles.includes(workspaceStore.currentRole)
  }

  return { isOwner, isAdmin, hasRole, user: authStore.user }
}

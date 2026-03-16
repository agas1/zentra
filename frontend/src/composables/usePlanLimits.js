import { computed } from 'vue'
import { useWorkspaceStore } from '../stores/workspace'

export function usePlanLimits() {
  const workspaceStore = useWorkspaceStore()

  const plan = computed(() => workspaceStore.currentWorkspace?.plan)
  const features = computed(() => plan.value?.features ?? [])
  const planName = computed(() => plan.value?.name ?? 'Free')

  // Feature checks
  function hasFeature(feature) {
    return features.value.includes(feature)
  }

  const hasCustomBackgrounds = computed(() => hasFeature('custom_backgrounds'))
  const hasAutomations = computed(() => hasFeature('automations'))
  const hasCustomFields = computed(() => hasFeature('custom_fields'))
  const hasPrioritySupport = computed(() => hasFeature('priority_support'))
  const hasApiAccess = computed(() => hasFeature('api_access'))
  const hasSso = computed(() => hasFeature('sso'))
  const hasPowerUps = computed(() => hasFeature('power_ups'))

  // Limit values
  const maxMembers = computed(() => plan.value?.max_members ?? 2)
  const maxBoards = computed(() => plan.value?.max_boards ?? 5)
  const maxStorageMb = computed(() => plan.value?.max_storage_mb ?? 100)
  const maxLabels = computed(() => plan.value?.max_labels ?? 6)
  const maxAttachmentSizeMb = computed(() => plan.value?.max_attachment_size_mb ?? 5)

  function isUnlimited(val) {
    return val === -1 || val === null
  }

  // Usage from planUsage
  const usage = computed(() => workspaceStore.planUsage?.usage)

  const canCreateBoard = computed(() => {
    if (!usage.value) return true
    const { boards } = usage.value
    if (boards.unlimited) return true
    return boards.used < boards.limit
  })

  const canAddMember = computed(() => {
    if (!usage.value) return true
    const { members } = usage.value
    if (members.unlimited) return true
    return members.used < members.limit
  })

  const boardsUsed = computed(() => usage.value?.boards?.used ?? 0)
  const boardsLimit = computed(() => usage.value?.boards?.limit ?? 5)
  const boardsUnlimited = computed(() => usage.value?.boards?.unlimited ?? false)

  const membersUsed = computed(() => usage.value?.members?.used ?? 0)
  const membersLimit = computed(() => usage.value?.members?.limit ?? 2)
  const membersUnlimited = computed(() => usage.value?.members?.unlimited ?? false)

  // Plan required for feature
  function requiredPlanForFeature(feature) {
    const map = {
      custom_backgrounds: 'Starter',
      automations: 'Pro',
      custom_fields: 'Pro',
      priority_support: 'Pro',
      api_access: 'Business',
      sso: 'Business',
      power_ups: 'Pro',
    }
    return map[feature] || 'Starter'
  }

  return {
    plan,
    planName,
    features,
    hasFeature,
    hasCustomBackgrounds,
    hasAutomations,
    hasCustomFields,
    hasPrioritySupport,
    hasApiAccess,
    hasSso,
    hasPowerUps,
    maxMembers,
    maxBoards,
    maxStorageMb,
    maxLabels,
    maxAttachmentSizeMb,
    isUnlimited,
    usage,
    canCreateBoard,
    canAddMember,
    boardsUsed,
    boardsLimit,
    boardsUnlimited,
    membersUsed,
    membersLimit,
    membersUnlimited,
    requiredPlanForFeature,
  }
}

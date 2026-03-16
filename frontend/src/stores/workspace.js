import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { workspacesApi } from '../api/workspaces'
import { plansApi } from '../api/plans'

export const useWorkspaceStore = defineStore('workspace', () => {
  const workspaces = ref([])
  const currentWorkspace = ref(null)
  const members = ref([])
  const invitations = ref([])
  const loading = ref(false)
  const plans = ref([])
  const planUsage = ref(null)

  // Load workspace ID from localStorage on init
  const savedId = localStorage.getItem('zentra_workspace_id')

  // Request deduplication - prevent duplicate API calls
  let _fetchWorkspacesPromise = null
  let _fetchMembersPromise = null
  let _fetchPlansPromise = null

  async function fetchWorkspaces() {
    if (_fetchWorkspacesPromise) return _fetchWorkspacesPromise
    loading.value = true
    _fetchWorkspacesPromise = (async () => {
      try {
        const res = await workspacesApi.list()
        workspaces.value = res.data.data
        if (!currentWorkspace.value && workspaces.value.length > 0) {
          const saved = workspaces.value.find(w => w.id === savedId)
          setCurrentWorkspace(saved || workspaces.value[0])
        }
      } finally {
        loading.value = false
        _fetchWorkspacesPromise = null
      }
    })()
    return _fetchWorkspacesPromise
  }

  function setCurrentWorkspace(workspace) {
    currentWorkspace.value = workspace
    localStorage.setItem('zentra_workspace_id', workspace.id)
  }

  async function createWorkspace(data) {
    const res = await workspacesApi.create(data)
    workspaces.value.push(res.data.data)
    setCurrentWorkspace(res.data.data)
    return res.data.data
  }

  async function updateWorkspace(id, data) {
    const res = await workspacesApi.update(id, data)
    const idx = workspaces.value.findIndex(w => w.id === id)
    if (idx !== -1) workspaces.value[idx] = res.data.data
    if (currentWorkspace.value?.id === id) currentWorkspace.value = res.data.data
    return res.data.data
  }

  async function fetchMembers() {
    if (!currentWorkspace.value) return
    if (_fetchMembersPromise) return _fetchMembersPromise
    _fetchMembersPromise = (async () => {
      try {
        const res = await workspacesApi.members(currentWorkspace.value.id)
        members.value = res.data.data
      } finally {
        _fetchMembersPromise = null
      }
    })()
    return _fetchMembersPromise
  }

  async function removeMember(userId) {
    await workspacesApi.removeMember(currentWorkspace.value.id, userId)
    members.value = members.value.filter(m => m.id !== userId)
  }

  async function updateMemberRole(userId, role) {
    await workspacesApi.updateMemberRole(currentWorkspace.value.id, userId, { role })
    const member = members.value.find(m => m.id === userId)
    if (member) member.pivot.role = role
  }

  async function fetchInvitations() {
    if (!currentWorkspace.value) return
    const res = await workspacesApi.invitations(currentWorkspace.value.id)
    invitations.value = res.data.data
  }

  async function invite(data) {
    const res = await workspacesApi.invite(currentWorkspace.value.id, data)
    invitations.value.push(res.data.data)
    return res.data.data
  }

  async function cancelInvitation(invId) {
    await workspacesApi.cancelInvitation(currentWorkspace.value.id, invId)
    invitations.value = invitations.value.filter(i => i.id !== invId)
  }

  async function acceptInvitation(token) {
    const res = await workspacesApi.acceptInvitation(token)
    return res.data.data
  }

  async function fetchPlans() {
    if (_fetchPlansPromise) return _fetchPlansPromise
    _fetchPlansPromise = (async () => {
      try {
        const res = await plansApi.list()
        plans.value = res.data.data
        return plans.value
      } finally {
        _fetchPlansPromise = null
      }
    })()
    return _fetchPlansPromise
  }

  async function fetchPlanUsage() {
    const res = await plansApi.usage()
    planUsage.value = res.data.data
    return planUsage.value
  }

  async function upgradePlan(planId) {
    const res = await workspacesApi.upgrade(currentWorkspace.value.id, { plan_id: planId })
    const updated = res.data.data
    currentWorkspace.value = updated
    const idx = workspaces.value.findIndex(w => w.id === updated.id)
    if (idx !== -1) workspaces.value[idx] = updated
    return updated
  }

  function clearWorkspace() {
    currentWorkspace.value = null
    workspaces.value = []
    members.value = []
    invitations.value = []
    plans.value = []
    planUsage.value = null
    localStorage.removeItem('zentra_workspace_id')
  }

  const currentRole = computed(() => {
    // role is embedded in workspaces list from API
    return currentWorkspace.value?.pivot?.role || null
  })

  const isOwner = computed(() => currentRole.value === 'owner')
  const isAdmin = computed(() => ['owner', 'admin'].includes(currentRole.value))

  // Persona computed properties
  const persona = computed(() => currentWorkspace.value?.persona || 'studio')
  const isAgency = computed(() => persona.value === 'agency')
  const isStudio = computed(() => persona.value === 'studio')
  const isFreelancer = computed(() => persona.value === 'freelancer')
  const isClient = computed(() => persona.value === 'client')
  const hasDashboard = computed(() => persona.value !== 'client')
  const hasApiAccess = computed(() => currentWorkspace.value?.plan?.features?.includes('api_access'))
  const hasPowerUps = computed(() => currentWorkspace.value?.plan?.features?.includes('power_ups'))

  return {
    workspaces, currentWorkspace, members, invitations, loading,
    plans, planUsage,
    fetchWorkspaces, setCurrentWorkspace, createWorkspace, updateWorkspace,
    fetchMembers, removeMember, updateMemberRole,
    fetchInvitations, invite, cancelInvitation, acceptInvitation,
    fetchPlans, fetchPlanUsage, upgradePlan,
    clearWorkspace, currentRole, isOwner, isAdmin,
    persona, isAgency, isStudio, isFreelancer, isClient, hasDashboard, hasApiAccess, hasPowerUps,
  }
})

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#0d1117]">
    <div class="w-full max-w-md">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#6366f1] mb-4 shadow-lg shadow-[#6366f1]/30">
          <Palette :size="28" color="#fff" />
        </div>
        <h1 class="text-2xl font-bold text-[#e6edf3]">Selecione um Workspace</h1>
        <p class="text-[#8b949e] mt-1 text-sm">Escolha o workspace para continuar</p>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="flex items-center justify-center py-12 text-[#8b949e]">
        <Loader2 :size="24" class="animate-spin mr-2" />
        Carregando...
      </div>

      <!-- Workspace list -->
      <div v-else class="space-y-3">
        <div
          v-for="ws in workspaceStore.workspaces"
          :key="ws.id"
          @click="selectWorkspace(ws)"
          class="bg-[#161b22] border border-[#444c56] rounded-xl p-4 cursor-pointer hover:border-[#6e7681] hover:shadow-md hover:shadow-black/10 transition-all flex items-center gap-4"
        >
          <div class="w-10 h-10 rounded-lg bg-[#6366f1] flex items-center justify-center flex-shrink-0">
            <Briefcase :size="20" color="#fff" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-[#e6edf3] truncate">{{ ws.name }}</p>
            <p class="text-xs text-[#8b949e] capitalize">{{ ws.pivot?.role || 'membro' }}</p>
          </div>
          <ChevronRight :size="18" class="text-[#6e7681]" />
        </div>

        <!-- Empty state -->
        <div v-if="workspaceStore.workspaces.length === 0" class="text-center py-8">
          <p class="text-[#8b949e] text-sm mb-4">Voce ainda nao faz parte de nenhum workspace.</p>
        </div>

        <!-- Create button -->
        <button
          @click="showCreateModal = true"
          class="monday-btn monday-btn-primary w-full justify-center py-2.5 mt-4"
        >
          <Plus :size="16" />
          Criar Novo Workspace
        </button>
      </div>

      <!-- Create workspace modal -->
      <AppModal :show="showCreateModal" title="Novo Workspace" @close="showCreateModal = false">
        <form @submit.prevent="handleCreate" class="space-y-4">
          <div>
            <label class="monday-label">Nome do Workspace</label>
            <div class="relative">
              <Briefcase :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#6e7681]" />
              <input
                v-model="newWorkspaceName"
                type="text"
                required
                class="monday-input pl-10"
                placeholder="Ex: Minha Empresa"
              />
            </div>
          </div>
          <button
            type="submit"
            :disabled="creating"
            class="monday-btn monday-btn-primary w-full disabled:opacity-50"
          >
            <Loader2 v-if="creating" :size="16" class="animate-spin" />
            {{ creating ? 'Criando...' : 'Criar Workspace' }}
          </button>
        </form>
      </AppModal>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspace'
import { Palette, Briefcase, Plus, ChevronRight, Loader2 } from 'lucide-vue-next'
import AppModal from '../components/shared/AppModal.vue'

const router = useRouter()
const workspaceStore = useWorkspaceStore()

const loading = ref(false)
const showCreateModal = ref(false)
const newWorkspaceName = ref('')
const creating = ref(false)

function selectWorkspace(ws) {
  workspaceStore.setCurrentWorkspace(ws)
  router.push('/dashboard')
}

async function handleCreate() {
  creating.value = true
  try {
    await workspaceStore.createWorkspace({ name: newWorkspaceName.value })
    showCreateModal.value = false
    newWorkspaceName.value = ''
    router.push('/dashboard')
  } finally {
    creating.value = false
  }
}

onMounted(async () => {
  loading.value = true
  try {
    await workspaceStore.fetchWorkspaces()
  } finally {
    loading.value = false
  }
})
</script>

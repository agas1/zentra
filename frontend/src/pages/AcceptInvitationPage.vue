<template>
  <div class="min-h-screen flex items-center justify-center bg-[#0d1117]">
    <div class="w-full max-w-sm text-center">
      <!-- Logo -->
      <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-[#6366f1] mb-4 shadow-lg shadow-[#6366f1]/30">
        <Palette :size="28" color="#fff" />
      </div>

      <!-- Loading -->
      <div v-if="loading" class="bg-[#161b22] border border-[#444c56] rounded-xl p-6 shadow-sm shadow-black/10">
        <div class="flex flex-col items-center py-6">
          <Loader2 :size="32" class="animate-spin text-[#6366f1] mb-4" />
          <p class="text-[#e6edf3] font-semibold">Aceitando convite...</p>
          <p class="text-sm text-[#8b949e] mt-1">Aguarde um momento</p>
        </div>
      </div>

      <!-- Success -->
      <div v-else-if="success" class="bg-[#161b22] border border-[#444c56] rounded-xl p-6 shadow-sm shadow-black/10">
        <div class="flex flex-col items-center py-6">
          <div class="w-12 h-12 rounded-full bg-[#238636]/20 flex items-center justify-center mb-4">
            <CheckCircle2 :size="24" class="text-[#3fb950]" />
          </div>
          <p class="text-[#e6edf3] font-semibold">Convite aceito com sucesso!</p>
          <p class="text-sm text-[#8b949e] mt-1">Voce foi adicionado ao workspace.</p>
          <button
            @click="$router.push('/dashboard')"
            class="monday-btn monday-btn-primary mt-4"
          >
            Ir para o Dashboard
          </button>
        </div>
      </div>

      <!-- Error -->
      <div v-else class="bg-[#161b22] border border-[#444c56] rounded-xl p-6 shadow-sm shadow-black/10">
        <div class="flex flex-col items-center py-6">
          <div class="w-12 h-12 rounded-full bg-[#f8514926] flex items-center justify-center mb-4">
            <AlertCircle :size="24" class="text-[#f85149]" />
          </div>
          <p class="text-[#e6edf3] font-semibold">Erro ao aceitar convite</p>
          <p class="text-sm text-[#8b949e] mt-1">{{ error }}</p>
          <button
            @click="$router.push('/login')"
            class="monday-btn monday-btn-secondary mt-4"
          >
            Ir para Login
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspace'
import { Palette, Loader2, CheckCircle2, AlertCircle } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const workspaceStore = useWorkspaceStore()

const loading = ref(true)
const success = ref(false)
const error = ref('')

onMounted(async () => {
  const token = route.params.token
  if (!token) {
    error.value = 'Token de convite invalido'
    loading.value = false
    return
  }

  try {
    await workspaceStore.acceptInvitation(token)
    success.value = true
    // Refresh workspaces list
    await workspaceStore.fetchWorkspaces()
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Convite invalido ou expirado'
  } finally {
    loading.value = false
  }
})
</script>

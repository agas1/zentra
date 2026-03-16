<template>
  <div class="min-h-screen flex items-center justify-center bg-[#0d1117] px-4">
    <div class="w-full max-w-lg text-center">
      <!-- Logo -->
      <div class="flex items-center justify-center gap-2.5 mb-8 animate-fade-in-down">
        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#388bfd] flex items-center justify-center">
          <Palette :size="22" color="#fff" />
        </div>
        <span class="text-xl font-bold text-[#e6edf3]">Orbita</span>
      </div>

      <h1 class="text-3xl font-bold text-[#e6edf3] mb-2 animate-fade-in-up">Qual melhor descreve voce?</h1>
      <p class="text-[#8b949e] mb-8 animate-fade-in-up">Isso nos ajuda a personalizar sua experiencia.</p>

      <!-- Persona cards -->
      <div class="grid grid-cols-2 gap-4 mb-6">
        <button
          v-for="p in personas"
          :key="p.value"
          type="button"
          @click="selected = p.value"
          :class="[
            'relative p-5 rounded-xl border-2 text-left transition-all duration-200 hover:-translate-y-0.5 group',
            selected === p.value
              ? 'border-[#6366f1] bg-[#6366f1]/10 shadow-lg shadow-[#6366f1]/10'
              : 'border-[#444c56] bg-[#161b22] hover:border-[#6e7681] hover:bg-[#1c2128]'
          ]"
        >
          <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3 transition-colors', selected === p.value ? 'bg-[#6366f1]' : 'bg-[#21262d] group-hover:bg-[#2d333b]']">
            <component :is="p.icon" :size="20" :class="selected === p.value ? 'text-white' : 'text-[#8b949e]'" />
          </div>
          <p class="text-sm font-bold text-[#e6edf3] mb-1">{{ p.label }}</p>
          <p class="text-xs text-[#8b949e] leading-relaxed">{{ p.description }}</p>
          <div v-if="selected === p.value" class="absolute top-3 right-3">
            <CheckCircle2 :size="18" class="text-[#6366f1]" />
          </div>
        </button>
      </div>

      <button
        @click="handleSave"
        :disabled="!selected || saving"
        class="w-full h-12 bg-[#6366f1] hover:bg-[#4f46e5] text-white font-semibold rounded-xl transition-all duration-200 disabled:opacity-50 flex items-center justify-center gap-2 hover:shadow-lg hover:shadow-[#6366f1]/25"
      >
        <Loader2 v-if="saving" :size="18" class="animate-spin" />
        {{ saving ? 'Salvando...' : 'Continuar' }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspace'
import { Palette, Building2, User, Package, CheckCircle2, Loader2 } from 'lucide-vue-next'

const router = useRouter()
const workspaceStore = useWorkspaceStore()

const selected = ref('')
const saving = ref(false)

const personas = [
  { value: 'agency', label: 'Agencia de marketing', description: 'Gerencie clientes e equipes de producao', icon: Building2 },
  { value: 'studio', label: 'Estudio de design', description: 'Organize projetos criativos e entregas', icon: Palette },
  { value: 'freelancer', label: 'Freelancer', description: 'Controle seus projetos pessoais', icon: User },
  { value: 'client', label: 'Cliente', description: 'Acompanhe seus pedidos e aprovacoes', icon: Package },
]

async function handleSave() {
  if (!selected.value) return
  saving.value = true
  try {
    const ws = workspaceStore.currentWorkspace
    if (ws) {
      await workspaceStore.updateWorkspace(ws.id, { persona: selected.value })
    }
    router.push(selected.value === 'client' ? '/boards' : '/dashboard')
  } catch {
    router.push('/boards')
  } finally {
    saving.value = false
  }
}
</script>

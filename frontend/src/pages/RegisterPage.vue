<template>
  <div class="min-h-screen flex bg-[#07090d] relative overflow-hidden">
    <!-- Ambient background -->
    <div class="absolute inset-0 pointer-events-none">
      <div class="absolute top-[-20%] right-[-10%] w-[600px] h-[600px] bg-[#6366f1]/10 rounded-full blur-[150px] animate-ambient-glow" />
      <div class="absolute bottom-[-20%] left-[-10%] w-[500px] h-[500px] bg-[#388bfd]/8 rounded-full blur-[130px] animate-ambient-glow" style="animation-delay: 2s" />
    </div>

    <!-- Left side - Form -->
    <div class="w-full lg:w-1/2 flex flex-col justify-center px-8 sm:px-16 lg:px-20 xl:px-28 py-10 relative z-10">
      <div class="w-full max-w-md mx-auto">
        <!-- Logo -->
        <div class="flex items-center gap-2.5 mb-8 animate-fade-in-down">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#818cf8] flex items-center justify-center hover-rotate shadow-lg shadow-indigo-500/30">
            <Palette :size="22" color="#fff" />
          </div>
          <span class="text-xl font-bold text-white/90">Orbita</span>
        </div>

        <!-- Step indicator -->
        <div class="flex items-center gap-3 mb-6 animate-fade-in">
          <div class="flex items-center gap-2">
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300', step === 1 ? 'bg-gradient-to-br from-[#6366f1] to-[#818cf8] text-white shadow-md shadow-indigo-500/25' : 'bg-[#818cf8]/20 text-[#818cf8]']">1</div>
            <span class="text-xs text-white/35 hidden sm:inline">Dados</span>
          </div>
          <div class="flex-1 h-px bg-white/[0.06]">
            <div :class="['h-full bg-gradient-to-r from-[#6366f1] to-[#818cf8] transition-all duration-500', step === 2 ? 'w-full' : 'w-0']" />
          </div>
          <div class="flex items-center gap-2">
            <div :class="['w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold transition-all duration-300', step === 2 ? 'bg-gradient-to-br from-[#6366f1] to-[#818cf8] text-white shadow-md shadow-indigo-500/25' : 'bg-white/[0.04] text-white/25 border border-white/[0.08]']">2</div>
            <span class="text-xs text-white/35 hidden sm:inline">Perfil</span>
          </div>
        </div>

        <!-- Welcome text -->
        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-2 animate-fade-in-up stagger-1">
          {{ step === 1 ? 'Crie sua conta' : 'Qual melhor descreve voce?' }}
        </h1>
        <p class="text-white/40 mb-6 animate-fade-in-up stagger-2">
          {{ step === 1 ? 'Comece a gerenciar seus projetos criativos com o Orbita. E gratis!' : 'Isso nos ajuda a personalizar sua experiencia.' }}
        </p>

        <!-- Error -->
        <div v-if="error" class="flex items-center gap-2 glass px-4 py-3 rounded-xl text-sm mb-5 border-red-500/30 bg-red-500/10 animate-pop-in">
          <AlertCircle :size="16" class="flex-shrink-0 text-red-400" />
          <span class="text-red-300">{{ error }}</span>
        </div>

        <!-- Step 1: Basic info -->
        <form v-if="step === 1" @submit.prevent="goToStep2" class="space-y-4">
          <div class="animate-fade-in-up stagger-2">
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full h-12 px-4 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
              placeholder="Nome completo"
            />
          </div>

          <div class="animate-fade-in-up stagger-3">
            <input
              v-model="form.email"
              type="email"
              required
              class="w-full h-12 px-4 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
              placeholder="Email"
            />
          </div>

          <div class="relative animate-fade-in-up stagger-4">
            <input
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              required
              minlength="8"
              class="w-full h-12 px-4 pr-12 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
              placeholder="Senha (minimo 8 caracteres)"
            />
            <button
              type="button"
              class="absolute right-4 top-1/2 -translate-y-1/2 text-white/25 hover:text-white/50 transition-colors"
              @click="showPassword = !showPassword"
            >
              <EyeOff v-if="showPassword" :size="18" />
              <Eye v-else :size="18" />
            </button>
          </div>

          <div class="animate-fade-in-up stagger-5">
            <input
              v-model="form.password_confirmation"
              type="password"
              required
              class="w-full h-12 px-4 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
              placeholder="Confirmar senha"
            />
          </div>

          <button
            type="submit"
            class="w-full h-12 bg-gradient-to-r from-[#6366f1] to-[#818cf8] hover:from-[#818cf8] hover:to-[#6366f1] text-white font-semibold rounded-xl transition-all duration-300 flex items-center justify-center gap-2 animate-fade-in-up stagger-6 shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/35 hover:-translate-y-0.5 active:translate-y-0 border border-white/[0.1]"
          >
            Continuar
            <ArrowRight :size="18" />
          </button>
        </form>

        <!-- Step 2: Persona + Workspace -->
        <div v-else class="space-y-4">
          <!-- Persona cards -->
          <div class="grid grid-cols-2 gap-3">
            <button
              v-for="p in personas"
              :key="p.value"
              type="button"
              @click="form.persona = p.value"
              :class="[
                'relative p-4 rounded-2xl border text-left transition-all duration-200 hover:-translate-y-0.5 group backdrop-blur-md',
                form.persona === p.value
                  ? 'border-[#6366f1]/40 bg-[#6366f1]/10 shadow-lg shadow-[#6366f1]/10'
                  : 'border-white/[0.08] bg-white/[0.04] hover:border-white/[0.15] hover:bg-white/[0.06]'
              ]"
            >
              <div :class="['w-10 h-10 rounded-xl flex items-center justify-center mb-3 transition-colors', form.persona === p.value ? 'bg-gradient-to-br from-[#6366f1] to-[#818cf8]' : 'bg-white/[0.06] group-hover:bg-white/[0.1]']">
                <component :is="p.icon" :size="20" :class="form.persona === p.value ? 'text-white' : 'text-white/45'" />
              </div>
              <p class="text-sm font-bold text-white/90 mb-1">{{ p.label }}</p>
              <p class="text-xs text-white/40 leading-relaxed">{{ p.description }}</p>
              <div v-if="form.persona === p.value" class="absolute top-3 right-3">
                <CheckCircle2 :size="18" class="text-[#818cf8]" />
              </div>
            </button>
          </div>

          <!-- Workspace name -->
          <div class="mt-2">
            <input
              v-model="form.workspace_name"
              type="text"
              required
              class="w-full h-12 px-4 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
              :placeholder="workspaceLabel"
            />
          </div>

          <!-- Buttons -->
          <div class="flex gap-3 mt-2">
            <button
              type="button"
              @click="step = 1"
              class="h-12 px-6 rounded-xl border border-white/[0.08] text-white/45 hover:text-white/80 hover:border-white/[0.15] hover:bg-white/[0.04] transition-all duration-200 flex items-center gap-2"
            >
              <ArrowLeft :size="18" />
              Voltar
            </button>
            <button
              type="button"
              @click="handleRegister"
              :disabled="loading || !form.persona || !form.workspace_name"
              class="flex-1 h-12 bg-gradient-to-r from-[#6366f1] to-[#818cf8] hover:from-[#818cf8] hover:to-[#6366f1] text-white font-semibold rounded-xl transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-2 shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/35 hover:-translate-y-0.5 active:translate-y-0 border border-white/[0.1]"
            >
              <Loader2 v-if="loading" :size="18" class="animate-spin" />
              {{ loading ? 'Criando conta...' : 'Criar Conta' }}
            </button>
          </div>
        </div>

        <!-- Divider (step 1 only) -->
        <template v-if="step === 1">
          <div class="flex items-center gap-4 my-5 animate-fade-in stagger-7">
            <div class="flex-1 h-px bg-white/[0.06]"></div>
            <span class="text-xs text-white/25 font-medium">ou continue com</span>
            <div class="flex-1 h-px bg-white/[0.06]"></div>
          </div>

          <!-- Social buttons -->
          <div class="flex justify-center gap-4 animate-fade-in-up stagger-8">
            <button
              @click="handleGoogleRegister"
              :disabled="loading"
              class="w-14 h-14 rounded-2xl glass hover:bg-white/[0.08] flex items-center justify-center transition-all duration-200 disabled:opacity-50 hover:shadow-lg hover:shadow-black/25 hover:-translate-y-1 active:translate-y-0"
              title="Entrar com Google"
            >
              <svg class="w-5 h-5" viewBox="0 0 24 24" fill="white">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
              </svg>
            </button>
          </div>
        </template>

        <!-- Login link -->
        <p class="text-center text-sm text-white/35 mt-6 animate-fade-in stagger-8">
          Ja tem conta?
          <router-link to="/login" class="text-[#818cf8] hover:text-[#a5b4fc] hover:underline font-semibold transition-colors">Entrar</router-link>
        </p>
      </div>
    </div>

    <!-- Right side - Glass Illustration -->
    <div class="hidden lg:flex w-1/2 items-center justify-center p-12 relative overflow-hidden">
      <div class="absolute inset-4 glass-elevated rounded-3xl" />

      <!-- Decorative floating shapes -->
      <div class="absolute top-20 right-16 w-28 h-28 rounded-full border border-white/[0.06] animate-float-slow" />
      <div class="absolute bottom-24 left-20 w-20 h-20 rounded-full border border-[#6366f1]/15 animate-float-delay" />
      <div class="absolute top-1/2 left-8 w-10 h-10 rounded-full bg-[#388bfd]/8 animate-float animate-blob backdrop-blur-sm" />
      <div class="absolute top-[10%] left-[40%] w-5 h-5 rounded-full bg-[#818cf8]/8 animate-float" style="animation-delay: 1.2s" />
      <div class="absolute bottom-[15%] right-[35%] w-6 h-6 rounded-full bg-[#6366f1]/12 animate-float-slow animate-pulse-soft" />

      <div class="relative z-10 text-center max-w-lg">
        <!-- Illustration -->
        <img
          src="https://img.freepik.com/free-vector/team-work-concept-illustration_114360-673.jpg"
          alt="Ilustracao de trabalho em equipe"
          class="w-full max-w-sm mx-auto mb-8 rounded-2xl shadow-2xl shadow-black/30 animate-fade-in-up hover-lift ring-1 ring-white/[0.08]"
          loading="lazy"
          @error="imgError = true"
        />
        <div v-if="imgError" class="w-full max-w-sm mx-auto mb-8 h-64 rounded-2xl glass-elevated flex items-center justify-center animate-scale-in">
          <div class="text-center">
            <Palette :size="48" class="text-[#818cf8] mx-auto mb-3 animate-wiggle" />
            <p class="text-white/40 text-sm">Orbita Design Platform</p>
          </div>
        </div>

        <!-- Floating card -->
        <div class="inline-flex items-center gap-3 glass-elevated rounded-2xl px-5 py-4 mb-8 animate-pop-in hover-lift relative" style="animation-delay: 0.4s">
          <div class="absolute top-0 left-[15%] right-[15%] h-px bg-gradient-to-r from-transparent via-white/20 to-transparent" />
          <div class="flex -space-x-2">
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#388bfd] to-[#60a5fa] border-2 border-white/10 flex items-center justify-center text-white text-xs font-bold animate-fade-in" style="animation-delay: 0.6s">A</div>
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#22c55e] to-[#4ade80] border-2 border-white/10 flex items-center justify-center text-white text-xs font-bold animate-fade-in" style="animation-delay: 0.7s">B</div>
            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-[#f59e0b] to-[#fbbf24] border-2 border-white/10 flex items-center justify-center text-white text-xs font-bold animate-fade-in" style="animation-delay: 0.8s">C</div>
          </div>
          <div class="text-left">
            <p class="text-sm font-bold text-white/90">+500 equipes</p>
            <p class="text-xs text-white/40">ja usam o Orbita</p>
          </div>
        </div>

        <!-- Bottom text -->
        <h2 class="text-2xl font-bold text-white/85 leading-snug animate-fade-in-up" style="animation-delay: 0.6s">
          Colabore com sua equipe<br />e entregue projetos <span class="gradient-text">incriveis</span>
        </h2>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { Palette, Eye, EyeOff, AlertCircle, Loader2, ArrowRight, ArrowLeft, Building2, User, Package, CheckCircle2 } from 'lucide-vue-next'

const router = useRouter()
const authStore = useAuthStore()

const step = ref(1)
const form = ref({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
  workspace_name: '',
  persona: '',
})
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const imgError = ref(false)

const personas = [
  { value: 'agency', label: 'Agencia de marketing', description: 'Gerencie clientes e equipes de producao', icon: Building2 },
  { value: 'studio', label: 'Estudio de design', description: 'Organize projetos criativos e entregas', icon: Palette },
  { value: 'freelancer', label: 'Freelancer', description: 'Controle seus projetos pessoais', icon: User },
  { value: 'client', label: 'Cliente', description: 'Acompanhe seus pedidos e aprovacoes', icon: Package },
]

const workspaceLabel = computed(() => {
  const labels = {
    agency: 'Nome da agencia',
    studio: 'Nome do estudio',
    freelancer: 'Nome do workspace',
    client: 'Nome da empresa',
  }
  return labels[form.value.persona] || 'Nome do workspace (ex: Minha Empresa)'
})

function goToStep2() {
  error.value = ''
  if (!form.value.name || !form.value.email || !form.value.password || !form.value.password_confirmation) {
    error.value = 'Preencha todos os campos'
    return
  }
  if (form.value.password !== form.value.password_confirmation) {
    error.value = 'As senhas nao coincidem'
    return
  }
  if (form.value.password.length < 8) {
    error.value = 'A senha deve ter no minimo 8 caracteres'
    return
  }
  step.value = 2
}

async function handleRegister() {
  error.value = ''

  if (!form.value.persona) {
    error.value = 'Selecione um perfil'
    return
  }
  if (!form.value.workspace_name) {
    error.value = 'Informe o nome do workspace'
    return
  }

  loading.value = true
  try {
    await authStore.register(form.value)
    const persona = form.value.persona
    router.push(persona === 'client' ? '/boards' : '/dashboard')
  } catch (err) {
    error.value = err.response?.data?.error?.message || 'Erro ao criar conta'
  } finally {
    loading.value = false
  }
}

async function handleGoogleRegister() {
  try {
    await authStore.loginWithGoogle()
  } catch (err) {
    error.value = err.message || 'Erro ao redirecionar para Google'
  }
}
</script>

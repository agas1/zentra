<template>
  <div class="min-h-screen flex bg-[#07090d] relative overflow-hidden">
    <!-- Ambient background -->
    <div class="absolute inset-0 pointer-events-none">
      <div class="absolute top-[-20%] left-[-10%] w-[600px] h-[600px] bg-[#6366f1]/10 rounded-full blur-[150px] animate-ambient-glow" />
      <div class="absolute bottom-[-20%] right-[-10%] w-[500px] h-[500px] bg-[#388bfd]/8 rounded-full blur-[130px] animate-ambient-glow" style="animation-delay: 2s" />
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-[#818cf8]/5 rounded-full blur-[120px] animate-ambient-glow" style="animation-delay: 3s" />
    </div>

    <!-- Left side - Form -->
    <div class="w-full lg:w-1/2 flex flex-col justify-center px-8 sm:px-16 lg:px-20 xl:px-28 relative z-10">
      <div class="w-full max-w-md mx-auto">
        <!-- Logo -->
        <div class="flex items-center gap-2.5 mb-10 animate-fade-in-down">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#818cf8] flex items-center justify-center hover-rotate shadow-lg shadow-indigo-500/30">
            <Palette :size="22" color="#fff" />
          </div>
          <span class="text-xl font-bold text-white/90">Orbita</span>
        </div>

        <!-- Welcome text -->
        <h1 class="text-3xl sm:text-4xl font-bold text-white mb-2 animate-fade-in-up stagger-1">Bem-vindo de volta!</h1>
        <p class="text-white/45 mb-8 animate-fade-in-up stagger-2">
          Simplifique seu fluxo de trabalho e aumente sua produtividade com o <span class="font-semibold gradient-text">Orbita</span>. Comece gratuitamente.
        </p>

        <!-- Error -->
        <div v-if="error" class="flex items-center gap-2 glass px-4 py-3 rounded-xl text-sm mb-5 border-red-500/30 bg-red-500/10 animate-pop-in">
          <AlertCircle :size="16" class="flex-shrink-0 text-red-400" />
          <span class="text-red-300">{{ error }}</span>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleLogin" class="space-y-5">
          <div class="animate-fade-in-up stagger-3">
            <input
              v-model="email"
              type="email"
              required
              class="w-full h-12 px-4 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
              placeholder="Email"
            />
          </div>

          <!-- SSO discovery banner -->
          <div v-if="ssoInfo?.sso_available" class="animate-fade-in-up">
            <button
              type="button"
              class="w-full h-12 glass hover:bg-white/[0.08] text-white/90 font-semibold rounded-xl transition-all duration-200 flex items-center justify-center gap-2"
              @click="handleSsoLogin"
            >
              <svg class="w-5 h-5 text-[#818cf8]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
              Entrar com SSO
            </button>
            <p v-if="ssoInfo.sso_enforced" class="text-xs text-amber-400/80 text-center mt-2">
              Seu dominio exige login via SSO.
            </p>
          </div>

          <template v-if="!ssoInfo?.sso_enforced">
            <div class="relative animate-fade-in-up stagger-4">
              <input
                v-model="password"
                :type="showPassword ? 'text' : 'password'"
                required
                class="w-full h-12 px-4 pr-12 rounded-xl border border-white/[0.08] bg-white/[0.04] backdrop-blur-sm text-sm text-white/90 placeholder-white/25 focus:outline-none focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30 focus:bg-white/[0.06] transition-all duration-200 hover:border-white/[0.15]"
                placeholder="Senha"
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

            <div class="flex justify-end animate-fade-in stagger-5">
              <a href="#" class="text-sm text-white/35 hover:text-[#818cf8] transition-colors">Esqueceu a senha?</a>
            </div>

            <button
              type="submit"
              :disabled="loading"
              class="w-full h-12 bg-gradient-to-r from-[#6366f1] to-[#818cf8] hover:from-[#818cf8] hover:to-[#6366f1] text-white font-semibold rounded-xl transition-all duration-300 disabled:opacity-50 flex items-center justify-center gap-2 animate-fade-in-up stagger-5 shadow-lg shadow-indigo-500/25 hover:shadow-xl hover:shadow-indigo-500/35 hover:-translate-y-0.5 active:translate-y-0 border border-white/[0.1]"
            >
              <Loader2 v-if="loading" :size="18" class="animate-spin" />
              {{ loading ? 'Entrando...' : 'Entrar' }}
            </button>
          </template>
        </form>

        <!-- Divider -->
        <div v-if="!ssoInfo?.sso_enforced" class="flex items-center gap-4 my-6 animate-fade-in stagger-6">
          <div class="flex-1 h-px bg-white/[0.06]"></div>
          <span class="text-xs text-white/25 font-medium">ou continue com</span>
          <div class="flex-1 h-px bg-white/[0.06]"></div>
        </div>

        <!-- Social buttons -->
        <div v-if="!ssoInfo?.sso_enforced" class="flex justify-center gap-4 animate-fade-in-up stagger-7">
          <button
            @click="handleGoogleLogin"
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

        <!-- Register link -->
        <p class="text-center text-sm text-white/35 mt-8 animate-fade-in stagger-8">
          Nao tem conta?
          <router-link to="/register" class="text-[#818cf8] hover:text-[#a5b4fc] hover:underline font-semibold transition-colors">Cadastre-se agora</router-link>
        </p>
      </div>
    </div>

    <!-- Right side - Glass Illustration -->
    <div class="hidden lg:flex w-1/2 items-center justify-center p-12 relative overflow-hidden">
      <!-- Glass panel background -->
      <div class="absolute inset-4 glass-elevated rounded-3xl" />

      <!-- Decorative floating shapes -->
      <div class="absolute top-16 left-16 w-24 h-24 rounded-full border border-white/[0.06] animate-float-slow" />
      <div class="absolute bottom-20 right-20 w-36 h-36 rounded-full border border-[#6366f1]/15 animate-float-delay" />
      <div class="absolute top-1/3 right-10 w-12 h-12 rounded-full bg-[#388bfd]/8 animate-float animate-blob backdrop-blur-sm" />
      <div class="absolute bottom-1/3 left-10 w-8 h-8 rounded-full bg-[#6366f1]/10 animate-float-slow animate-pulse-soft" />
      <div class="absolute top-[15%] right-[30%] w-6 h-6 rounded-full bg-[#818cf8]/8 animate-float" style="animation-delay: 1s" />
      <div class="absolute bottom-[25%] left-[25%] w-4 h-4 rounded-full bg-[#6366f1]/12 animate-float-delay" style="animation-delay: 0.7s" />

      <div class="relative z-10 text-center max-w-lg">
        <!-- Illustration image -->
        <img
          src="https://img.freepik.com/free-vector/design-process-concept-illustration_114360-1688.jpg"
          alt="Ilustracao de processo de design"
          class="w-full max-w-sm mx-auto mb-8 rounded-2xl shadow-2xl shadow-black/30 animate-fade-in-up hover-lift ring-1 ring-white/[0.08]"
          loading="lazy"
          @error="imgError = true"
        />
        <!-- Fallback if image fails -->
        <div v-if="imgError" class="w-full max-w-sm mx-auto mb-8 h-64 rounded-2xl glass-elevated flex items-center justify-center animate-scale-in">
          <div class="text-center">
            <Palette :size="48" class="text-[#818cf8] mx-auto mb-3 animate-wiggle" />
            <p class="text-white/40 text-sm">Orbita Design Platform</p>
          </div>
        </div>

        <!-- Floating card -->
        <div class="inline-flex items-center gap-3 glass-elevated rounded-2xl px-5 py-4 mb-8 animate-pop-in hover-lift relative" style="animation-delay: 0.4s">
          <div class="absolute top-0 left-[15%] right-[15%] h-px bg-gradient-to-r from-transparent via-white/20 to-transparent" />
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#818cf8] flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/25">
            <Palette :size="18" color="#fff" />
          </div>
          <div class="text-left">
            <p class="text-sm font-bold text-white/90">Orbita Design</p>
            <p class="text-xs text-white/40">Gestao criativa</p>
          </div>
          <div class="ml-3 relative w-10 h-10">
            <svg class="w-10 h-10 -rotate-90" viewBox="0 0 36 36">
              <path d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="3" />
              <path d="M18 2.0845a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#22c55e" stroke-width="3" stroke-dasharray="84, 100" class="animate-[circleGrow_1.5s_ease-out_0.8s_both]" />
            </svg>
            <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-emerald-400">84%</span>
          </div>
        </div>

        <!-- Bottom text -->
        <h2 class="text-2xl font-bold text-white/85 leading-snug animate-fade-in-up" style="animation-delay: 0.6s">
          Organize seus projetos criativos<br />com o <span class="gradient-text">Orbita</span>
        </h2>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { ssoApi } from '../api/sso'
import { Palette, Eye, EyeOff, AlertCircle, Loader2 } from 'lucide-vue-next'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref('')
const loading = ref(false)
const showPassword = ref(false)
const imgError = ref(false)

// SSO discovery
const ssoInfo = ref(null)
const ssoChecking = ref(false)
let ssoDebounce = null

onMounted(() => {
  const ssoError = route.query.sso_error
  if (ssoError) {
    error.value = ssoError
    window.history.replaceState({}, '', '/login')
  }
})

watch(email, (val) => {
  clearTimeout(ssoDebounce)
  ssoInfo.value = null

  if (!val || !val.includes('@')) return

  const domain = val.split('@')[1]
  if (!domain || domain.length < 3 || !domain.includes('.')) return

  ssoDebounce = setTimeout(async () => {
    ssoChecking.value = true
    try {
      const res = await ssoApi.discover(val)
      ssoInfo.value = res.data.data
    } catch {
      // Ignore errors silently
    } finally {
      ssoChecking.value = false
    }
  }, 600)
})

function handleSsoLogin() {
  if (ssoInfo.value?.login_url) {
    window.location.href = ssoInfo.value.login_url
  }
}

async function handleLogin() {
  error.value = ''
  loading.value = true
  try {
    await authStore.login(email.value, password.value)
    router.push('/boards')
  } catch (err) {
    const data = err.response?.data?.error
    if (data?.code === 'SSO_REQUIRED' && data?.sso_login_url) {
      window.location.href = data.sso_login_url
      return
    }
    error.value = data?.message || 'Erro ao fazer login'
  } finally {
    loading.value = false
  }
}

async function handleGoogleLogin() {
  try {
    await authStore.loginWithGoogle()
  } catch (err) {
    error.value = err.message || 'Erro ao redirecionar para Google'
  }
}
</script>

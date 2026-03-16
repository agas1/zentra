<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspace'
import { billingApi } from '../api/billing'
import { loadStripe } from '@stripe/stripe-js'
import PageHeader from '../components/shared/PageHeader.vue'
import ConfirmModal from '../components/shared/ConfirmModal.vue'

const workspaceStore = useWorkspaceStore()

const loading = ref(true)
const upgrading = ref(null)
const billingCycle = ref('monthly')
const error = ref('')
const success = ref('')
const showDowngradeConfirm = ref(false)
const pendingDowngradePlan = ref(null)

// Checkout modal state
const showCheckout = ref(false)
const checkoutLoading = ref(false)
const checkoutPlan = ref(null)
let checkoutInstance = null

const currentPlanId = computed(() => workspaceStore.currentWorkspace?.plan?.id)
const hasActiveSubscription = computed(() => {
  const ws = workspaceStore.currentWorkspace
  return ws?.subscription_status === 'active' || ws?.subscription_status === 'trialing'
})

onMounted(async () => {
  try {
    await workspaceStore.fetchPlans()
  } finally {
    loading.value = false
  }

  // Check if returning from checkout
  const urlParams = new URLSearchParams(window.location.search)
  const sessionId = urlParams.get('session_id')
  if (sessionId) {
    await handleCheckoutReturn(sessionId)
    window.history.replaceState({}, '', '/plans')
  }
})

onUnmounted(() => {
  destroyCheckout()
})

const annualDiscount = 0.2

function getPrice(plan) {
  if (!plan) return 0
  const monthly = parseFloat(plan.price_monthly)
  if (monthly === 0) return 0
  if (billingCycle.value === 'annual') return monthly * (1 - annualDiscount)
  return monthly
}

function formatPrice(value) {
  if (value === 0) return 'Gratis'
  return `R$ ${value.toFixed(2).replace('.', ',')}`
}

function formatLimit(value, unit = '') {
  if (value === -1) return 'Ilimitado'
  if (unit === 'storage') {
    if (value >= 1024) return `${(value / 1024).toFixed(0)} GB`
    return `${value} MB`
  }
  return `${value}${unit}`
}

const featureLabels = {
  custom_backgrounds: 'Backgrounds customizados',
  automations: 'Automacoes',
  custom_fields: 'Campos customizados',
  priority_support: 'Suporte prioritario',
  api_access: 'Acesso a API',
  sso: 'SSO / SAML',
}

const comingSoonFeatures = new Set([])

const allFeatureKeys = ['custom_backgrounds', 'custom_fields', 'priority_support', 'automations', 'api_access', 'sso']

async function handleUpgrade(plan) {
  if (plan.id === currentPlanId.value) return
  error.value = ''
  success.value = ''

  const currentPlan = workspaceStore.plans.find(p => p.id === currentPlanId.value)
  const isCurrentFree = !currentPlan || parseFloat(currentPlan.price_monthly) === 0
  const isNewFree = parseFloat(plan.price_monthly) === 0

  // Free -> Paid: open Stripe Checkout
  if (isCurrentFree && !isNewFree) {
    await openCheckout(plan)
    return
  }

  // Paid -> Paid with active subscription: use changePlan
  if (!isCurrentFree && !isNewFree && hasActiveSubscription.value) {
    upgrading.value = plan.id
    try {
      const res = await billingApi.changePlan({ plan_id: plan.id, cycle: billingCycle.value })
      workspaceStore.currentWorkspace = res.data.data
      const idx = workspaceStore.workspaces.findIndex(w => w.id === res.data.data.id)
      if (idx !== -1) workspaceStore.workspaces[idx] = res.data.data
      success.value = 'Plano alterado com sucesso!'
    } catch (e) {
      error.value = e.response?.data?.error?.message || 'Erro ao alterar plano.'
    } finally {
      upgrading.value = null
    }
    return
  }

  // Paid -> Free: cancel subscription
  if (isNewFree && hasActiveSubscription.value) {
    pendingDowngradePlan.value = plan
    showDowngradeConfirm.value = true
    return
  }

  await proceedWithFallback(plan)
}

async function confirmDowngrade() {
  showDowngradeConfirm.value = false
  const plan = pendingDowngradePlan.value
  pendingDowngradePlan.value = null
  if (!plan) return

  upgrading.value = plan.id
  try {
    const res = await billingApi.cancel()
    workspaceStore.currentWorkspace = res.data.data
    const idx = workspaceStore.workspaces.findIndex(w => w.id === res.data.data.id)
    if (idx !== -1) workspaceStore.workspaces[idx] = res.data.data
    success.value = 'Assinatura cancelada. Acesso ate o fim do periodo.'
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao cancelar assinatura.'
  } finally {
    upgrading.value = null
  }
}

async function proceedWithFallback(plan) {
  // Fallback: try regular upgrade
  upgrading.value = plan.id
  try {
    await workspaceStore.upgradePlan(plan.id)
    success.value = 'Plano atualizado com sucesso!'
  } catch (e) {
    const code = e.response?.data?.error?.code
    if (code === 'PAYMENT_REQUIRED') {
      await openCheckout(plan)
    } else {
      error.value = e.response?.data?.error?.message || 'Erro ao alterar plano.'
    }
  } finally {
    upgrading.value = null
  }
}

async function handleResume() {
  try {
    const res = await billingApi.resume()
    workspaceStore.currentWorkspace = res.data.data
    const idx = workspaceStore.workspaces.findIndex(w => w.id === res.data.data.id)
    if (idx !== -1) workspaceStore.workspaces[idx] = res.data.data
    success.value = 'Assinatura reativada com sucesso!'
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao reativar assinatura.'
  }
}

async function openCheckout(plan) {
  checkoutPlan.value = plan
  checkoutLoading.value = true
  showCheckout.value = true
  error.value = ''

  try {
    const res = await billingApi.createCheckout({
      plan_id: plan.id,
      cycle: billingCycle.value,
    })

    const { client_secret } = res.data.data
    const stripePublicKey = import.meta.env.VITE_STRIPE_PUBLIC_KEY

    if (!stripePublicKey) {
      error.value = 'Chave publica do Stripe nao configurada.'
      showCheckout.value = false
      return
    }

    const stripe = await loadStripe(stripePublicKey)
    checkoutInstance = await stripe.initEmbeddedCheckout({ clientSecret: client_secret })
    checkoutInstance.mount('#stripe-checkout-container')
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao iniciar checkout.'
    showCheckout.value = false
  } finally {
    checkoutLoading.value = false
  }
}

function destroyCheckout() {
  if (checkoutInstance) {
    checkoutInstance.destroy()
    checkoutInstance = null
  }
}

function closeCheckout() {
  destroyCheckout()
  showCheckout.value = false
  checkoutPlan.value = null
}

async function handleCheckoutReturn(sessionId) {
  try {
    const res = await billingApi.checkoutStatus(sessionId)
    if (res.data.data.status === 'complete') {
      success.value = 'Pagamento confirmado! Seu plano foi atualizado.'
      await workspaceStore.fetchWorkspaces()
    }
  } catch {
    // Webhook will update the workspace
  }
}

function isCurrentPlan(plan) {
  return plan.id === currentPlanId.value
}

function isUpgrade(plan) {
  const current = workspaceStore.plans.find(p => p.id === currentPlanId.value)
  if (!current) return true
  return plan.sort_order > current.sort_order
}

function getButtonLabel(plan) {
  if (isCurrentPlan(plan)) return 'Plano Atual'
  if (isUpgrade(plan)) return 'Fazer Upgrade'
  return 'Downgrade'
}
</script>

<template>
  <div>
    <PageHeader title="Planos" />

    <!-- Hero -->
    <div class="text-center mb-8 animate-fade-in-down">
      <h2 class="text-2xl font-bold text-[#e6edf3] mb-2">Escolha o plano ideal para seu time</h2>
      <p class="text-[#8b949e] max-w-lg mx-auto">
        Preco fixo por workspace, nao por usuario. Escale seu time sem surpresas na fatura.
      </p>

      <!-- Billing toggle -->
      <div class="flex items-center justify-center gap-3 mt-5">
        <span class="text-sm" :class="billingCycle === 'monthly' ? 'text-[#e6edf3] font-semibold' : 'text-[#6e7681]'">Mensal</span>
        <button
          class="relative w-11 h-6 rounded-full transition-colors flex-shrink-0"
          :class="billingCycle === 'annual' ? 'bg-[#6366f1]' : 'bg-[#2d333b]'"
          @click="billingCycle = billingCycle === 'monthly' ? 'annual' : 'monthly'"
        >
          <span
            class="absolute top-[2px] left-[2px] w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200"
            :style="{ transform: billingCycle === 'annual' ? 'translateX(20px)' : 'translateX(0)' }"
          />
        </button>
        <span class="text-sm" :class="billingCycle === 'annual' ? 'text-[#e6edf3] font-semibold' : 'text-[#6e7681]'">
          Anual
          <span class="ml-1 text-xs bg-[#238636]/20 text-[#3fb950] px-1.5 py-0.5 rounded-full font-medium">-20%</span>
        </span>
      </div>
    </div>

    <!-- Success -->
    <div v-if="success" class="max-w-3xl mx-auto mb-4 p-3 bg-[#23863626] border border-[#23863633] rounded-xl text-sm text-[#3fb950] text-center">
      {{ success }}
    </div>

    <!-- Error -->
    <div v-if="error" class="max-w-3xl mx-auto mb-4 p-3 bg-[#f8514926] border border-[#f8514933] rounded-xl text-sm text-[#f85149] text-center">
      {{ error }}
    </div>

    <!-- Subscription status banner -->
    <div
      v-if="workspaceStore.currentWorkspace?.subscription_status === 'canceled' && workspaceStore.currentWorkspace?.subscription_ends_at"
      class="max-w-3xl mx-auto mb-4 p-3 bg-[#d2992226] border border-[#d2992233] rounded-xl text-sm text-[#d29922] text-center"
    >
      Sua assinatura sera cancelada em {{ new Date(workspaceStore.currentWorkspace.subscription_ends_at).toLocaleDateString('pt-BR') }}.
      <button class="underline font-semibold ml-1" @click="handleResume">Reativar assinatura</button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" />
    </div>

    <!-- Plans grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 max-w-6xl mx-auto">
      <div
        v-for="(plan, idx) in workspaceStore.plans"
        :key="plan.id"
        class="relative bg-[#161b22] rounded-2xl border-2 p-6 flex flex-col transition-all duration-300 hover:shadow-lg hover:shadow-black/20 animate-fade-in-up"
        :class="plan.slug === 'pro'
          ? 'border-[#6366f1] shadow-lg shadow-[#6366f1]/10'
          : isCurrentPlan(plan)
            ? 'border-[#6366f1]/50'
            : 'border-[#444c56] hover:border-[#6e7681]'"
        :style="{ animationDelay: `${idx * 0.1}s` }"
      >
        <!-- Popular badge -->
        <div v-if="plan.slug === 'pro'" class="absolute -top-3 left-1/2 -translate-x-1/2">
          <span class="bg-[#6366f1] text-white text-xs font-semibold px-3 py-1 rounded-full shadow-md shadow-[#6366f1]/30">
            Mais Popular
          </span>
        </div>

        <!-- Current badge -->
        <div v-if="isCurrentPlan(plan)" class="absolute -top-3 right-4">
          <span class="bg-[#6366f1]/20 text-[#a5b4fc] text-xs font-semibold px-3 py-1 rounded-full">
            Plano Atual
          </span>
        </div>

        <!-- Plan name -->
        <h3 class="text-lg font-bold text-[#e6edf3] mb-1">{{ plan.name }}</h3>
        <p class="text-xs text-[#6e7681] mb-4 min-h-[32px]">{{ plan.description }}</p>

        <!-- Price -->
        <div class="mb-5">
          <div class="flex items-baseline gap-1">
            <span class="text-3xl font-bold text-[#e6edf3]">{{ formatPrice(getPrice(plan)) }}</span>
            <span v-if="getPrice(plan) > 0" class="text-sm text-[#6e7681]">/mes</span>
          </div>
          <p v-if="billingCycle === 'annual' && getPrice(plan) > 0" class="text-xs text-[#6e7681] mt-0.5">
            cobrado anualmente
          </p>
          <p v-if="getPrice(plan) > 0" class="text-xs text-[#3fb950] mt-1 font-medium">
            Preco fixo — {{ plan.max_members === -1 ? 'membros ilimitados' : `ate ${plan.max_members} membros` }}
          </p>
        </div>

        <!-- Limits -->
        <div class="space-y-2.5 mb-5 flex-1">
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-[#6366f1] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-[#8b949e]"><strong class="text-[#e6edf3]">{{ formatLimit(plan.max_members) }}</strong> membros</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-[#6366f1] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
            <span class="text-[#8b949e]"><strong class="text-[#e6edf3]">{{ formatLimit(plan.max_boards) }}</strong> quadros</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-[#6366f1] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/></svg>
            <span class="text-[#8b949e]"><strong class="text-[#e6edf3]">{{ formatLimit(plan.max_storage_mb, 'storage') }}</strong> armazenamento</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-[#6366f1] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span class="text-[#8b949e]"><strong class="text-[#e6edf3]">{{ plan.max_attachment_size_mb }} MB</strong> por arquivo</span>
          </div>
          <div class="flex items-center gap-2 text-sm">
            <svg class="w-4 h-4 text-[#6366f1] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
            <span class="text-[#8b949e]"><strong class="text-[#e6edf3]">{{ formatLimit(plan.max_labels) }}</strong> etiquetas</span>
          </div>

          <!-- Feature flags -->
          <div class="pt-2 border-t border-[#444c56] space-y-2">
            <div
              v-for="featureKey in allFeatureKeys"
              :key="featureKey"
              class="flex items-center gap-2 text-sm"
            >
              <svg
                v-if="plan.features?.includes(featureKey)"
                class="w-4 h-4 text-[#3fb950] flex-shrink-0"
                fill="currentColor" viewBox="0 0 20 20"
              ><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              <svg
                v-else
                class="w-4 h-4 text-[#6e7681] flex-shrink-0"
                fill="none" viewBox="0 0 24 24" stroke="currentColor"
              ><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span :class="plan.features?.includes(featureKey) ? 'text-[#e6edf3]' : 'text-[#6e7681]'">
                {{ featureLabels[featureKey] }}
              </span>
              <span
                v-if="comingSoonFeatures.has(featureKey) && plan.features?.includes(featureKey)"
                class="text-[10px] bg-[#d29922]/20 text-[#d29922] px-1.5 py-0.5 rounded-full font-medium whitespace-nowrap"
              >
                Em breve
              </span>
            </div>
          </div>
        </div>

        <!-- CTA Button -->
        <button
          :disabled="isCurrentPlan(plan) || upgrading === plan.id"
          class="w-full py-2.5 rounded-xl font-semibold text-sm transition-all duration-200"
          :class="isCurrentPlan(plan)
            ? 'bg-[#2d333b] text-[#6e7681] cursor-not-allowed'
            : plan.slug === 'pro'
              ? 'bg-[#6366f1] text-white hover:bg-[#4f46e5] hover:shadow-lg hover:shadow-[#6366f1]/25 hover:-translate-y-0.5 active:translate-y-0'
              : 'bg-[#2d333b] text-[#e6edf3] hover:bg-[#444c56] hover:-translate-y-0.5 active:translate-y-0'"
          @click="handleUpgrade(plan)"
        >
          <span v-if="upgrading === plan.id" class="inline-flex items-center gap-2">
            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            Alterando...
          </span>
          <span v-else>{{ getButtonLabel(plan) }}</span>
        </button>
      </div>
    </div>

    <!-- Billing link -->
    <div v-if="hasActiveSubscription" class="max-w-3xl mx-auto mt-6 text-center">
      <router-link to="/billing" class="text-sm text-[#6366f1] hover:underline">
        Gerenciar faturamento e faturas
      </router-link>
    </div>

    <!-- Comparison footer -->
    <div class="max-w-3xl mx-auto mt-10 text-center animate-fade-in-up" style="animation-delay: 0.5s">
      <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-6">
        <h3 class="font-bold text-[#e6edf3] mb-2">Por que Orbita e diferente?</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-[#8b949e] mt-4">
          <div>
            <div class="w-10 h-10 rounded-xl bg-[#d29922]/15 flex items-center justify-center mx-auto mb-2">
              <svg class="w-5 h-5 text-[#d29922]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            </div>
            <strong class="text-[#e6edf3]">Preco por workspace</strong>
            <p class="text-xs mt-1">Nao por usuario. Convide seu time todo sem pagar mais.</p>
          </div>
          <div>
            <div class="w-10 h-10 rounded-xl bg-[#6366f1]/15 flex items-center justify-center mx-auto mb-2">
              <svg class="w-5 h-5 text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
            </div>
            <strong class="text-[#e6edf3]">Simples de usar</strong>
            <p class="text-xs mt-1">Interface intuitiva sem curva de aprendizado.</p>
          </div>
          <div>
            <div class="w-10 h-10 rounded-xl bg-[#3fb950]/15 flex items-center justify-center mx-auto mb-2">
              <svg class="w-5 h-5 text-[#3fb950]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
            </div>
            <strong class="text-[#e6edf3]">Seus dados seguros</strong>
            <p class="text-xs mt-1">Infraestrutura segura com backups automaticos.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Stripe Checkout Modal -->
    <Teleport to="body">
      <div
        v-if="showCheckout"
        class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm"
        @click.self="closeCheckout"
      >
        <div class="bg-[#161b22] border border-[#444c56] rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
          <!-- Header -->
          <div class="flex items-center justify-between px-6 py-4 border-b border-[#444c56]">
            <div>
              <h3 class="text-lg font-bold text-[#e6edf3]">Assinar {{ checkoutPlan?.name }}</h3>
              <p class="text-xs text-[#8b949e] mt-0.5">
                {{ formatPrice(getPrice(checkoutPlan)) }}/mes
                <span v-if="billingCycle === 'annual'"> (cobrado anualmente)</span>
              </p>
            </div>
            <button
              class="w-8 h-8 rounded-lg flex items-center justify-center text-[#8b949e] hover:bg-[#2d333b] hover:text-[#e6edf3] transition-colors"
              @click="closeCheckout"
            >
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Checkout container -->
          <div class="p-6">
            <div v-if="checkoutLoading" class="flex justify-center py-12">
              <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" />
            </div>
            <div id="stripe-checkout-container" />
          </div>
        </div>
      </div>
    </Teleport>

    <ConfirmModal
      :show="showDowngradeConfirm"
      title="Voltar para o plano Free"
      message="Tem certeza que deseja cancelar sua assinatura e voltar para o plano Free? Voce mantera acesso ate o fim do periodo atual."
      confirm-text="Cancelar assinatura"
      cancel-text="Manter plano"
      variant="warning"
      @confirm="confirmDowngrade"
      @cancel="showDowngradeConfirm = false; pendingDowngradePlan = null"
    />
  </div>
</template>

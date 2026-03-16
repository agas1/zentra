<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useWorkspaceStore } from '../stores/workspace'
import { billingApi } from '../api/billing'
import PageHeader from '../components/shared/PageHeader.vue'
import ConfirmModal from '../components/shared/ConfirmModal.vue'

const router = useRouter()
const workspaceStore = useWorkspaceStore()

const loading = ref(true)
const billingStatus = ref(null)
const invoices = ref([])
const invoicesLoading = ref(false)
const error = ref('')
const success = ref('')
const showCancelConfirm = ref(false)

const workspace = computed(() => workspaceStore.currentWorkspace)

const statusLabels = {
  none: { label: 'Sem assinatura', color: 'text-[#8b949e]', bg: 'bg-[#2d333b]' },
  active: { label: 'Ativo', color: 'text-[#3fb950]', bg: 'bg-[#238636]/20' },
  trialing: { label: 'Periodo de teste', color: 'text-[#a5b4fc]', bg: 'bg-[#6366f1]/20' },
  past_due: { label: 'Pagamento pendente', color: 'text-[#d29922]', bg: 'bg-[#d29922]/20' },
  canceled: { label: 'Cancelado', color: 'text-[#f85149]', bg: 'bg-[#f85149]/20' },
}

onMounted(async () => {
  try {
    const [statusRes] = await Promise.all([
      billingApi.status(),
      fetchInvoices(),
    ])
    billingStatus.value = statusRes.data.data
  } catch {
    // Use workspace data as fallback
  } finally {
    loading.value = false
  }

  // Check checkout return
  const urlParams = new URLSearchParams(window.location.search)
  const sessionId = urlParams.get('session_id')
  if (sessionId) {
    try {
      const res = await billingApi.checkoutStatus(sessionId)
      if (res.data.data.status === 'complete') {
        success.value = 'Pagamento confirmado! Seu plano foi atualizado.'
        await workspaceStore.fetchWorkspaces()
        const statusRes = await billingApi.status()
        billingStatus.value = statusRes.data.data
      }
    } catch {
      // Webhook will handle it
    }
    window.history.replaceState({}, '', '/billing')
  }
})

async function fetchInvoices() {
  invoicesLoading.value = true
  try {
    const res = await billingApi.invoices()
    invoices.value = res.data.data || []
  } catch {
    invoices.value = []
  } finally {
    invoicesLoading.value = false
  }
}

async function handleCancel() {
  showCancelConfirm.value = false
  error.value = ''
  try {
    const res = await billingApi.cancel()
    workspaceStore.currentWorkspace = res.data.data
    const idx = workspaceStore.workspaces.findIndex(w => w.id === res.data.data.id)
    if (idx !== -1) workspaceStore.workspaces[idx] = res.data.data
    const statusRes = await billingApi.status()
    billingStatus.value = statusRes.data.data
    success.value = 'Assinatura cancelada. Acesso ate o fim do periodo.'
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao cancelar assinatura.'
  }
}

async function handleResume() {
  error.value = ''
  try {
    const res = await billingApi.resume()
    workspaceStore.currentWorkspace = res.data.data
    const idx = workspaceStore.workspaces.findIndex(w => w.id === res.data.data.id)
    if (idx !== -1) workspaceStore.workspaces[idx] = res.data.data
    const statusRes = await billingApi.status()
    billingStatus.value = statusRes.data.data
    success.value = 'Assinatura reativada com sucesso!'
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao reativar assinatura.'
  }
}

async function openPortal() {
  try {
    const res = await billingApi.portalUrl()
    window.open(res.data.data.url, '_blank')
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao abrir portal de pagamento.'
  }
}

function formatDate(date) {
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatCurrency(amount) {
  return `R$ ${parseFloat(amount).toFixed(2).replace('.', ',')}`
}

const invoiceStatusLabels = {
  paid: 'Pago',
  open: 'Aberto',
  void: 'Cancelado',
  draft: 'Rascunho',
  uncollectible: 'Inadimplente',
}
</script>

<template>
  <div>
    <PageHeader title="Faturamento" />

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" />
    </div>

    <div v-else class="max-w-4xl mx-auto space-y-6">
      <!-- Success / Error -->
      <div v-if="success" class="p-3 bg-[#23863626] border border-[#23863633] rounded-xl text-sm text-[#3fb950] text-center">
        {{ success }}
      </div>
      <div v-if="error" class="p-3 bg-[#f8514926] border border-[#f8514933] rounded-xl text-sm text-[#f85149] text-center">
        {{ error }}
      </div>

      <!-- Current Plan Card -->
      <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-6">
        <div class="flex items-start justify-between mb-4">
          <div>
            <h3 class="text-xl font-bold text-[#e6edf3]">Plano Atual</h3>
            <p class="text-sm text-[#8b949e] mt-1">{{ workspace?.plan?.description }}</p>
          </div>
          <span
            v-if="billingStatus"
            class="text-xs font-semibold px-3 py-1 rounded-full"
            :class="[
              (statusLabels[billingStatus.subscription_status] || statusLabels.none).color,
              (statusLabels[billingStatus.subscription_status] || statusLabels.none).bg,
            ]"
          >
            {{ (statusLabels[billingStatus.subscription_status] || statusLabels.none).label }}
          </span>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <p class="text-[10px] text-[#6e7681] uppercase tracking-wider mb-1">Plano</p>
            <p class="text-sm font-semibold text-[#e6edf3]">{{ workspace?.plan?.name || 'Free' }}</p>
          </div>
          <div>
            <p class="text-[10px] text-[#6e7681] uppercase tracking-wider mb-1">Preco</p>
            <p class="text-sm font-semibold text-[#e6edf3]">
              {{ parseFloat(workspace?.plan?.price_monthly || 0) === 0 ? 'Gratis' : formatCurrency(workspace?.plan?.price_monthly) + '/mes' }}
            </p>
          </div>
          <div>
            <p class="text-[10px] text-[#6e7681] uppercase tracking-wider mb-1">Ciclo</p>
            <p class="text-sm font-semibold text-[#e6edf3]">
              {{ billingStatus?.billing_cycle === 'annual' ? 'Anual' : 'Mensal' }}
            </p>
          </div>
          <div v-if="billingStatus?.subscription_ends_at">
            <p class="text-[10px] text-[#6e7681] uppercase tracking-wider mb-1">Acesso ate</p>
            <p class="text-sm font-semibold text-[#d29922]">
              {{ formatDate(billingStatus.subscription_ends_at) }}
            </p>
          </div>
        </div>

        <div class="flex flex-wrap gap-2 mt-5 pt-5 border-t border-[#2d333b]">
          <button
            class="px-4 py-2 text-sm font-medium rounded-xl bg-[#6366f1] text-white hover:bg-[#4f46e5] transition-colors"
            @click="router.push('/plans')"
          >
            Mudar plano
          </button>

          <button
            v-if="billingStatus?.has_active_subscription && billingStatus?.subscription_status !== 'canceled'"
            class="px-4 py-2 text-sm font-medium rounded-xl bg-[#2d333b] text-[#f85149] hover:bg-[#f85149]/10 transition-colors"
            @click="showCancelConfirm = true"
          >
            Cancelar assinatura
          </button>

          <button
            v-if="billingStatus?.subscription_status === 'canceled' && billingStatus?.subscription_ends_at"
            class="px-4 py-2 text-sm font-medium rounded-xl bg-[#238636] text-white hover:bg-[#2ea043] transition-colors"
            @click="handleResume"
          >
            Reativar assinatura
          </button>

          <button
            v-if="workspace?.stripe_customer_id"
            class="px-4 py-2 text-sm font-medium rounded-xl bg-[#2d333b] text-[#e6edf3] hover:bg-[#444c56] transition-colors"
            @click="openPortal"
          >
            Gerenciar pagamento
          </button>
        </div>
      </div>

      <!-- Invoices -->
      <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-6">
        <h3 class="text-lg font-semibold text-[#e6edf3] mb-4">Historico de Faturas</h3>

        <div v-if="invoicesLoading" class="flex justify-center py-8">
          <div class="animate-spin w-5 h-5 border-2 border-[#6366f1] border-t-transparent rounded-full" />
        </div>

        <div v-else-if="invoices.length === 0" class="py-8 text-center">
          <p class="text-sm text-[#8b949e]">Nenhuma fatura encontrada</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-[#444c56]">
                <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Data</th>
                <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Numero</th>
                <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Valor</th>
                <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Status</th>
                <th class="text-right py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium"></th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="invoice in invoices"
                :key="invoice.id"
                class="border-b border-[#2d333b] hover:bg-[#1c2128] transition-colors"
              >
                <td class="py-2.5 px-3 text-[#8b949e]">{{ formatDate(invoice.created_at) }}</td>
                <td class="py-2.5 px-3 text-[#e6edf3]">{{ invoice.number || '-' }}</td>
                <td class="py-2.5 px-3 text-[#e6edf3] font-medium">{{ formatCurrency(invoice.amount) }}</td>
                <td class="py-2.5 px-3">
                  <span
                    class="text-xs font-medium px-2 py-0.5 rounded-full"
                    :class="invoice.status === 'paid' ? 'bg-[#238636]/20 text-[#3fb950]' : 'bg-[#d29922]/20 text-[#d29922]'"
                  >
                    {{ invoiceStatusLabels[invoice.status] || invoice.status }}
                  </span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <a
                    v-if="invoice.pdf_url"
                    :href="invoice.pdf_url"
                    target="_blank"
                    rel="noopener"
                    class="text-xs text-[#6366f1] hover:underline"
                  >
                    PDF
                  </a>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <ConfirmModal
      :show="showCancelConfirm"
      title="Cancelar assinatura"
      message="Tem certeza que deseja cancelar sua assinatura? Voce mantera acesso ate o fim do periodo atual."
      confirm-text="Cancelar assinatura"
      cancel-text="Manter assinatura"
      variant="danger"
      @confirm="handleCancel"
      @cancel="showCancelConfirm = false"
    />
  </div>
</template>

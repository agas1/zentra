<script setup>
import { ref, computed } from 'vue'
import { MessageSquare, Calendar, HardDrive, Zap, Settings, Link, Unlink } from 'lucide-vue-next'

const props = defineProps({
  powerUp: { type: Object, required: true },
})
const emit = defineEmits(['install', 'uninstall', 'configure'])

const loading = ref(false)

const iconMap = {
  slack: MessageSquare,
  calendar: Calendar,
  drive: HardDrive,
}

const categoryLabels = {
  communication: 'Comunicacao',
  productivity: 'Produtividade',
  storage: 'Armazenamento',
}

// Status logic: "Conectado" only if installed AND properly configured
const isConnected = computed(() => {
  if (!props.powerUp.is_installed) return false
  const cfg = props.powerUp.config || {}
  switch (props.powerUp.slug) {
    case 'slack':
      return !!cfg.webhook_url
    case 'google_calendar':
      return !!cfg.access_token || !!cfg.email
    case 'google_drive':
      return !!cfg.access_token || !!cfg.email
    default:
      return false
  }
})

const statusLabel = computed(() => {
  if (!props.powerUp.is_installed) return 'Disponivel'
  if (isConnected.value) return 'Conectado'
  return 'Pendente'
})

const statusClass = computed(() => {
  if (!props.powerUp.is_installed) return 'text-[#8b949e]'
  if (isConnected.value) return 'text-[#3fb950]'
  return 'text-[#f0883e]'
})

async function handleConnect() {
  loading.value = true
  try {
    await emit('install')
  } finally {
    loading.value = false
  }
}

async function handleDisconnect() {
  loading.value = true
  try {
    await emit('uninstall')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="rounded-xl border bg-[#161b22] overflow-hidden transition-all duration-200 hover:shadow-lg hover:shadow-black/20"
    :class="isConnected ? 'border-[#3fb950]/30' : 'border-[#444c56] hover:border-[#6e7681]'">

    <!-- Top accent bar -->
    <div class="h-1" :class="isConnected ? 'bg-[#3fb950]' : powerUp.is_installed ? 'bg-[#f0883e]' : 'bg-[#2d333b]'" />

    <div class="p-5">
      <!-- Header -->
      <div class="flex items-start gap-3 mb-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
          :class="isConnected ? 'bg-[#3fb950]/10' : powerUp.is_installed ? 'bg-[#f0883e]/10' : 'bg-[#2d333b]'">
          <component
            :is="iconMap[powerUp.icon] || Zap"
            :size="22"
            :class="isConnected ? 'text-[#3fb950]' : powerUp.is_installed ? 'text-[#f0883e]' : 'text-[#8b949e]'"
          />
        </div>
        <div class="flex-1 min-w-0">
          <h4 class="font-semibold text-[#e6edf3] text-[15px] leading-tight">{{ powerUp.name }}</h4>
          <div class="flex items-center gap-2 mt-0.5">
            <span class="text-[10px] uppercase tracking-wider font-semibold" :class="statusClass">
              {{ statusLabel }}
            </span>
            <span class="text-[10px] text-[#6e7681]">{{ categoryLabels[powerUp.category] || powerUp.category }}</span>
          </div>
        </div>
      </div>

      <!-- Description -->
      <p class="text-[13px] text-[#8b949e] mb-4 leading-relaxed">{{ powerUp.description }}</p>

      <!-- Connected info -->
      <div v-if="powerUp.is_installed && powerUp.connected_by" class="text-xs text-[#6e7681] mb-3 flex items-center gap-1">
        <Link :size="10" />
        Conectado por {{ powerUp.connected_by.name }}
      </div>

      <!-- Actions -->
      <div class="flex items-center gap-2">
        <template v-if="!powerUp.is_installed">
          <button
            class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors bg-[#6366f1] text-white hover:bg-[#5558e6] disabled:opacity-50 flex items-center justify-center gap-1.5"
            :disabled="loading"
            @click="handleConnect"
          >
            <Link :size="14" v-if="!loading" />
            <div v-else class="w-3.5 h-3.5 border-2 border-white/30 border-t-white rounded-full animate-spin" />
            {{ loading ? 'Conectando...' : 'Conectar' }}
          </button>
        </template>

        <template v-else>
          <button
            class="flex-1 px-3 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-1.5"
            :class="isConnected
              ? 'bg-[#2d333b] text-[#e6edf3] hover:bg-[#444c56]'
              : 'bg-[#f0883e]/15 text-[#f0883e] hover:bg-[#f0883e]/25 border border-[#f0883e]/30'"
            @click="emit('configure')"
          >
            <Settings :size="14" />
            {{ isConnected ? 'Configurar' : 'Finalizar configuracao' }}
          </button>
          <button
            class="px-3 py-2 rounded-lg text-sm font-medium transition-colors text-[#8b949e] hover:text-[#f85149] hover:bg-[#f8514915]"
            :disabled="loading"
            :title="'Desconectar'"
            @click="handleDisconnect"
          >
            <Unlink :size="16" />
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

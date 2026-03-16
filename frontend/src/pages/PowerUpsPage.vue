<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePowerUpsStore } from '../stores/powerups'
import { useWorkspaceStore } from '../stores/workspace'
import { useNotificationsStore } from '../stores/notifications'
import { usePlanLimits } from '../composables/usePlanLimits'
import PowerUpCard from '../components/powerups/PowerUpCard.vue'
import PowerUpConfigModal from '../components/powerups/PowerUpConfigModal.vue'
import { Zap } from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const powerUpsStore = usePowerUpsStore()
const workspaceStore = useWorkspaceStore()
const notifications = useNotificationsStore()
const { hasFeature, requiredPlanForFeature } = usePlanLimits()

const hasPowerUps = computed(() => hasFeature('power_ups'))
const configSlug = ref(null)

const connectionLabels = {
  google_calendar: 'Google Calendar',
  google_drive: 'Google Drive',
}

onMounted(async () => {
  await powerUpsStore.fetchPowerUps()

  // Handle OAuth callback redirect
  const connected = route.query.connected
  const error = route.query.error
  if (connected) {
    notifications.add(`${connectionLabels[connected] || connected} conectado com sucesso!`, 'success')
    router.replace({ path: '/power-ups' })
  } else if (error) {
    notifications.add('Erro ao conectar. Tente novamente.', 'error')
    router.replace({ path: '/power-ups' })
  }
})

function openConfig(slug) {
  configSlug.value = slug
}

function closeConfig() {
  configSlug.value = null
}
</script>

<template>
  <div class="p-2 sm:p-4 w-full">
    <div class="flex items-center gap-4 mb-10">
      <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#388bfd] flex items-center justify-center">
        <Zap :size="24" class="text-white" />
      </div>
      <div>
        <h1 class="text-3xl font-bold text-[#e6edf3]">Power-Ups</h1>
        <p class="text-base text-[#8b949e]">Conecte ferramentas externas ao seu workspace</p>
      </div>
    </div>

    <!-- Upgrade gate -->
    <div v-if="!hasPowerUps" class="rounded-xl border border-[#444c56] bg-[#161b22] p-10 text-center">
      <Zap :size="56" class="text-[#8b949e] mx-auto mb-5" />
      <h3 class="text-xl font-semibold text-[#e6edf3] mb-3">Power-Ups disponiveis no plano Pro</h3>
      <p class="text-base text-[#8b949e] mb-6 max-w-md mx-auto">
        Conecte o Zentra com Slack, Google Calendar, Google Drive e mais.
        Faca upgrade para o plano {{ requiredPlanForFeature('power_ups') }} para desbloquear.
      </p>
      <router-link to="/plans" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-[#6366f1] text-white text-sm font-medium hover:bg-[#5558e6] transition-colors">
        Ver planos
      </router-link>
    </div>

    <!-- Power-Ups grid -->
    <div v-else>
      <div v-if="powerUpsStore.loading" class="flex items-center justify-center py-20">
        <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" />
      </div>

      <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <PowerUpCard
          v-for="pu in powerUpsStore.powerUps"
          :key="pu.slug"
          :power-up="pu"
          @install="powerUpsStore.install(pu.slug)"
          @uninstall="powerUpsStore.uninstall(pu.slug)"
          @configure="openConfig(pu.slug)"
        />
      </div>
    </div>

    <!-- Config modal -->
    <PowerUpConfigModal
      v-if="configSlug"
      :slug="configSlug"
      @close="closeConfig"
    />
  </div>
</template>

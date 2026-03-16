<template>
  <div class="p-2 sm:p-4 w-full">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-white mb-2">Dashboard</h1>
      <p class="text-base text-white/40">{{ subtitleText }}</p>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <Loader2 :size="32" class="animate-spin text-[#818cf8]" />
    </div>

    <template v-else-if="metrics">
      <!-- Stat Cards -->
      <div :class="['grid gap-5 mb-10', isFreelancer ? 'grid-cols-1 sm:grid-cols-3' : 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4']">
        <div
          v-for="(stat, idx) in statCards"
          :key="stat.label"
          class="glass rounded-2xl p-6 hover-glass transition-all duration-300 relative overflow-hidden animate-fade-in-up"
          :style="{ animationDelay: `${idx * 0.08}s` }"
        >
          <!-- Specular highlight -->
          <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/10 to-transparent pointer-events-none" />
          <div class="flex items-center justify-between mb-4">
            <span class="text-sm text-white/45 font-medium uppercase tracking-wide">{{ stat.label }}</span>
            <div :class="['w-10 h-10 rounded-xl flex items-center justify-center', stat.bg]">
              <component :is="stat.icon" :size="20" :class="stat.color" />
            </div>
          </div>
          <p class="text-3xl font-bold text-white">{{ stat.value }}</p>
        </div>
      </div>

      <!-- Agency: Boards grouped by client -->
      <template v-if="isAgency">
        <div v-for="group in clientGroups" :key="group.name" class="mb-10">
          <div class="flex items-center gap-3 mb-5">
            <Building2 :size="20" class="text-white/40" />
            <h2 class="text-lg font-bold text-white">{{ group.name }}</h2>
            <span class="text-sm text-white/30 bg-white/[0.06] px-2.5 py-0.5 rounded-full">{{ group.boards.length }} quadros</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
              v-for="board in group.boards"
              :key="board.id"
              class="glass rounded-2xl p-5 hover-glass transition-all cursor-pointer hover:-translate-y-0.5 relative overflow-hidden"
              @click="$router.push(`/boards/${board.id}`)"
            >
              <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/8 to-transparent pointer-events-none" />
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white truncate">{{ board.name }}</h3>
                <span class="text-sm font-bold" :class="board.progress >= 75 ? 'text-emerald-400' : board.progress >= 40 ? 'text-amber-400' : 'text-white/45'">{{ board.progress }}%</span>
              </div>
              <div class="w-full h-2.5 bg-white/[0.06] rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" :class="board.progress >= 75 ? 'bg-emerald-400' : board.progress >= 40 ? 'bg-amber-400' : 'bg-[#818cf8]'" :style="{ width: board.progress + '%' }" />
              </div>
              <div class="flex justify-between mt-3 text-sm text-white/30">
                <span>{{ board.completed }}/{{ board.total_cards }} cards</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Studio/Freelancer: Boards list with progress -->
      <template v-else>
        <div class="mb-10">
          <h2 class="text-lg font-bold text-white mb-5">{{ isStudio ? 'Projetos' : 'Meus Projetos' }}</h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            <div
              v-for="board in metrics.boards_summary"
              :key="board.id"
              class="glass rounded-2xl p-5 hover-glass transition-all cursor-pointer hover:-translate-y-0.5 relative overflow-hidden"
              @click="$router.push(`/boards/${board.id}`)"
            >
              <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/8 to-transparent pointer-events-none" />
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-white truncate">{{ board.name }}</h3>
                <span class="text-sm font-bold" :class="board.progress >= 75 ? 'text-emerald-400' : board.progress >= 40 ? 'text-amber-400' : 'text-white/45'">{{ board.progress }}%</span>
              </div>
              <div class="w-full h-2.5 bg-white/[0.06] rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" :class="board.progress >= 75 ? 'bg-emerald-400' : board.progress >= 40 ? 'bg-amber-400' : 'bg-[#818cf8]'" :style="{ width: board.progress + '%' }" />
              </div>
              <div class="flex justify-between mt-3 text-sm text-white/30">
                <span>{{ board.completed }}/{{ board.total_cards }} cards</span>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Completion trend -->
      <div v-if="!isFreelancer && metrics.completion_trend?.length" class="glass rounded-2xl p-6 relative overflow-hidden">
        <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/10 to-transparent pointer-events-none" />
        <h2 class="text-lg font-bold text-white mb-5">Tendencia de conclusao</h2>
        <div class="flex items-end gap-4 h-52">
          <div v-for="week in metrics.completion_trend" :key="week.week" class="flex-1 flex flex-col items-center gap-2">
            <span class="text-sm font-bold text-white">{{ week.completed }}</span>
            <div class="w-full bg-white/[0.04] rounded-t-lg overflow-hidden flex-1 flex items-end">
              <div class="w-full bg-gradient-to-t from-[#6366f1] to-[#818cf8] rounded-t-lg transition-all duration-700" :style="{ height: trendBarHeight(week.completed) }" />
            </div>
            <span class="text-xs text-white/30">{{ formatWeek(week.week) }}</span>
          </div>
        </div>
      </div>

      <!-- Cards by status -->
      <div v-if="isStudio && metrics.cards_by_status?.length" class="glass rounded-2xl p-6 mt-6 relative overflow-hidden">
        <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/10 to-transparent pointer-events-none" />
        <h2 class="text-lg font-bold text-white mb-5">Cards por status</h2>
        <div class="space-y-4">
          <div v-for="status in metrics.cards_by_status" :key="status.list_name" class="flex items-center gap-4">
            <span class="text-sm text-white/45 w-36 truncate">{{ status.list_name }}</span>
            <div class="flex-1 h-7 bg-white/[0.04] rounded-lg overflow-hidden">
              <div class="h-full bg-[#818cf8]/40 rounded-lg transition-all duration-500" :style="{ width: statusBarWidth(status.count) }" />
            </div>
            <span class="text-sm font-bold text-white w-10 text-right">{{ status.count }}</span>
          </div>
        </div>
      </div>

    </template>

    <!-- Empty state -->
    <div v-else class="flex flex-col items-center justify-center py-24 text-center">
      <BarChart3 :size="56" class="text-white/15 mb-5" />
      <p class="text-lg text-white/45 mb-2">Nenhuma metrica disponivel ainda</p>
      <p class="text-sm text-white/25">Crie boards e adicione cards para ver seus dados aqui</p>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useWorkspaceStore } from '../stores/workspace'
import { dashboardApi } from '../api/dashboard'
import { Loader2, Building2, LayoutGrid, CheckCircle2, AlertTriangle, Users, BarChart3, Palette, Briefcase } from 'lucide-vue-next'

const workspaceStore = useWorkspaceStore()

const loading = ref(true)
const metrics = ref(null)

const isAgency = computed(() => workspaceStore.isAgency)
const isStudio = computed(() => workspaceStore.isStudio)
const isFreelancer = computed(() => workspaceStore.isFreelancer)

const subtitleText = computed(() => {
  if (isAgency.value) return 'Visao geral dos seus clientes e producao'
  if (isStudio.value) return 'Acompanhe seus projetos de design'
  return 'Resumo dos seus projetos'
})

const statCards = computed(() => {
  if (!metrics.value) return []
  const o = metrics.value.overview

  if (isAgency.value) {
    const clientCount = new Set(metrics.value.boards_summary?.map(b => b.client_name).filter(Boolean)).size
    return [
      { label: 'Clientes', value: clientCount || o.total_boards, icon: Building2, bg: 'bg-[#6366f1]/15', color: 'text-[#818cf8]' },
      { label: 'Cards Ativos', value: o.total_cards - o.cards_completed, icon: LayoutGrid, bg: 'bg-[#388bfd]/15', color: 'text-[#60a5fa]' },
      { label: 'Concluidos', value: o.cards_completed, icon: CheckCircle2, bg: 'bg-emerald-500/15', color: 'text-emerald-400' },
      { label: 'Atrasados', value: o.cards_overdue, icon: AlertTriangle, bg: o.cards_overdue > 0 ? 'bg-red-500/15' : 'bg-white/[0.04]', color: o.cards_overdue > 0 ? 'text-red-400' : 'text-white/25' },
    ]
  }

  if (isStudio.value) {
    return [
      { label: 'Projetos', value: o.total_boards, icon: Palette, bg: 'bg-[#6366f1]/15', color: 'text-[#818cf8]' },
      { label: 'Em Andamento', value: o.total_cards - o.cards_completed, icon: LayoutGrid, bg: 'bg-[#388bfd]/15', color: 'text-[#60a5fa]' },
      { label: 'Entregues', value: o.cards_completed, icon: CheckCircle2, bg: 'bg-emerald-500/15', color: 'text-emerald-400' },
      { label: 'Atrasados', value: o.cards_overdue, icon: AlertTriangle, bg: o.cards_overdue > 0 ? 'bg-red-500/15' : 'bg-white/[0.04]', color: o.cards_overdue > 0 ? 'text-red-400' : 'text-white/25' },
    ]
  }

  return [
    { label: 'Projetos', value: o.total_boards, icon: Briefcase, bg: 'bg-[#6366f1]/15', color: 'text-[#818cf8]' },
    { label: 'Tarefas Ativas', value: o.total_cards - o.cards_completed, icon: LayoutGrid, bg: 'bg-[#388bfd]/15', color: 'text-[#60a5fa]' },
    { label: 'Concluidas', value: o.cards_completed, icon: CheckCircle2, bg: 'bg-emerald-500/15', color: 'text-emerald-400' },
  ]
})

const clientGroups = computed(() => {
  if (!metrics.value?.boards_summary) return []
  const groups = {}
  for (const board of metrics.value.boards_summary) {
    const key = board.client_name || 'Sem cliente'
    if (!groups[key]) groups[key] = []
    groups[key].push(board)
  }
  return Object.entries(groups).map(([name, boards]) => ({ name, boards })).sort((a, b) => {
    if (a.name === 'Sem cliente') return 1
    if (b.name === 'Sem cliente') return -1
    return a.name.localeCompare(b.name)
  })
})

const maxTrend = computed(() => Math.max(...(metrics.value?.completion_trend?.map(w => w.completed) || [1]), 1))
const maxStatus = computed(() => Math.max(...(metrics.value?.cards_by_status?.map(s => s.count) || [1]), 1))

function trendBarHeight(count) {
  return Math.max((count / maxTrend.value) * 100, 4) + '%'
}

function statusBarWidth(count) {
  return Math.max((count / maxStatus.value) * 100, 2) + '%'
}

function formatWeek(dateStr) {
  const d = new Date(dateStr)
  return `${d.getDate()}/${d.getMonth() + 1}`
}

onMounted(async () => {
  try {
    const res = await dashboardApi.getMetrics()
    metrics.value = res.data.data
  } catch {
    // silently fail
  } finally {
    loading.value = false
  }
})
</script>

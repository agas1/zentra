<template>
  <div class="flex h-screen bg-[#07090d] relative overflow-hidden">
    <!-- Ambient background blobs -->
    <div class="absolute inset-0 pointer-events-none overflow-hidden">
      <div class="absolute -top-32 -left-32 w-96 h-96 bg-[#6366f1]/8 rounded-full blur-[120px] animate-ambient-glow" />
      <div class="absolute top-1/2 -right-48 w-[500px] h-[500px] bg-[#388bfd]/6 rounded-full blur-[140px] animate-ambient-glow" style="animation-delay: 2s" />
      <div class="absolute -bottom-32 left-1/3 w-80 h-80 bg-[#818cf8]/5 rounded-full blur-[100px] animate-ambient-glow" style="animation-delay: 4s" />
    </div>

    <!-- Sidebar - Liquid Glass -->
    <aside
      class="group/sidebar flex flex-col glass-elevated rounded-2xl m-3 mr-0 overflow-hidden flex-shrink-0 transition-all duration-300 ease-[cubic-bezier(0.34,1.56,0.64,1)] w-[60px] hover:w-60 z-20 relative"
    >
      <!-- Specular highlight -->
      <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/20 to-transparent pointer-events-none" />

      <!-- Logo -->
      <div class="flex items-center h-14 flex-shrink-0 px-[14px] group-hover/sidebar:px-4 transition-all duration-300">
        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#818cf8] flex items-center justify-center flex-shrink-0 shadow-lg shadow-indigo-500/25">
          <Palette :size="16" color="#fff" />
        </div>
        <span class="text-lg font-bold text-white/90 whitespace-nowrap ml-3 opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300 overflow-hidden">Zentra</span>
      </div>

      <!-- Workspace pill -->
      <div v-if="workspaceStore.currentWorkspace" class="px-[10px] group-hover/sidebar:px-3 mb-2 transition-all duration-300">
        <div
          class="flex items-center gap-2.5 py-2 px-[6px] group-hover/sidebar:px-2 rounded-xl bg-white/[0.06] cursor-pointer hover:bg-white/[0.1] transition-all duration-300 overflow-hidden border border-white/[0.04]"
          @click="workspaceStore.workspaces.length > 1 ? $router.push('/workspaces') : null"
        >
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#388bfd] to-[#6366f1] flex items-center justify-center flex-shrink-0 text-white text-[11px] font-bold shadow-sm shadow-indigo-500/20">
            {{ workspaceStore.currentWorkspace.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div class="min-w-0 overflow-hidden opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300">
            <p class="text-xs font-semibold text-white/85 truncate leading-tight">{{ workspaceStore.currentWorkspace.name }}</p>
            <p class="text-[10px] text-white/40 capitalize leading-tight">{{ workspaceStore.currentRole || 'membro' }}</p>
          </div>
        </div>
      </div>

      <!-- Separator -->
      <div class="mx-[10px] group-hover/sidebar:mx-3 mb-2 h-px bg-white/[0.06] transition-all duration-300" />

      <!-- Navigation -->
      <nav class="flex-1 px-[10px] group-hover/sidebar:px-3 space-y-1 overflow-y-auto overflow-x-hidden transition-all duration-300">
        <router-link
          v-for="item in navItems"
          :key="item.to"
          :to="item.to"
          class="flex items-center gap-3 py-2.5 px-[8px] group-hover/sidebar:px-2.5 rounded-xl transition-all duration-200 relative overflow-hidden"
          :class="
            isActive(item.to)
              ? 'bg-white/[0.1] text-white border border-white/[0.08] shadow-sm shadow-black/10'
              : 'text-white/45 hover:bg-white/[0.06] hover:text-white/80 border border-transparent'
          "
          :title="item.label"
        >
          <!-- Active indicator bar -->
          <div
            v-if="isActive(item.to)"
            class="absolute left-0 top-1/2 -translate-y-1/2 w-[3px] h-5 bg-gradient-to-b from-[#818cf8] to-[#6366f1] rounded-r-full shadow-sm shadow-indigo-400/30"
          />
          <component
            :is="item.icon"
            :size="20"
            :stroke-width="isActive(item.to) ? 2.2 : 1.8"
            class="flex-shrink-0"
          />
          <span class="text-sm font-medium whitespace-nowrap opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300 overflow-hidden">
            {{ item.label }}
          </span>
          <!-- Unread badge -->
          <span
            v-if="item.badge && item.badge > 0"
            class="ml-auto bg-[#388bfd] text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300 shadow-sm shadow-blue-500/30"
          >
            {{ item.badge > 99 ? '99+' : item.badge }}
          </span>
        </router-link>
      </nav>

      <!-- Upgrade pill (Free plan only) -->
      <router-link
        v-if="isFreePlan"
        to="/plans"
        class="flex items-center gap-3 mx-[10px] group-hover/sidebar:mx-3 mb-2 py-2.5 px-[8px] group-hover/sidebar:px-2.5 rounded-xl bg-gradient-to-r from-[#6366f1]/15 to-[#388bfd]/10 border border-[#6366f1]/20 hover:from-[#6366f1]/25 hover:to-[#388bfd]/15 transition-all duration-300 overflow-hidden"
        title="Fazer upgrade"
      >
        <Sparkles :size="18" class="text-[#818cf8] flex-shrink-0" />
        <span class="text-xs font-semibold text-[#818cf8] whitespace-nowrap opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300">Fazer upgrade</span>
      </router-link>

      <!-- User footer -->
      <div class="px-[10px] group-hover/sidebar:px-3 pb-3 pt-2 border-t border-white/[0.06] transition-all duration-300">
        <!-- Profile link -->
        <router-link
          to="/profile"
          class="flex items-center gap-3 py-2 px-[6px] group-hover/sidebar:px-2 rounded-xl hover:bg-white/[0.06] transition-all duration-300 cursor-pointer overflow-hidden mb-1"
          title="Meu Perfil"
        >
          <UserAvatar :name="authStore.user?.name || ''" :url="authStore.user?.avatar_url" size="sm" />
          <div class="min-w-0 flex-1 overflow-hidden opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300">
            <p class="text-sm font-medium text-white/85 truncate leading-tight">{{ authStore.user?.name }}</p>
            <p class="text-[10px] text-white/35 leading-tight">Meu Perfil</p>
          </div>
        </router-link>

        <!-- Logout -->
        <div
          class="flex items-center gap-3 py-2 px-[6px] group-hover/sidebar:px-2 rounded-xl hover:bg-white/[0.06] transition-all duration-300 cursor-pointer group/user overflow-hidden"
          @click="authStore.logout()"
          title="Sair"
        >
          <div class="w-7 h-7 rounded-full bg-white/[0.06] flex items-center justify-center flex-shrink-0">
            <LogOut :size="14" class="text-white/40 group-hover/user:text-red-400 transition-colors" />
          </div>
          <div class="min-w-0 flex-1 overflow-hidden opacity-0 group-hover/sidebar:opacity-100 transition-opacity duration-300">
            <p class="text-sm font-medium text-white/40 group-hover/user:text-red-400 transition-colors leading-tight">Sair</p>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main content -->
    <main class="flex-1 flex flex-col overflow-hidden p-3 relative z-10">
      <!-- Header (hidden on board detail and chat page) -->
      <header
        v-if="route.name !== 'board' && route.name !== 'chat'"
        class="flex items-center justify-end px-5 h-14 glass rounded-2xl mb-3 flex-shrink-0 relative"
      >
        <!-- Specular -->
        <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/15 to-transparent pointer-events-none" />
        <div class="flex items-center gap-2">
          <NotificationBell />
          <div class="w-px h-6 bg-white/[0.08] mx-1" />
          <router-link to="/profile" class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-white/[0.06] transition-colors cursor-pointer">
            <UserAvatar :name="authStore.user?.name || ''" :url="authStore.user?.avatar_url" size="sm" />
            <span class="text-sm font-medium text-white/80 hidden sm:block">{{ authStore.user?.name?.split(' ')[0] }}</span>
          </router-link>
        </div>
      </header>

      <!-- Notifications toast -->
      <div class="fixed top-4 right-4 z-[100] space-y-2">
        <div
          v-for="notification in notificationsStore.notifications"
          :key="notification.id"
          class="toast-enter glass-elevated rounded-2xl px-4 py-3 max-w-sm flex items-start gap-3"
        >
          <div class="w-5 h-5 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
            :class="notification.type === 'error' ? 'bg-red-500/20 text-red-400' : 'bg-emerald-500/20 text-emerald-400'">
            <AlertCircle v-if="notification.type === 'error'" :size="14" />
            <CheckIcon v-else :size="14" />
          </div>
          <p class="text-sm text-white/90">{{ notification.message }}</p>
        </div>
      </div>

      <!-- Page content -->
      <div
        class="flex-1 overflow-auto"
        :class="route.name === 'board' || route.name === 'chat' ? 'rounded-2xl overflow-hidden' : 'glass-subtle rounded-2xl p-6'"
      >
        <div :class="route.name === 'board' || route.name === 'chat' ? 'h-full' : 'min-h-full flex flex-col'">
          <router-view v-slot="{ Component }">
            <transition name="page-fade" mode="out-in">
              <component :is="Component" />
            </transition>
          </router-view>
          <div v-if="showUpgradeBanner" class="mt-auto pt-8">
            <UpgradeBanner :context="upgradeBannerContext" />
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import { useWorkspaceStore } from '../stores/workspace'
import { useNotificationsStore } from '../stores/notifications'
import { useBoardsStore } from '../stores/boards'
import UserAvatar from '../components/shared/UserAvatar.vue'
import NotificationBell from '../components/shared/NotificationBell.vue'
import UpgradeBanner from '../components/shared/UpgradeBanner.vue'
import {
  Palette,
  LayoutGrid,
  Settings,
  Package,
  CreditCard,
  LogOut,
  Bell,
  AlertCircle,
  Check as CheckIcon,
  MessageSquare,
  User as UserIcon,
  BarChart3,
  KeyRound,
  Sparkles,
  Zap,
  FileStack,
} from 'lucide-vue-next'
import { usePlanLimits } from '../composables/usePlanLimits'

const route = useRoute()
const authStore = useAuthStore()
const workspaceStore = useWorkspaceStore()
const notificationsStore = useNotificationsStore()
const boardsStore = useBoardsStore()
const { planName } = usePlanLimits()
const isFreePlan = computed(() => planName.value === 'Free')

const showUpgradeBanner = computed(() => {
  const excludedRoutes = ['board', 'chat', 'plans']
  return isFreePlan.value && !excludedRoutes.includes(route.name)
})

const upgradeBannerContext = computed(() => {
  if (route.name === 'boards') return 'boards'
  if (route.name === 'settings') return 'settings'
  if (route.name === 'chat') return 'chat'
  return 'dashboard'
})

const pageTitle = computed(() => {
  if (route.name === 'dashboard') return 'Dashboard'
  if (route.name === 'boards') return 'Meus Quadros'
  if (route.name === 'board' && boardsStore.currentBoard) return boardsStore.currentBoard.name
  if (route.name === 'settings') return 'Configuracoes'
  if (route.name === 'plans') return 'Planos'
  if (route.name === 'billing') return 'Faturamento'
  if (route.name === 'profile') return 'Meu Perfil'
  if (route.name === 'api-keys') return 'API Keys'
  if (route.name === 'power-ups') return 'Power-Ups'
  if (route.name === 'templates') return 'Templates'
  return 'Zentra'
})

const navItems = computed(() => {
  const persona = workspaceStore.persona
  const isClient = persona === 'client'
  const items = []

  if (!isClient) {
    items.push({ to: '/dashboard', label: 'Dashboard', icon: BarChart3 })
  }

  items.push({ to: '/boards', label: 'Quadros', icon: LayoutGrid })
  items.push({ to: '/templates', label: 'Templates', icon: FileStack })
  items.push({ to: '/chat', label: 'Chat', icon: MessageSquare, badge: 0 })

  if (!isClient) {
    items.push({ to: '/plans', label: 'Planos', icon: Package })
    items.push({ to: '/billing', label: 'Faturamento', icon: CreditCard })
    items.push({ to: '/power-ups', label: 'Power-Ups', icon: Zap })

    if (workspaceStore.hasApiAccess && workspaceStore.isAdmin) {
      items.push({ to: '/api-keys', label: 'API', icon: KeyRound })
    }

    if (workspaceStore.isAdmin) {
      items.push({ to: '/settings', label: 'Configuracoes', icon: Settings })
    }
  }

  return items
})

function isActive(path) {
  if (path === '/dashboard') return route.path === '/dashboard'
  if (path === '/boards') return route.path === '/boards' || route.path === '/' || route.path.startsWith('/boards/')
  return route.path.startsWith(path)
}

onMounted(() => {
  if (!workspaceStore.currentWorkspace) {
    workspaceStore.fetchWorkspaces()
  }
})
</script>

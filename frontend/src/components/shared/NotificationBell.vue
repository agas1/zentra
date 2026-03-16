<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAppNotificationsStore } from '../../stores/appNotifications'
import { Bell } from 'lucide-vue-next'

const store = useAppNotificationsStore()
const router = useRouter()
const open = ref(false)
const container = ref(null)
let pollInterval = null

function toggle() {
  open.value = !open.value
  if (open.value) store.fetch()
}

function handleClick(notification) {
  if (!notification.read_at) store.markRead(notification.id)
  if (notification.data?.board_id) {
    router.push(`/boards/${notification.data.board_id}`)
    open.value = false
  }
}

function timeAgo(date) {
  const now = new Date()
  const d = new Date(date)
  const diff = Math.floor((now - d) / 1000)
  if (diff < 60) return 'agora'
  if (diff < 3600) return `${Math.floor(diff / 60)}min`
  if (diff < 86400) return `${Math.floor(diff / 3600)}h`
  return `${Math.floor(diff / 86400)}d`
}

const typeIcons = {
  member_assigned: { path: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z', color: 'text-[#579bfc]' },
  comment_added: { path: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', color: 'text-[#a5b4fc]' },
  due_approaching: { path: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', color: 'text-[#d29922]' },
  default: { path: 'M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9', color: 'text-[#8b949e]' },
}

function onClickOutside(e) {
  if (open.value && container.value && !container.value.contains(e.target)) {
    open.value = false
  }
}

function onKeydown(e) {
  if (open.value && e.key === 'Escape') {
    open.value = false
  }
}

onMounted(() => {
  store.fetch()
  pollInterval = setInterval(() => store.fetch(), 60000)
  document.addEventListener('mousedown', onClickOutside)
  document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
  clearInterval(pollInterval)
  document.removeEventListener('mousedown', onClickOutside)
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <div ref="container" class="relative">
    <button
      class="w-9 h-9 rounded-xl flex items-center justify-center text-[#8b949e] hover:bg-[#2d333b] hover:text-[#e6edf3] transition-all duration-200 relative"
      :aria-label="store.unreadCount > 0 ? `Notificacoes (${store.unreadCount} nao lidas)` : 'Notificacoes'"
      :aria-expanded="open"
      aria-haspopup="true"
      @click="toggle"
    >
      <Bell :size="18" aria-hidden="true" />
      <span
        v-if="store.unreadCount > 0"
        class="absolute -top-0.5 -right-0.5 bg-[#388bfd] text-white text-[9px] font-bold rounded-full min-w-[16px] h-[16px] flex items-center justify-center px-1"
        aria-hidden="true"
      >
        {{ store.unreadCount > 99 ? '99+' : store.unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="open"
      class="absolute right-0 top-full mt-2 w-80 glass-elevated rounded-2xl shadow-xl z-50"
      role="menu"
      aria-label="Notificacoes"
    >
      <div class="flex items-center justify-between px-4 py-3 border-b border-white/[0.06]">
        <span class="text-sm font-semibold text-white/90">Notificacoes</span>
        <button
          v-if="store.unreadCount > 0"
          class="text-[10px] text-[#388bfd] hover:underline"
          @click="store.markAllRead()"
        >
          Marcar todas como lidas
        </button>
      </div>

      <div class="max-h-80 overflow-y-auto" aria-live="polite">
        <div v-if="store.loading && store.notifications.length === 0" class="py-8 text-center" role="status">
          <div class="animate-spin w-5 h-5 border-2 border-[#388bfd] border-t-transparent rounded-full mx-auto" aria-hidden="true" />
          <span class="sr-only">Carregando notificacoes...</span>
        </div>

        <div v-else-if="store.notifications.length === 0" class="py-8 text-center" role="status">
          <p class="text-sm text-[#8b949e]">Nenhuma notificacao</p>
        </div>

        <button
          v-for="n in store.notifications"
          :key="n.id"
          class="w-full text-left px-4 py-3 border-b border-[#2d333b] hover:bg-[#1c2128] transition-colors flex items-start gap-3"
          :class="!n.read_at ? 'bg-[#388bfd]/5' : ''"
          role="menuitem"
          :aria-label="`${n.title}${!n.read_at ? ' (nao lida)' : ''} - ${timeAgo(n.created_at)}`"
          @click="handleClick(n)"
        >
          <svg class="w-4 h-4 flex-shrink-0 mt-0.5" :class="(typeIcons[n.type] || typeIcons.default).color" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" :d="(typeIcons[n.type] || typeIcons.default).path" /></svg>
          <div class="flex-1 min-w-0">
            <p class="text-xs leading-snug" :class="!n.read_at ? 'text-[#e6edf3] font-medium' : 'text-[#8b949e]'">
              {{ n.title }}
            </p>
            <p v-if="n.body" class="text-[11px] text-[#6e7681] mt-0.5 truncate">{{ n.body }}</p>
            <p class="text-[10px] text-[#6e7681] mt-1">{{ timeAgo(n.created_at) }}</p>
          </div>
          <div v-if="!n.read_at" class="w-2 h-2 rounded-full bg-[#388bfd] flex-shrink-0 mt-1.5" aria-hidden="true">
            <span class="sr-only">Nao lida</span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

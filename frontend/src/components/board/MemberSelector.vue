<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue'
import { useWorkspaceStore } from '../../stores/workspace'

const props = defineProps({
  cardMembers: { type: Array, default: () => [] },
})
const emit = defineEmits(['toggle'])

const workspaceStore = useWorkspaceStore()
const isDark = inject('boardIsDark', ref(true))
const open = ref(false)
const search = ref('')
const container = ref(null)

const filteredMembers = computed(() => {
  const members = workspaceStore.members || []
  if (!search.value) return members
  const q = search.value.toLowerCase()
  return members.filter(m => m.name.toLowerCase().includes(q))
})

function isAssigned(userId) {
  return props.cardMembers.some(m => m.id === userId)
}

function toggle(userId) {
  emit('toggle', userId, isAssigned(userId))
}

const avatarColors = ['#0073ea', '#00c875', '#e44258', '#fdab3d', '#a25ddc', '#037f4c', '#579bfc', '#ff642e']
function getAvatarColor(name) {
  const hash = name?.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) || 0
  return avatarColors[hash % avatarColors.length]
}

function onClickOutside(e) {
  if (open.value && container.value && !container.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="container" class="relative">
    <button class="sidebar-btn" @click="open = !open">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Membros
    </button>

    <div v-if="open" :class="isDark ? 'bg-[#1c2128] border-[#444c56]' : 'bg-white border-gray-200'" class="absolute right-0 mt-1 w-64 rounded-lg shadow-lg border z-50 p-3">
      <div class="flex items-center justify-between mb-2">
        <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-semibold">Membros</span>
        <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" @click="open = false">&times;</button>
      </div>
      <input v-model="search" class="monday-input text-sm mb-2" placeholder="Buscar membros..." />
      <div class="max-h-48 overflow-y-auto space-y-1">
        <button
          v-for="member in filteredMembers"
          :key="member.id"
          :class="isDark ? 'hover:bg-[#2d333b]' : 'hover:bg-gray-100'"
          class="flex items-center gap-2 w-full px-2 py-1.5 rounded text-sm transition-colors"
          @click="toggle(member.id)"
        >
          <div
            class="w-7 h-7 rounded-full text-white text-xs flex items-center justify-center font-medium"
            :style="{ backgroundColor: getAvatarColor(member.name) }"
          >
            {{ member.name?.charAt(0)?.toUpperCase() }}
          </div>
          <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="flex-1 text-left">{{ member.name }}</span>
          <svg v-if="isAssigned(member.id)" class="w-4 h-4 text-[#6366f1]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        </button>
      </div>
    </div>
  </div>
</template>

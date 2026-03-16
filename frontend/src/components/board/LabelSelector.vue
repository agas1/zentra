<script setup>
import { ref, onMounted, onUnmounted, inject } from 'vue'

const props = defineProps({
  boardLabels: { type: Array, default: () => [] },
  cardLabels: { type: Array, default: () => [] },
})
const emit = defineEmits(['toggle'])

const isDark = inject('boardIsDark', ref(true))
const open = ref(false)
const container = ref(null)

function isActive(labelId) {
  return props.cardLabels.some(l => l.id === labelId)
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
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
      Etiquetas
    </button>

    <div v-if="open" :class="isDark ? 'bg-[#1c2128] border-[#444c56]' : 'bg-white border-gray-200'" class="absolute right-0 mt-1 w-64 rounded-lg shadow-lg border z-50 p-3">
      <div class="flex items-center justify-between mb-2">
        <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-semibold">Etiquetas</span>
        <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" @click="open = false">&times;</button>
      </div>
      <div class="space-y-1">
        <button
          v-for="label in boardLabels"
          :key="label.id"
          :class="isDark ? 'hover:bg-[#2d333b]' : 'hover:bg-gray-100'"
          class="flex items-center gap-2 w-full px-2 py-1.5 rounded transition-colors"
          @click="emit('toggle', label.id)"
        >
          <span class="w-full h-8 rounded flex items-center px-3 text-sm font-medium text-white" :style="{ backgroundColor: label.color }">
            {{ label.name || '' }}
          </span>
          <svg v-if="isActive(label.id)" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="w-4 h-4 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
        </button>
      </div>
    </div>
  </div>
</template>

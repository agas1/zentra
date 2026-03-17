<script setup>
import { ref, watch, onMounted, onUnmounted, inject } from 'vue'
import Calendar from '../shared/Calendar.vue'

const props = defineProps({
  dueDate: { type: String, default: null },
  dueCompleted: { type: Boolean, default: false },
})
const emit = defineEmits(['save', 'remove'])

const isDark = inject('boardIsDark', ref(true))
const open = ref(false)
const date = ref(props.dueDate ? props.dueDate.slice(0, 10) : '')
const container = ref(null)

watch(() => props.dueDate, (val) => {
  date.value = val ? val.slice(0, 10) : ''
})

watch(date, (val) => {
  if (val) {
    emit('save', val)
  }
})

function save() {
  if (date.value) {
    emit('save', date.value)
    open.value = false
  }
}

function remove() {
  emit('remove')
  open.value = false
}

function setQuickDate(days) {
  const d = new Date()
  d.setDate(d.getDate() + days)
  date.value = d.toISOString().slice(0, 10)
}

function formatShortDate(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr + 'T00:00:00').toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
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
    <button class="sidebar-btn" :aria-expanded="open" aria-haspopup="dialog" @click="open = !open">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
      <span class="flex-1 text-left">Data Limite</span>
    </button>
    <div
      v-if="dueDate"
      class="flex items-center gap-1.5 mt-1 px-3 py-1.5 rounded-lg text-xs font-medium"
      :class="dueCompleted ? 'bg-[#3fb950]/15 text-[#3fb950]' : 'bg-[#6366f1]/15 text-[#a5b4fc]'"
    >
      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      {{ formatShortDate(dueDate) }}
    </div>

    <div
      v-if="open"
      :class="isDark ? 'bg-[#1c2128] border-[#444c56]' : 'bg-white border-gray-200'"
      class="absolute right-0 mt-1 w-72 rounded-xl shadow-xl border z-50 p-4 animate-scale-in"
      role="dialog"
      aria-label="Selecionar data limite"
    >
      <!-- Header -->
      <div class="flex items-center justify-between mb-3">
        <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-semibold">Data Limite</span>
        <button
          class="w-6 h-6 rounded-md flex items-center justify-center transition-colors text-base leading-none"
          :class="isDark ? 'text-[#6e7681] hover:bg-[#2d333b] hover:text-[#8b949e]' : 'text-gray-400 hover:bg-gray-100 hover:text-gray-600'"
          @click="open = false"
        >
          &times;
        </button>
      </div>

      <!-- Quick date options -->
      <div class="flex gap-1.5 mb-3">
        <button
          v-for="opt in [{ label: 'Hoje', days: 0 }, { label: 'Amanha', days: 1 }, { label: '1 Semana', days: 7 }]"
          :key="opt.days"
          :class="isDark ? 'bg-[#2d333b] hover:bg-[#444c56] text-[#e6edf3]' : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
          class="flex-1 text-[11px] font-medium py-1.5 px-2 rounded-lg transition-colors"
          @click="setQuickDate(opt.days)"
        >
          {{ opt.label }}
        </button>
      </div>

      <!-- Calendar -->
      <div
        class="rounded-lg p-2 mb-3"
        :class="isDark ? 'bg-[#0d1117]/50' : 'bg-gray-50'"
      >
        <Calendar v-model="date" :is-dark="isDark" />
      </div>

      <!-- Selected date display -->
      <div v-if="date" class="mb-3">
        <div
          class="text-xs text-center py-1.5 rounded-lg font-medium"
          :class="isDark ? 'bg-[#6366f1]/10 text-[#a5b4fc] border border-[#6366f1]/20' : 'bg-indigo-50 text-indigo-600 border border-indigo-200'"
        >
          {{ new Date(date + 'T00:00:00').toLocaleDateString('pt-BR', { weekday: 'long', day: 'numeric', month: 'long' }) }}
        </div>
      </div>

      <!-- Actions -->
      <div class="flex gap-2">
        <button
          class="monday-btn monday-btn-primary text-xs !py-1.5 flex-1 !rounded-lg"
          :class="{ 'opacity-40 pointer-events-none': !date }"
          @click="save"
        >
          Salvar
        </button>
        <button
          v-if="dueDate"
          class="monday-btn monday-btn-secondary text-xs !py-1.5 !rounded-lg"
          @click="remove"
        >
          Remover
        </button>
      </div>
    </div>
  </div>
</template>

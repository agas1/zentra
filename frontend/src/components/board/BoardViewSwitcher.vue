<script setup>
import { inject } from 'vue'
import { LayoutGrid, List, Calendar } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: String, default: 'kanban' },
})
const emit = defineEmits(['update:modelValue'])

const isDark = inject('boardIsDark', true)

const views = [
  { id: 'kanban', label: 'Kanban', icon: LayoutGrid },
  { id: 'list', label: 'Lista', icon: List },
  { id: 'calendar', label: 'Calendario', icon: Calendar },
]
</script>

<template>
  <div
    class="flex rounded-xl p-1 border backdrop-blur-md"
    :class="isDark ? 'bg-black/50 border-white/15' : 'bg-white/70 border-black/10 shadow-lg'"
    role="tablist"
    aria-label="Modo de visualizacao"
  >
    <button
      v-for="view in views"
      :key="view.id"
      role="tab"
      :aria-selected="modelValue === view.id"
      :aria-label="`Visualizacao ${view.label}`"
      class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-all duration-200"
      :class="modelValue === view.id
        ? (isDark ? 'bg-[#6366f1] text-white shadow-md shadow-[#6366f1]/30' : 'bg-[#6366f1] text-white shadow-md shadow-[#6366f1]/30')
        : (isDark ? 'text-white/60 hover:text-white hover:bg-white/10' : 'text-gray-500 hover:text-gray-800 hover:bg-black/5')"
      @click="emit('update:modelValue', view.id)"
    >
      <component :is="view.icon" :size="18" aria-hidden="true" />
      {{ view.label }}
    </button>
  </div>
</template>

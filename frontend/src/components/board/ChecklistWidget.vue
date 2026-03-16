<script setup>
import { ref, computed, inject } from 'vue'

const props = defineProps({
  checklist: { type: Object, required: true },
})
const emit = defineEmits(['toggle-item', 'add-item', 'delete-item', 'delete-checklist'])

const isDark = inject('boardIsDark', ref(true))

const newItemTitle = ref('')
const showAddItem = ref(false)

const total = computed(() => props.checklist.items?.length || 0)
const checked = computed(() => props.checklist.items?.filter(i => i.is_checked).length || 0)
const percent = computed(() => total.value > 0 ? Math.round((checked.value / total.value) * 100) : 0)

function submitItem() {
  const title = newItemTitle.value.trim()
  if (!title) return
  emit('add-item', props.checklist.id, title)
  newItemTitle.value = ''
}
</script>

<template>
  <div class="mb-4">
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-2">
        <svg :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <h4 :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-semibold text-sm">{{ checklist.title }}</h4>
      </div>
      <button :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="text-xs hover:text-[#f85149] transition-colors" @click="emit('delete-checklist', checklist.id)">Excluir</button>
    </div>

    <!-- Progress bar -->
    <div class="flex items-center gap-2 mb-2">
      <span :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs w-8 text-right">{{ percent }}%</span>
      <div :class="isDark ? 'bg-[#2d333b]' : 'bg-[#e2e8f0]'" class="flex-1 rounded-full h-1.5">
        <div
          class="h-1.5 rounded-full transition-all duration-300"
          :class="percent === 100 ? 'bg-[#3fb950]' : 'bg-[#6366f1]'"
          :style="{ width: `${percent}%` }"
        />
      </div>
    </div>

    <!-- Items -->
    <div class="space-y-0.5">
      <div
        v-for="item in checklist.items"
        :key="item.id"
        :class="isDark ? 'hover:bg-[#2d333b]' : 'hover:bg-gray-100'"
        class="flex items-center gap-2 group py-1 px-2 rounded-md transition-colors"
      >
        <input
          type="checkbox"
          :checked="item.is_checked"
          :class="isDark ? 'border-[#444c56] bg-[#0d1117]' : 'border-gray-300 bg-white'"
          class="rounded text-[#6366f1] cursor-pointer"
          @change="emit('toggle-item', item.id)"
        />
        <span class="flex-1 text-sm transition-colors" :class="item.is_checked ? (isDark ? 'line-through text-[#6e7681]' : 'line-through text-gray-400') : (isDark ? 'text-[#e6edf3]' : 'text-gray-900')">
          {{ item.title }}
        </span>
        <button :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="hover:text-[#f85149] opacity-0 group-hover:opacity-100 transition-all text-xs" @click="emit('delete-item', item.id)">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>

    <!-- Add item -->
    <div v-if="showAddItem" class="mt-2">
      <input
        v-model="newItemTitle"
        class="monday-input text-sm"
        placeholder="Adicionar item..."
        @keyup.enter="submitItem"
      />
      <div class="flex gap-2 mt-1">
        <button class="monday-btn monday-btn-primary text-xs !py-1 !px-2" @click="submitItem">Adicionar</button>
        <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" class="text-sm transition-colors" @click="showAddItem = false">Cancelar</button>
      </div>
    </div>
    <button v-else :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-500 hover:text-gray-900'" class="text-sm mt-2 transition-colors" @click="showAddItem = true">
      + Adicionar item
    </button>
  </div>
</template>

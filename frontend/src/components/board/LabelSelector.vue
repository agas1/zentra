<script setup>
import { ref, onMounted, onUnmounted, inject } from 'vue'
import { boardsApi } from '../../api/boards'
import { useBoardsStore } from '../../stores/boards'

const props = defineProps({
  boardLabels: { type: Array, default: () => [] },
  cardLabels: { type: Array, default: () => [] },
})
const emit = defineEmits(['toggle'])

const isDark = inject('boardIsDark', ref(true))
const boardsStore = useBoardsStore()
const open = ref(false)
const container = ref(null)
const mode = ref('select') // 'select' | 'create' | 'edit'
const editingLabel = ref(null)

const newLabel = ref({ name: '', color: '#6366f1' })
const saving = ref(false)

const presetColors = [
  '#6366f1', '#818cf8', '#388bfd', '#3fb950', '#d29922',
  '#f85149', '#f0883e', '#a371f7', '#db61a2', '#6e7681',
  '#2ea043', '#0969da', '#8b949e', '#bf4b8a', '#e3b341',
]

function isActive(labelId) {
  return props.cardLabels.some(l => l.id === labelId)
}

function startCreate() {
  newLabel.value = { name: '', color: '#6366f1' }
  editingLabel.value = null
  mode.value = 'create'
}

function startEdit(label) {
  newLabel.value = { name: label.name || '', color: label.color }
  editingLabel.value = label
  mode.value = 'edit'
}

async function saveLabel() {
  saving.value = true
  try {
    const boardId = boardsStore.currentBoard?.id
    if (!boardId) return

    if (mode.value === 'edit' && editingLabel.value) {
      await boardsApi.updateLabel(boardId, editingLabel.value.id, newLabel.value)
    } else {
      await boardsApi.createLabel(boardId, newLabel.value)
    }
    await boardsStore.fetchBoard(boardId)
    mode.value = 'select'
  } finally {
    saving.value = false
  }
}

async function deleteLabel() {
  if (!editingLabel.value) return
  const boardId = boardsStore.currentBoard?.id
  if (!boardId) return
  await boardsApi.deleteLabel(boardId, editingLabel.value.id)
  await boardsStore.fetchBoard(boardId)
  mode.value = 'select'
}

function onClickOutside(e) {
  if (open.value && container.value && !container.value.contains(e.target)) {
    open.value = false
    mode.value = 'select'
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="container" class="relative">
    <button class="sidebar-btn" @click="open = !open; mode = 'select'">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
      Etiquetas
    </button>

    <div
      v-if="open"
      :class="isDark ? 'bg-[#1c2128]/95 border-white/[0.08]' : 'bg-white/95 border-gray-200'"
      class="absolute right-0 mt-1 w-72 rounded-xl shadow-xl backdrop-blur-xl border z-50 p-3 animate-scale-in"
    >
      <!-- SELECT MODE -->
      <template v-if="mode === 'select'">
        <div class="flex items-center justify-between mb-2.5">
          <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-semibold">Etiquetas</span>
          <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" class="text-lg leading-none" @click="open = false">&times;</button>
        </div>

        <div v-if="boardLabels.length === 0" class="text-center py-4">
          <p class="text-xs mb-2" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Nenhuma etiqueta criada</p>
        </div>

        <div v-else class="space-y-1 mb-3 max-h-48 overflow-y-auto">
          <div
            v-for="label in boardLabels"
            :key="label.id"
            class="flex items-center gap-1.5 group"
          >
            <button
              :class="isDark ? 'hover:bg-white/[0.06]' : 'hover:bg-gray-50'"
              class="flex items-center gap-2 flex-1 px-2 py-1.5 rounded-lg transition-colors"
              @click="emit('toggle', label.id)"
            >
              <span
                class="w-full h-7 rounded-md flex items-center px-2.5 text-xs font-medium text-white shadow-sm"
                :style="{ backgroundColor: label.color }"
              >
                {{ label.name || '' }}
              </span>
              <svg
                v-if="isActive(label.id)"
                class="w-4 h-4 flex-shrink-0 text-[#6366f1]"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </button>
            <button
              class="w-6 h-6 flex items-center justify-center rounded-md opacity-0 group-hover:opacity-100 transition-opacity"
              :class="isDark ? 'hover:bg-white/[0.08] text-[#8b949e]' : 'hover:bg-gray-100 text-gray-400'"
              @click="startEdit(label)"
            >
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
            </button>
          </div>
        </div>

        <button
          class="w-full flex items-center justify-center gap-1.5 text-xs font-medium py-2 rounded-lg transition-colors"
          :class="isDark ? 'bg-white/[0.06] hover:bg-white/[0.1] text-white/70' : 'bg-gray-50 hover:bg-gray-100 text-gray-600'"
          @click="startCreate"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
          Criar etiqueta
        </button>
      </template>

      <!-- CREATE / EDIT MODE -->
      <template v-else>
        <div class="flex items-center gap-2 mb-3">
          <button
            class="w-6 h-6 flex items-center justify-center rounded-md transition-colors"
            :class="isDark ? 'hover:bg-white/[0.08] text-[#8b949e]' : 'hover:bg-gray-100 text-gray-400'"
            @click="mode = 'select'"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-semibold">
            {{ mode === 'edit' ? 'Editar etiqueta' : 'Criar etiqueta' }}
          </span>
        </div>

        <!-- Preview -->
        <div
          class="h-9 rounded-lg flex items-center px-3 text-sm font-medium text-white mb-3 shadow-sm"
          :style="{ backgroundColor: newLabel.color }"
        >
          {{ newLabel.name || 'Preview' }}
        </div>

        <!-- Name input -->
        <input
          v-model="newLabel.name"
          type="text"
          placeholder="Nome da etiqueta..."
          class="w-full text-sm rounded-lg px-3 py-2 border mb-3 focus:outline-none focus:ring-1"
          :class="isDark
            ? 'bg-white/[0.04] border-white/[0.08] text-white/90 placeholder-white/25 focus:border-[#6366f1]/50 focus:ring-[#6366f1]/30'
            : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400 focus:border-indigo-400 focus:ring-indigo-200'"
          @keydown.enter="saveLabel"
        />

        <!-- Color picker -->
        <p class="text-[10px] font-semibold uppercase mb-1.5" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Cor</p>
        <div class="flex flex-wrap gap-1.5 mb-3">
          <button
            v-for="color in presetColors"
            :key="color"
            class="w-7 h-7 rounded-md transition-all hover:scale-110"
            :class="newLabel.color === color ? 'ring-2 ring-offset-1 ring-[#6366f1]' : ''"
            :style="{ backgroundColor: color, '--tw-ring-offset-color': isDark ? '#1c2128' : '#fff' }"
            @click="newLabel.color = color"
          />
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <button
            class="flex-1 text-xs font-medium py-2 rounded-lg bg-[#6366f1] text-white hover:bg-[#5558e6] transition-colors disabled:opacity-50"
            :disabled="saving"
            @click="saveLabel"
          >
            {{ saving ? 'Salvando...' : (mode === 'edit' ? 'Salvar' : 'Criar') }}
          </button>
          <button
            v-if="mode === 'edit'"
            class="text-xs font-medium py-2 px-3 rounded-lg transition-colors"
            :class="isDark ? 'text-[#f85149] hover:bg-[#f85149]/10' : 'text-red-500 hover:bg-red-50'"
            @click="deleteLabel"
          >
            Excluir
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

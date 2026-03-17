<script setup>
import { ref, computed, inject } from 'vue'
import draggable from 'vuedraggable'
import BoardCard from './BoardCard.vue'

const props = defineProps({
  list: { type: Object, required: true },
  boardId: { type: String, required: true },
})
const emit = defineEmits(['card-click', 'add-card', 'rename', 'archive', 'cards-updated'])

const isDark = inject('boardIsDark', ref(true))
const cardFilter = inject('cardFilter', computed(() => ({ text: '', memberId: '', labelId: '', due: '' })))

const showColorPicker = ref(false)
const listColor = ref(props.list.color || '')
const listBorderColor = ref(props.list.border_color || '')

const presetColors = [
  '', '#6366f1', '#818cf8', '#388bfd', '#3fb950', '#d29922',
  '#f85149', '#f0883e', '#a371f7', '#db61a2', '#2ea043',
]

const columnStyle = computed(() => {
  const style = {}
  if (listColor.value) {
    style.backgroundColor = listColor.value + '18'
  }
  if (listBorderColor.value) {
    style.borderColor = listBorderColor.value + '40'
  }
  return style
})

function applyColor(color, type) {
  if (type === 'bg') {
    listColor.value = color
  } else {
    listBorderColor.value = color
  }
  // Persist via API
  const boardsApi = import('../../api/boards').then(m => {
    m.boardsApi.updateList(props.boardId, props.list.id, {
      color: listColor.value || null,
      border_color: listBorderColor.value || null,
    })
  })
}

function matchesFilter(card) {
  const f = cardFilter.value
  if (!f.text && !f.memberId && !f.labelId && !f.due) return true

  if (f.text && !card.title?.toLowerCase().includes(f.text)) return false

  if (f.memberId && !card.members?.some(m => m.id === f.memberId)) return false

  if (f.labelId && !card.labels?.some(l => l.id === f.labelId)) return false

  if (f.due) {
    const now = new Date()
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const dueDate = card.due_date ? new Date(card.due_date) : null
    if (f.due === 'none' && dueDate) return false
    if (f.due === 'overdue' && (!dueDate || dueDate >= today)) return false
    if (f.due === 'today' && (!dueDate || dueDate.toDateString() !== now.toDateString())) return false
    if (f.due === 'week') {
      const weekAhead = new Date(today)
      weekAhead.setDate(weekAhead.getDate() + 7)
      if (!dueDate || dueDate < today || dueDate > weekAhead) return false
    }
  }

  return true
}

const filteredCards = computed(() => {
  if (!props.list.cards) return []
  return props.list.cards.filter(matchesFilter)
})

const isEditing = ref(false)
const editName = ref('')
const showAddCard = ref(false)
const newCardTitle = ref('')
const showMenu = ref(false)

function startEdit() {
  editName.value = props.list.name
  isEditing.value = true
}

function saveEdit() {
  if (editName.value.trim() && editName.value !== props.list.name) {
    emit('rename', props.list.id, editName.value.trim())
  }
  isEditing.value = false
}

function submitCard() {
  const title = newCardTitle.value.trim()
  if (!title) return
  emit('add-card', props.list.id, { title })
  newCardTitle.value = ''
}

function onCardsChange() {
  emit('cards-updated', props.list.id, props.list.cards)
}

function archiveList() {
  showMenu.value = false
  emit('archive', props.list.id)
}
</script>

<template>
  <div
    class="board-list-column rounded-2xl w-72 flex-shrink-0 flex flex-col max-h-[calc(100vh-140px)] animate-fade-in-up backdrop-blur-xl border"
    :class="isDark ? 'bg-white/[0.06] border-white/[0.08] shadow-lg shadow-black/20' : 'bg-white/50 border-black/[0.06] shadow-lg shadow-black/5'"
    :style="columnStyle"
  >
    <!-- Header -->
    <div class="flex items-center justify-between px-3 pt-2.5 pb-1.5">
      <input
        v-if="isEditing"
        v-model="editName"
        class="text-sm font-semibold rounded px-1.5 py-0.5 w-full border"
        :class="isDark ? 'text-[#e6edf3] bg-[#0d1117] border-[#6366f1]' : 'text-gray-900 bg-gray-50 border-indigo-500'"
        @blur="saveEdit"
        @keyup.enter="saveEdit"
      />
      <h3
        v-else
        class="text-sm font-semibold cursor-pointer flex-1 px-1"
        :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'"
        @click="startEdit"
      >
        {{ list.name }}
      </h3>
      <div class="relative">
        <button
          class="w-7 h-7 flex items-center justify-center rounded-md transition-colors"
          :class="isDark ? 'text-[#8b949e] hover:bg-[#2d333b]' : 'text-gray-400 hover:bg-gray-100'"
          @click="showMenu = !showMenu"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><circle cx="4" cy="10" r="2"/><circle cx="10" cy="10" r="2"/><circle cx="16" cy="10" r="2"/></svg>
        </button>
        <div
          v-if="showMenu"
          class="absolute right-0 top-8 w-48 rounded-xl shadow-xl backdrop-blur-xl border z-50 py-1"
          :class="isDark ? 'bg-white/[0.08] border-white/[0.1]' : 'bg-white/80 border-black/[0.06]'"
        >
          <button
            class="w-full text-left px-3 py-2 text-sm"
            :class="isDark ? 'text-[#e6edf3] hover:bg-[#2d333b]' : 'text-gray-700 hover:bg-gray-100'"
            @click="showMenu = false; showColorPicker = !showColorPicker"
          >Cor da coluna</button>
          <button
            class="w-full text-left px-3 py-2 text-sm"
            :class="isDark ? 'text-[#e6edf3] hover:bg-[#2d333b]' : 'text-gray-700 hover:bg-gray-100'"
            @click="archiveList"
          >Arquivar lista</button>
          <button
            class="w-full text-left px-3 py-2 text-sm"
            :class="isDark ? 'text-[#e6edf3] hover:bg-[#2d333b]' : 'text-gray-700 hover:bg-gray-100'"
            @click="showMenu = false; showAddCard = true"
          >Adicionar cartao</button>
        </div>
      </div>
    </div>

    <!-- Color picker -->
    <div v-if="showColorPicker" class="px-3 pb-2">
      <div
        class="rounded-xl p-3 border"
        :class="isDark ? 'bg-white/[0.04] border-white/[0.06]' : 'bg-white/60 border-black/[0.04]'"
      >
        <div class="flex items-center justify-between mb-2">
          <span class="text-[10px] font-semibold uppercase" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Fundo</span>
          <button class="text-xs" :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" @click="showColorPicker = false">&times;</button>
        </div>
        <div class="flex flex-wrap gap-1.5 mb-2.5">
          <button
            v-for="color in presetColors"
            :key="'bg-' + color"
            class="w-5 h-5 rounded-md transition-all hover:scale-125"
            :class="[
              listColor === color ? 'ring-2 ring-[#6366f1] ring-offset-1' : '',
              !color ? (isDark ? 'bg-white/10 border border-white/20' : 'bg-gray-200') : ''
            ]"
            :style="color ? { backgroundColor: color } : { '--tw-ring-offset-color': isDark ? '#1c2128' : '#fff' }"
            :title="color || 'Sem cor'"
            @click="applyColor(color, 'bg')"
          />
        </div>
        <span class="text-[10px] font-semibold uppercase" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Borda</span>
        <div class="flex flex-wrap gap-1.5 mt-1.5">
          <button
            v-for="color in presetColors"
            :key="'border-' + color"
            class="w-5 h-5 rounded-md transition-all hover:scale-125"
            :class="[
              listBorderColor === color ? 'ring-2 ring-[#6366f1] ring-offset-1' : '',
              !color ? (isDark ? 'bg-white/10 border border-white/20' : 'bg-gray-200') : ''
            ]"
            :style="color ? { backgroundColor: color } : { '--tw-ring-offset-color': isDark ? '#1c2128' : '#fff' }"
            :title="color || 'Sem cor'"
            @click="applyColor(color, 'border')"
          />
        </div>
      </div>
    </div>

    <!-- Cards -->
    <div class="px-2 pb-1 overflow-y-auto flex-1">
      <draggable
        :list="list.cards"
        group="cards"
        item-key="id"
        class="space-y-1.5 min-h-[2px]"
        ghost-class="opacity-30"
        @change="onCardsChange"
      >
        <template #item="{ element }">
          <BoardCard v-show="matchesFilter(element)" class="board-card-item" :card="element" @click="emit('card-click', element)" />
        </template>
      </draggable>
    </div>

    <!-- Add card -->
    <div class="px-2 pb-2">
      <div v-if="showAddCard">
        <textarea
          v-model="newCardTitle"
          class="w-full text-sm border rounded-lg p-2 resize-none focus:outline-none"
          :class="isDark ? 'border-[#444c56] focus:border-[#388bfd] bg-[#0d1117] text-[#e6edf3]' : 'border-gray-300 focus:border-indigo-500 bg-white text-gray-900'"
          rows="3"
          placeholder="Insira um titulo para este cartao..."
          @keyup.enter.prevent="submitCard"
        />
        <div class="flex items-center gap-2 mt-1">
          <button class="monday-btn monday-btn-primary text-xs !py-1.5 !px-3" @click="submitCard">Adicionar cartao</button>
          <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" class="text-xl leading-none" @click="showAddCard = false">&times;</button>
        </div>
      </div>
      <button
        v-else
        class="board-add-card-btn w-full flex items-center gap-1.5 text-sm rounded-lg px-2 py-1.5 transition-colors"
        :class="isDark ? 'text-[#8b949e] hover:bg-[#2d333b]' : 'text-gray-500 hover:bg-gray-100'"
        @click="showAddCard = true"
      >
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Adicionar um cartao
      </button>
    </div>
  </div>
</template>

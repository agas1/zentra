<script setup>
import { ref, computed, inject } from 'vue'
import { ChevronDown, ChevronRight, Paperclip, MessageSquare, CheckSquare, Clock } from 'lucide-vue-next'

const props = defineProps({
  board: { type: Object, required: true },
})
const emit = defineEmits(['card-click'])

const isDark = inject('boardIsDark', ref(true))
const cardFilter = inject('cardFilter', computed(() => ({ text: '', memberId: '', labelId: '', due: '' })))

const collapsedLists = ref({})
const sortBy = ref('position')
const sortDir = ref('asc')

function toggleList(listId) {
  collapsedLists.value[listId] = !collapsedLists.value[listId]
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

function getFilteredCards(list) {
  if (!list.cards) return []
  let cards = list.cards.filter(c => !c.is_archived).filter(matchesFilter)
  if (sortBy.value !== 'position') {
    cards = [...cards].sort((a, b) => {
      let va, vb
      if (sortBy.value === 'title') {
        va = a.title?.toLowerCase() || ''
        vb = b.title?.toLowerCase() || ''
      } else if (sortBy.value === 'due_date') {
        va = a.due_date || '9999'
        vb = b.due_date || '9999'
      }
      if (va < vb) return sortDir.value === 'asc' ? -1 : 1
      if (va > vb) return sortDir.value === 'asc' ? 1 : -1
      return 0
    })
  }
  return cards
}

function toggleSort(col) {
  if (sortBy.value === col) {
    sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortBy.value = col
    sortDir.value = 'asc'
  }
}

function sortIcon(col) {
  if (sortBy.value !== col) return ''
  return sortDir.value === 'asc' ? ' ↑' : ' ↓'
}

const activeLists = computed(() => {
  if (!props.board?.lists) return []
  return props.board.lists.filter(l => !l.is_archived)
})

const totalCards = computed(() => {
  return activeLists.value.reduce((sum, l) => sum + getFilteredCards(l).length, 0)
})

function formatDueDate(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

function dueStatus(card) {
  if (!card.due_date) return ''
  if (card.due_completed) return 'completed'
  const now = new Date()
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const due = new Date(card.due_date)
  const dueDay = new Date(due.getFullYear(), due.getMonth(), due.getDate())
  if (dueDay < today) return 'overdue'
  if (dueDay.getTime() === today.getTime()) return 'today'
  const tomorrow = new Date(today)
  tomorrow.setDate(tomorrow.getDate() + 1)
  if (dueDay.getTime() === tomorrow.getTime()) return 'soon'
  return 'upcoming'
}

function dueColorClass(status) {
  const map = {
    overdue: 'text-[#f85149] bg-[#f85149]/10',
    today: 'text-[#d29922] bg-[#d29922]/10',
    soon: 'text-[#d29922] bg-[#d29922]/10',
    completed: 'text-[#3fb950] bg-[#3fb950]/10',
    upcoming: isDark.value ? 'text-[#8b949e] bg-[#8b949e]/10' : 'text-gray-500 bg-gray-100',
  }
  return map[status] || ''
}

function checklistProgress(card) {
  if (!card.checklists?.length) return null
  let total = 0, checked = 0
  card.checklists.forEach(cl => {
    cl.items?.forEach(item => {
      total++
      if (item.is_checked) checked++
    })
  })
  if (total === 0) return null
  return { checked, total }
}

function initials(name) {
  return name?.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2) || '?'
}
</script>

<template>
  <div class="flex-1 overflow-auto p-4">
    <div
      class="rounded-xl border overflow-hidden"
      :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200 shadow-sm'"
    >
      <!-- Table header -->
      <div
        class="grid grid-cols-[1fr_140px_140px_120px_100px] gap-0 text-[10px] uppercase tracking-wider font-semibold border-b"
        :class="isDark ? 'bg-[#1c2128] border-[#444c56] text-[#6e7681]' : 'bg-gray-50 border-gray-200 text-gray-500'"
      >
        <button class="text-left px-4 py-2.5 hover:text-[#e6edf3] transition-colors" @click="toggleSort('title')">
          Titulo{{ sortIcon('title') }}
        </button>
        <div class="px-3 py-2.5">Etiquetas</div>
        <div class="px-3 py-2.5">Membros</div>
        <button class="text-left px-3 py-2.5 hover:text-[#e6edf3] transition-colors" @click="toggleSort('due_date')">
          Prazo{{ sortIcon('due_date') }}
        </button>
        <div class="px-3 py-2.5 text-center">Status</div>
      </div>

      <!-- Lists -->
      <div v-for="list in activeLists" :key="list.id">
        <!-- List header -->
        <button
          class="w-full flex items-center gap-2 px-4 py-2 text-left border-b transition-colors"
          :class="isDark ? 'bg-[#0d1117]/50 border-[#2d333b] hover:bg-[#1c2128]' : 'bg-gray-50/80 border-gray-100 hover:bg-gray-100'"
          @click="toggleList(list.id)"
        >
          <component
            :is="collapsedLists[list.id] ? ChevronRight : ChevronDown"
            :size="14"
            :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'"
          />
          <span class="text-sm font-semibold" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
            {{ list.name }}
          </span>
          <span class="text-xs px-1.5 py-0.5 rounded-full" :class="isDark ? 'text-[#8b949e] bg-[#2d333b]' : 'text-gray-500 bg-gray-200'">
            {{ getFilteredCards(list).length }}
          </span>
        </button>

        <!-- Cards rows -->
        <template v-if="!collapsedLists[list.id]">
          <div
            v-for="card in getFilteredCards(list)"
            :key="card.id"
            class="grid grid-cols-[1fr_140px_140px_120px_100px] gap-0 border-b cursor-pointer transition-colors"
            :class="isDark ? 'border-[#2d333b] hover:bg-[#1c2128]' : 'border-gray-100 hover:bg-gray-50'"
            @click="emit('card-click', card)"
          >
            <!-- Title -->
            <div class="px-4 py-2.5 flex items-center gap-2 min-w-0">
              <span class="text-sm truncate" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
                {{ card.title }}
              </span>
              <div class="flex items-center gap-1.5 flex-shrink-0">
                <span v-if="card.attachments?.length" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="flex items-center gap-0.5 text-[10px]">
                  <Paperclip :size="10" /> {{ card.attachments.length }}
                </span>
                <span v-if="card.comments?.length" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="flex items-center gap-0.5 text-[10px]">
                  <MessageSquare :size="10" /> {{ card.comments.length }}
                </span>
              </div>
            </div>

            <!-- Labels -->
            <div class="px-3 py-2.5 flex items-center gap-1 flex-wrap overflow-hidden">
              <span
                v-for="label in (card.labels || []).slice(0, 3)"
                :key="label.id"
                class="text-[9px] font-medium px-1.5 py-0.5 rounded-full text-white truncate max-w-[60px]"
                :style="{ backgroundColor: label.color }"
                :title="label.name || label.color"
              >
                {{ label.name || '&nbsp;' }}
              </span>
              <span v-if="(card.labels?.length || 0) > 3" class="text-[9px]" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">
                +{{ card.labels.length - 3 }}
              </span>
            </div>

            <!-- Members -->
            <div class="px-3 py-2.5 flex items-center gap-0.5">
              <div
                v-for="member in (card.members || []).slice(0, 4)"
                :key="member.id"
                class="w-6 h-6 rounded-full flex items-center justify-center text-[9px] font-bold text-white -ml-1 first:ml-0 ring-1"
                :class="isDark ? 'bg-[#6366f1] ring-[#161b22]' : 'bg-indigo-500 ring-white'"
                :title="member.name"
              >
                {{ initials(member.name) }}
              </div>
              <span v-if="(card.members?.length || 0) > 4" class="text-[9px] ml-1" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">
                +{{ card.members.length - 4 }}
              </span>
            </div>

            <!-- Due date -->
            <div class="px-3 py-2.5 flex items-center">
              <span
                v-if="card.due_date"
                class="text-[11px] font-medium px-2 py-0.5 rounded-md flex items-center gap-1"
                :class="dueColorClass(dueStatus(card))"
              >
                <Clock :size="10" />
                {{ formatDueDate(card.due_date) }}
              </span>
            </div>

            <!-- Checklist progress -->
            <div class="px-3 py-2.5 flex items-center justify-center">
              <template v-if="checklistProgress(card)">
                <div class="flex items-center gap-1.5">
                  <CheckSquare :size="12" :class="checklistProgress(card).checked === checklistProgress(card).total ? 'text-[#3fb950]' : (isDark ? 'text-[#6e7681]' : 'text-gray-400')" />
                  <span class="text-[11px]" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
                    {{ checklistProgress(card).checked }}/{{ checklistProgress(card).total }}
                  </span>
                </div>
              </template>
            </div>
          </div>
        </template>
      </div>

      <!-- Empty -->
      <div v-if="totalCards === 0" class="py-12 text-center">
        <p class="text-sm" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Nenhum card encontrado</p>
      </div>
    </div>
  </div>
</template>

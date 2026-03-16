<script setup>
import { ref, computed, inject } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  board: { type: Object, required: true },
})
const emit = defineEmits(['card-click'])

const isDark = inject('boardIsDark', ref(true))
const cardFilter = inject('cardFilter', computed(() => ({ text: '', memberId: '', labelId: '', due: '' })))

const today = new Date()
const currentMonth = ref(today.getMonth())
const currentYear = ref(today.getFullYear())

const weekDays = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab']

const monthLabel = computed(() => {
  const d = new Date(currentYear.value, currentMonth.value, 1)
  return d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
})

function prevMonth() {
  if (currentMonth.value === 0) {
    currentMonth.value = 11
    currentYear.value--
  } else {
    currentMonth.value--
  }
}

function nextMonth() {
  if (currentMonth.value === 11) {
    currentMonth.value = 0
    currentYear.value++
  } else {
    currentMonth.value++
  }
}

function goToday() {
  currentMonth.value = today.getMonth()
  currentYear.value = today.getFullYear()
}

function matchesFilter(card) {
  const f = cardFilter.value
  if (!f.text && !f.memberId && !f.labelId && !f.due) return true
  if (f.text && !card.title?.toLowerCase().includes(f.text)) return false
  if (f.memberId && !card.members?.some(m => m.id === f.memberId)) return false
  if (f.labelId && !card.labels?.some(l => l.id === f.labelId)) return false
  if (f.due) {
    const now = new Date()
    const todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate())
    const dueDate = card.due_date ? new Date(card.due_date) : null
    if (f.due === 'none' && dueDate) return false
    if (f.due === 'overdue' && (!dueDate || dueDate >= todayDate)) return false
    if (f.due === 'today' && (!dueDate || dueDate.toDateString() !== now.toDateString())) return false
    if (f.due === 'week') {
      const weekAhead = new Date(todayDate)
      weekAhead.setDate(weekAhead.getDate() + 7)
      if (!dueDate || dueDate < todayDate || dueDate > weekAhead) return false
    }
  }
  return true
}

const allCards = computed(() => {
  if (!props.board?.lists) return []
  const cards = []
  props.board.lists.forEach(list => {
    if (list.is_archived) return
    list.cards?.forEach(card => {
      if (!card.is_archived && matchesFilter(card)) {
        cards.push({ ...card, listName: list.name, listColor: list.color })
      }
    })
  })
  return cards
})

const cardsWithDue = computed(() => allCards.value.filter(c => c.due_date))
const cardsWithoutDue = computed(() => allCards.value.filter(c => !c.due_date))

// Build calendar grid
const calendarDays = computed(() => {
  const year = currentYear.value
  const month = currentMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDow = firstDay.getDay() // 0=Sun
  const totalDays = lastDay.getDate()

  const days = []

  // Leading blanks
  for (let i = 0; i < startDow; i++) {
    days.push({ day: null, date: null })
  }

  // Actual days
  for (let d = 1; d <= totalDays; d++) {
    const date = new Date(year, month, d)
    days.push({ day: d, date })
  }

  // Trailing blanks to fill grid to 42 cells (6 weeks)
  while (days.length < 42) {
    days.push({ day: null, date: null })
  }

  return days
})

function cardsForDay(date) {
  if (!date) return []
  const dateStr = date.toISOString().split('T')[0]
  return cardsWithDue.value.filter(card => {
    const cardDate = new Date(card.due_date)
    const cardStr = cardDate.getFullYear() + '-' +
      String(cardDate.getMonth() + 1).padStart(2, '0') + '-' +
      String(cardDate.getDate()).padStart(2, '0')
    return cardStr === dateStr
  })
}

function isToday(date) {
  if (!date) return false
  return date.toDateString() === today.toDateString()
}

function dueStatus(card) {
  if (!card.due_date) return ''
  if (card.due_completed) return 'completed'
  const now = new Date()
  const todayDate = new Date(now.getFullYear(), now.getMonth(), now.getDate())
  const due = new Date(card.due_date)
  const dueDay = new Date(due.getFullYear(), due.getMonth(), due.getDate())
  if (dueDay < todayDate) return 'overdue'
  if (dueDay.getTime() === todayDate.getTime()) return 'today'
  return 'upcoming'
}

function cardBorderClass(card) {
  const status = dueStatus(card)
  if (status === 'overdue') return 'border-l-[#f85149]'
  if (status === 'today') return 'border-l-[#d29922]'
  if (status === 'completed') return 'border-l-[#3fb950]'
  return isDark.value ? 'border-l-[#444c56]' : 'border-l-gray-300'
}
</script>

<template>
  <div class="flex-1 overflow-auto p-4">
    <!-- Header: navigation + no-due cards -->
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-3">
        <button
          class="p-1.5 rounded-lg transition-colors"
          :class="isDark ? 'hover:bg-[#2d333b] text-[#8b949e]' : 'hover:bg-gray-200 text-gray-500'"
          aria-label="Mes anterior"
          @click="prevMonth"
        >
          <ChevronLeft :size="18" aria-hidden="true" />
        </button>
        <h2 class="text-lg font-semibold capitalize min-w-[180px] text-center" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
          {{ monthLabel }}
        </h2>
        <button
          class="p-1.5 rounded-lg transition-colors"
          :class="isDark ? 'hover:bg-[#2d333b] text-[#8b949e]' : 'hover:bg-gray-200 text-gray-500'"
          aria-label="Proximo mes"
          @click="nextMonth"
        >
          <ChevronRight :size="18" aria-hidden="true" />
        </button>
        <button
          class="text-xs px-3 py-1 rounded-md transition-colors ml-2"
          :class="isDark ? 'bg-[#2d333b] text-[#8b949e] hover:bg-[#3d444d]' : 'bg-gray-200 text-gray-600 hover:bg-gray-300'"
          @click="goToday"
        >
          Hoje
        </button>
      </div>

      <!-- Cards without due date -->
      <div v-if="cardsWithoutDue.length" class="flex items-center gap-2">
        <span class="text-xs" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">
          Sem prazo: {{ cardsWithoutDue.length }} cards
        </span>
      </div>
    </div>

    <!-- Calendar grid -->
    <div
      class="rounded-xl border overflow-hidden"
      :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200 shadow-sm'"
      role="grid"
      :aria-label="`Calendario - ${monthLabel}`"
    >
      <!-- Week day headers -->
      <div class="grid grid-cols-7 border-b" :class="isDark ? 'border-[#444c56]' : 'border-gray-200'" role="row">
        <div
          v-for="day in weekDays"
          :key="day"
          role="columnheader"
          class="text-center text-[10px] uppercase tracking-wider font-semibold py-2"
          :class="isDark ? 'text-[#6e7681] bg-[#1c2128]' : 'text-gray-500 bg-gray-50'"
        >
          {{ day }}
        </div>
      </div>

      <!-- Days grid -->
      <div class="grid grid-cols-7">
        <div
          v-for="(cell, i) in calendarDays"
          :key="i"
          class="min-h-[100px] border-b border-r p-1"
          :class="[
            isDark ? 'border-[#2d333b]' : 'border-gray-100',
            cell.day ? '' : (isDark ? 'bg-[#0d1117]/30' : 'bg-gray-50/50'),
            isToday(cell.date) ? (isDark ? 'bg-[#6366f1]/5' : 'bg-indigo-50/50') : ''
          ]"
        >
          <!-- Day number -->
          <div v-if="cell.day" class="flex items-center justify-between mb-1">
            <span
              class="text-xs font-medium w-6 h-6 flex items-center justify-center rounded-full"
              :class="[
                isToday(cell.date)
                  ? 'bg-[#6366f1] text-white'
                  : (isDark ? 'text-[#8b949e]' : 'text-gray-500')
              ]"
            >
              {{ cell.day }}
            </span>
          </div>

          <!-- Cards for this day -->
          <div v-if="cell.date" class="space-y-0.5">
            <div
              v-for="card in cardsForDay(cell.date)"
              :key="card.id"
              class="text-[10px] px-1.5 py-1 rounded cursor-pointer border-l-2 transition-colors truncate"
              :class="[
                isDark ? 'bg-[#1c2128] hover:bg-[#2d333b] text-[#e6edf3]' : 'bg-gray-50 hover:bg-gray-100 text-gray-900',
                cardBorderClass(card)
              ]"
              :title="card.title + (card.listName ? ' (' + card.listName + ')' : '')"
              @click="emit('card-click', card)"
            >
              <!-- Labels dots -->
              <div v-if="card.labels?.length" class="flex gap-0.5 mb-0.5">
                <span
                  v-for="label in card.labels.slice(0, 3)"
                  :key="label.id"
                  class="w-3 h-1 rounded-full inline-block"
                  :style="{ backgroundColor: label.color }"
                />
              </div>
              {{ card.title }}
            </div>
            <!-- Overflow indicator -->
            <div
              v-if="cardsForDay(cell.date).length > 3"
              class="text-[9px] text-center py-0.5"
              :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'"
            >
              +{{ cardsForDay(cell.date).length - 3 }} mais
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- No due cards section -->
    <div v-if="cardsWithoutDue.length" class="mt-4">
      <h3 class="text-xs font-semibold uppercase tracking-wider mb-2" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">
        Sem prazo definido ({{ cardsWithoutDue.length }})
      </h3>
      <div
        class="rounded-xl border overflow-hidden"
        :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200 shadow-sm'"
      >
        <div
          v-for="card in cardsWithoutDue"
          :key="card.id"
          class="flex items-center gap-3 px-4 py-2 border-b cursor-pointer transition-colors"
          :class="isDark ? 'border-[#2d333b] hover:bg-[#1c2128]' : 'border-gray-100 hover:bg-gray-50'"
          @click="emit('card-click', card)"
        >
          <div v-if="card.labels?.length" class="flex gap-0.5 flex-shrink-0">
            <span
              v-for="label in card.labels.slice(0, 3)"
              :key="label.id"
              class="w-2 h-2 rounded-full"
              :style="{ backgroundColor: label.color }"
            />
          </div>
          <span class="text-sm truncate" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ card.title }}</span>
          <span class="text-[10px] ml-auto flex-shrink-0" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">{{ card.listName }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

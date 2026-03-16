<script setup>
import { ref, computed, watch } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  modelValue: { type: String, default: '' },
  isDark: { type: Boolean, default: true },
})
const emit = defineEmits(['update:modelValue'])

const today = new Date()
const todayStr = formatDateStr(today)

// Parse initial date or use today
const initial = props.modelValue ? new Date(props.modelValue + 'T00:00:00') : today
const viewMonth = ref(initial.getMonth())
const viewYear = ref(initial.getFullYear())

watch(() => props.modelValue, (val) => {
  if (val) {
    const d = new Date(val + 'T00:00:00')
    viewMonth.value = d.getMonth()
    viewYear.value = d.getFullYear()
  }
})

const weekDays = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']

const monthLabel = computed(() => {
  const d = new Date(viewYear.value, viewMonth.value, 1)
  const str = d.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
  return str.charAt(0).toUpperCase() + str.slice(1)
})

function prevMonth() {
  if (viewMonth.value === 0) {
    viewMonth.value = 11
    viewYear.value--
  } else {
    viewMonth.value--
  }
}

function nextMonth() {
  if (viewMonth.value === 11) {
    viewMonth.value = 0
    viewYear.value++
  } else {
    viewMonth.value++
  }
}

const calendarDays = computed(() => {
  const year = viewYear.value
  const month = viewMonth.value
  const firstDay = new Date(year, month, 1)
  const lastDay = new Date(year, month + 1, 0)
  const startDow = firstDay.getDay()
  const totalDays = lastDay.getDate()

  // Previous month trailing days
  const prevLastDay = new Date(year, month, 0).getDate()
  const days = []
  for (let i = startDow - 1; i >= 0; i--) {
    days.push({ day: prevLastDay - i, current: false, date: null })
  }

  // Current month days
  for (let d = 1; d <= totalDays; d++) {
    const date = new Date(year, month, d)
    days.push({ day: d, current: true, date, dateStr: formatDateStr(date) })
  }

  // Next month leading days
  let nextDay = 1
  while (days.length < 42) {
    days.push({ day: nextDay++, current: false, date: null })
  }

  return days
})

function formatDateStr(date) {
  return date.getFullYear() + '-' +
    String(date.getMonth() + 1).padStart(2, '0') + '-' +
    String(date.getDate()).padStart(2, '0')
}

function selectDay(cell) {
  if (!cell.current || !cell.dateStr) return
  emit('update:modelValue', cell.dateStr)
}

function isSelected(cell) {
  return cell.dateStr === props.modelValue
}

function isToday(cell) {
  return cell.dateStr === todayStr
}

function isPast(cell) {
  return cell.current && cell.dateStr && cell.dateStr < todayStr
}
</script>

<template>
  <div class="select-none" role="grid" :aria-label="`Calendario - ${monthLabel}`">
    <!-- Month navigation -->
    <div class="flex items-center justify-between mb-3">
      <button
        class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
        :class="isDark ? 'hover:bg-[#2d333b] text-[#8b949e]' : 'hover:bg-gray-100 text-gray-500'"
        aria-label="Mes anterior"
        @click="prevMonth"
      >
        <ChevronLeft :size="15" aria-hidden="true" />
      </button>
      <span
        class="text-xs font-semibold tracking-wide"
        :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'"
      >
        {{ monthLabel }}
      </span>
      <button
        class="w-7 h-7 rounded-lg flex items-center justify-center transition-colors"
        :class="isDark ? 'hover:bg-[#2d333b] text-[#8b949e]' : 'hover:bg-gray-100 text-gray-500'"
        aria-label="Proximo mes"
        @click="nextMonth"
      >
        <ChevronRight :size="15" aria-hidden="true" />
      </button>
    </div>

    <!-- Week day headers -->
    <div class="grid grid-cols-7 mb-1">
      <div
        v-for="(wd, i) in weekDays"
        :key="i"
        class="text-center text-[10px] font-medium py-1"
        :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'"
      >
        {{ wd }}
      </div>
    </div>

    <!-- Days grid -->
    <div class="grid grid-cols-7 gap-px">
      <button
        v-for="(cell, i) in calendarDays"
        :key="i"
        class="relative w-full aspect-square flex items-center justify-center rounded-lg text-xs transition-all duration-150"
        :class="[
          cell.current
            ? 'cursor-pointer'
            : 'cursor-default',
          // Not current month
          !cell.current
            ? (isDark ? 'text-[#2d333b]' : 'text-gray-200')
            : '',
          // Current month, normal day
          cell.current && !isSelected(cell) && !isToday(cell)
            ? (isDark
              ? 'text-[#8b949e] hover:bg-[#2d333b] hover:text-[#e6edf3]'
              : 'text-gray-700 hover:bg-gray-100')
            : '',
          // Past day (dimmer)
          cell.current && isPast(cell) && !isSelected(cell) && !isToday(cell)
            ? (isDark ? '!text-[#545d68]' : '!text-gray-400')
            : '',
          // Today
          isToday(cell) && !isSelected(cell)
            ? (isDark
              ? 'text-[#6366f1] font-bold ring-1 ring-[#6366f1]/40'
              : 'text-indigo-600 font-bold ring-1 ring-indigo-300')
            : '',
          // Selected
          isSelected(cell)
            ? 'bg-[#6366f1] text-white font-semibold shadow-sm shadow-[#6366f1]/30'
            : '',
        ]"
        :disabled="!cell.current"
        @click="selectDay(cell)"
      >
        {{ cell.day }}
      </button>
    </div>
  </div>
</template>

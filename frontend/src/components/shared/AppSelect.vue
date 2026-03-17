<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue'

const props = defineProps({
  modelValue: { type: [String, Number], default: '' },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Selecione...' },
  disabled: { type: Boolean, default: false },
  dark: { type: Boolean, default: true },
  size: { type: String, default: 'md' }, // 'sm' | 'md'
  ariaLabel: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const selectRef = ref(null)
const listRef = ref(null)
const focusedIndex = ref(-1)

const selectedLabel = computed(() => {
  const opt = props.options.find(o => (o.value ?? o) === props.modelValue)
  return opt ? (opt.label ?? opt) : ''
})

const normalizedOptions = computed(() =>
  props.options.map(o => (typeof o === 'object' ? o : { value: o, label: o }))
)

function toggle() {
  if (props.disabled) return
  open.value = !open.value
  if (open.value) {
    focusedIndex.value = normalizedOptions.value.findIndex(o => o.value === props.modelValue)
    nextTick(() => listRef.value?.scrollIntoView?.({ block: 'nearest' }))
  }
}

function select(opt) {
  emit('update:modelValue', opt.value)
  open.value = false
}

function onKeydown(e) {
  if (!open.value) {
    if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
      e.preventDefault()
      open.value = true
      focusedIndex.value = 0
    }
    return
  }

  if (e.key === 'Escape') {
    open.value = false
    return
  }
  if (e.key === 'ArrowDown') {
    e.preventDefault()
    focusedIndex.value = Math.min(focusedIndex.value + 1, normalizedOptions.value.length - 1)
  }
  if (e.key === 'ArrowUp') {
    e.preventDefault()
    focusedIndex.value = Math.max(focusedIndex.value - 1, 0)
  }
  if (e.key === 'Enter' || e.key === ' ') {
    e.preventDefault()
    if (focusedIndex.value >= 0) {
      select(normalizedOptions.value[focusedIndex.value])
    }
  }
}

function onClickOutside(e) {
  if (selectRef.value && !selectRef.value.contains(e.target)) {
    open.value = false
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="selectRef" class="relative" :class="disabled ? 'opacity-50 pointer-events-none' : ''">
    <button
      type="button"
      class="w-full flex items-center justify-between gap-2 rounded-lg border text-left transition-all duration-150 cursor-pointer"
      :class="[
        size === 'sm' ? 'text-xs py-1.5 px-2' : 'text-sm py-2 px-3',
        dark
          ? 'bg-white/[0.04] border-white/[0.08] text-white/80 hover:border-white/[0.15] focus:border-[#6366f1]/50 focus:ring-1 focus:ring-[#6366f1]/30'
          : 'bg-white border-gray-200 text-gray-700 hover:border-gray-300 focus:border-indigo-400 focus:ring-1 focus:ring-indigo-200',
        open ? (dark ? 'border-[#6366f1]/50 ring-1 ring-[#6366f1]/30' : 'border-indigo-400 ring-1 ring-indigo-200') : ''
      ]"
      :aria-label="ariaLabel"
      :aria-expanded="open"
      aria-haspopup="listbox"
      @click="toggle"
      @keydown="onKeydown"
    >
      <span :class="selectedLabel ? '' : (dark ? 'text-white/25' : 'text-gray-400')">
        {{ selectedLabel || placeholder }}
      </span>
      <svg
        class="w-3.5 h-3.5 flex-shrink-0 transition-transform duration-200"
        :class="[open ? 'rotate-180' : '', dark ? 'text-white/30' : 'text-gray-400']"
        fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
      </svg>
    </button>

    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="opacity-0 -translate-y-1 scale-[0.97]"
      enter-to-class="opacity-100 translate-y-0 scale-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="opacity-100 translate-y-0 scale-100"
      leave-to-class="opacity-0 -translate-y-1 scale-[0.97]"
    >
      <ul
        v-if="open"
        ref="listRef"
        role="listbox"
        class="absolute z-50 mt-1.5 w-full max-h-52 overflow-y-auto rounded-xl border shadow-xl backdrop-blur-xl"
        :class="dark
          ? 'bg-[#1c2128]/95 border-white/[0.08] shadow-black/40'
          : 'bg-white/95 border-gray-200 shadow-gray-200/50'"
      >
        <li
          v-for="(opt, i) in normalizedOptions"
          :key="opt.value"
          role="option"
          :aria-selected="opt.value === modelValue"
          class="flex items-center justify-between px-3 cursor-pointer transition-colors duration-100"
          :class="[
            size === 'sm' ? 'py-1.5 text-xs' : 'py-2 text-sm',
            opt.value === modelValue
              ? (dark ? 'bg-[#6366f1]/15 text-[#a5b4fc]' : 'bg-indigo-50 text-indigo-700')
              : focusedIndex === i
                ? (dark ? 'bg-white/[0.06] text-white/90' : 'bg-gray-50 text-gray-900')
                : (dark ? 'text-white/70 hover:bg-white/[0.06] hover:text-white/90' : 'text-gray-700 hover:bg-gray-50')
          ]"
          @click="select(opt)"
          @mouseenter="focusedIndex = i"
        >
          <span>{{ opt.label }}</span>
          <svg
            v-if="opt.value === modelValue"
            class="w-3.5 h-3.5 flex-shrink-0"
            :class="dark ? 'text-[#6366f1]' : 'text-indigo-500'"
            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </li>
      </ul>
    </Transition>
  </div>
</template>

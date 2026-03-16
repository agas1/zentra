<script setup>
import { computed, ref, onUnmounted, watch, nextTick } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  show: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: '',
  },
  maxWidth: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg', 'xl'].includes(v),
  },
})

const emit = defineEmits(['close'])
const modalRef = ref(null)
const previousFocus = ref(null)

const widthClass = computed(() => {
  const map = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
  }
  return map[props.maxWidth] || 'max-w-md'
})

// Focus trap (WCAG 2.4.3)
function trapFocus(e) {
  if (!props.show || !modalRef.value) return
  const focusable = modalRef.value.querySelectorAll(
    'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
  )
  if (!focusable.length) return
  const first = focusable[0]
  const last = focusable[focusable.length - 1]

  if (e.shiftKey && document.activeElement === first) {
    e.preventDefault()
    last.focus()
  } else if (!e.shiftKey && document.activeElement === last) {
    e.preventDefault()
    first.focus()
  }
}

function onKeydown(e) {
  if (e.key === 'Escape') emit('close')
  if (e.key === 'Tab') trapFocus(e)
}

watch(() => props.show, async (val) => {
  if (val) {
    previousFocus.value = document.activeElement
    document.addEventListener('keydown', onKeydown)
    await nextTick()
    const firstFocusable = modalRef.value?.querySelector(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    )
    firstFocusable?.focus()
  } else {
    document.removeEventListener('keydown', onKeydown)
    previousFocus.value?.focus()
  }
})

onUnmounted(() => {
  document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-all duration-250 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-all duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="show"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center"
        role="dialog"
        aria-modal="true"
        :aria-label="title"
        @click.self="emit('close')"
      >
        <div
          ref="modalRef"
          class="glass-elevated rounded-2xl shadow-2xl w-full animate-scale-in relative overflow-hidden"
          :class="widthClass"
        >
          <!-- Specular highlight -->
          <div class="absolute top-0 left-[10%] right-[10%] h-px bg-gradient-to-r from-transparent via-white/15 to-transparent pointer-events-none" />
          <div class="flex justify-between items-center p-5 border-b border-white/[0.06]">
            <h2 id="modal-title" class="text-lg font-semibold text-white/90">{{ title }}</h2>
            <button
              class="text-white/25 hover:text-white/50 transition-colors"
              aria-label="Fechar"
              @click="emit('close')"
            >
              <X :size="20" :stroke-width="2" aria-hidden="true" />
            </button>
          </div>
          <div class="p-5">
            <slot />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

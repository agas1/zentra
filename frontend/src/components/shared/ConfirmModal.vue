<script setup>
import { ref, watch, nextTick, onUnmounted } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  title: { type: String, default: 'Confirmar' },
  message: { type: String, default: 'Tem certeza?' },
  confirmText: { type: String, default: 'Confirmar' },
  cancelText: { type: String, default: 'Cancelar' },
  variant: { type: String, default: 'danger' },
})

const emit = defineEmits(['confirm', 'cancel'])
const cancelBtnRef = ref(null)
const previousFocus = ref(null)
const modalRef = ref(null)

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
  if (e.key === 'Escape') emit('cancel')
  if (e.key === 'Tab') trapFocus(e)
}

watch(() => props.show, async (val) => {
  if (val) {
    previousFocus.value = document.activeElement
    document.addEventListener('keydown', onKeydown)
    await nextTick()
    cancelBtnRef.value?.focus()
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
    <div
      v-if="show"
      class="fixed inset-0 z-[100] flex items-center justify-center"
      role="alertdialog"
      aria-modal="true"
      :aria-label="title"
    >
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" aria-hidden="true" @click="emit('cancel')" />
      <div ref="modalRef" class="relative bg-[#161b22] border border-[#444c56] rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden">
        <!-- Header -->
        <div class="flex items-center gap-3 px-5 pt-5 pb-2">
          <div
            class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
            :class="{
              'bg-[#f85149]/10': variant === 'danger',
              'bg-[#d29922]/10': variant === 'warning',
              'bg-[#6366f1]/10': variant === 'info',
            }"
            aria-hidden="true"
          >
            <svg
              v-if="variant === 'danger'"
              class="w-5 h-5 text-[#f85149]"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <svg
              v-else-if="variant === 'warning'"
              class="w-5 h-5 text-[#d29922]"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <svg
              v-else
              class="w-5 h-5 text-[#6366f1]"
              fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
          <h3 class="text-base font-semibold text-[#e6edf3]">{{ title }}</h3>
        </div>

        <!-- Body -->
        <div class="px-5 py-3">
          <p class="text-sm text-[#8b949e] leading-relaxed">{{ message }}</p>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 px-5 py-4 border-t border-[#2d333b]">
          <button
            ref="cancelBtnRef"
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d]"
            @click="emit('cancel')"
          >
            {{ cancelText }}
          </button>
          <button
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors text-white"
            :class="{
              'bg-[#f85149] hover:bg-[#f85149]/80': variant === 'danger',
              'bg-[#d29922] hover:bg-[#d29922]/80': variant === 'warning',
              'bg-[#6366f1] hover:bg-[#6366f1]/80': variant === 'info',
            }"
            @click="emit('confirm')"
          >
            {{ confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

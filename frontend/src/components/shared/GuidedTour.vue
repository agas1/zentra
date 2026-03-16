<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick, inject } from 'vue'

const props = defineProps({
  steps: { type: Array, required: true },
  storageKey: { type: String, default: 'zentra_tour_board_done' },
  autoStart: { type: Boolean, default: true },
})
const emit = defineEmits(['complete', 'skip'])
const isDark = inject('boardIsDark', ref(true))

const isActive = ref(false)
const currentStep = ref(0)
const highlightStyle = ref({})
const tooltipStyle = ref({})

const step = computed(() => props.steps[currentStep.value])
const isLast = computed(() => currentStep.value === props.steps.length - 1)

function start() {
  currentStep.value = 0
  isActive.value = true
  nextTick(() => positionHighlight())
}

function next() {
  if (isLast.value) {
    complete()
  } else {
    currentStep.value++
    nextTick(() => positionHighlight())
  }
}

function prev() {
  if (currentStep.value > 0) {
    currentStep.value--
    nextTick(() => positionHighlight())
  }
}

function skip() {
  isActive.value = false
  localStorage.setItem(props.storageKey, 'true')
  emit('skip')
}

function complete() {
  isActive.value = false
  localStorage.setItem(props.storageKey, 'true')
  emit('complete')
}

function positionHighlight() {
  if (!step.value) return
  const el = document.querySelector(step.value.target)
  if (!el) {
    // If target not found, skip to next step or complete
    if (!isLast.value) {
      currentStep.value++
      nextTick(() => positionHighlight())
    } else {
      complete()
    }
    return
  }

  const rect = el.getBoundingClientRect()
  const pad = 8

  highlightStyle.value = {
    top: `${rect.top - pad}px`,
    left: `${rect.left - pad}px`,
    width: `${rect.width + pad * 2}px`,
    height: `${rect.height + pad * 2}px`,
  }

  // Position tooltip
  const pos = step.value.position || 'bottom'
  const tooltip = {}
  const tooltipW = 320
  const tooltipH = 180

  if (pos === 'bottom') {
    tooltip.top = `${rect.bottom + pad + 12}px`
    tooltip.left = `${Math.max(16, Math.min(rect.left, window.innerWidth - tooltipW - 16))}px`
  } else if (pos === 'top') {
    tooltip.top = `${rect.top - pad - tooltipH - 12}px`
    tooltip.left = `${Math.max(16, Math.min(rect.left, window.innerWidth - tooltipW - 16))}px`
  } else if (pos === 'right') {
    tooltip.top = `${rect.top}px`
    tooltip.left = `${rect.right + pad + 12}px`
  } else if (pos === 'left') {
    tooltip.top = `${rect.top}px`
    tooltip.left = `${rect.left - pad - tooltipW - 12}px`
  }

  tooltipStyle.value = tooltip
}

function onResize() {
  if (isActive.value) positionHighlight()
}

function onKeydown(e) {
  if (!isActive.value) return
  if (e.key === 'Escape') skip()
  if (e.key === 'ArrowRight' || e.key === 'Enter') next()
  if (e.key === 'ArrowLeft') prev()
}

onMounted(() => {
  window.addEventListener('resize', onResize)
  document.addEventListener('keydown', onKeydown)
  if (props.autoStart && !localStorage.getItem(props.storageKey)) {
    setTimeout(() => start(), 800)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', onResize)
  document.removeEventListener('keydown', onKeydown)
})

defineExpose({ start })
</script>

<template>
  <Teleport to="body">
    <div v-if="isActive" class="fixed inset-0 z-[200]" role="dialog" aria-modal="true" :aria-label="`Tour: ${step?.title || 'Passo ' + (currentStep + 1)}`">
      <!-- Overlay with cutout -->
      <div class="fixed inset-0 bg-black/60 transition-all duration-300" aria-hidden="true" />

      <!-- Highlight box -->
      <div
        class="fixed rounded-lg ring-2 ring-[#6366f1] ring-offset-2 transition-all duration-300 z-[201]"
        :class="isDark ? 'ring-offset-[#0d1117]' : 'ring-offset-white'"
        :style="highlightStyle"
      >
        <div class="absolute inset-0 bg-[#6366f1]/10 rounded-lg animate-pulse" />
      </div>

      <!-- Tooltip -->
      <div
        class="fixed w-80 rounded-xl shadow-2xl border z-[202] animate-scale-in"
        :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200'"
        :style="tooltipStyle"
      >
        <div class="p-4">
          <!-- Step indicator -->
          <div class="flex items-center gap-1.5 mb-3">
            <div
              v-for="(_, i) in steps"
              :key="i"
              class="h-1 rounded-full transition-all duration-300"
              :class="i === currentStep ? 'w-6 bg-[#6366f1]' : 'w-2 ' + (i < currentStep ? 'bg-[#6366f1]/50' : (isDark ? 'bg-[#2d333b]' : 'bg-gray-200'))"
            />
            <span class="text-[10px] ml-auto" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" aria-live="polite">
              Passo {{ currentStep + 1 }} de {{ steps.length }}
            </span>
          </div>

          <!-- Content -->
          <h3 class="text-sm font-bold mb-1" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
            {{ step?.title }}
          </h3>
          <p class="text-xs leading-relaxed mb-4" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
            {{ step?.description }}
          </p>

          <!-- Actions -->
          <div class="flex items-center justify-between">
            <button
              class="text-xs transition-colors"
              :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'"
              @click="skip"
            >
              Pular tour
            </button>
            <div class="flex gap-2">
              <button
                v-if="currentStep > 0"
                class="text-xs px-3 py-1.5 rounded-lg transition-colors"
                :class="isDark ? 'bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d]' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                @click="prev"
              >
                Anterior
              </button>
              <button
                class="text-xs px-4 py-1.5 rounded-lg bg-[#6366f1] text-white hover:bg-[#5558e6] transition-colors font-medium"
                @click="next"
              >
                {{ isLast ? 'Concluir' : 'Proximo' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { usePlanLimits } from '../../composables/usePlanLimits'

const props = defineProps({
  feature: { type: String, default: '' },
  allowed: { type: Boolean, default: undefined },
  requiredPlan: { type: String, default: '' },
})

const { hasFeature, requiredPlanForFeature } = usePlanLimits()

const isAllowed = computed(() => {
  if (props.allowed !== undefined) return props.allowed
  if (props.feature) return hasFeature(props.feature)
  return true
})

const planLabel = computed(() => {
  if (props.requiredPlan) return props.requiredPlan
  if (props.feature) return requiredPlanForFeature(props.feature)
  return 'superior'
})
</script>

<template>
  <div class="relative">
    <slot v-if="isAllowed" />
    <slot v-else name="locked">
      <div class="relative">
        <div class="opacity-40 pointer-events-none select-none">
          <slot />
        </div>
        <div class="absolute inset-0 flex items-center justify-center">
          <div class="bg-[#161b22] border border-[#444c56] rounded-xl px-4 py-3 text-center shadow-lg max-w-[200px]">
            <svg class="w-5 h-5 text-[#6e7681] mx-auto mb-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <p class="text-xs text-[#8b949e] mb-2">Disponivel no plano {{ planLabel }}+</p>
            <router-link to="/plans" class="text-xs text-[#6366f1] hover:underline font-medium">
              Ver planos
            </router-link>
          </div>
        </div>
      </div>
    </slot>
  </div>
</template>

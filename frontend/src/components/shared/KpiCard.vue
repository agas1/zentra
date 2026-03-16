<script setup>
import { computed } from 'vue'

const props = defineProps({
  value: {
    required: true,
  },
  label: {
    type: String,
    required: true,
  },
  color: {
    type: String,
    default: '#388bfd',
  },
  subtitle: {
    type: String,
    default: '',
  },
})

const iconBgStyle = computed(() => ({
  backgroundColor: props.color + '1a',
}))
</script>

<template>
  <div class="bg-[#161b22] border border-[#444c56] rounded-xl p-5">
    <div class="flex items-center gap-3">
      <div
        class="w-10 h-10 rounded-lg flex items-center justify-center"
        :style="iconBgStyle"
      >
        <slot name="icon" />
      </div>
    </div>
    <div class="text-2xl font-bold text-[#e6edf3] mt-3">{{ value }}</div>
    <div class="text-sm text-[#8b949e] mt-0.5">{{ label }}</div>
    <div v-if="subtitle" class="text-xs text-[#6e7681] mt-1">{{ subtitle }}</div>
  </div>
</template>

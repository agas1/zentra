<script setup>
import { computed } from 'vue'

const props = defineProps({
  label: { type: String, required: true },
  used: { type: Number, default: 0 },
  limit: { type: Number, default: 0 },
  unlimited: { type: Boolean, default: false },
  unit: { type: String, default: '' },
})

const percent = computed(() => {
  if (props.unlimited || props.limit <= 0) return 0
  return Math.min(100, Math.round((props.used / props.limit) * 100))
})

const barColor = computed(() => {
  if (props.unlimited) return 'bg-[#388bfd]'
  if (percent.value >= 90) return 'bg-[#f85149]'
  if (percent.value >= 70) return 'bg-[#d29922]'
  return 'bg-[#388bfd]'
})

function formatValue(val) {
  if (props.unit === 'storage') {
    if (val >= 1024) return `${(val / 1024).toFixed(1)} GB`
    return `${val} MB`
  }
  return val
}
</script>

<template>
  <div class="bg-[#161b22] border border-[#444c56] rounded-xl p-4">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-medium text-[#e6edf3]">{{ label }}</span>
      <span class="text-xs text-[#8b949e]">
        <strong class="text-[#e6edf3]">{{ formatValue(used) }}</strong>
        /
        <span v-if="unlimited">Ilimitado</span>
        <span v-else>{{ formatValue(limit) }}</span>
      </span>
    </div>
    <div class="w-full bg-[#2d333b] rounded-full h-2">
      <div
        class="h-2 rounded-full transition-all duration-500"
        :class="barColor"
        :style="{ width: unlimited ? '15%' : `${percent}%` }"
      />
    </div>
    <p v-if="!unlimited && percent >= 90" class="text-xs text-[#f85149] mt-1">
      Quase no limite! Considere fazer upgrade.
    </p>
  </div>
</template>

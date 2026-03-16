<script setup>
defineProps({
  label: { type: Object, required: true },
  small: { type: Boolean, default: false },
})
</script>

<template>
  <span
    class="inline-flex items-center rounded-sm font-medium text-white"
    :class="small ? 'text-[10px] px-1.5 py-0 h-4' : 'text-xs px-2 py-0.5 min-w-[48px]'"
    :style="{ backgroundColor: label.color }"
  >
    {{ label.name || '' }}
  </span>
</template>

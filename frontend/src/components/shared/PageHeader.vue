<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  count: {
    type: Number,
    default: null,
  },
  countLabel: {
    type: String,
    default: 'itens',
  },
})
</script>

<template>
  <header class="flex items-center justify-between mb-8">
    <div class="flex items-center gap-3">
      <h1 class="text-3xl font-bold text-[#e6edf3]">{{ title }}</h1>
      <span
        v-if="count !== null"
        class="bg-[#2d333b] text-[#8b949e] text-sm font-medium px-2.5 py-0.5 rounded-full"
        :aria-label="`${count} ${countLabel}`"
      >
        {{ count }} {{ countLabel }}
      </span>
    </div>
    <div>
      <slot name="actions" />
    </div>
  </header>
</template>

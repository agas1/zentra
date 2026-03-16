<script setup>
defineProps({
  message: {
    type: String,
    required: true,
  },
  actionLabel: {
    type: String,
    default: '',
  },
})

const emit = defineEmits(['action'])
</script>

<template>
  <div class="flex flex-col items-center justify-center py-16" role="status">
    <div class="w-12 h-12 rounded-full bg-[#2d333b] flex items-center justify-center mb-4 text-[#6e7681]" aria-hidden="true">
      <slot name="icon" />
    </div>
    <p class="text-sm text-[#6e7681] text-center">{{ message }}</p>
    <button
      v-if="actionLabel"
      class="monday-btn monday-btn-secondary mt-4"
      @click="emit('action')"
    >
      {{ actionLabel }}
    </button>
  </div>
</template>

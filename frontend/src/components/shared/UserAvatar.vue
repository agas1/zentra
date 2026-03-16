<script setup>
import { computed } from 'vue'

const props = defineProps({
  name: {
    type: String,
    required: true,
  },
  url: {
    type: String,
    default: null,
  },
  size: {
    type: String,
    default: 'md',
    validator: (v) => ['sm', 'md', 'lg'].includes(v),
  },
})

const PALETTE = [
  '#6366f1',
  '#2563eb',
  '#059669',
  '#d97706',
  '#dc2626',
  '#8b5cf6',
  '#0891b2',
  '#be185d',
]

const initials = computed(() => {
  const parts = props.name.trim().split(/\s+/)
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return props.name.trim().substring(0, 2).toUpperCase()
})

const bgColor = computed(() => {
  let hash = 0
  for (let i = 0; i < props.name.length; i++) {
    hash = props.name.charCodeAt(i) + ((hash << 5) - hash)
  }
  const index = Math.abs(hash) % PALETTE.length
  return PALETTE[index]
})

const resolvedUrl = computed(() => {
  if (!props.url) return null
  if (props.url.startsWith('http')) return props.url
  return `${import.meta.env.VITE_API_URL}${props.url}`
})

const sizeClasses = computed(() => {
  switch (props.size) {
    case 'sm':
      return 'w-7 h-7 text-xs'
    case 'lg':
      return 'w-10 h-10 text-sm'
    default:
      return 'w-8 h-8 text-sm'
  }
})
</script>

<template>
  <div
    v-if="resolvedUrl"
    class="rounded-full bg-cover bg-center flex-shrink-0"
    :class="sizeClasses"
    :style="{ backgroundImage: `url(${resolvedUrl})` }"
    :title="name"
  />
  <div
    v-else
    class="rounded-full flex items-center justify-center text-white font-semibold flex-shrink-0"
    :class="sizeClasses"
    :style="{ backgroundColor: bgColor }"
    :title="name"
  >
    {{ initials }}
  </div>
</template>

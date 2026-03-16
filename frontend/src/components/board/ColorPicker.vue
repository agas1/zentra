<script setup>
import { ref, computed, watch, onMounted } from 'vue'

const props = defineProps({
  modelValue: { type: String, default: '#1e3a5f' },
})
const emit = defineEmits(['update:modelValue'])

const satBrightRef = ref(null)
const hueRef = ref(null)
const draggingSB = ref(false)
const draggingHue = ref(false)

// Internal HSV state
const hue = ref(210)
const sat = ref(100)
const bright = ref(75)
const hexInput = ref('')

// Convert hex to HSV on mount and when modelValue changes
function hexToHsv(hex) {
  hex = hex.replace('#', '')
  if (hex.length === 3) hex = hex.split('').map(c => c + c).join('')
  const r = parseInt(hex.substring(0, 2), 16) / 255
  const g = parseInt(hex.substring(2, 4), 16) / 255
  const b = parseInt(hex.substring(4, 6), 16) / 255
  const max = Math.max(r, g, b)
  const min = Math.min(r, g, b)
  const d = max - min
  let h = 0
  if (d !== 0) {
    if (max === r) h = ((g - b) / d + (g < b ? 6 : 0)) * 60
    else if (max === g) h = ((b - r) / d + 2) * 60
    else h = ((r - g) / d + 4) * 60
  }
  const s = max === 0 ? 0 : (d / max) * 100
  const v = max * 100
  return { h, s, v }
}

function hsvToHex(h, s, v) {
  s /= 100
  v /= 100
  const c = v * s
  const x = c * (1 - Math.abs(((h / 60) % 2) - 1))
  const m = v - c
  let r, g, b
  if (h < 60) { r = c; g = x; b = 0 }
  else if (h < 120) { r = x; g = c; b = 0 }
  else if (h < 180) { r = 0; g = c; b = x }
  else if (h < 240) { r = 0; g = x; b = c }
  else if (h < 300) { r = x; g = 0; b = c }
  else { r = c; g = 0; b = x }
  const toHex = (n) => Math.round((n + m) * 255).toString(16).padStart(2, '0')
  return `#${toHex(r)}${toHex(g)}${toHex(b)}`
}

const currentHex = computed(() => hsvToHex(hue.value, sat.value, bright.value))
const hueColor = computed(() => hsvToHex(hue.value, 100, 100))

// Sync hex input display
watch(currentHex, (val) => {
  hexInput.value = val.toUpperCase()
})

// Emit color on change
watch(currentHex, (val) => {
  emit('update:modelValue', val)
})

// Initialize from prop
function initFromProp() {
  try {
    const hsv = hexToHsv(props.modelValue)
    hue.value = hsv.h
    sat.value = hsv.s
    bright.value = hsv.v
    hexInput.value = props.modelValue.toUpperCase()
  } catch {
    // ignore invalid hex
  }
}

onMounted(initFromProp)
watch(() => props.modelValue, (val) => {
  if (val.toLowerCase() !== currentHex.value.toLowerCase()) {
    initFromProp()
  }
})

// Saturation/Brightness picker
const sbThumbStyle = computed(() => ({
  left: `${sat.value}%`,
  top: `${100 - bright.value}%`,
}))

function handleSBPointer(e) {
  const rect = satBrightRef.value.getBoundingClientRect()
  const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width))
  const y = Math.max(0, Math.min(e.clientY - rect.top, rect.height))
  sat.value = Math.round((x / rect.width) * 100)
  bright.value = Math.round(100 - (y / rect.height) * 100)
}

function startSBDrag(e) {
  draggingSB.value = true
  handleSBPointer(e)
  window.addEventListener('pointermove', onSBMove)
  window.addEventListener('pointerup', stopSBDrag)
}

function onSBMove(e) {
  if (draggingSB.value) handleSBPointer(e)
}

function stopSBDrag() {
  draggingSB.value = false
  window.removeEventListener('pointermove', onSBMove)
  window.removeEventListener('pointerup', stopSBDrag)
}

// Hue slider
function handleHuePointer(e) {
  const rect = hueRef.value.getBoundingClientRect()
  const x = Math.max(0, Math.min(e.clientX - rect.left, rect.width))
  hue.value = Math.round((x / rect.width) * 360)
}

function startHueDrag(e) {
  draggingHue.value = true
  handleHuePointer(e)
  window.addEventListener('pointermove', onHueMove)
  window.addEventListener('pointerup', stopHueDrag)
}

function onHueMove(e) {
  if (draggingHue.value) handleHuePointer(e)
}

function stopHueDrag() {
  draggingHue.value = false
  window.removeEventListener('pointermove', onHueMove)
  window.removeEventListener('pointerup', stopHueDrag)
}

// Hex input
function onHexSubmit() {
  let val = hexInput.value.trim()
  if (!val.startsWith('#')) val = '#' + val
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    const hsv = hexToHsv(val)
    hue.value = hsv.h
    sat.value = hsv.s
    bright.value = hsv.v
  }
}
</script>

<template>
  <div class="select-none">
    <!-- Saturation/Brightness area -->
    <div
      ref="satBrightRef"
      class="relative w-full h-40 rounded-lg cursor-crosshair overflow-hidden"
      :style="{ backgroundColor: hueColor }"
      @pointerdown.prevent="startSBDrag"
    >
      <!-- White gradient (left to right) -->
      <div class="absolute inset-0" style="background: linear-gradient(to right, #fff, transparent)" />
      <!-- Black gradient (top to bottom) -->
      <div class="absolute inset-0" style="background: linear-gradient(to bottom, transparent, #000)" />
      <!-- Thumb -->
      <div
        class="absolute w-4 h-4 -translate-x-1/2 -translate-y-1/2 rounded-full border-2 border-white shadow-md pointer-events-none"
        :style="sbThumbStyle"
      />
    </div>

    <!-- Hue slider -->
    <div
      ref="hueRef"
      class="relative w-full h-3.5 mt-3 rounded-full cursor-pointer"
      style="background: linear-gradient(to right, #f00, #ff0, #0f0, #0ff, #00f, #f0f, #f00)"
      @pointerdown.prevent="startHueDrag"
    >
      <div
        class="absolute top-1/2 -translate-x-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-2 border-white shadow-md pointer-events-none"
        :style="{ left: `${(hue / 360) * 100}%`, backgroundColor: hueColor }"
      />
    </div>

    <!-- Hex input + preview -->
    <div class="flex items-center gap-3 mt-3">
      <div
        class="w-9 h-9 rounded-lg border border-[#444c56] flex-shrink-0"
        :style="{ backgroundColor: currentHex }"
      />
      <div class="flex items-center gap-1 flex-1">
        <span class="text-[#6e7681] text-sm font-mono">#</span>
        <input
          v-model="hexInput"
          class="flex-1 text-sm font-mono bg-[#0d1117] border border-[#444c56] text-[#e6edf3] rounded px-2 py-1.5 focus:outline-none focus:border-[#388bfd] uppercase"
          maxlength="7"
          @keyup.enter="onHexSubmit"
          @blur="onHexSubmit"
        />
      </div>
    </div>
  </div>
</template>

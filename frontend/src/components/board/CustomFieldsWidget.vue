<script setup>
import { ref, nextTick, inject } from 'vue'

const props = defineProps({
  customFields: { type: Array, default: () => [] },
  customFieldValues: { type: Array, default: () => [] },
})

const emit = defineEmits(['update-value'])

const isDark = inject('boardIsDark', ref(true))

const editingField = ref(null)
const editValue = ref('')
const inputRef = ref(null)

function getValue(field) {
  const val = props.customFieldValues.find(v => v.custom_field_id === field.id)
  return val?.value ?? ''
}

async function startEdit(field) {
  editingField.value = field.id
  editValue.value = getValue(field)
  await nextTick()
  inputRef.value?.focus()
}

function saveEdit(field) {
  const val = editValue.value?.trim() || null
  emit('update-value', field.id, val)
  editingField.value = null
}

function cancelEdit() {
  editingField.value = null
  editValue.value = ''
}

function handleKeydown(e, field) {
  if (e.key === 'Enter') saveEdit(field)
  if (e.key === 'Escape') cancelEdit()
}

function handleCheckboxToggle(field) {
  const current = getValue(field)
  const newVal = current === 'true' ? 'false' : 'true'
  emit('update-value', field.id, newVal)
}

function formatDisplayValue(field) {
  const val = getValue(field)
  if (!val) return null
  if (field.type === 'checkbox') return val === 'true' ? 'Sim' : 'Nao'
  if (field.type === 'date') {
    try {
      return new Date(val).toLocaleDateString('pt-BR', { day: 'numeric', month: 'short', year: 'numeric' })
    } catch { return val }
  }
  return val
}

const typeLabels = {
  text: 'Texto',
  number: 'Numero',
  date: 'Data',
  dropdown: 'Selecao',
  checkbox: 'Checkbox',
}
</script>

<template>
  <div v-if="customFields.length > 0" class="mb-4">
    <div class="flex items-center gap-2 mb-2">
      <svg :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
      <h4 :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-semibold text-sm">Campos Customizados</h4>
    </div>

    <div :class="isDark ? 'border-[#444c56] divide-[#444c56]' : 'border-gray-200 divide-gray-200'" class="border rounded-lg overflow-hidden divide-y">
      <div
        v-for="field in customFields"
        :key="field.id"
        :class="isDark ? 'bg-[#2d333b]' : 'bg-[#f6f8fa]'"
        class="flex items-center gap-3 px-3 py-2.5 group"
      >
        <!-- Label side -->
        <div class="w-32 flex-shrink-0">
          <p :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs font-semibold truncate">{{ field.name }}</p>
        </div>

        <!-- Value side -->
        <div class="flex-1 min-w-0">
          <!-- Checkbox type -->
          <label v-if="field.type === 'checkbox'" class="flex items-center gap-2 cursor-pointer">
            <input
              type="checkbox"
              :checked="getValue(field) === 'true'"
              :class="isDark ? 'border-[#444c56] bg-[#0d1117]' : 'border-gray-300 bg-white'"
              class="rounded text-[#6366f1] focus:ring-[#6366f1] w-4 h-4"
              @change="handleCheckboxToggle(field)"
            />
            <span :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-sm">{{ getValue(field) === 'true' ? 'Sim' : 'Nao' }}</span>
          </label>

          <!-- Editing mode -->
          <template v-else-if="editingField === field.id">
            <!-- Dropdown -->
            <select
              v-if="field.type === 'dropdown'"
              ref="inputRef"
              v-model="editValue"
              :class="isDark ? 'bg-[#0d1117] text-[#e6edf3]' : 'bg-white text-gray-900'"
              class="w-full border border-[#6366f1] rounded-md text-sm py-1 px-2 focus:outline-none focus:ring-1 focus:ring-[#6366f1]"
              @change="saveEdit(field)"
              @blur="cancelEdit"
            >
              <option value="">Selecionar...</option>
              <option v-for="opt in field.options" :key="opt" :value="opt">{{ opt }}</option>
            </select>
            <!-- Date -->
            <input
              v-else-if="field.type === 'date'"
              ref="inputRef"
              v-model="editValue"
              type="date"
              :class="isDark ? 'bg-[#0d1117] text-[#e6edf3]' : 'bg-white text-gray-900'"
              class="w-full border border-[#6366f1] rounded-md text-sm py-1 px-2 focus:outline-none focus:ring-1 focus:ring-[#6366f1]"
              @blur="saveEdit(field)"
              @keydown="handleKeydown($event, field)"
            />
            <!-- Number -->
            <input
              v-else-if="field.type === 'number'"
              ref="inputRef"
              v-model="editValue"
              type="number"
              :class="isDark ? 'bg-[#0d1117] text-[#e6edf3]' : 'bg-white text-gray-900'"
              class="w-full border border-[#6366f1] rounded-md text-sm py-1 px-2 focus:outline-none focus:ring-1 focus:ring-[#6366f1]"
              @blur="saveEdit(field)"
              @keydown="handleKeydown($event, field)"
            />
            <!-- Text -->
            <input
              v-else
              ref="inputRef"
              v-model="editValue"
              type="text"
              :class="isDark ? 'bg-[#0d1117] text-[#e6edf3]' : 'bg-white text-gray-900'"
              class="w-full border border-[#6366f1] rounded-md text-sm py-1 px-2 focus:outline-none focus:ring-1 focus:ring-[#6366f1]"
              placeholder="Digite o valor..."
              @blur="saveEdit(field)"
              @keydown="handleKeydown($event, field)"
            />
          </template>

          <!-- Display mode -->
          <div
            v-else
            :class="isDark ? 'hover:bg-[#444c56]' : 'hover:bg-gray-100'"
            class="flex items-center gap-1.5 cursor-pointer rounded-md px-2 py-1 -mx-2 -my-1 transition-colors"
            @click="startEdit(field)"
          >
            <span v-if="formatDisplayValue(field)" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm">
              {{ formatDisplayValue(field) }}
            </span>
            <span v-else :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="text-sm italic">Vazio</span>
            <!-- Edit icon on hover -->
            <svg :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0 ml-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

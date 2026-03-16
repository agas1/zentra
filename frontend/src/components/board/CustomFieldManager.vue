<script setup>
import { ref, inject } from 'vue'
import { boardsApi } from '../../api/boards'

const props = defineProps({
  boardId: { type: String, required: true },
  customFields: { type: Array, default: () => [] },
  hasFeature: { type: Boolean, default: false },
})

const emit = defineEmits(['updated'])

const isDark = inject('boardIsDark', ref(true))

const open = ref(false)
const showForm = ref(false)
const saving = ref(false)
const error = ref('')

const fieldName = ref('')
const fieldType = ref('text')
const fieldOptions = ref('')

const fieldTypes = [
  { value: 'text', label: 'Texto' },
  { value: 'number', label: 'Numero' },
  { value: 'date', label: 'Data' },
  { value: 'dropdown', label: 'Dropdown' },
  { value: 'checkbox', label: 'Checkbox' },
]

function resetForm() {
  fieldName.value = ''
  fieldType.value = 'text'
  fieldOptions.value = ''
  showForm.value = false
  error.value = ''
}

async function handleCreate() {
  const name = fieldName.value.trim()
  if (!name) return
  error.value = ''
  saving.value = true
  try {
    const data = { name, type: fieldType.value }
    if (fieldType.value === 'dropdown' && fieldOptions.value.trim()) {
      data.options = fieldOptions.value.split(',').map(o => o.trim()).filter(Boolean)
    }
    await boardsApi.createCustomField(props.boardId, data)
    resetForm()
    emit('updated')
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao criar campo.'
  } finally {
    saving.value = false
  }
}

async function handleDelete(fieldId) {
  try {
    await boardsApi.deleteCustomField(props.boardId, fieldId)
    emit('updated')
  } catch {
    // ignore
  }
}
</script>

<template>
  <div class="relative">
    <button
      class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
      :class="isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10'"
      @click="open = !open"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
      </svg>
      Campos
    </button>

    <div
      v-if="open"
      :class="isDark ? 'bg-[#1c2128] border-[#444c56]' : 'bg-white border-gray-200'"
      class="absolute right-0 top-full mt-2 w-80 rounded-lg shadow-xl border z-50"
    >
      <div :class="isDark ? 'border-[#444c56]' : 'border-gray-200'" class="flex items-center justify-between px-4 py-3 border-b">
        <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-semibold text-sm">Campos Customizados</span>
        <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" class="text-lg" @click="open = false">&times;</button>
      </div>

      <div class="p-4">
        <!-- Feature gate -->
        <div v-if="!hasFeature" class="text-center py-4">
          <svg :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="w-8 h-8 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <p :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-sm mb-1">Disponivel no plano Pro+</p>
          <router-link to="/plans" class="text-sm text-[#6366f1] hover:underline">Ver planos</router-link>
        </div>

        <template v-else>
          <!-- Existing fields -->
          <div v-if="customFields.length > 0" class="space-y-2 mb-3">
            <div
              v-for="field in customFields"
              :key="field.id"
              :class="isDark ? 'bg-[#2d333b]' : 'bg-[#f6f8fa]'"
              class="flex items-center justify-between rounded-lg px-3 py-2"
            >
              <div class="flex items-center gap-2">
                <span :class="isDark ? 'text-[#6e7681] bg-[#0d1117]' : 'text-gray-500 bg-gray-100'" class="text-xs uppercase font-medium px-1.5 py-0.5 rounded">{{ field.type }}</span>
                <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm">{{ field.name }}</span>
              </div>
              <button :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="hover:text-[#f85149] transition-colors" @click="handleDelete(field.id)">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </div>
          <div v-else class="text-center py-3 mb-3">
            <p :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="text-sm">Nenhum campo customizado</p>
          </div>

          <!-- Add new field form -->
          <div v-if="showForm" :class="isDark ? 'border-[#444c56]' : 'border-gray-200'" class="border rounded-lg p-3 space-y-2">
            <input
              v-model="fieldName"
              class="monday-input text-sm"
              placeholder="Nome do campo..."
              @keyup.enter="handleCreate"
            />
            <select v-model="fieldType" class="monday-input text-sm">
              <option v-for="t in fieldTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
            </select>
            <input
              v-if="fieldType === 'dropdown'"
              v-model="fieldOptions"
              class="monday-input text-sm"
              placeholder="Opcoes separadas por virgula..."
            />
            <p v-if="error" class="text-xs text-[#f85149]">{{ error }}</p>
            <div class="flex gap-2">
              <button
                class="monday-btn monday-btn-primary text-xs !py-1 flex-1"
                :disabled="saving"
                @click="handleCreate"
              >
                {{ saving ? 'Criando...' : 'Criar Campo' }}
              </button>
              <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" class="text-xs" @click="resetForm">Cancelar</button>
            </div>
          </div>
          <button
            v-else
            :class="isDark ? 'border-[#444c56] text-[#8b949e]' : 'border-gray-300 text-gray-500'"
            class="w-full py-2 border-2 border-dashed rounded-lg text-sm hover:border-[#6366f1] hover:text-[#6366f1] transition-colors"
            @click="showForm = true"
          >
            + Adicionar campo
          </button>
        </template>
      </div>
    </div>
  </div>
</template>

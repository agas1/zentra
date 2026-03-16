<script setup>
import { ref, watch, computed } from 'vue'

const props = defineProps({
  show: { type: Boolean, default: false },
  template: { type: Object, default: null },
})
const emit = defineEmits(['save', 'cancel'])

const name = ref('')
const description = ref('')
const lists = ref([])
const labels = ref([])
const customFields = ref([])
const automations = ref([])
const newListName = ref('')
const newLabelName = ref('')
const newLabelColor = ref('#6366f1')

// Custom field form
const newFieldName = ref('')
const newFieldType = ref('text')
const newFieldOptions = ref('')

// Automation form
const showAutoForm = ref(false)
const autoName = ref('')
const autoTriggerType = ref('card_moved_to_list')
const autoTriggerListName = ref('')
const autoTriggerLabelName = ref('')
const autoActionType = ref('move_to_list')
const autoActionListName = ref('')
const autoActionLabelName = ref('')
const autoActionDays = ref(3)
const autoActionText = ref('')
const autoActionChecklistTitle = ref('')

const LABEL_COLORS = ['#ef4444', '#f97316', '#eab308', '#22c55e', '#06b6d4', '#3b82f6', '#6366f1', '#8b5cf6', '#ec4899', '#6b7280']

const FIELD_TYPES = [
  { value: 'text', label: 'Texto' },
  { value: 'number', label: 'Numero' },
  { value: 'date', label: 'Data' },
  { value: 'dropdown', label: 'Dropdown' },
  { value: 'checkbox', label: 'Checkbox' },
]

const TRIGGERS = [
  { value: 'card_moved_to_list', label: 'Card movido para lista', needsConfig: 'list' },
  { value: 'card_created', label: 'Card criado', needsConfig: 'list' },
  { value: 'checklist_completed', label: 'Checklist completado', needsConfig: null },
  { value: 'label_added', label: 'Etiqueta adicionada', needsConfig: 'label' },
]

const ACTIONS = [
  { value: 'move_to_list', label: 'Mover para lista', needsConfig: 'list' },
  { value: 'set_due_date', label: 'Definir prazo', needsConfig: 'days' },
  { value: 'add_label', label: 'Adicionar etiqueta', needsConfig: 'label' },
  { value: 'add_checklist', label: 'Adicionar checklist', needsConfig: 'checklist' },
  { value: 'add_comment', label: 'Adicionar comentario', needsConfig: 'text' },
  { value: 'mark_due_complete', label: 'Marcar prazo concluido', needsConfig: null },
  { value: 'archive_card', label: 'Arquivar card', needsConfig: null },
]

const listNames = computed(() => lists.value.map(l => l.name))
const labelNames = computed(() => labels.value.map(l => l.name))

const selectedTrigger = computed(() => TRIGGERS.find(t => t.value === autoTriggerType.value))
const selectedAction = computed(() => ACTIONS.find(a => a.value === autoActionType.value))

watch(() => props.show, (val) => {
  if (val && props.template) {
    name.value = props.template.name || ''
    description.value = props.template.description || ''
    lists.value = (props.template.lists || []).map((l, i) => ({ ...l, _id: i }))
    labels.value = (props.template.labels || []).map((l, i) => ({ ...l, _id: i }))
    customFields.value = (props.template.custom_fields || []).map((f, i) => ({ ...f, _id: i }))
    automations.value = (props.template.automations || []).map((a, i) => ({ ...a, _id: i }))
  } else if (val) {
    name.value = ''
    description.value = ''
    lists.value = [
      { name: 'A Fazer', position: 1000, _id: 0 },
      { name: 'Em Progresso', position: 2000, _id: 1 },
      { name: 'Concluido', position: 3000, _id: 2 },
    ]
    labels.value = []
    customFields.value = []
    automations.value = []
  }
  showAutoForm.value = false
})

let nextId = 100

// Lists
function addList() {
  const n = newListName.value.trim()
  if (!n) return
  lists.value.push({ name: n, position: (lists.value.length + 1) * 1000, _id: nextId++ })
  newListName.value = ''
}

function removeList(idx) {
  lists.value.splice(idx, 1)
}

function moveList(idx, dir) {
  const newIdx = idx + dir
  if (newIdx < 0 || newIdx >= lists.value.length) return
  const temp = lists.value[idx]
  lists.value[idx] = lists.value[newIdx]
  lists.value[newIdx] = temp
}

// Labels
function addLabel() {
  const n = newLabelName.value.trim()
  if (!n) return
  labels.value.push({ name: n, color: newLabelColor.value, _id: nextId++ })
  newLabelName.value = ''
}

function removeLabel(idx) {
  labels.value.splice(idx, 1)
}

// Custom fields
function addField() {
  const n = newFieldName.value.trim()
  if (!n) return
  const field = { name: n, type: newFieldType.value, options: null, _id: nextId++ }
  if (newFieldType.value === 'dropdown' && newFieldOptions.value.trim()) {
    field.options = newFieldOptions.value.split(',').map(o => o.trim()).filter(Boolean)
  }
  customFields.value.push(field)
  newFieldName.value = ''
  newFieldOptions.value = ''
  newFieldType.value = 'text'
}

function removeField(idx) {
  customFields.value.splice(idx, 1)
}

// Automations
function getTriggerLabel(type) {
  return TRIGGERS.find(t => t.value === type)?.label || type
}

function getActionLabel(type) {
  return ACTIONS.find(a => a.value === type)?.label || type
}

function resetAutoForm() {
  autoName.value = ''
  autoTriggerType.value = 'card_moved_to_list'
  autoTriggerListName.value = ''
  autoTriggerLabelName.value = ''
  autoActionType.value = 'move_to_list'
  autoActionListName.value = ''
  autoActionLabelName.value = ''
  autoActionDays.value = 3
  autoActionText.value = ''
  autoActionChecklistTitle.value = ''
}

function addAutomation() {
  if (!autoName.value.trim()) return

  const triggerConfig = {}
  if (selectedTrigger.value?.needsConfig === 'list' && autoTriggerListName.value) {
    triggerConfig.list_name = autoTriggerListName.value
  }
  if (selectedTrigger.value?.needsConfig === 'label' && autoTriggerLabelName.value) {
    triggerConfig.label_name = autoTriggerLabelName.value
  }

  const actionConfig = {}
  if (selectedAction.value?.needsConfig === 'list' && autoActionListName.value) {
    actionConfig.list_name = autoActionListName.value
  }
  if (selectedAction.value?.needsConfig === 'label' && autoActionLabelName.value) {
    actionConfig.label_name = autoActionLabelName.value
  }
  if (selectedAction.value?.needsConfig === 'days') {
    actionConfig.days = autoActionDays.value
  }
  if (selectedAction.value?.needsConfig === 'text' && autoActionText.value) {
    actionConfig.text = autoActionText.value
  }
  if (selectedAction.value?.needsConfig === 'checklist' && autoActionChecklistTitle.value) {
    actionConfig.title = autoActionChecklistTitle.value
  }

  automations.value.push({
    name: autoName.value.trim(),
    trigger_type: autoTriggerType.value,
    trigger_config: triggerConfig,
    action_type: autoActionType.value,
    action_config: actionConfig,
    is_active: true,
    _id: nextId++,
  })

  resetAutoForm()
  showAutoForm.value = false
}

function removeAutomation(idx) {
  automations.value.splice(idx, 1)
}

function handleSave() {
  if (!name.value.trim() || !lists.value.length) return
  emit('save', {
    name: name.value.trim(),
    description: description.value.trim() || null,
    lists: lists.value.map((l, i) => ({ name: l.name, position: (i + 1) * 1000 })),
    labels: labels.value.map(l => ({ name: l.name, color: l.color })),
    custom_fields: customFields.value.map((f, i) => ({
      name: f.name,
      type: f.type,
      options: f.options || null,
      position: i + 1,
    })),
    automations: automations.value.map((a, i) => ({
      name: a.name,
      trigger_type: a.trigger_type,
      trigger_config: a.trigger_config || {},
      action_type: a.action_type,
      action_config: a.action_config || {},
      is_active: a.is_active !== false,
      position: (i + 1) * 1000,
    })),
  })
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center">
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="emit('cancel')" />
      <div class="relative rounded-xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden border bg-[#161b22] border-[#444c56] max-h-[85vh] overflow-y-auto">
        <!-- Header -->
        <div class="flex items-center justify-between px-5 pt-5 pb-2">
          <h3 class="text-base font-semibold text-[#e6edf3]">
            {{ template ? 'Editar Template' : 'Novo Template' }}
          </h3>
          <button class="text-[#8b949e] hover:text-[#e6edf3] text-lg" @click="emit('cancel')">&times;</button>
        </div>

        <div class="px-5 py-3 space-y-4">
          <!-- Name -->
          <div>
            <label class="text-xs font-semibold uppercase mb-1 block text-[#8b949e]">Nome</label>
            <input
              v-model="name"
              class="w-full text-sm rounded-lg px-3 py-2 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
              placeholder="Ex: Pipeline de Design"
            />
          </div>

          <!-- Description -->
          <div>
            <label class="text-xs font-semibold uppercase mb-1 block text-[#8b949e]">Descricao</label>
            <textarea
              v-model="description"
              class="w-full text-sm rounded-lg px-3 py-2 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] resize-none focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
              rows="2"
              placeholder="Descricao opcional..."
            />
          </div>

          <!-- Lists editor -->
          <div>
            <label class="text-xs font-semibold uppercase mb-2 block text-[#8b949e]">Listas</label>
            <div class="space-y-1.5 mb-2">
              <div
                v-for="(list, i) in lists"
                :key="list._id"
                class="flex items-center gap-2 bg-[#1c2128] rounded-lg px-3 py-2 border border-[#444c56]"
              >
                <svg class="w-4 h-4 text-[#6e7681] flex-shrink-0 cursor-move" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                <input
                  v-model="list.name"
                  class="flex-1 text-sm bg-transparent text-[#e6edf3] outline-none"
                />
                <button class="text-[#6e7681] hover:text-[#e6edf3]" @click="moveList(i, -1)" :disabled="i === 0">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                </button>
                <button class="text-[#6e7681] hover:text-[#e6edf3]" @click="moveList(i, 1)" :disabled="i === lists.length - 1">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <button class="text-[#6e7681] hover:text-[#f85149]" @click="removeList(i)">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>
            <div class="flex gap-2">
              <input
                v-model="newListName"
                class="flex-1 text-sm rounded-lg px-3 py-1.5 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                placeholder="Nome da lista..."
                @keyup.enter="addList"
              />
              <button class="text-xs px-3 py-1.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors" @click="addList">+ Lista</button>
            </div>
          </div>

          <!-- Labels editor -->
          <div>
            <label class="text-xs font-semibold uppercase mb-2 block text-[#8b949e]">Etiquetas</label>
            <div v-if="labels.length" class="flex flex-wrap gap-1.5 mb-2">
              <div
                v-for="(label, i) in labels"
                :key="label._id"
                class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-xs text-white font-medium group/label"
                :style="{ backgroundColor: label.color }"
              >
                {{ label.name }}
                <button class="opacity-0 group-hover/label:opacity-100 transition-opacity" @click="removeLabel(i)">
                  <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>
            <div class="flex gap-2 items-center">
              <input
                v-model="newLabelName"
                class="flex-1 text-sm rounded-lg px-3 py-1.5 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                placeholder="Nome da etiqueta..."
                @keyup.enter="addLabel"
              />
              <div class="flex gap-1">
                <button
                  v-for="color in LABEL_COLORS"
                  :key="color"
                  class="w-5 h-5 rounded-full transition-transform hover:scale-110"
                  :class="newLabelColor === color ? 'ring-2 ring-white ring-offset-1 ring-offset-[#161b22]' : ''"
                  :style="{ backgroundColor: color }"
                  @click="newLabelColor = color"
                />
              </div>
              <button class="text-xs px-3 py-1.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors flex-shrink-0" @click="addLabel">+</button>
            </div>
          </div>

          <!-- Custom Fields editor -->
          <div>
            <label class="text-xs font-semibold uppercase mb-2 block text-[#8b949e]">
              Campos Customizados
              <span class="text-[10px] normal-case font-normal text-[#6e7681] ml-1">(Plano Pro+)</span>
            </label>
            <div v-if="customFields.length" class="space-y-1.5 mb-2">
              <div
                v-for="(field, i) in customFields"
                :key="field._id"
                class="flex items-center gap-2 bg-[#1c2128] rounded-lg px-3 py-2 border border-[#444c56]"
              >
                <span class="text-sm text-[#e6edf3] flex-1">{{ field.name }}</span>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-[#2d333b] text-[#8b949e] uppercase font-medium">{{ field.type }}</span>
                <span v-if="field.options?.length" class="text-[10px] text-[#6e7681]">{{ field.options.length }} opcoes</span>
                <button class="text-[#6e7681] hover:text-[#f85149]" @click="removeField(i)">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>
            <div class="flex gap-2 items-end">
              <div class="flex-1">
                <input
                  v-model="newFieldName"
                  class="w-full text-sm rounded-lg px-3 py-1.5 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                  placeholder="Nome do campo..."
                  @keyup.enter="addField"
                />
              </div>
              <select
                v-model="newFieldType"
                class="text-xs rounded-lg px-2 py-1.5 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer"
              >
                <option v-for="ft in FIELD_TYPES" :key="ft.value" :value="ft.value">{{ ft.label }}</option>
              </select>
              <button class="text-xs px-3 py-1.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors flex-shrink-0" @click="addField">+</button>
            </div>
            <input
              v-if="newFieldType === 'dropdown'"
              v-model="newFieldOptions"
              class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none mt-1.5"
              placeholder="Opcoes separadas por virgula: Alta, Media, Baixa"
            />
          </div>

          <!-- Automations editor -->
          <div>
            <label class="text-xs font-semibold uppercase mb-2 block text-[#8b949e]">
              Automacoes
              <span class="text-[10px] normal-case font-normal text-[#6e7681] ml-1">(Plano Pro+)</span>
            </label>
            <div v-if="automations.length" class="space-y-1.5 mb-2">
              <div
                v-for="(auto, i) in automations"
                :key="auto._id"
                class="flex items-center gap-2 bg-[#1c2128] rounded-lg px-3 py-2 border border-[#444c56]"
              >
                <div class="flex-1 min-w-0">
                  <p class="text-xs font-medium text-[#e6edf3] truncate">{{ auto.name }}</p>
                  <p class="text-[10px] text-[#6e7681] truncate">
                    {{ getTriggerLabel(auto.trigger_type) }}
                    <span v-if="auto.trigger_config?.list_name" class="text-[#8b949e]"> → {{ auto.trigger_config.list_name }}</span>
                    <span v-if="auto.trigger_config?.label_name" class="text-[#8b949e]"> → {{ auto.trigger_config.label_name }}</span>
                    <span class="text-[#444c56] mx-1">|</span>
                    {{ getActionLabel(auto.action_type) }}
                    <span v-if="auto.action_config?.list_name" class="text-[#8b949e]"> → {{ auto.action_config.list_name }}</span>
                    <span v-if="auto.action_config?.label_name" class="text-[#8b949e]"> → {{ auto.action_config.label_name }}</span>
                  </p>
                </div>
                <button class="text-[#6e7681] hover:text-[#f85149] flex-shrink-0" @click="removeAutomation(i)">
                  <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
            </div>

            <!-- Add automation form -->
            <div v-if="showAutoForm" class="bg-[#0d1117] rounded-lg border border-[#444c56] p-3 space-y-2.5">
              <input
                v-model="autoName"
                class="w-full text-sm rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                placeholder="Nome da automacao..."
              />

              <!-- Trigger -->
              <div>
                <p class="text-[10px] uppercase font-semibold text-[#6e7681] mb-1">Quando</p>
                <select
                  v-model="autoTriggerType"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer"
                >
                  <option v-for="t in TRIGGERS" :key="t.value" :value="t.value">{{ t.label }}</option>
                </select>
                <!-- Trigger list config -->
                <select
                  v-if="selectedTrigger?.needsConfig === 'list' && listNames.length"
                  v-model="autoTriggerListName"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer mt-1.5"
                >
                  <option value="">Qualquer lista</option>
                  <option v-for="ln in listNames" :key="ln" :value="ln">{{ ln }}</option>
                </select>
                <!-- Trigger label config -->
                <select
                  v-if="selectedTrigger?.needsConfig === 'label' && labelNames.length"
                  v-model="autoTriggerLabelName"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer mt-1.5"
                >
                  <option value="">Qualquer etiqueta</option>
                  <option v-for="ln in labelNames" :key="ln" :value="ln">{{ ln }}</option>
                </select>
              </div>

              <!-- Action -->
              <div>
                <p class="text-[10px] uppercase font-semibold text-[#6e7681] mb-1">Entao</p>
                <select
                  v-model="autoActionType"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer"
                >
                  <option v-for="a in ACTIONS" :key="a.value" :value="a.value">{{ a.label }}</option>
                </select>
                <!-- Action list config -->
                <select
                  v-if="selectedAction?.needsConfig === 'list' && listNames.length"
                  v-model="autoActionListName"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer mt-1.5"
                >
                  <option value="">Selecione lista...</option>
                  <option v-for="ln in listNames" :key="ln" :value="ln">{{ ln }}</option>
                </select>
                <!-- Action label config -->
                <select
                  v-if="selectedAction?.needsConfig === 'label' && labelNames.length"
                  v-model="autoActionLabelName"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none appearance-none cursor-pointer mt-1.5"
                >
                  <option value="">Selecione etiqueta...</option>
                  <option v-for="ln in labelNames" :key="ln" :value="ln">{{ ln }}</option>
                </select>
                <!-- Action days config -->
                <div v-if="selectedAction?.needsConfig === 'days'" class="flex items-center gap-2 mt-1.5">
                  <input
                    v-model.number="autoActionDays"
                    type="number"
                    min="1"
                    max="365"
                    class="w-20 text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] focus:outline-none"
                  />
                  <span class="text-xs text-[#8b949e]">dias</span>
                </div>
                <!-- Action text config -->
                <input
                  v-if="selectedAction?.needsConfig === 'text'"
                  v-model="autoActionText"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none mt-1.5"
                  placeholder="Texto do comentario..."
                />
                <!-- Action checklist config -->
                <input
                  v-if="selectedAction?.needsConfig === 'checklist'"
                  v-model="autoActionChecklistTitle"
                  class="w-full text-xs rounded-lg px-3 py-1.5 border bg-[#161b22] border-[#444c56] text-[#e6edf3] placeholder-[#545d68] focus:outline-none mt-1.5"
                  placeholder="Titulo do checklist..."
                />
              </div>

              <div class="flex gap-2 pt-1">
                <button
                  class="flex-1 text-xs py-1.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors font-medium"
                  @click="showAutoForm = false; resetAutoForm()"
                >Cancelar</button>
                <button
                  class="flex-1 text-xs py-1.5 rounded-lg bg-[#6366f1] text-white hover:bg-[#5558e6] transition-colors font-medium disabled:opacity-50"
                  :disabled="!autoName.trim()"
                  @click="addAutomation"
                >Adicionar</button>
              </div>
            </div>
            <button
              v-else
              class="text-xs px-3 py-1.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors"
              @click="showAutoForm = true"
            >+ Automacao</button>
          </div>

          <!-- Preview -->
          <div v-if="lists.length">
            <label class="text-xs font-semibold uppercase mb-2 block text-[#8b949e]">Preview</label>
            <div class="flex gap-2 overflow-x-auto py-2">
              <div
                v-for="list in lists"
                :key="list._id"
                class="w-28 flex-shrink-0 bg-[#1c2128] rounded-lg px-2 py-2 border border-[#444c56]"
              >
                <p class="text-[10px] font-semibold text-[#e6edf3] truncate mb-1">{{ list.name }}</p>
                <div class="space-y-1">
                  <div class="h-3 bg-[#2d333b] rounded w-full" />
                  <div class="h-3 bg-[#2d333b] rounded w-3/4" />
                </div>
              </div>
            </div>
            <!-- Summary -->
            <div class="flex gap-3 text-[10px] text-[#6e7681] mt-1">
              <span>{{ lists.length }} listas</span>
              <span v-if="labels.length">{{ labels.length }} etiquetas</span>
              <span v-if="customFields.length">{{ customFields.length }} campos</span>
              <span v-if="automations.length">{{ automations.length }} automacoes</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 px-5 py-4 border-t border-[#2d333b]">
          <button
            class="px-4 py-2 text-sm font-medium rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors"
            @click="emit('cancel')"
          >
            Cancelar
          </button>
          <button
            class="px-4 py-2 text-sm font-medium rounded-lg bg-[#6366f1] hover:bg-[#5558e6] text-white transition-colors disabled:opacity-50"
            :disabled="!name.trim() || !lists.length"
            @click="handleSave"
          >
            {{ template ? 'Salvar' : 'Criar Template' }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

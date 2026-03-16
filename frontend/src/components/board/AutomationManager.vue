<script setup>
import { ref, computed, onMounted, onUnmounted, inject } from 'vue'
import { boardsApi } from '../../api/boards'
import { useBoardsStore } from '../../stores/boards'
import { useWorkspaceStore } from '../../stores/workspace'

const props = defineProps({
  boardId: { type: String, required: true },
  hasFeature: { type: Boolean, default: false },
})

const boardsStore = useBoardsStore()
const workspaceStore = useWorkspaceStore()
const isDark = inject('boardIsDark', ref(true))

const open = ref(false)
const automations = ref([])
const presets = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')

// View: 'list' | 'presets' | 'custom'
const view = ref('list')

// Custom form state
const formName = ref('')
const formTrigger = ref('card_moved_to_list')
const formTriggerConfig = ref({})
const formAction = ref('assign_member')
const formActionConfig = ref({})

const lists = computed(() => boardsStore.currentBoard?.lists?.filter(l => !l.is_archived) || [])
const labels = computed(() => boardsStore.currentBoard?.labels || [])
const members = computed(() => workspaceStore.members || [])

const triggers = [
  { value: 'card_moved_to_list', label: 'Card movido para lista', needsConfig: 'list' },
  { value: 'card_created', label: 'Card criado', needsConfig: 'list_optional' },
  { value: 'checklist_completed', label: 'Checklist completado', needsConfig: null },
  { value: 'label_added', label: 'Etiqueta adicionada', needsConfig: 'label' },
  { value: 'member_assigned', label: 'Membro atribuído', needsConfig: 'member' },
]

const actions = [
  { value: 'move_to_list', label: 'Mover para lista', needsConfig: 'list' },
  { value: 'assign_member', label: 'Atribuir membro', needsConfig: 'member' },
  { value: 'set_due_date', label: 'Definir prazo', needsConfig: 'days' },
  { value: 'add_label', label: 'Adicionar etiqueta', needsConfig: 'label' },
  { value: 'add_checklist', label: 'Adicionar checklist', needsConfig: 'checklist' },
  { value: 'add_comment', label: 'Adicionar comentário', needsConfig: 'text' },
  { value: 'mark_due_complete', label: 'Marcar prazo concluído', needsConfig: null },
  { value: 'archive_card', label: 'Arquivar card', needsConfig: null },
]

const currentTrigger = computed(() => triggers.find(t => t.value === formTrigger.value))
const currentAction = computed(() => actions.find(a => a.value === formAction.value))

// Checklist builder
const checklistTitle = ref('Checklist')
const checklistItems = ref([''])

function addChecklistItem() {
  checklistItems.value.push('')
}
function removeChecklistItem(i) {
  if (checklistItems.value.length > 1) checklistItems.value.splice(i, 1)
}

// Preset categories
const categoryIcons = {
  design: 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
  atendimento: 'M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z',
  geral: 'M13 10V3L4 14h7v7l9-11h-7z',
}
const categoryLabels = {
  design: { label: 'Design', color: '#c377e0' },
  atendimento: { label: 'Atendimento', color: '#388bfd' },
  geral: { label: 'Geral', color: '#6366f1' },
}

const groupedPresets = computed(() => {
  const groups = {}
  for (const p of presets.value) {
    const cat = p.category || 'geral'
    if (!groups[cat]) groups[cat] = []
    groups[cat].push(p)
  }
  return groups
})

async function fetchData() {
  loading.value = true
  try {
    const [autoRes, presetRes] = await Promise.all([
      boardsApi.getAutomations(props.boardId),
      boardsApi.getAutomationPresets(props.boardId),
    ])
    automations.value = autoRes.data.data
    presets.value = presetRes.data.data
  } catch {
    // ignore
  } finally {
    loading.value = false
  }
}

async function installPreset(preset) {
  saving.value = true
  error.value = ''
  try {
    await boardsApi.createAutomation(props.boardId, {
      name: preset.name,
      trigger_type: preset.trigger_type,
      trigger_config: preset.trigger_config,
      action_type: preset.action_type,
      action_config: preset.action_config,
    })
    await fetchData()
    view.value = 'list'
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao instalar automação.'
  } finally {
    saving.value = false
  }
}

function isPresetInstalled(preset) {
  return automations.value.some(
    a => a.trigger_type === preset.trigger_type &&
         a.action_type === preset.action_type &&
         JSON.stringify(a.trigger_config) === JSON.stringify(preset.trigger_config) &&
         JSON.stringify(a.action_config) === JSON.stringify(preset.action_config)
  )
}

function openCustomForm() {
  formName.value = ''
  formTrigger.value = 'card_moved_to_list'
  formTriggerConfig.value = {}
  formAction.value = 'assign_member'
  formActionConfig.value = {}
  checklistTitle.value = 'Checklist'
  checklistItems.value = ['']
  error.value = ''
  view.value = 'custom'
}

function buildAutoName() {
  const t = triggers.find(x => x.value === formTrigger.value)
  const a = actions.find(x => x.value === formAction.value)
  return `${t?.label || formTrigger.value} → ${a?.label || formAction.value}`
}

async function handleCreate() {
  error.value = ''
  saving.value = true
  try {
    const triggerConfig = { ...formTriggerConfig.value }
    // Clean undefined values
    Object.keys(triggerConfig).forEach(k => { if (triggerConfig[k] === undefined) delete triggerConfig[k] })

    let actionConfig = { ...formActionConfig.value }

    // Build checklist config
    if (formAction.value === 'add_checklist') {
      const validItems = checklistItems.value.filter(i => i.trim())
      if (validItems.length === 0) {
        error.value = 'Adicione pelo menos um item ao checklist.'
        saving.value = false
        return
      }
      actionConfig = { title: checklistTitle.value || 'Checklist', items: validItems }
    }

    await boardsApi.createAutomation(props.boardId, {
      name: formName.value || buildAutoName(),
      trigger_type: formTrigger.value,
      trigger_config: triggerConfig,
      action_type: formAction.value,
      action_config: actionConfig,
    })

    await fetchData()
    view.value = 'list'
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao criar automação.'
  } finally {
    saving.value = false
  }
}

async function handleToggle(auto) {
  try {
    await boardsApi.toggleAutomation(auto.id)
    auto.is_active = !auto.is_active
  } catch {
    // ignore
  }
}

async function handleDelete(id) {
  try {
    await boardsApi.deleteAutomation(id)
    automations.value = automations.value.filter(a => a.id !== id)
  } catch {
    // ignore
  }
}

function triggerLabel(auto) {
  const t = triggers.find(tr => tr.value === auto.trigger_type)
  let label = t?.label || auto.trigger_type
  if (auto.trigger_config?.list_id) {
    const list = lists.value.find(l => l.id === auto.trigger_config.list_id)
    if (list) label += ` "${list.name}"`
  }
  if (auto.trigger_config?.label_id) {
    const lbl = labels.value.find(l => l.id === auto.trigger_config.label_id)
    if (lbl) label += ` "${lbl.name || lbl.color}"`
  }
  return label
}

function actionLabel(auto) {
  const a = actions.find(ac => ac.value === auto.action_type)
  let label = a?.label || auto.action_type
  if (auto.action_config?.user_id) {
    const m = members.value.find(m => m.id === auto.action_config.user_id)
    if (m) label += `: ${m.name}`
  }
  if (auto.action_config?.label_id) {
    const lbl = labels.value.find(l => l.id === auto.action_config.label_id)
    if (lbl) label += `: ${lbl.name || lbl.color}`
  }
  if (auto.action_config?.list_id) {
    const list = lists.value.find(l => l.id === auto.action_config.list_id)
    if (list) label += `: ${list.name}`
  }
  if (auto.action_config?.days) label += `: +${auto.action_config.days} dias`
  if (auto.action_config?.title) label += `: "${auto.action_config.title}"`
  if (auto.action_config?.text) {
    const txt = auto.action_config.text.length > 30 ? auto.action_config.text.slice(0, 30) + '...' : auto.action_config.text
    label += `: "${txt}"`
  }
  return label
}

const container = ref(null)
function onClickOutside(e) {
  if (open.value && container.value && !container.value.contains(e.target)) {
    open.value = false
  }
}

function handleOpen() {
  open.value = !open.value
  if (open.value && props.hasFeature) {
    view.value = 'list'
    fetchData()
  }
}

onMounted(() => document.addEventListener('mousedown', onClickOutside))
onUnmounted(() => document.removeEventListener('mousedown', onClickOutside))
</script>

<template>
  <div ref="container" class="relative">
    <!-- Trigger button -->
    <button
      class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
      :class="isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10'"
      @click="handleOpen"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
      </svg>
      Automações
    </button>

    <!-- Panel -->
    <div
      v-if="open"
      :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200'"
      class="absolute right-0 top-full mt-2 rounded-lg shadow-xl border z-50"
      :style="{ width: view === 'custom' ? '440px' : '400px' }"
    >
      <!-- Header -->
      <div :class="isDark ? 'border-[#444c56]' : 'border-gray-200'" class="flex items-center justify-between px-4 py-3 border-b">
        <div class="flex items-center gap-2">
          <button
            v-if="view !== 'list'"
            :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-400 hover:text-gray-700'"
            @click="view = 'list'"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
          </button>
          <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-semibold text-sm">
            {{ view === 'presets' ? 'Adicionar automação' : view === 'custom' ? 'Criar personalizada' : 'Automações' }}
          </span>
        </div>
        <button :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-400 hover:text-gray-600'" class="text-lg leading-none" @click="open = false">&times;</button>
      </div>

      <div class="p-4 max-h-[70vh] overflow-y-auto">
        <!-- Feature gate -->
        <div v-if="!hasFeature" class="text-center py-6">
          <svg :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="w-10 h-10 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          <p :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-medium text-sm mb-1">Automações</p>
          <p :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs mb-3">Automatize tarefas repetitivas como mover cards, atribuir membros e definir prazos.</p>
          <router-link to="/plans" class="inline-flex items-center gap-1 text-sm text-[#6366f1] hover:underline font-medium">Fazer upgrade</router-link>
        </div>

        <template v-else>
          <!-- Loading -->
          <div v-if="loading" class="flex items-center justify-center py-8">
            <div class="animate-spin w-5 h-5 border-2 border-[#6366f1] border-t-transparent rounded-full" />
          </div>

          <template v-else>
            <!-- ===== LIST VIEW ===== -->
            <template v-if="view === 'list'">
              <!-- Active automations -->
              <div v-if="automations.length > 0" class="space-y-2 mb-4">
                <div
                  v-for="auto in automations"
                  :key="auto.id"
                  :class="[
                    isDark ? 'bg-[#1c2128] border-[#2d333b]' : 'bg-[#f6f8fa] border-gray-100',
                    !auto.is_active ? 'opacity-50' : ''
                  ]"
                  class="rounded-lg px-3 py-2.5 border transition-opacity"
                >
                  <div class="flex items-start justify-between gap-2">
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-medium mb-1.5 truncate" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
                        {{ auto.name }}
                      </div>
                      <div class="flex items-center gap-1.5 mb-1">
                        <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded font-medium" :class="isDark ? 'bg-[#6366f1]/15 text-[#a5b4fc]' : 'bg-indigo-50 text-indigo-600'">
                          Quando
                        </span>
                        <span :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-[11px] truncate">{{ triggerLabel(auto) }}</span>
                      </div>
                      <div class="flex items-center gap-1.5">
                        <span class="inline-flex items-center text-[10px] px-1.5 py-0.5 rounded font-medium" :class="isDark ? 'bg-emerald-500/15 text-emerald-400' : 'bg-green-50 text-green-600'">
                          Então
                        </span>
                        <span :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-[11px] truncate">{{ actionLabel(auto) }}</span>
                      </div>
                    </div>
                    <div class="flex items-center gap-2 flex-shrink-0 pt-0.5">
                      <button
                        class="w-8 h-[18px] rounded-full relative transition-colors"
                        :class="auto.is_active ? 'bg-[#6366f1]' : (isDark ? 'bg-[#444c56]' : 'bg-gray-300')"
                        @click="handleToggle(auto)"
                      >
                        <span
                          class="absolute top-[2px] w-[14px] h-[14px] bg-white rounded-full transition-all"
                          :style="{ left: auto.is_active ? '16px' : '2px' }"
                        />
                      </button>
                      <button :class="isDark ? 'text-[#6e7681] hover:text-[#f85149]' : 'text-gray-300 hover:text-red-500'" class="transition-colors" @click="handleDelete(auto.id)">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                      </button>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Empty state -->
              <div v-else class="text-center py-6">
                <div class="w-12 h-12 rounded-full mx-auto mb-3 flex items-center justify-center" :class="isDark ? 'bg-[#6366f1]/10' : 'bg-indigo-50'">
                  <svg class="w-6 h-6 text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <p :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-medium mb-1">Nenhuma automação ativa</p>
                <p :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs">Automatize tarefas repetitivas do seu fluxo de trabalho</p>
              </div>

              <!-- Add button -->
              <button
                class="w-full py-2.5 rounded-lg text-sm font-medium transition-colors flex items-center justify-center gap-2 bg-[#6366f1] hover:bg-[#4f46e5] text-white"
                @click="view = 'presets'"
              >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Adicionar automação
              </button>
            </template>

            <!-- ===== PRESETS VIEW ===== -->
            <template v-if="view === 'presets'">
              <p v-if="error" class="text-xs text-[#f85149] mb-3">{{ error }}</p>

              <!-- Preset groups -->
              <div v-for="(group, cat) in groupedPresets" :key="cat" class="mb-4 last:mb-2">
                <div class="flex items-center gap-2 mb-2">
                  <svg class="w-4 h-4" :style="{ color: categoryLabels[cat]?.color || '#6366f1' }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" :d="categoryIcons[cat] || categoryIcons.geral" /></svg>
                  <span class="text-xs font-semibold uppercase tracking-wide" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
                    {{ categoryLabels[cat]?.label || cat }}
                  </span>
                </div>

                <div class="space-y-1.5">
                  <button
                    v-for="(preset, pi) in group"
                    :key="pi"
                    :disabled="saving || isPresetInstalled(preset)"
                    class="w-full text-left rounded-lg px-3 py-2.5 border transition-all group"
                    :class="[
                      isPresetInstalled(preset)
                        ? (isDark ? 'border-[#2d333b] bg-[#1c2128] opacity-50 cursor-default' : 'border-gray-100 bg-gray-50 opacity-50 cursor-default')
                        : (isDark ? 'border-[#2d333b] hover:border-[#6366f1]/50 bg-[#1c2128] hover:bg-[#6366f1]/5' : 'border-gray-200 hover:border-purple-300 bg-white hover:bg-indigo-50/50'),
                    ]"
                    @click="!isPresetInstalled(preset) && installPreset(preset)"
                  >
                    <div class="flex items-start justify-between gap-2">
                      <div class="flex-1 min-w-0">
                        <div class="text-xs font-medium mb-0.5" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
                          {{ preset.name }}
                        </div>
                        <div class="text-[11px]" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
                          {{ preset.description }}
                        </div>
                      </div>
                      <div class="flex-shrink-0 pt-0.5">
                        <span v-if="isPresetInstalled(preset)" class="text-[10px] px-1.5 py-0.5 rounded font-medium" :class="isDark ? 'bg-emerald-500/15 text-emerald-400' : 'bg-green-50 text-green-600'">
                          Ativa
                        </span>
                        <svg v-else class="w-4 h-4 opacity-0 group-hover:opacity-100 transition-opacity text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                      </div>
                    </div>
                  </button>
                </div>
              </div>

              <!-- Custom option -->
              <div :class="isDark ? 'border-[#2d333b]' : 'border-gray-200'" class="border-t pt-3 mt-3">
                <button
                  class="w-full text-left rounded-lg px-3 py-2.5 border border-dashed transition-colors"
                  :class="isDark ? 'border-[#444c56] text-[#8b949e] hover:border-[#6366f1] hover:text-[#a5b4fc]' : 'border-gray-300 text-gray-500 hover:border-purple-400 hover:text-indigo-600'"
                  @click="openCustomForm"
                >
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    <div>
                      <div class="text-xs font-medium">Criar personalizada</div>
                      <div class="text-[11px] opacity-70">Configure gatilho e ação manualmente</div>
                    </div>
                  </div>
                </button>
              </div>
            </template>

            <!-- ===== CUSTOM FORM ===== -->
            <template v-if="view === 'custom'">
              <div class="space-y-4">
                <!-- Name -->
                <div>
                  <label :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs font-medium mb-1 block">Nome (opcional)</label>
                  <input
                    v-model="formName"
                    class="monday-input text-sm"
                    placeholder="Ex: Briefing completo → Iniciar criação"
                  />
                </div>

                <!-- Trigger -->
                <div>
                  <label class="text-xs font-medium mb-1.5 flex items-center gap-1.5" :class="isDark ? 'text-[#a5b4fc]' : 'text-indigo-600'">
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded text-[10px] font-bold bg-[#6366f1]/20 text-[#6366f1]">1</span>
                    Quando isso acontecer...
                  </label>
                  <select v-model="formTrigger" class="monday-input text-sm" @change="formTriggerConfig = {}">
                    <option v-for="t in triggers" :key="t.value" :value="t.value">{{ t.label }}</option>
                  </select>

                  <!-- Trigger config: list -->
                  <select
                    v-if="currentTrigger?.needsConfig === 'list' || currentTrigger?.needsConfig === 'list_optional'"
                    v-model="formTriggerConfig.list_id"
                    class="monday-input text-sm mt-1.5"
                  >
                    <option :value="undefined">{{ currentTrigger.needsConfig === 'list_optional' ? 'Qualquer lista' : 'Selecione uma lista...' }}</option>
                    <option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option>
                  </select>

                  <!-- Trigger config: label -->
                  <select
                    v-if="currentTrigger?.needsConfig === 'label'"
                    v-model="formTriggerConfig.label_id"
                    class="monday-input text-sm mt-1.5"
                  >
                    <option :value="undefined">Qualquer etiqueta</option>
                    <option v-for="l in labels" :key="l.id" :value="l.id">{{ l.name || l.color }}</option>
                  </select>

                  <!-- Trigger config: member -->
                  <select
                    v-if="currentTrigger?.needsConfig === 'member'"
                    v-model="formTriggerConfig.user_id"
                    class="monday-input text-sm mt-1.5"
                  >
                    <option :value="undefined">Qualquer membro</option>
                    <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                  </select>
                </div>

                <!-- Action -->
                <div>
                  <label class="text-xs font-medium mb-1.5 flex items-center gap-1.5" :class="isDark ? 'text-emerald-400' : 'text-green-600'">
                    <span class="inline-flex items-center justify-center w-4 h-4 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-500">2</span>
                    Fazer isso automaticamente...
                  </label>
                  <select v-model="formAction" class="monday-input text-sm" @change="formActionConfig = {}; checklistTitle = 'Checklist'; checklistItems = ['']">
                    <option v-for="a in actions" :key="a.value" :value="a.value">{{ a.label }}</option>
                  </select>

                  <!-- Action config: list -->
                  <select
                    v-if="currentAction?.needsConfig === 'list'"
                    v-model="formActionConfig.list_id"
                    class="monday-input text-sm mt-1.5"
                  >
                    <option value="">Selecione uma lista...</option>
                    <option v-for="l in lists" :key="l.id" :value="l.id">{{ l.name }}</option>
                  </select>

                  <!-- Action config: member -->
                  <select
                    v-if="currentAction?.needsConfig === 'member'"
                    v-model="formActionConfig.user_id"
                    class="monday-input text-sm mt-1.5"
                  >
                    <option value="">Selecione um membro...</option>
                    <option v-for="m in members" :key="m.id" :value="m.id">{{ m.name }}</option>
                  </select>

                  <!-- Action config: days -->
                  <div v-if="currentAction?.needsConfig === 'days'" class="flex items-center gap-2 mt-1.5">
                    <span :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs">Prazo de</span>
                    <input v-model.number="formActionConfig.days" type="number" min="1" max="365" class="monday-input text-sm w-16" placeholder="3" />
                    <span :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="text-xs">dias</span>
                  </div>

                  <!-- Action config: label -->
                  <select
                    v-if="currentAction?.needsConfig === 'label'"
                    v-model="formActionConfig.label_id"
                    class="monday-input text-sm mt-1.5"
                  >
                    <option value="">Selecione uma etiqueta...</option>
                    <option v-for="l in labels" :key="l.id" :value="l.id">{{ l.name || l.color }}</option>
                  </select>

                  <!-- Action config: text (comment) -->
                  <textarea
                    v-if="currentAction?.needsConfig === 'text'"
                    v-model="formActionConfig.text"
                    class="monday-input text-sm mt-1.5"
                    rows="2"
                    placeholder="Texto do comentário automático..."
                  />

                  <!-- Action config: checklist -->
                  <div v-if="currentAction?.needsConfig === 'checklist'" class="mt-1.5 space-y-2">
                    <input
                      v-model="checklistTitle"
                      class="monday-input text-sm"
                      placeholder="Título do checklist"
                    />
                    <div class="space-y-1">
                      <div v-for="(item, i) in checklistItems" :key="i" class="flex items-center gap-1.5">
                        <div class="w-3.5 h-3.5 rounded border flex-shrink-0" :class="isDark ? 'border-[#444c56]' : 'border-gray-300'" />
                        <input
                          v-model="checklistItems[i]"
                          class="monday-input text-sm flex-1 !py-1"
                          :placeholder="`Item ${i + 1}...`"
                          @keyup.enter="addChecklistItem"
                        />
                        <button
                          v-if="checklistItems.length > 1"
                          :class="isDark ? 'text-[#6e7681] hover:text-[#f85149]' : 'text-gray-300 hover:text-red-500'"
                          class="flex-shrink-0"
                          @click="removeChecklistItem(i)"
                        >
                          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                      </div>
                    </div>
                    <button
                      class="text-xs flex items-center gap-1"
                      :class="isDark ? 'text-[#8b949e] hover:text-[#a5b4fc]' : 'text-gray-500 hover:text-indigo-600'"
                      @click="addChecklistItem"
                    >
                      <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                      Adicionar item
                    </button>
                  </div>
                </div>

                <!-- Preview -->
                <div
                  v-if="formTrigger && formAction"
                  class="rounded-lg px-3 py-2 text-[11px]"
                  :class="isDark ? 'bg-[#1c2128] text-[#8b949e]' : 'bg-gray-50 text-gray-500'"
                >
                  <span class="font-medium" :class="isDark ? 'text-[#a5b4fc]' : 'text-indigo-600'">Resumo:</span>
                  Quando <strong :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ triggers.find(t => t.value === formTrigger)?.label?.toLowerCase() }}</strong>,
                  automaticamente <strong :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ actions.find(a => a.value === formAction)?.label?.toLowerCase() }}</strong>
                </div>

                <p v-if="error" class="text-xs text-[#f85149]">{{ error }}</p>

                <!-- Actions -->
                <div class="flex gap-2">
                  <button
                    class="flex-1 py-2 rounded-lg text-sm font-medium transition-colors bg-[#6366f1] hover:bg-[#4f46e5] text-white"
                    :disabled="saving"
                    @click="handleCreate"
                  >
                    {{ saving ? 'Criando...' : 'Criar automação' }}
                  </button>
                  <button
                    class="px-4 py-2 rounded-lg text-sm transition-colors"
                    :class="isDark ? 'text-[#8b949e] hover:bg-[#2d333b]' : 'text-gray-500 hover:bg-gray-100'"
                    @click="view = 'presets'"
                  >
                    Cancelar
                  </button>
                </div>
              </div>
            </template>
          </template>
        </template>
      </div>
    </div>
  </div>
</template>

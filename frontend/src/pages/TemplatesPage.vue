<script setup>
import { ref, computed, onMounted } from 'vue'
import { boardsApi } from '../api/boards'
import { useBoardsStore } from '../stores/boards'
import { usePlanLimits } from '../composables/usePlanLimits'
import TemplateEditorModal from '../components/board/TemplateEditorModal.vue'

const boardsStore = useBoardsStore()
const { plan } = usePlanLimits()

const isFreePlan = computed(() => !plan.value || (plan.value.price_monthly ?? 0) <= 0)
const canCreateMore = computed(() => !isFreePlan.value || templates.value.length < 1)
const limitError = ref('')

const templates = ref([])
const loading = ref(false)
const showEditor = ref(false)
const editingTemplate = ref(null)
const deleting = ref(null)
const successMessage = ref('')
const applyingTo = ref(null)
const selectedBoardId = ref('')
const applying = ref(false)
const showHowItWorks = ref(false)

let successTimeout = null
function showSuccess(msg) {
  successMessage.value = msg
  clearTimeout(successTimeout)
  successTimeout = setTimeout(() => { successMessage.value = '' }, 3000)
}

async function fetchTemplates() {
  loading.value = true
  try {
    const res = await boardsApi.listTemplates()
    templates.value = res.data.data || []
  } finally {
    loading.value = false
  }
}

function openCreate() {
  if (!canCreateMore.value) {
    limitError.value = 'Seu plano gratis permite apenas 1 template. Faca upgrade para criar mais.'
    setTimeout(() => { limitError.value = '' }, 4000)
    return
  }
  editingTemplate.value = null
  showEditor.value = true
}

function openEdit(template) {
  editingTemplate.value = template
  showEditor.value = true
}

async function handleSave(data) {
  if (editingTemplate.value) {
    await boardsApi.updateTemplate(editingTemplate.value.id, data)
    showSuccess('Template atualizado com sucesso!')
  } else {
    await boardsApi.createTemplate(data)
    showSuccess('Template criado com sucesso!')
  }
  showEditor.value = false
  editingTemplate.value = null
  await fetchTemplates()
}

async function handleDelete(template) {
  if (template.is_default) return
  deleting.value = template.id
  try {
    await boardsApi.deleteTemplate(template.id)
    templates.value = templates.value.filter(t => t.id !== template.id)
    showSuccess('Template excluido!')
  } finally {
    deleting.value = null
  }
}

async function handleDuplicate(template) {
  if (!canCreateMore.value) {
    limitError.value = 'Seu plano gratis permite apenas 1 template. Faca upgrade para criar mais.'
    setTimeout(() => { limitError.value = '' }, 4000)
    return
  }
  await boardsApi.createTemplate({
    name: `${template.name} (copia)`,
    description: template.description,
    lists: template.lists,
    labels: template.labels,
    custom_fields: template.custom_fields || [],
    automations: template.automations || [],
  })
  await fetchTemplates()
  showSuccess('Template duplicado com sucesso!')
}

function openApplyModal(template) {
  applyingTo.value = template
  selectedBoardId.value = ''
}

async function handleApply() {
  if (!selectedBoardId.value || !applyingTo.value) return
  applying.value = true
  try {
    await boardsApi.applyTemplate(applyingTo.value.id, selectedBoardId.value)
    showSuccess(`Template "${applyingTo.value.name}" aplicado ao quadro com sucesso!`)
    applyingTo.value = null
    selectedBoardId.value = ''
  } catch {
    showSuccess('Erro ao aplicar template. Tente novamente.')
  } finally {
    applying.value = false
  }
}

onMounted(() => {
  fetchTemplates()
  boardsStore.fetchBoards()
})
</script>

<template>
  <div class="p-2 sm:p-4 w-full">
    <!-- Success toast -->
    <Teleport to="body">
      <Transition name="slide-up">
        <div
          v-if="successMessage"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[200] px-5 py-3 rounded-xl shadow-2xl bg-[#3fb950] text-white text-sm font-medium flex items-center gap-2"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          {{ successMessage }}
        </div>
      </Transition>
    </Teleport>

    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-3xl font-bold text-[#e6edf3]">Templates de Quadro</h1>
        <p class="text-base text-[#8b949e] mt-2">Estruturas prontas para criar quadros padronizados em 1 clique</p>
      </div>
      <div class="flex items-center gap-2">
        <button
          class="flex items-center gap-1.5 px-3 py-2 text-xs font-medium rounded-lg border transition-colors"
          :class="showHowItWorks ? 'border-[#6366f1] bg-[#6366f1]/10 text-[#a5b4fc]' : 'border-[#444c56] text-[#8b949e] hover:border-[#6e7681] hover:text-[#e6edf3]'"
          @click="showHowItWorks = !showHowItWorks"
        >
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Como funciona?
        </button>
        <button
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-lg transition-colors"
          :class="canCreateMore ? 'bg-[#6366f1] hover:bg-[#5558e6] text-white' : 'bg-[#2d333b] text-[#6e7681] cursor-not-allowed'"
          @click="openCreate"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Novo Template
        </button>
      </div>
    </div>

    <!-- How it works -->
    <Transition name="expand">
      <div v-if="showHowItWorks" class="mb-6 rounded-xl border border-[#2d333b] bg-[#161b22] p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
          <div class="flex gap-3">
            <div class="w-9 h-9 rounded-lg bg-[#6366f1]/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-[#e6edf3]">Padronize</p>
              <p class="text-xs text-[#8b949e] mt-0.5">Mesma estrutura de listas, etiquetas, campos e automacoes para todos os projetos do mesmo tipo.</p>
            </div>
          </div>
          <div class="flex gap-3">
            <div class="w-9 h-9 rounded-lg bg-[#3fb950]/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[#3fb950]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-[#e6edf3]">Escale</p>
              <p class="text-xs text-[#8b949e] mt-0.5">Novo cliente ou projeto? Crie o quadro com template e tudo ja vem configurado. Sem montar do zero.</p>
            </div>
          </div>
          <div class="flex gap-3">
            <div class="w-9 h-9 rounded-lg bg-[#d29922]/10 flex items-center justify-center flex-shrink-0">
              <svg class="w-5 h-5 text-[#d29922]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </div>
            <div>
              <p class="text-sm font-semibold text-[#e6edf3]">Personalize</p>
              <p class="text-xs text-[#8b949e] mt-0.5">Cada template pode ter suas listas, etiquetas, campos customizados e automacoes proprias.</p>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-2 pt-3 border-t border-[#2d333b]">
          <svg class="w-4 h-4 text-[#6e7681] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <p class="text-xs text-[#6e7681]">
            <strong class="text-[#8b949e]">Dica:</strong> Voce tambem pode salvar a estrutura de um quadro existente como template. Basta abrir o quadro e clicar em "Template" no canto superior.
          </p>
        </div>
      </div>
    </Transition>

    <!-- Limit warning -->
    <Transition name="slide-up">
      <div
        v-if="limitError"
        class="mb-4 flex items-center gap-3 p-3 rounded-xl bg-[#d29922]/10 border border-[#d29922]/30"
      >
        <svg class="w-5 h-5 text-[#d29922] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
        <p class="text-sm text-[#d29922] flex-1">{{ limitError }}</p>
        <a href="/plans" class="text-xs font-semibold text-[#6366f1] hover:text-[#a5b4fc] transition-colors whitespace-nowrap">Ver planos</a>
      </div>
    </Transition>

    <!-- Feature cards (always visible, compact) -->
    <div v-if="!loading && templates.length > 0" class="flex gap-2 mb-5 overflow-x-auto">
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[#161b22] border border-[#2d333b] flex-shrink-0">
        <svg class="w-3.5 h-3.5 text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
        <span class="text-[11px] text-[#8b949e]">Listas pre-definidas</span>
      </div>
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[#161b22] border border-[#2d333b] flex-shrink-0">
        <svg class="w-3.5 h-3.5 text-[#ec4899]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        <span class="text-[11px] text-[#8b949e]">Etiquetas</span>
      </div>
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[#161b22] border border-[#2d333b] flex-shrink-0">
        <svg class="w-3.5 h-3.5 text-[#3fb950]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
        <span class="text-[11px] text-[#8b949e]">Campos customizados</span>
      </div>
      <div class="flex items-center gap-2 px-3 py-2 rounded-lg bg-[#161b22] border border-[#2d333b] flex-shrink-0">
        <svg class="w-3.5 h-3.5 text-[#d29922]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
        <span class="text-[11px] text-[#8b949e]">Automacoes</span>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex items-center justify-center py-20">
      <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" />
    </div>

    <!-- Empty state -->
    <div v-else-if="templates.length === 0" class="text-center py-16">
      <div class="w-20 h-20 rounded-2xl bg-[#6366f1]/10 flex items-center justify-center mx-auto mb-5">
        <svg class="w-10 h-10 text-[#6366f1]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
      </div>
      <h3 class="text-xl font-semibold text-[#e6edf3] mb-3">Crie seu primeiro template</h3>
      <p class="text-base text-[#8b949e] mb-6 max-w-lg mx-auto">
        Templates permitem criar quadros com estrutura completa ja definida: listas, etiquetas, campos customizados e automacoes. Ideal para padronizar processos e escalar sua operacao.
      </p>

      <!-- Use case examples -->
      <div class="flex flex-wrap justify-center gap-3 mb-6">
        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#161b22] border border-[#2d333b]">
          <div class="w-2 h-2 rounded-full bg-[#ec4899]" />
          <span class="text-xs text-[#e6edf3] font-medium">Social Media</span>
          <span class="text-[10px] text-[#6e7681]">6 listas + 6 etiquetas</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#161b22] border border-[#2d333b]">
          <div class="w-2 h-2 rounded-full bg-[#6366f1]" />
          <span class="text-xs text-[#e6edf3] font-medium">Branding</span>
          <span class="text-[10px] text-[#6e7681]">7 listas + 5 etiquetas</span>
        </div>
        <div class="flex items-center gap-2 px-4 py-2.5 rounded-xl bg-[#161b22] border border-[#2d333b]">
          <div class="w-2 h-2 rounded-full bg-[#3fb950]" />
          <span class="text-xs text-[#e6edf3] font-medium">Web Design</span>
          <span class="text-[10px] text-[#6e7681]">5 listas + 4 etiquetas</span>
        </div>
      </div>

      <button
        class="px-5 py-2.5 text-sm font-medium rounded-lg bg-[#6366f1] hover:bg-[#5558e6] text-white transition-colors"
        @click="openCreate"
      >
        Criar primeiro template
      </button>

      <p class="text-[11px] text-[#6e7681] mt-4 max-w-md mx-auto">
        Voce tambem pode abrir um quadro existente, clicar em "Template" no topo e salvar sua estrutura atual como template reutilizavel.
      </p>
    </div>

    <!-- Template grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
      <div
        v-for="template in templates"
        :key="template.id"
        class="rounded-xl border bg-[#161b22] border-[#444c56] hover:border-[#6e7681] transition-all duration-200 group"
      >
        <div class="p-4">
          <!-- Name and badge -->
          <div class="flex items-start justify-between mb-2">
            <h3 class="text-sm font-semibold text-[#e6edf3]">{{ template.name }}</h3>
            <span v-if="template.is_default" class="text-[10px] px-2 py-0.5 rounded-full bg-[#6366f1]/20 text-[#a5b4fc] font-medium flex-shrink-0 ml-2">Padrao</span>
          </div>

          <!-- Description -->
          <p v-if="template.description" class="text-xs text-[#8b949e] mb-3 line-clamp-2">{{ template.description }}</p>
          <p v-else class="text-xs text-[#6e7681] italic mb-3">Sem descricao</p>

          <!-- Stats row -->
          <div class="flex flex-wrap gap-2 mb-3">
            <span class="inline-flex items-center gap-1 text-[10px] text-[#8b949e]">
              <svg class="w-3 h-3 text-[#6e7681]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
              {{ template.lists?.length || 0 }} listas
            </span>
            <span v-if="template.labels?.length" class="inline-flex items-center gap-1 text-[10px] text-[#8b949e]">
              <svg class="w-3 h-3 text-[#6e7681]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
              {{ template.labels.length }} etiquetas
            </span>
            <span v-if="template.custom_fields?.length" class="inline-flex items-center gap-1 text-[10px] text-[#8b949e]">
              <svg class="w-3 h-3 text-[#6e7681]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              {{ template.custom_fields.length }} campos
            </span>
            <span v-if="template.automations?.length" class="inline-flex items-center gap-1 text-[10px] text-[#a5b4fc]">
              <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
              {{ template.automations.length }} automacoes
            </span>
          </div>

          <!-- Lists preview -->
          <div class="mb-3">
            <div class="flex gap-1 items-center overflow-x-auto pb-1">
              <template v-for="(list, i) in template.lists" :key="i">
                <span class="flex-shrink-0 px-2 py-0.5 rounded bg-[#2d333b] border border-[#444c56] text-[10px] text-[#e6edf3] whitespace-nowrap font-medium">{{ list.name }}</span>
                <svg v-if="i < template.lists.length - 1" class="w-2.5 h-2.5 text-[#444c56] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </template>
            </div>
          </div>

          <!-- Labels preview -->
          <div v-if="template.labels?.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
              <span
                v-for="(label, i) in template.labels.slice(0, 8)"
                :key="i"
                class="inline-flex items-center h-4 px-1.5 rounded-sm text-[9px] text-white font-medium"
                :style="{ backgroundColor: label.color }"
              >
                {{ label.name }}
              </span>
              <span v-if="template.labels.length > 8" class="text-[10px] text-[#6e7681] self-center">+{{ template.labels.length - 8 }}</span>
            </div>
          </div>

          <!-- Custom fields preview -->
          <div v-if="template.custom_fields?.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
              <span
                v-for="(field, i) in template.custom_fields.slice(0, 5)"
                :key="i"
                class="inline-flex items-center h-4 px-1.5 rounded-sm text-[9px] text-[#e6edf3] font-medium bg-[#2d333b] border border-[#444c56]"
              >{{ field.name }} <span class="ml-1 text-[#6e7681]">{{ field.type }}</span></span>
              <span v-if="template.custom_fields.length > 5" class="text-[10px] text-[#6e7681] self-center">+{{ template.custom_fields.length - 5 }}</span>
            </div>
          </div>

          <!-- Automations preview -->
          <div v-if="template.automations?.length" class="mb-3">
            <div class="flex flex-wrap gap-1">
              <span
                v-for="(auto, i) in template.automations.slice(0, 3)"
                :key="i"
                class="inline-flex items-center h-4 px-1.5 rounded-sm text-[9px] text-[#a5b4fc] font-medium bg-[#6366f1]/10 border border-[#6366f1]/20"
              >{{ auto.name }}</span>
              <span v-if="template.automations.length > 3" class="text-[10px] text-[#6e7681] self-center">+{{ template.automations.length - 3 }}</span>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex gap-1.5 pt-2 border-t border-[#2d333b]">
            <button
              class="flex-1 text-xs py-1.5 rounded-lg bg-[#6366f1]/15 text-[#a5b4fc] hover:bg-[#6366f1]/25 transition-colors font-medium"
              @click="openApplyModal(template)"
            >
              Aplicar
            </button>
            <button
              class="flex-1 text-xs py-1.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors font-medium"
              @click="openEdit(template)"
            >
              Editar
            </button>
            <button
              v-if="canCreateMore"
              class="text-xs py-1.5 px-2.5 rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors font-medium"
              @click="handleDuplicate(template)"
              title="Duplicar"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            </button>
            <button
              v-if="!template.is_default"
              class="text-xs py-1.5 px-2.5 rounded-lg text-[#f85149] hover:bg-[#f8514926] transition-colors font-medium"
              :disabled="deleting === template.id"
              @click="handleDelete(template)"
              title="Excluir"
            >
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Editor Modal -->
    <TemplateEditorModal
      :show="showEditor"
      :template="editingTemplate"
      @save="handleSave"
      @cancel="showEditor = false"
    />

    <!-- Apply Template Modal -->
    <Teleport to="body">
      <div v-if="applyingTo" class="fixed inset-0 z-[100] flex items-center justify-center">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="applyingTo = null" />
        <div class="relative rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden border bg-[#161b22] border-[#444c56]">
          <div class="px-5 pt-5 pb-2">
            <h3 class="text-base font-semibold text-[#e6edf3]">Aplicar Template</h3>
            <p class="text-xs text-[#8b949e] mt-1">Selecione o quadro onde deseja aplicar o template "{{ applyingTo.name }}"</p>
          </div>

          <div class="px-5 py-4 space-y-3">
            <!-- Warning -->
            <div class="flex items-start gap-2 p-3 rounded-lg bg-[#d29922]/10 border border-[#d29922]/30">
              <svg class="w-4 h-4 text-[#d29922] flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
              <p class="text-xs text-[#d29922]">Atencao: aplicar um template substituira todas as listas, etiquetas, campos e automacoes atuais do quadro.</p>
            </div>

            <!-- Board selector -->
            <div>
              <label class="text-xs font-semibold uppercase mb-1.5 block text-[#8b949e]">Quadro</label>
              <select
                v-model="selectedBoardId"
                class="w-full text-sm rounded-lg px-3 py-2.5 border appearance-none cursor-pointer bg-[#0d1117] border-[#444c56] text-[#e6edf3] focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
              >
                <option value="" disabled>Selecione um quadro...</option>
                <option
                  v-for="board in boardsStore.boards"
                  :key="board.id"
                  :value="board.id"
                >
                  {{ board.name }}{{ board.client_name ? ` (${board.client_name})` : '' }}
                </option>
              </select>
            </div>

            <!-- Preview what will be applied -->
            <div v-if="selectedBoardId" class="p-3 rounded-lg bg-[#0d1117] border border-[#2d333b]">
              <p class="text-[10px] text-[#6e7681] uppercase font-semibold mb-2">Sera aplicado:</p>
              <div class="flex gap-1 items-center flex-wrap mb-1.5">
                <span
                  v-for="(list, i) in applyingTo.lists"
                  :key="i"
                  class="px-1.5 py-0.5 rounded bg-[#2d333b] text-[10px] text-[#e6edf3] font-medium"
                >{{ list.name }}</span>
              </div>
              <div v-if="applyingTo.labels?.length" class="flex gap-1 flex-wrap">
                <span
                  v-for="(label, i) in applyingTo.labels"
                  :key="i"
                  class="h-3 px-1.5 rounded-sm text-[8px] text-white font-medium inline-flex items-center"
                  :style="{ backgroundColor: label.color }"
                >{{ label.name }}</span>
              </div>
              <p v-if="applyingTo.custom_fields?.length || applyingTo.automations?.length"
                class="text-[10px] text-[#8b949e] mt-1.5">
                <span v-if="applyingTo.custom_fields?.length">{{ applyingTo.custom_fields.length }} campo(s)</span>
                <span v-if="applyingTo.custom_fields?.length && applyingTo.automations?.length"> + </span>
                <span v-if="applyingTo.automations?.length">{{ applyingTo.automations.length }} automacao(oes)</span>
                tambem serao aplicados
              </p>
            </div>
          </div>

          <div class="flex justify-end gap-2 px-5 py-4 border-t border-[#2d333b]">
            <button
              class="px-4 py-2 text-sm font-medium rounded-lg bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d] transition-colors"
              @click="applyingTo = null"
            >
              Cancelar
            </button>
            <button
              class="px-4 py-2 text-sm font-medium rounded-lg bg-[#d29922] hover:bg-[#bb8a1e] text-white transition-colors disabled:opacity-50"
              :disabled="!selectedBoardId || applying"
              @click="handleApply"
            >
              {{ applying ? 'Aplicando...' : 'Aplicar Template' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translate(-50%, 20px);
}
.expand-enter-active,
.expand-leave-active {
  transition: all 0.3s ease;
  overflow: hidden;
}
.expand-enter-from,
.expand-leave-to {
  opacity: 0;
  max-height: 0;
  margin-bottom: 0;
}
.expand-enter-to,
.expand-leave-from {
  opacity: 1;
  max-height: 300px;
}
</style>

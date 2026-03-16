<script setup>
import { ref, computed, onMounted, onUnmounted, provide, watch } from 'vue'
import { useRoute } from 'vue-router'
import draggable from 'vuedraggable'
import { useBoardsStore } from '../stores/boards'
import { useWorkspaceStore } from '../stores/workspace'
import { boardsApi } from '../api/boards'
import { BOARD_COLORS, BOARD_THEMES } from '../lib/constants'
import BoardList from '../components/board/BoardList.vue'
import BoardListView from '../components/board/BoardListView.vue'
import BoardCalendarView from '../components/board/BoardCalendarView.vue'
import BoardViewSwitcher from '../components/board/BoardViewSwitcher.vue'
import CardDetailModal from '../components/board/CardDetailModal.vue'
import ColorPicker from '../components/board/ColorPicker.vue'
import CustomFieldManager from '../components/board/CustomFieldManager.vue'
import AutomationManager from '../components/board/AutomationManager.vue'
import GuidedTour from '../components/shared/GuidedTour.vue'

const route = useRoute()
const boardsStore = useBoardsStore()
const workspaceStore = useWorkspaceStore()

const activeView = ref('kanban')
const selectedCardId = ref(null)
const showBgPanel = ref(false)
const bgUploading = ref(false)
const bgError = ref('')
const pickerColor = ref('#1e3a5f')
let colorDebounce = null

// Filters
const filterText = ref('')
const filterMemberId = ref('')
const filterLabelId = ref('')
const filterDue = ref('')
const showFilters = ref(false)

const cardFilter = computed(() => ({
  text: filterText.value.toLowerCase().trim(),
  memberId: filterMemberId.value,
  labelId: filterLabelId.value,
  due: filterDue.value,
}))
const hasActiveFilter = computed(() => !!(filterText.value || filterMemberId.value || filterLabelId.value || filterDue.value))
provide('cardFilter', cardFilter)

function clearFilters() {
  filterText.value = ''
  filterMemberId.value = ''
  filterLabelId.value = ''
  filterDue.value = ''
}

// Archived
const showArchived = ref(false)
const archivedCards = ref([])
const archivedLists = ref([])
const archivedLoading = ref(false)

async function fetchArchived() {
  archivedLoading.value = true
  try {
    const res = await boardsApi.getArchived(route.params.id)
    archivedCards.value = res.data.data?.cards || []
    archivedLists.value = res.data.data?.lists || []
  } finally {
    archivedLoading.value = false
  }
}

async function restoreCard(cardId) {
  await boardsApi.restoreCard(cardId)
  archivedCards.value = archivedCards.value.filter(c => c.id !== cardId)
  boardsStore.fetchBoard(route.params.id)
}

function openArchived() {
  showArchived.value = true
  fetchArchived()
}

// Theme
const showThemeMenu = ref(false)
const systemPrefersDark = ref(window.matchMedia('(prefers-color-scheme: dark)').matches)
let mediaQuery = null

const boardTheme = computed(() => boardsStore.currentBoard?.theme || 'dark')
const isDark = computed(() => {
  if (boardTheme.value === 'light') return false
  if (boardTheme.value === 'dark') return true
  return systemPrefersDark.value
})
provide('boardIsDark', isDark)

async function changeTheme(theme) {
  showThemeMenu.value = false
  await boardsStore.updateBoard(route.params.id, { theme })
}

const hasCustomBackgrounds = computed(() => {
  return workspaceStore.currentWorkspace?.plan?.features?.includes('custom_backgrounds')
})

const hasCustomFields = computed(() => {
  return workspaceStore.currentWorkspace?.plan?.features?.includes('custom_fields')
})

const hasAutomations = computed(() => {
  return workspaceStore.currentWorkspace?.plan?.features?.includes('automations')
})

const boardBgStyle = computed(() => {
  const board = boardsStore.currentBoard
  if (!board) return { backgroundColor: '#1e3a5f' }
  if (board.background_image) {
    return {
      backgroundImage: `url(${board.background_image})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    }
  }
  return { backgroundColor: board.background_color || '#1e3a5f' }
})
const showAddList = ref(false)
const newListName = ref('')

onMounted(async () => {
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  mediaQuery.addEventListener('change', (e) => { systemPrefersDark.value = e.matches })
  await boardsStore.fetchBoard(route.params.id)
  if (boardsStore.currentBoard?.background_color) {
    pickerColor.value = boardsStore.currentBoard.background_color
  }
  if (workspaceStore.members.length === 0) {
    workspaceStore.fetchMembers()
  }
})

onUnmounted(() => {
  boardsStore.clearBoard()
  if (mediaQuery) mediaQuery.removeEventListener('change', () => {})
})

async function changeBgColor(color) {
  pickerColor.value = color
  await boardsStore.updateBoard(route.params.id, { background_color: color })
}

function onPickerChange(color) {
  // Live preview: update local style immediately
  if (boardsStore.currentBoard) {
    boardsStore.currentBoard.background_color = color
    boardsStore.currentBoard.background_image = null
  }
  // Debounce API call
  clearTimeout(colorDebounce)
  colorDebounce = setTimeout(() => {
    boardsStore.updateBoard(route.params.id, { background_color: color })
  }, 500)
}

async function handleBgImageUpload(e) {
  const file = e.target.files[0]
  if (!file) return
  bgError.value = ''
  bgUploading.value = true
  try {
    const formData = new FormData()
    formData.append('background_image', file)
    await boardsStore.updateBoard(route.params.id, formData)
  } catch (err) {
    bgError.value = err.response?.data?.error?.message || 'Erro ao enviar imagem.'
  } finally {
    bgUploading.value = false
    e.target.value = ''
  }
}

async function handleAddCard(listId, data) {
  await boardsStore.addCard(route.params.id, listId, data)
}

async function handleRenameList(listId, name) {
  await boardsStore.renameList(route.params.id, listId, name)
}

async function handleArchiveList(listId) {
  await boardsStore.archiveList(route.params.id, listId)
}

async function addList() {
  const name = newListName.value.trim()
  if (!name) return
  await boardsStore.addList(route.params.id, name)
  newListName.value = ''
  showAddList.value = false
}

async function handleToggleStar() {
  await boardsStore.toggleStar(route.params.id)
}

function onListsReorder() {
  const positions = boardsStore.sortedLists.map((list, i) => ({
    id: list.id,
    position: (i + 1) * 1000,
  }))
  boardsStore.reorderLists(route.params.id, positions)
}

async function handleCardsUpdated(listId, cards) {
  if (!cards) return
  const positions = cards.map((card, i) => ({
    id: card.id,
    position: (i + 1) * 1000,
  }))
  // Update list_id for moved cards
  for (const card of cards) {
    if (card.list_id !== listId) {
      await boardsStore.moveCard(card.id, listId, positions.find(p => p.id === card.id)?.position || 1000)
      card.list_id = listId
    }
  }
  await boardsStore.reorderCards(route.params.id, listId, positions)
}

function handleCardDetailUpdated() {
  boardsStore.fetchBoard(route.params.id)
}

function handleCardDetailClose() {
  selectedCardId.value = null
}

// Convert vertical scroll to horizontal on kanban view
const tourRef = ref(null)
const tourSteps = [
  { target: '.board-header-area', title: 'Seu Quadro', description: 'Este e o seu quadro de tarefas! Aqui voce organiza todo o fluxo de trabalho da sua equipe de forma visual e intuitiva.', position: 'bottom' },
  { target: '.board-list-column', title: 'Colunas / Listas', description: 'Cada coluna representa um estagio do seu fluxo. Arraste cards entre colunas para atualizar o progresso.', position: 'right' },
  { target: '.board-card-item', title: 'Cards de Tarefas', description: 'Clique em um card para ver detalhes, anexos, checklists, sub-tarefas e comentarios.', position: 'right' },
  { target: '.board-add-card-btn', title: 'Adicionar Tarefas', description: 'Use este botao para criar novas tarefas rapidamente na lista.', position: 'top' },
  { target: '.board-filter-btn', title: 'Filtros', description: 'Use filtros para encontrar cards por membro, etiqueta, prazo ou texto.', position: 'bottom' },
  { target: '.board-view-switcher', title: 'Modos de Visualizacao', description: 'Alterne entre Kanban, Lista e Calendario para ver suas tarefas de diferentes formas.', position: 'top' },
]

const showTemplatePanel = ref(false)
const templateTab = ref('apply') // 'apply' | 'save'
const templateName = ref('')
const templateDesc = ref('')
const savingTemplate = ref(false)
const availableTemplates = ref([])
const loadingTemplates = ref(false)
const applyingTemplate = ref(false)
const confirmApplyTemplate = ref(null)
const toastMessage = ref('')
let toastTimeout = null

function showToast(msg) {
  toastMessage.value = msg
  clearTimeout(toastTimeout)
  toastTimeout = setTimeout(() => { toastMessage.value = '' }, 3000)
}

function openTemplatePanel() {
  showTemplatePanel.value = !showTemplatePanel.value
  if (showTemplatePanel.value) {
    fetchAvailableTemplates()
  }
}

async function fetchAvailableTemplates() {
  loadingTemplates.value = true
  try {
    const res = await boardsApi.listTemplates()
    availableTemplates.value = res.data.data || []
  } catch {
    availableTemplates.value = []
  } finally {
    loadingTemplates.value = false
  }
}

async function handleApplyTemplate() {
  if (!confirmApplyTemplate.value) return
  applyingTemplate.value = true
  try {
    await boardsApi.applyTemplate(confirmApplyTemplate.value.id, route.params.id)
    showTemplatePanel.value = false
    confirmApplyTemplate.value = null
    showToast('Template aplicado com sucesso!')
    await boardsStore.fetchBoard(route.params.id)
  } finally {
    applyingTemplate.value = false
  }
}

async function handleSaveAsTemplate() {
  if (!templateName.value.trim()) return
  savingTemplate.value = true
  try {
    await boardsApi.saveAsTemplate(route.params.id, {
      name: templateName.value.trim(),
      description: templateDesc.value.trim() || null,
    })
    showTemplatePanel.value = false
    templateName.value = ''
    templateDesc.value = ''
    showToast('Template salvo com sucesso!')
  } finally {
    savingTemplate.value = false
  }
}

const kanbanRef = ref(null)
function onKanbanWheel(e) {
  if (!kanbanRef.value) return
  if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
    e.preventDefault()
    kanbanRef.value.scrollLeft += e.deltaY
  }
}
</script>

<template>
  <div
    class="h-full flex flex-col"
    :class="isDark ? '' : 'board-light'"
    :style="boardBgStyle"
  >
    <!-- Board header -->
    <div
      v-if="boardsStore.currentBoard"
      class="board-header-area flex items-center gap-3 px-4 py-3 backdrop-blur-xl relative z-20 border-b"
      :class="isDark ? 'bg-black/20 border-white/[0.06]' : 'bg-white/40 border-black/[0.06]'"
    >
      <h1 class="text-lg font-bold" :class="isDark ? 'text-white' : 'text-gray-900'">{{ boardsStore.currentBoard.name }}</h1>
      <button
        :aria-label="boardsStore.currentBoard.is_starred ? 'Remover dos favoritos' : 'Adicionar aos favoritos'"
        :aria-pressed="boardsStore.currentBoard.is_starred"
        :class="isDark ? 'text-white/70 hover:text-yellow-300' : 'text-gray-500 hover:text-yellow-500'"
        @click="handleToggleStar"
      >
        <svg class="w-5 h-5" :fill="boardsStore.currentBoard.is_starred ? 'currentColor' : 'none'" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
        </svg>
      </button>

      <div class="ml-auto flex items-center gap-2">
        <!-- Tour replay -->
        <button
          class="flex items-center gap-1.5 text-sm px-2 py-1.5 rounded transition-colors"
          :class="isDark ? 'text-white/60 hover:bg-white/10 hover:text-white/90' : 'text-gray-400 hover:bg-black/10 hover:text-gray-700'"
          aria-label="Tutorial do quadro"
          @click="tourRef?.start()"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </button>

        <!-- Filter toggle -->
        <button
          class="board-filter-btn flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
          :class="[
            hasActiveFilter ? 'bg-[#6366f1] text-white' : (isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10')
          ]"
          :aria-expanded="showFilters"
          :aria-label="hasActiveFilter ? 'Filtros (ativos)' : 'Filtrar'"
          @click="showFilters = !showFilters"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" /></svg>
          Filtrar
          <span v-if="hasActiveFilter" class="bg-white/20 text-white text-[10px] rounded-full px-1.5">!</span>
        </button>

        <!-- Archived -->
        <button
          class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
          :class="isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10'"
          @click="openArchived"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
          Arquivados
        </button>

        <!-- Template panel -->
        <div class="relative">
          <button
            class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
            :class="isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10'"
            @click="openTemplatePanel"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg>
            Template
          </button>
          <div
            v-if="showTemplatePanel"
            class="absolute right-0 top-full mt-2 w-80 rounded-lg shadow-xl z-50 border overflow-hidden"
            :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200'"
          >
            <!-- Tabs -->
            <div class="flex border-b" :class="isDark ? 'border-[#2d333b]' : 'border-gray-200'">
              <button
                class="flex-1 text-xs font-medium py-2.5 transition-colors"
                :class="templateTab === 'apply'
                  ? (isDark ? 'text-[#a5b4fc] border-b-2 border-[#6366f1]' : 'text-indigo-600 border-b-2 border-indigo-600')
                  : (isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-500 hover:text-gray-700')"
                @click="templateTab = 'apply'"
              >Aplicar template</button>
              <button
                class="flex-1 text-xs font-medium py-2.5 transition-colors"
                :class="templateTab === 'save'
                  ? (isDark ? 'text-[#a5b4fc] border-b-2 border-[#6366f1]' : 'text-indigo-600 border-b-2 border-indigo-600')
                  : (isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-500 hover:text-gray-700')"
                @click="templateTab = 'save'"
              >Salvar como template</button>
            </div>

            <!-- Apply tab -->
            <div v-if="templateTab === 'apply'" class="p-4">
              <div v-if="loadingTemplates" class="flex justify-center py-4">
                <div class="animate-spin w-5 h-5 border-2 border-[#6366f1] border-t-transparent rounded-full" />
              </div>
              <div v-else-if="availableTemplates.length === 0" class="text-center py-4">
                <p class="text-xs mb-1" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Nenhum template criado ainda</p>
                <p class="text-[10px]" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Crie templates na aba "Salvar como template" ou na pagina Templates</p>
              </div>
              <div v-else class="space-y-1.5 max-h-52 overflow-y-auto">
                <!-- Confirmation view -->
                <template v-if="confirmApplyTemplate">
                  <div class="flex items-start gap-2 p-2.5 rounded-lg mb-2" :class="isDark ? 'bg-[#d29922]/10 border border-[#d29922]/30' : 'bg-amber-50 border border-amber-200'">
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5" :class="isDark ? 'text-[#d29922]' : 'text-amber-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <div>
                      <p class="text-xs font-medium" :class="isDark ? 'text-[#d29922]' : 'text-amber-700'">Substituir estrutura do quadro?</p>
                      <p class="text-[10px] mt-0.5" :class="isDark ? 'text-[#d29922]/80' : 'text-amber-600'">As listas, etiquetas, campos e automacoes atuais serao substituidos pelo template "{{ confirmApplyTemplate.name }}"</p>
                    </div>
                  </div>
                  <!-- Preview -->
                  <div class="flex gap-1 flex-wrap mb-2">
                    <span
                      v-for="(list, i) in confirmApplyTemplate.lists"
                      :key="i"
                      class="px-1.5 py-0.5 rounded text-[10px] font-medium"
                      :class="isDark ? 'bg-[#2d333b] text-[#e6edf3]' : 'bg-gray-100 text-gray-700'"
                    >{{ list.name }}</span>
                  </div>
                  <div class="flex gap-2">
                    <button
                      class="flex-1 text-xs py-1.5 rounded-lg transition-colors font-medium"
                      :class="isDark ? 'bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d]' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                      @click="confirmApplyTemplate = null"
                    >Cancelar</button>
                    <button
                      class="flex-1 text-xs py-1.5 rounded-lg bg-[#d29922] hover:bg-[#bb8a1e] text-white transition-colors font-medium disabled:opacity-50"
                      :disabled="applyingTemplate"
                      @click="handleApplyTemplate"
                    >{{ applyingTemplate ? 'Aplicando...' : 'Confirmar' }}</button>
                  </div>
                </template>
                <!-- Template list -->
                <template v-else>
                  <button
                    v-for="tpl in availableTemplates"
                    :key="tpl.id"
                    class="w-full text-left p-2.5 rounded-lg border transition-colors group"
                    :class="isDark
                      ? 'border-[#2d333b] hover:border-[#444c56] hover:bg-[#1c2128]'
                      : 'border-gray-100 hover:border-gray-300 hover:bg-gray-50'"
                    @click="confirmApplyTemplate = tpl"
                  >
                    <div class="flex items-center gap-1.5">
                      <p class="text-xs font-medium flex-1" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ tpl.name }}</p>
                      <span v-if="tpl.custom_fields?.length" class="text-[8px] px-1 rounded" :class="isDark ? 'bg-[#2d333b] text-[#6e7681]' : 'bg-gray-100 text-gray-400'">{{ tpl.custom_fields.length }}C</span>
                      <span v-if="tpl.automations?.length" class="text-[8px] px-1 rounded" :class="isDark ? 'bg-[#6366f1]/10 text-[#a5b4fc]' : 'bg-indigo-50 text-indigo-400'">{{ tpl.automations.length }}A</span>
                    </div>
                    <div class="flex gap-1 mt-1 flex-wrap">
                      <span
                        v-for="(list, i) in tpl.lists?.slice(0, 5)"
                        :key="i"
                        class="px-1 py-0.5 rounded text-[9px]"
                        :class="isDark ? 'bg-[#2d333b] text-[#8b949e]' : 'bg-gray-100 text-gray-500'"
                      >{{ list.name }}</span>
                      <span v-if="tpl.lists?.length > 5" class="text-[9px] self-center" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">+{{ tpl.lists.length - 5 }}</span>
                    </div>
                  </button>
                </template>
              </div>
            </div>

            <!-- Save tab -->
            <div v-if="templateTab === 'save'" class="p-4">
              <input
                v-model="templateName"
                class="w-full text-sm rounded-lg px-3 py-2 border mb-2 focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
                placeholder="Nome do template..."
              />
              <textarea
                v-model="templateDesc"
                class="w-full text-sm rounded-lg px-3 py-2 border mb-2 resize-none focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
                rows="2"
                placeholder="Descricao opcional..."
              />
              <p class="text-[10px] mb-3" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Salva a estrutura completa deste quadro: listas, etiquetas, campos customizados e automacoes.</p>
              <div class="flex justify-end gap-2">
                <button class="text-xs px-3 py-1.5 rounded-lg transition-colors" :class="isDark ? 'text-[#8b949e] hover:bg-[#2d333b]' : 'text-gray-500 hover:bg-gray-100'" @click="showTemplatePanel = false">Cancelar</button>
                <button
                  class="text-xs px-3 py-1.5 rounded-lg bg-[#6366f1] text-white hover:bg-[#5558e6] transition-colors font-medium disabled:opacity-50"
                  :disabled="!templateName.trim() || savingTemplate"
                  @click="handleSaveAsTemplate"
                >
                  {{ savingTemplate ? 'Salvando...' : 'Salvar' }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <CustomFieldManager
          :board-id="route.params.id"
          :custom-fields="boardsStore.currentBoard?.custom_fields || []"
          :has-feature="hasCustomFields"
          @updated="boardsStore.fetchBoard(route.params.id)"
        />

        <AutomationManager
          :board-id="route.params.id"
          :has-feature="hasAutomations"
        />

        <!-- Theme selector -->
        <div class="relative">
          <button
            class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
            :class="isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10'"
            @click="showThemeMenu = !showThemeMenu; showBgPanel = false"
          >
            <svg v-if="boardTheme === 'light'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
            <svg v-else-if="boardTheme === 'dark'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
            Tema
          </button>

          <div
            v-if="showThemeMenu"
            class="absolute right-0 top-full mt-2 w-56 rounded-lg shadow-xl z-50 border"
            :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200'"
          >
            <div class="flex items-center justify-between px-4 py-3 border-b" :class="isDark ? 'border-[#444c56]' : 'border-gray-200'">
              <span class="font-semibold text-sm" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">Tema do quadro</span>
              <button :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-400 hover:text-gray-600'" class="text-lg" @click="showThemeMenu = false">&times;</button>
            </div>
            <div class="p-2">
              <button
                v-for="t in BOARD_THEMES"
                :key="t.value"
                class="flex items-center gap-3 w-full px-3 py-2.5 rounded-md text-sm transition-colors"
                :class="[
                  boardTheme === t.value
                    ? (isDark ? 'bg-[#6366f1]/20 text-[#a5b4fc]' : 'bg-indigo-50 text-indigo-700')
                    : (isDark ? 'text-[#e6edf3] hover:bg-[#2d333b]' : 'text-gray-700 hover:bg-gray-100')
                ]"
                @click="changeTheme(t.value)"
              >
                <svg v-if="t.value === 'light'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                <svg v-else-if="t.value === 'dark'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>
                <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                {{ t.label }}
                <svg v-if="boardTheme === t.value" class="w-4 h-4 ml-auto text-[#6366f1]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>
              </button>
            </div>
          </div>
        </div>

        <div class="relative">
        <button
          class="flex items-center gap-1.5 text-sm px-3 py-1.5 rounded transition-colors"
          :class="isDark ? 'text-white/80 hover:bg-white/10' : 'text-gray-700 hover:bg-black/10'"
          @click="showBgPanel = !showBgPanel; showThemeMenu = false"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Mudar fundo
        </button>

        <!-- Background panel -->
        <div
          v-if="showBgPanel"
          class="absolute right-0 top-full mt-2 w-80 rounded-lg shadow-xl z-50 border"
          :class="isDark ? 'bg-[#161b22] border-[#444c56] shadow-black/30' : 'bg-white border-gray-200 shadow-gray-300/30'"
        >
          <div class="flex items-center justify-between px-4 py-3 border-b" :class="isDark ? 'border-[#444c56]' : 'border-gray-200'">
            <span class="font-semibold text-sm" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">Fundo do quadro</span>
            <button :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-400 hover:text-gray-600'" class="text-lg" @click="showBgPanel = false">&times;</button>
          </div>

          <div class="p-4 space-y-4">
            <!-- Color picker -->
            <div>
              <h4 class="text-xs font-semibold text-[#8b949e] uppercase mb-2">Cor</h4>
              <ColorPicker :model-value="pickerColor" @update:model-value="onPickerChange" />
            </div>

            <!-- Quick colors -->
            <div>
              <h4 class="text-xs font-semibold text-[#8b949e] uppercase mb-2">Cores rapidas</h4>
              <div class="flex gap-1.5 flex-wrap">
                <button
                  v-for="color in BOARD_COLORS"
                  :key="color"
                  class="w-8 h-8 rounded-md transition-all hover:scale-110 relative"
                  :class="boardsStore.currentBoard?.background_color === color && !boardsStore.currentBoard?.background_image ? 'ring-2 ring-[#6366f1] ring-offset-1 ring-offset-[#161b22]' : ''"
                  :style="{ backgroundColor: color }"
                  @click="changeBgColor(color)"
                />
              </div>
            </div>

            <!-- Image upload -->
            <div>
              <h4 class="text-xs font-semibold text-[#8b949e] uppercase mb-2">Imagem</h4>
              <div v-if="!hasCustomBackgrounds" class="bg-[#2d333b] border border-[#444c56] rounded-lg p-3 text-center">
                <svg class="w-5 h-5 text-[#6e7681] mx-auto mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <p class="text-xs text-[#8b949e]">Disponivel no plano Starter+</p>
                <router-link to="/plans" class="text-xs text-[#6366f1] hover:underline">Ver planos</router-link>
              </div>
              <template v-else>
                <label class="flex items-center justify-center gap-2 w-full py-2.5 border-2 border-dashed border-[#444c56] rounded-lg text-sm text-[#8b949e] hover:border-[#6366f1] hover:text-[#a5b4fc] cursor-pointer transition-colors">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                  </svg>
                  {{ bgUploading ? 'Enviando...' : 'Carregar imagem' }}
                  <input type="file" accept="image/*" class="hidden" :disabled="bgUploading" @change="handleBgImageUpload" />
                </label>
                <p v-if="bgError" class="text-xs text-[#f85149] mt-1">{{ bgError }}</p>
              </template>
            </div>
          </div>
        </div>
        </div>
      </div>
    </div>

    <!-- Filter bar -->
    <div
      v-if="showFilters"
      class="flex items-center gap-3 px-4 py-2 border-b backdrop-blur-xl"
      :class="isDark ? 'bg-black/20 border-white/[0.06]' : 'bg-white/40 border-black/[0.06]'"
    >
      <!-- Text search -->
      <div class="relative flex-1 max-w-xs">
        <svg class="w-4 h-4 absolute left-2.5 top-1/2 -translate-y-1/2" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        <label for="filter-search" class="sr-only">Buscar cards</label>
        <input
          id="filter-search"
          v-model="filterText"
          class="w-full text-sm pl-8 pr-3 py-1.5 rounded-lg border"
          :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-300 text-gray-900 placeholder-gray-400'"
          placeholder="Buscar cards..."
        />
      </div>

      <!-- Member filter -->
      <select
        v-model="filterMemberId"
        aria-label="Filtrar por membro"
        class="text-sm py-1.5 px-2 rounded-lg border appearance-none cursor-pointer"
        :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3]' : 'bg-white border-gray-300 text-gray-900'"
      >
        <option value="">Todos os membros</option>
        <option v-for="m in workspaceStore.members" :key="m.id" :value="m.id">{{ m.name }}</option>
      </select>

      <!-- Label filter -->
      <select
        v-model="filterLabelId"
        aria-label="Filtrar por etiqueta"
        class="text-sm py-1.5 px-2 rounded-lg border appearance-none cursor-pointer"
        :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3]' : 'bg-white border-gray-300 text-gray-900'"
      >
        <option value="">Todas as etiquetas</option>
        <option v-for="l in boardsStore.currentBoard?.labels" :key="l.id" :value="l.id">{{ l.name || l.color }}</option>
      </select>

      <!-- Due filter -->
      <select
        v-model="filterDue"
        aria-label="Filtrar por prazo"
        class="text-sm py-1.5 px-2 rounded-lg border appearance-none cursor-pointer"
        :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3]' : 'bg-white border-gray-300 text-gray-900'"
      >
        <option value="">Qualquer prazo</option>
        <option value="overdue">Atrasados</option>
        <option value="today">Vence hoje</option>
        <option value="week">Proximos 7 dias</option>
        <option value="none">Sem prazo</option>
      </select>

      <!-- Clear -->
      <button
        v-if="hasActiveFilter"
        class="text-xs px-2 py-1 rounded transition-colors"
        :class="isDark ? 'text-[#f85149] hover:bg-[#f8514926]' : 'text-red-500 hover:bg-red-50'"
        @click="clearFilters"
      >Limpar</button>
    </div>

    <!-- Loading -->
    <div v-if="boardsStore.loading" class="flex-1 flex items-center justify-center" role="status" aria-label="Carregando quadro">
      <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" aria-hidden="true" />
      <span class="sr-only">Carregando quadro...</span>
    </div>

    <!-- Kanban view -->
    <main id="main-content" v-else-if="activeView === 'kanban'" ref="kanbanRef" class="flex-1 overflow-x-auto p-4" role="region" aria-label="Quadro Kanban" @wheel="onKanbanWheel">
      <div class="flex gap-4 items-start h-full">
        <draggable
          :list="boardsStore.currentBoard?.lists || []"
          item-key="id"
          class="flex gap-4 items-start"
          ghost-class="opacity-30"
          handle=".cursor-pointer"
          direction="horizontal"
          @change="onListsReorder"
        >
          <template #item="{ element }">
            <BoardList
              v-if="!element.is_archived"
              :list="element"
              :board-id="route.params.id"
              @card-click="(card) => selectedCardId = card.id"
              @add-card="handleAddCard"
              @rename="handleRenameList"
              @archive="handleArchiveList"
              @cards-updated="handleCardsUpdated"
            />
          </template>
        </draggable>

        <!-- Add list -->
        <div class="w-72 flex-shrink-0">
          <div v-if="showAddList" class="rounded-2xl p-3 backdrop-blur-xl border" :class="isDark ? 'bg-white/[0.06] border-white/[0.08]' : 'bg-white/60 border-black/[0.06] shadow-sm'">
            <input
              v-model="newListName"
              class="monday-input text-sm mb-2"
              placeholder="Nome da lista..."
              @keyup.enter="addList"
            />
            <div class="flex items-center gap-2">
              <button class="monday-btn monday-btn-primary text-xs !py-1.5" @click="addList">Adicionar lista</button>
              <button :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-400 hover:text-gray-600'" class="text-lg" @click="showAddList = false">&times;</button>
            </div>
          </div>
          <button
            v-else
            class="w-full rounded-xl px-4 py-3 text-sm font-medium text-left transition-colors"
            :class="isDark ? 'bg-white/10 hover:bg-white/20 text-white/80' : 'bg-black/5 hover:bg-black/10 text-gray-700'"
            @click="showAddList = true"
          >
            + Adicionar outra lista
          </button>
        </div>
      </div>
    </main>

    <!-- List view -->
    <BoardListView
      v-else-if="activeView === 'list'"
      :board="boardsStore.currentBoard"
      @card-click="(card) => selectedCardId = card.id"
    />

    <!-- Calendar view -->
    <BoardCalendarView
      v-else-if="activeView === 'calendar'"
      :board="boardsStore.currentBoard"
      @card-click="(card) => selectedCardId = card.id"
    />

    <!-- Floating view switcher -->
    <div class="board-view-switcher absolute bottom-14 left-1/2 -translate-x-1/2 z-30 pointer-events-none">
      <BoardViewSwitcher v-model="activeView" class="shadow-lg pointer-events-auto" />
    </div>

    <!-- Guided Tour -->
    <GuidedTour ref="tourRef" :steps="tourSteps" storage-key="zentra_tour_board_done" />

    <!-- Card detail modal -->
    <CardDetailModal
      v-if="selectedCardId"
      :card-id="selectedCardId"
      @close="handleCardDetailClose"
      @updated="handleCardDetailUpdated"
    />

    <!-- Archived panel -->
    <Teleport to="body">
      <div v-if="showArchived" class="fixed inset-0 z-50 flex justify-end">
        <div class="fixed inset-0 bg-black/40" @click="showArchived = false" />
        <div class="relative w-96 h-full shadow-2xl border-l overflow-y-auto" :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200'">
          <div class="flex items-center justify-between px-4 py-3 border-b sticky top-0 z-10" :class="isDark ? 'border-[#444c56] bg-[#161b22]' : 'border-gray-200 bg-white'">
            <span class="font-semibold text-sm" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">Itens arquivados</span>
            <button :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-400 hover:text-gray-600'" class="text-lg" @click="showArchived = false">&times;</button>
          </div>

          <div v-if="archivedLoading" class="py-12 text-center">
            <div class="animate-spin w-6 h-6 border-2 border-[#6366f1] border-t-transparent rounded-full mx-auto" />
          </div>

          <div v-else-if="archivedCards.length === 0 && archivedLists.length === 0" class="py-12 text-center">
            <svg class="w-10 h-10 mx-auto mb-2" :class="isDark ? 'text-[#6e7681]' : 'text-gray-300'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>
            <p class="text-sm" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Nenhum item arquivado</p>
          </div>

          <div v-else class="p-3 space-y-2">
            <!-- Archived cards -->
            <div v-if="archivedCards.length">
              <p class="text-xs font-semibold uppercase mb-2 px-1" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Cards ({{ archivedCards.length }})</p>
              <div
                v-for="card in archivedCards"
                :key="card.id"
                class="p-3 rounded-lg border space-y-2"
                :class="isDark ? 'bg-[#1c2128] border-[#444c56]' : 'bg-gray-50 border-gray-200'"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1 min-w-0 mr-3">
                    <p class="text-sm font-medium truncate" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ card.title }}</p>
                    <p class="text-xs" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">{{ card.list?.name || 'Lista removida' }}</p>
                  </div>
                  <button
                    class="text-xs px-2.5 py-1 rounded-md transition-colors flex-shrink-0"
                    :class="isDark ? 'bg-[#6366f1]/20 text-[#a5b4fc] hover:bg-[#6366f1]/30' : 'bg-indigo-50 text-indigo-600 hover:bg-purple-100'"
                    @click="restoreCard(card.id)"
                  >Restaurar</button>
                </div>

                <!-- Archive reason -->
                <p v-if="card.archive_reason"
                  class="text-xs italic"
                  :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
                  "{{ card.archive_reason }}"
                </p>

                <!-- Scheduled unarchive info -->
                <div v-if="card.unarchive_at"
                  class="flex items-center gap-1.5 text-xs flex-wrap"
                  :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">
                  <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <span>
                    Retorna em {{ new Date(card.unarchive_at).toLocaleDateString('pt-BR', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                  </span>
                  <span v-if="card.unarchive_list">
                    na lista <strong :class="isDark ? 'text-[#e6edf3]' : 'text-gray-700'">{{ card.unarchive_list.name }}</strong>
                  </span>
                </div>
              </div>
            </div>

            <!-- Archived lists -->
            <div v-if="archivedLists.length">
              <p class="text-xs font-semibold uppercase mb-2 px-1 mt-4" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Listas ({{ archivedLists.length }})</p>
              <div
                v-for="list in archivedLists"
                :key="list.id"
                class="flex items-center justify-between p-3 rounded-lg border"
                :class="isDark ? 'bg-[#1c2128] border-[#444c56]' : 'bg-gray-50 border-gray-200'"
              >
                <p class="text-sm font-medium" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ list.name }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- Success toast -->
    <Teleport to="body">
      <Transition name="toast-slide">
        <div
          v-if="toastMessage"
          class="fixed bottom-6 left-1/2 -translate-x-1/2 z-[200] px-5 py-3 rounded-xl shadow-2xl bg-[#3fb950] text-white text-sm font-medium flex items-center gap-2"
        >
          <svg class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
          {{ toastMessage }}
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<style scoped>
.toast-slide-enter-active,
.toast-slide-leave-active {
  transition: all 0.3s ease;
}
.toast-slide-enter-from,
.toast-slide-leave-to {
  opacity: 0;
  transform: translate(-50%, 20px);
}
</style>

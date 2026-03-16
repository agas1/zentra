<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useBoardsStore } from '../stores/boards'
import { useWorkspaceStore } from '../stores/workspace'
import { usePlanLimits } from '../composables/usePlanLimits'
import { BOARD_COLORS } from '../lib/constants'
import { boardsApi } from '../api/boards'
import AppModal from '../components/shared/AppModal.vue'

const router = useRouter()
const boardsStore = useBoardsStore()
const workspaceStore = useWorkspaceStore()
const { canCreateBoard, boardsUsed, boardsLimit, boardsUnlimited, hasCustomBackgrounds } = usePlanLimits()

const showCreate = ref(false)
const defaultTemplate = computed(() => {
  const p = workspaceStore.persona
  if (p === 'agency') return 'agency_pipeline'
  if (p === 'freelancer') return 'freelancer_simple'
  if (p === 'client') return 'client_portal'
  return 'design_pipeline'
})
const form = ref({ name: '', background_color: BOARD_COLORS[0], template: 'design_pipeline', client_name: '' })
const bgImageFile = ref(null)
const bgImagePreview = ref(null)
const bgType = ref('color') // 'color' or 'image'
const creating = ref(false)
const clientFilter = ref('')
const customTemplates = ref([])
const selectedCustomTemplateId = ref('')

const isAgency = computed(() => workspaceStore.isAgency)

const builtinTemplates = [
  { value: 'agency_pipeline', label: 'Agencia', count: 6, lists: ['Briefing', 'Producao', 'Rev. Interna', 'Rev. Cliente', 'Aprovado', 'Entregue'] },
  { value: 'design_pipeline', label: 'Design', count: 5, lists: ['Briefing', 'Em Criacao', 'Rev. Cliente', 'Aprovado', 'Entregue'] },
  { value: 'freelancer_simple', label: 'Simples', count: 4, lists: ['A Fazer', 'Em Progresso', 'Em Revisao', 'Concluido'] },
  { value: 'client_portal', label: 'Portal Cliente', count: 4, lists: ['Solicitacoes', 'Em Andamento', 'Para Revisao', 'Aprovado'] },
  { value: 'default', label: 'Basico', count: 3, lists: ['A Fazer', 'Em Progresso', 'Concluido'] },
]
const selectedTemplate = computed(() => {
  if (selectedCustomTemplateId.value) {
    const ct = customTemplates.value.find(t => t.id === selectedCustomTemplateId.value)
    if (ct) return { value: 'custom', label: ct.name, count: ct.lists?.length || 0, lists: ct.lists?.map(l => l.name) || [] }
  }
  return builtinTemplates.find(t => t.value === form.value.template)
})

function selectBuiltinTemplate(value) {
  form.value.template = value
  selectedCustomTemplateId.value = ''
}

function selectCustomTemplate(templateId) {
  selectedCustomTemplateId.value = templateId
  form.value.template = ''
}

async function fetchCustomTemplates() {
  try {
    const res = await boardsApi.listTemplates()
    customTemplates.value = res.data.data || []
  } catch {
    customTemplates.value = []
  }
}

// Group boards by client_name for agency
const clientGroups = computed(() => {
  if (!isAgency.value) return []
  const groups = {}
  const boards = boardsStore.boards.filter(b => !b.is_starred)
  for (const board of boards) {
    const key = board.client_name || 'Sem cliente'
    if (!groups[key]) groups[key] = []
    groups[key].push(board)
  }
  let entries = Object.entries(groups).map(([name, boards]) => ({ name, boards }))
  if (clientFilter.value) {
    entries = entries.filter(g => g.name.toLowerCase().includes(clientFilter.value.toLowerCase()))
  }
  return entries.sort((a, b) => {
    if (a.name === 'Sem cliente') return 1
    if (b.name === 'Sem cliente') return -1
    return a.name.localeCompare(b.name)
  })
})

onMounted(() => {
  boardsStore.fetchBoards()
  workspaceStore.fetchPlanUsage()
  fetchCustomTemplates()
  form.value.template = defaultTemplate.value
})

function handleCreateClick() {
  if (!canCreateBoard.value) return
  showCreate.value = true
}

function handleBgImageSelect(e) {
  const file = e.target.files[0]
  if (!file) return
  bgImageFile.value = file
  bgImagePreview.value = URL.createObjectURL(file)
  bgType.value = 'image'
}

function clearBgImage() {
  bgImageFile.value = null
  bgImagePreview.value = null
  bgType.value = 'color'
}

async function createBoard() {
  if (!form.value.name.trim()) return
  creating.value = true
  try {
    let payload
    const templateToUse = selectedCustomTemplateId.value ? 'default' : (form.value.template || 'default')

    if (bgType.value === 'image' && bgImageFile.value) {
      payload = new FormData()
      payload.append('name', form.value.name)
      if (form.value.client_name) payload.append('client_name', form.value.client_name)
      payload.append('background_color', form.value.background_color)
      payload.append('background_image', bgImageFile.value)
      payload.append('template', templateToUse)
    } else {
      payload = { ...form.value, template: templateToUse }
      if (!payload.client_name) delete payload.client_name
    }
    const board = await boardsStore.createBoard(payload)

    // If custom template selected, apply it after board creation
    if (selectedCustomTemplateId.value) {
      await boardsApi.applyTemplate(selectedCustomTemplateId.value, board.id)
    }

    showCreate.value = false
    form.value = { name: '', background_color: BOARD_COLORS[0], template: defaultTemplate.value, client_name: '' }
    selectedCustomTemplateId.value = ''
    clearBgImage()
    router.push(`/boards/${board.id}`)
  } finally {
    creating.value = false
  }
}

function openBoard(board) {
  router.push(`/boards/${board.id}`)
}

async function toggleStar(e, boardId) {
  e.stopPropagation()
  await boardsStore.toggleStar(boardId)
}

function boardBgStyle(board) {
  if (board.background_image) {
    return {
      backgroundImage: `url(${board.background_image})`,
      backgroundSize: 'cover',
      backgroundPosition: 'center',
    }
  }
  return { backgroundColor: board.background_color || '#1e3a5f' }
}
</script>

<template>
  <div>
    <div class="flex items-center justify-between mb-8 animate-fade-in-down">
      <h1 class="text-3xl font-bold text-white">Meus Quadros</h1>
    </div>

    <!-- Loading -->
    <div v-if="boardsStore.loading" class="flex justify-center py-12">
      <div class="animate-spin w-8 h-8 border-2 border-[#818cf8] border-t-transparent rounded-full" />
    </div>

    <!-- Starred boards -->
    <div v-else>
      <div v-if="boardsStore.boards.some(b => b.is_starred)" class="mb-8">
        <h2 class="flex items-center gap-2 text-base font-semibold text-white/45 mb-4">
          <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
          Favoritos
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
          <div
            v-for="(board, idx) in boardsStore.boards.filter(b => b.is_starred)"
            :key="'star-' + board.id"
            class="h-24 rounded-2xl cursor-pointer relative group overflow-hidden transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-black/30 animate-fade-in-up ring-1 ring-white/[0.08] hover:ring-white/[0.15]"
            :style="{ ...boardBgStyle(board), animationDelay: `${idx * 0.07}s` }"
            @click="openBoard(board)"
          >
            <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 backdrop-blur-[1px] transition-colors" />
            <div class="relative p-3 h-full flex flex-col justify-between">
              <h3 class="text-white font-bold text-base truncate drop-shadow-sm">{{ board.name }}</h3>
              <div class="flex justify-end">
                <button class="text-amber-400 hover:scale-125 transition-transform" @click="toggleStar($event, board.id)">
                  <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Agency: Grouped by client -->
      <template v-if="isAgency">
        <!-- Client filter -->
        <div class="flex items-center gap-3 mb-4">
          <input
            v-model="clientFilter"
            type="text"
            class="h-9 px-3 rounded-lg border border-[#444c56] bg-[#0d1117] text-sm text-[#e6edf3] placeholder-[#545d68] focus:outline-none focus:border-[#388bfd] w-64"
            placeholder="Filtrar por cliente..."
          />
        </div>
        <div v-for="group in clientGroups" :key="group.name" class="mb-6">
          <h2 class="flex items-center gap-2 text-base font-semibold text-white/45 mb-4">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            {{ group.name }}
            <span class="text-sm text-white/25 bg-white/[0.06] px-2.5 py-0.5 rounded-full">{{ group.boards.length }}</span>
          </h2>
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
            <div
              v-for="(board, idx) in group.boards"
              :key="board.id"
              class="h-24 rounded-2xl cursor-pointer relative group overflow-hidden transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-black/30 animate-fade-in-up ring-1 ring-white/[0.08] hover:ring-white/[0.15]"
              :style="{ ...boardBgStyle(board), animationDelay: `${idx * 0.07}s` }"
              @click="openBoard(board)"
            >
              <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 backdrop-blur-[1px] transition-colors" />
              <div class="relative p-3 h-full flex flex-col justify-between">
                <h3 class="text-white font-bold text-base truncate drop-shadow-sm">{{ board.name }}</h3>
                <div class="flex justify-end">
                  <button class="text-white/60 hover:text-yellow-400 transition-all duration-200 hover:scale-125" @click="toggleStar($event, board.id)">
                    <svg class="w-4 h-4" :fill="board.is_starred ? 'currentColor' : 'none'" :class="board.is_starred ? 'text-yellow-400' : ''" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Non-agency: Flat list -->
      <template v-else>
        <h2 class="text-base font-semibold text-white/45 mb-4 animate-fade-in">Todos os quadros</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-5">
          <div
            v-for="(board, idx) in boardsStore.boards"
            :key="board.id"
            class="h-24 rounded-2xl cursor-pointer relative group overflow-hidden transition-all duration-300 hover:scale-[1.02] hover:shadow-xl hover:shadow-black/30 animate-fade-in-up ring-1 ring-white/[0.08] hover:ring-white/[0.15]"
            :style="{ ...boardBgStyle(board), animationDelay: `${idx * 0.07}s` }"
            @click="openBoard(board)"
          >
            <div class="absolute inset-0 bg-black/20 group-hover:bg-black/30 backdrop-blur-[1px] transition-colors" />
            <div class="relative p-3 h-full flex flex-col justify-between">
              <h3 class="text-white font-bold text-base truncate drop-shadow-sm">{{ board.name }}</h3>
              <div class="flex justify-end">
                <button class="text-white/60 hover:text-yellow-400 transition-all duration-200 hover:scale-125" @click="toggleStar($event, board.id)">
                  <svg class="w-4 h-4" :fill="board.is_starred ? 'currentColor' : 'none'" :class="board.is_starred ? 'text-yellow-400' : ''" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </template>

      <!-- Create board tile (both views) -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
        <!-- Create board tile -->
        <div
          class="h-24 rounded-2xl glass flex flex-col items-center justify-center transition-all duration-300 animate-fade-in-up"
          :class="canCreateBoard
            ? 'hover-glass cursor-pointer hover:scale-[1.02]'
            : 'opacity-60 cursor-not-allowed'"
          :style="{ animationDelay: `${boardsStore.boards.length * 0.07}s` }"
          @click="handleCreateClick"
        >
          <template v-if="canCreateBoard">
            <span class="text-base font-medium text-white/45">+ Criar novo quadro</span>
            <span v-if="!boardsUnlimited" class="text-xs text-white/25 mt-1">{{ boardsUsed }}/{{ boardsLimit }} quadros</span>
          </template>
          <template v-else>
            <svg class="w-5 h-5 text-white/25 mb-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <span class="text-xs text-white/45">Limite de {{ boardsLimit }} quadros atingido</span>
            <router-link to="/plans" class="text-[10px] text-[#818cf8] hover:underline mt-1">Fazer upgrade</router-link>
          </template>
        </div>
      </div>

    </div>

    <!-- Create board modal -->
    <AppModal :show="showCreate" title="Criar Quadro" @close="showCreate = false">
      <form @submit.prevent="createBoard" class="space-y-4">
        <div>
          <label class="monday-label">Nome do quadro</label>
          <input v-model="form.name" class="monday-input" placeholder="Ex: Projeto Branding 2026" required />
        </div>

        <!-- Client name (agency only) -->
        <div v-if="isAgency">
          <label class="monday-label">Cliente (opcional)</label>
          <input v-model="form.client_name" class="monday-input" placeholder="Ex: Empresa ABC" />
        </div>

        <!-- Background type toggle -->
        <div>
          <label class="monday-label">Fundo do quadro</label>
          <div class="flex gap-2 mb-3">
            <button
              type="button"
              class="px-3 py-1.5 text-sm rounded-md border transition-colors"
              :class="bgType === 'color' ? 'bg-[#6366f1]/15 border-[#6366f1] text-[#a5b4fc]' : 'border-[#444c56] text-[#8b949e] hover:bg-[#2d333b]'"
              @click="bgType = 'color'; clearBgImage()"
            >
              Cores
            </button>
            <button
              v-if="hasCustomBackgrounds"
              type="button"
              class="px-3 py-1.5 text-sm rounded-md border transition-colors"
              :class="bgType === 'image' ? 'bg-[#6366f1]/15 border-[#6366f1] text-[#a5b4fc]' : 'border-[#444c56] text-[#8b949e] hover:bg-[#2d333b]'"
              @click="bgType = 'image'"
            >
              Imagem
            </button>
            <span v-else class="flex items-center gap-1 px-3 py-1.5 text-sm text-[#6e7681]">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              Imagem (Starter+)
            </span>
          </div>

          <!-- Color picker -->
          <div v-if="bgType === 'color'" class="flex gap-2 flex-wrap">
            <button
              v-for="color in BOARD_COLORS"
              :key="color"
              type="button"
              class="w-12 h-8 rounded transition-transform"
              :class="form.background_color === color ? 'ring-2 ring-offset-2 ring-offset-[#161b22] ring-[#6366f1] scale-110' : 'hover:scale-105'"
              :style="{ backgroundColor: color }"
              @click="form.background_color = color"
            />
          </div>

          <!-- Image upload -->
          <div v-else>
            <div v-if="bgImagePreview" class="relative mb-2">
              <img :src="bgImagePreview" class="w-full h-28 object-cover rounded-lg" />
              <button type="button" class="absolute top-1 right-1 w-6 h-6 bg-black/50 hover:bg-black/70 rounded-full text-white flex items-center justify-center text-xs" @click="clearBgImage">
                &times;
              </button>
            </div>
            <label class="monday-btn monday-btn-secondary cursor-pointer inline-flex text-sm">
              <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
              Escolher imagem
              <input type="file" accept="image/*" class="hidden" @change="handleBgImageSelect" />
            </label>
          </div>
        </div>

        <div>
          <label class="monday-label">Template</label>
          <!-- Built-in templates -->
          <div class="flex flex-wrap gap-1.5">
            <button
              v-for="tpl in builtinTemplates"
              :key="tpl.value"
              type="button"
              @click="selectBuiltinTemplate(tpl.value)"
              class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all duration-150"
              :class="form.template === tpl.value && !selectedCustomTemplateId
                ? 'border-[#6366f1] bg-[#6366f1]/15 text-[#a5b4fc]'
                : 'border-[#444c56] text-[#8b949e] hover:border-[#6e7681] hover:bg-[#161b22]'"
            >{{ tpl.label }} <span class="text-[10px] opacity-60">{{ tpl.count }}</span></button>
          </div>
          <!-- Custom templates -->
          <div v-if="customTemplates.length" class="mt-2">
            <span class="text-[10px] uppercase font-semibold text-[#6e7681] tracking-wider">Meus Templates</span>
            <div class="flex flex-wrap gap-1.5 mt-1">
              <button
                v-for="ct in customTemplates"
                :key="ct.id"
                type="button"
                @click="selectCustomTemplate(ct.id)"
                class="px-3 py-1.5 text-xs font-medium rounded-lg border transition-all duration-150"
                :class="selectedCustomTemplateId === ct.id
                  ? 'border-[#6366f1] bg-[#6366f1]/15 text-[#a5b4fc]'
                  : 'border-[#444c56] text-[#8b949e] hover:border-[#6e7681] hover:bg-[#161b22]'"
              >{{ ct.name }} <span class="text-[10px] opacity-60">{{ ct.lists?.length || 0 }}L<template v-if="ct.custom_fields?.length"> {{ ct.custom_fields.length }}C</template><template v-if="ct.automations?.length"> {{ ct.automations.length }}A</template></span></button>
            </div>
          </div>
          <!-- Template preview -->
          <div v-if="selectedTemplate" class="flex items-center gap-1 mt-2 overflow-x-auto">
            <template v-for="(list, i) in selectedTemplate.lists" :key="list">
              <span class="text-[10px] px-2 py-0.5 rounded bg-[#21262d] text-[#8b949e] whitespace-nowrap">{{ list }}</span>
              <svg v-if="i < selectedTemplate.lists.length - 1" class="w-3 h-3 text-[#444c56] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </template>
          </div>
        </div>

        <!-- Preview -->
        <div>
          <label class="monday-label">Preview</label>
          <div
            class="h-20 rounded-lg overflow-hidden relative"
            :style="bgType === 'image' && bgImagePreview ? { backgroundImage: `url(${bgImagePreview})`, backgroundSize: 'cover', backgroundPosition: 'center' } : { backgroundColor: form.background_color }"
          >
            <div class="absolute inset-0 bg-black/20" />
            <div class="relative p-3">
              <span class="text-white font-bold text-sm drop-shadow-sm">{{ form.name || 'Nome do quadro' }}</span>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2">
          <button type="button" class="monday-btn monday-btn-secondary" @click="showCreate = false">Cancelar</button>
          <button type="submit" class="monday-btn monday-btn-primary" :disabled="creating">
            {{ creating ? 'Criando...' : 'Criar Quadro' }}
          </button>
        </div>
      </form>
    </AppModal>
  </div>
</template>

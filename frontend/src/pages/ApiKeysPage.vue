<script setup>
import { ref, onMounted, computed } from 'vue'
import { apiKeysApi } from '../api/apiKeys'
import { usePlanLimits } from '../composables/usePlanLimits'
import PageHeader from '../components/shared/PageHeader.vue'
import ConfirmModal from '../components/shared/ConfirmModal.vue'
import {
  Copy, Trash2, Plus, AlertTriangle, Check as CheckIcon,
  Key, Code, BookOpen, Zap, Shield, ChevronDown, ChevronRight,
  Terminal, Globe, FileJson
} from 'lucide-vue-next'

const { hasFeature, requiredPlanForFeature } = usePlanLimits()
const hasApiAccess = computed(() => hasFeature('api_access'))

const keys = ref([])
const loading = ref(true)
const showCreateModal = ref(false)
const newKeyName = ref('')
const creating = ref(false)
const rawKey = ref('')
const copied = ref(false)
const error = ref('')
const activeTab = ref('overview')
const expandedEndpoint = ref(null)
const confirmRevoke = ref(null)

const apiBaseUrl = window.location.origin

const endpoints = [
  {
    id: 'list-boards',
    method: 'GET',
    path: '/api/v1/external/boards',
    title: 'Listar boards',
    description: 'Retorna todos os boards ativos do workspace.',
    response: `{
  "data": [
    {
      "id": "uuid-do-board",
      "name": "Meu Projeto",
      "description": "Descricao do board",
      "created_at": "2026-01-15T10:00:00Z"
    }
  ]
}`,
  },
  {
    id: 'get-board',
    method: 'GET',
    path: '/api/v1/external/boards/{board_id}',
    title: 'Detalhes do board',
    description: 'Retorna o board com todas as listas e cards.',
    response: `{
  "data": {
    "id": "uuid-do-board",
    "name": "Meu Projeto",
    "lists": [
      {
        "id": "uuid-da-lista",
        "name": "A Fazer",
        "cards": [
          {
            "id": "uuid-do-card",
            "title": "Tarefa exemplo",
            "due_date": "2026-03-01"
          }
        ]
      }
    ]
  }
}`,
  },
  {
    id: 'create-card',
    method: 'POST',
    path: '/api/v1/external/boards/{board_id}/lists/{list_id}/cards',
    title: 'Criar card',
    description: 'Cria um novo card em uma lista especifica.',
    body: `{
  "title": "Nova tarefa",
  "description": "Descricao opcional",
  "due_date": "2026-03-15"
}`,
    response: `{
  "data": {
    "id": "uuid-do-novo-card",
    "title": "Nova tarefa",
    "description": "Descricao opcional",
    "due_date": "2026-03-15T00:00:00Z",
    "position": 1
  }
}`,
  },
  {
    id: 'get-card',
    method: 'GET',
    path: '/api/v1/external/cards/{card_id}',
    title: 'Detalhes do card',
    description: 'Retorna o card com membros, labels e checklists.',
    response: `{
  "data": {
    "id": "uuid-do-card",
    "title": "Tarefa exemplo",
    "description": "Detalhes da tarefa",
    "due_date": "2026-03-01T00:00:00Z",
    "members": [],
    "labels": [],
    "checklists": []
  }
}`,
  },
  {
    id: 'move-card',
    method: 'PATCH',
    path: '/api/v1/external/cards/{card_id}/move',
    title: 'Mover card',
    description: 'Move um card para outra lista ou posicao.',
    body: `{
  "list_id": "uuid-da-lista-destino",
  "position": 0
}`,
    response: `{
  "data": {
    "id": "uuid-do-card",
    "title": "Tarefa exemplo",
    "list_id": "uuid-da-lista-destino",
    "position": 0
  }
}`,
  },
]

const codeExamples = {
  curl: {
    label: 'cURL',
    icon: 'Terminal',
    code: `# Listar todos os boards
curl -H "X-Api-Key: SUA_CHAVE_AQUI" \\
  ${apiBaseUrl}/api/v1/external/boards

# Criar um card
curl -X POST \\
  -H "X-Api-Key: SUA_CHAVE_AQUI" \\
  -H "Content-Type: application/json" \\
  -d '{"title": "Nova tarefa", "description": "Via API"}' \\
  ${apiBaseUrl}/api/v1/external/boards/BOARD_ID/lists/LIST_ID/cards`,
  },
  javascript: {
    label: 'JavaScript',
    icon: 'FileJson',
    code: `const API_KEY = 'SUA_CHAVE_AQUI'
const BASE_URL = '${apiBaseUrl}/api/v1/external'

// Listar boards
const response = await fetch(\`\${BASE_URL}/boards\`, {
  headers: { 'X-Api-Key': API_KEY }
})
const boards = await response.json()
console.log(boards.data)

// Criar um card em uma lista
const newCard = await fetch(
  \`\${BASE_URL}/boards/\${boardId}/lists/\${listId}/cards\`,
  {
    method: 'POST',
    headers: {
      'X-Api-Key': API_KEY,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      title: 'Nova tarefa',
      description: 'Criada via API'
    })
  }
)
console.log(await newCard.json())`,
  },
  python: {
    label: 'Python',
    icon: 'Code',
    code: `import requests

API_KEY = 'SUA_CHAVE_AQUI'
BASE_URL = '${apiBaseUrl}/api/v1/external'
headers = {'X-Api-Key': API_KEY}

# Listar boards
response = requests.get(f'{BASE_URL}/boards', headers=headers)
boards = response.json()['data']
print(boards)

# Criar um card
new_card = requests.post(
    f'{BASE_URL}/boards/{board_id}/lists/{list_id}/cards',
    headers={**headers, 'Content-Type': 'application/json'},
    json={
        'title': 'Nova tarefa',
        'description': 'Criada via API'
    }
)
print(new_card.json())`,
  },
}

const activeCodeTab = ref('curl')

const useCases = [
  { title: 'Automacao com Zapier/n8n', desc: 'Crie cards automaticamente quando receber um email, formulario ou lead no CRM.' },
  { title: 'Dashboard no Power BI', desc: 'Puxe dados dos boards para criar relatorios e dashboards personalizados.' },
  { title: 'Integracao com CRM', desc: 'Sincronize deals do seu CRM com cards no Zentra automaticamente.' },
  { title: 'Chatbot / Suporte', desc: 'Crie cards a partir de tickets de suporte ou mensagens de chatbot.' },
  { title: 'CI/CD Pipeline', desc: 'Mova cards automaticamente quando um deploy e feito ou um bug e reportado.' },
  { title: 'App Customizado', desc: 'Construa sua propria interface ou app que leia e escreva dados no Zentra.' },
]

const howItWorksSteps = [
  { title: 'Gere uma API Key', desc: 'Va na aba "Minhas Chaves" e clique em "Gerar nova chave". De um nome descritivo (ex: "Zapier", "Power BI").' },
  { title: 'Copie a chave', desc: 'A chave sera exibida apenas uma vez. Copie e guarde em lugar seguro. Ela comeca com flw_ seguido de caracteres aleatorios.' },
  { title: 'Use nas requisicoes', desc: 'Envie a chave no header X-Api-Key em todas as chamadas para a API. Veja os exemplos na aba "Exemplos de Codigo".' },
]

const tabs = [
  { id: 'overview', label: 'Visao Geral', icon: BookOpen },
  { id: 'endpoints', label: 'Endpoints', icon: Globe },
  { id: 'examples', label: 'Exemplos de Codigo', icon: Code },
  { id: 'keys', label: 'Minhas Chaves', icon: Key },
]

function toggleEndpoint(id) {
  expandedEndpoint.value = expandedEndpoint.value === id ? null : id
}

function methodColor(method) {
  const colors = {
    GET: 'text-[#3fb950] bg-[#3fb950]/10 border-[#3fb950]/20',
    POST: 'text-[#a5b4fc] bg-[#a5b4fc]/10 border-[#a5b4fc]/20',
    PATCH: 'text-[#d29922] bg-[#d29922]/10 border-[#d29922]/20',
    DELETE: 'text-[#f85149] bg-[#f85149]/10 border-[#f85149]/20',
  }
  return colors[method] || ''
}

onMounted(async () => {
  await fetchKeys()
})

async function fetchKeys() {
  loading.value = true
  try {
    const res = await apiKeysApi.list()
    keys.value = res.data.data
  } catch {
    keys.value = []
  } finally {
    loading.value = false
  }
}

async function createKey() {
  if (!newKeyName.value.trim()) return
  creating.value = true
  error.value = ''
  try {
    const res = await apiKeysApi.create({ name: newKeyName.value.trim() })
    rawKey.value = res.data.raw_key
    keys.value.unshift(res.data.data)
    newKeyName.value = ''
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao criar chave.'
  } finally {
    creating.value = false
  }
}

function revokeKey(key) {
  confirmRevoke.value = key
}

async function confirmRevokeKey() {
  const key = confirmRevoke.value
  confirmRevoke.value = null
  if (!key) return
  try {
    await apiKeysApi.delete(key.id)
    keys.value = keys.value.filter(k => k.id !== key.id)
  } catch (e) {
    error.value = e.response?.data?.error?.message || 'Erro ao revogar chave.'
  }
}

async function copyToClipboard(text) {
  try {
    await navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  } catch {
    const ta = document.createElement('textarea')
    ta.value = text
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
    copied.value = true
    setTimeout(() => { copied.value = false }, 2000)
  }
}

function closeModal() {
  showCreateModal.value = false
  rawKey.value = ''
  newKeyName.value = ''
  error.value = ''
}

function formatDate(date) {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('pt-BR')
}

function formatDateTime(date) {
  if (!date) return 'Nunca'
  return new Date(date).toLocaleDateString('pt-BR', {
    day: '2-digit', month: '2-digit', year: 'numeric',
    hour: '2-digit', minute: '2-digit',
  })
}
</script>

<template>
  <div>
    <PageHeader title="API Keys" />

    <!-- Upgrade gate -->
    <div v-if="!hasApiAccess" class="max-w-4xl mx-auto">
      <div class="rounded-xl border border-[#444c56] bg-[#161b22] p-8 text-center">
        <Key :size="48" class="text-[#8b949e] mx-auto mb-4" />
        <h3 class="text-lg font-semibold text-[#e6edf3] mb-2">API disponivel no plano Business</h3>
        <p class="text-sm text-[#8b949e] mb-4 max-w-md mx-auto">
          Integre o Zentra com suas ferramentas externas via API REST.
          Automatize criacao de cards, sincronize dados e muito mais.
        </p>
        <router-link
          to="/plans"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-[#6366f1] text-white text-sm font-medium hover:bg-[#5558e6] transition-colors"
        >
          Ver planos
        </router-link>
      </div>
    </div>

    <div v-else class="space-y-5">

      <!-- Error -->
      <div v-if="error" class="max-w-5xl mx-auto p-3 bg-[#f8514926] border border-[#f8514933] rounded-xl text-sm text-[#f85149] text-center">
        {{ error }}
      </div>

      <!-- Tabs - centered -->
      <div class="max-w-5xl mx-auto">
        <div class="flex gap-1 bg-[#161b22] border border-[#444c56] rounded-xl p-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            class="flex items-center gap-2 px-4 py-2 text-sm rounded-lg transition-colors flex-1 justify-center"
            :class="activeTab === tab.id
              ? 'bg-[#6366f1] text-white font-medium'
              : 'text-[#8b949e] hover:text-[#e6edf3] hover:bg-[#2d333b]'"
            @click="activeTab = tab.id"
          >
            <component :is="tab.icon" :size="16" />
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- TAB: Visao Geral -->
      <div v-if="activeTab === 'overview'">
        <!-- Row 1: Intro + Como funciona + Limites -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
          <!-- O que e -->
          <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-5">
            <h3 class="text-base font-bold text-[#e6edf3] mb-3 flex items-center gap-2">
              <Zap :size="18" class="text-[#6366f1]" />
              O que e a API?
            </h3>
            <p class="text-sm text-[#8b949e] leading-relaxed">
              A API permite conectar o Zentra com qualquer sistema externo.
              Automatize tarefas, crie integracoes personalizadas
              e sincronize dados entre o Zentra e suas ferramentas.
            </p>
          </div>

          <!-- Como funciona -->
          <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-5">
            <h3 class="text-base font-bold text-[#e6edf3] mb-3 flex items-center gap-2">
              <Shield :size="18" class="text-[#3fb950]" />
              Como funciona
            </h3>
            <div class="space-y-3">
              <div v-for="(step, i) in howItWorksSteps" :key="i" class="flex gap-3">
                <div class="w-6 h-6 rounded-full bg-[#6366f1]/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                  <span class="text-xs font-bold text-[#6366f1]">{{ i + 1 }}</span>
                </div>
                <div>
                  <p class="text-sm font-medium text-[#e6edf3]">{{ step.title }}</p>
                  <p class="text-xs text-[#8b949e] mt-0.5 leading-relaxed">{{ step.desc }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Limites -->
          <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-5">
            <h3 class="text-base font-bold text-[#e6edf3] mb-3">Limites</h3>
            <div class="space-y-3">
              <div class="flex items-center gap-3 p-3 bg-[#0d1117] rounded-xl border border-[#2d333b]">
                <span class="text-xl font-bold text-[#6366f1]">60</span>
                <span class="text-xs text-[#8b949e]">requisicoes / minuto</span>
              </div>
              <div class="flex items-center gap-3 p-3 bg-[#0d1117] rounded-xl border border-[#2d333b]">
                <span class="text-xl font-bold text-[#3fb950]">5</span>
                <span class="text-xs text-[#8b949e]">endpoints disponiveis</span>
              </div>
              <div class="flex items-center gap-3 p-3 bg-[#0d1117] rounded-xl border border-[#2d333b]">
                <span class="text-xl font-bold text-[#d29922]">REST</span>
                <span class="text-xs text-[#8b949e]">JSON via HTTPS</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Row 2: Casos de uso - full width grid -->
        <h4 class="text-sm font-semibold text-[#e6edf3] mb-3">Casos de uso comuns</h4>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
          <div v-for="useCase in useCases" :key="useCase.title" class="bg-[#161b22] border border-[#444c56] rounded-xl p-4">
            <p class="text-sm font-medium text-[#e6edf3] mb-1">{{ useCase.title }}</p>
            <p class="text-xs text-[#8b949e] leading-relaxed">{{ useCase.desc }}</p>
          </div>
        </div>
      </div>

      <!-- TAB: Endpoints -->
      <div v-if="activeTab === 'endpoints'">
        <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-4 mb-4">
          <p class="text-sm text-[#8b949e]">
            Todas as requisicoes devem incluir o header
            <code class="text-[#a5b4fc] bg-[#2d333b] px-1.5 py-0.5 rounded text-xs">X-Api-Key: sua_chave</code>
            — URL base:
            <code class="text-[#a5b4fc] bg-[#2d333b] px-1.5 py-0.5 rounded text-xs">{{ apiBaseUrl }}/api/v1/external</code>
          </p>
        </div>

        <div class="space-y-3">
          <div
            v-for="ep in endpoints"
            :key="ep.id"
            class="bg-[#161b22] border border-[#444c56] rounded-2xl overflow-hidden"
          >
            <button
              class="w-full flex items-center gap-3 p-4 text-left hover:bg-[#1c2128] transition-colors"
              @click="toggleEndpoint(ep.id)"
            >
              <span
                class="text-[10px] font-bold px-2 py-0.5 rounded border"
                :class="methodColor(ep.method)"
              >
                {{ ep.method }}
              </span>
              <code class="text-sm text-[#e6edf3] font-mono flex-1">{{ ep.path }}</code>
              <span class="text-xs text-[#8b949e] hidden sm:inline">{{ ep.title }}</span>
              <component
                :is="expandedEndpoint === ep.id ? ChevronDown : ChevronRight"
                :size="16"
                class="text-[#6e7681] flex-shrink-0"
              />
            </button>

            <div v-if="expandedEndpoint === ep.id" class="border-t border-[#2d333b] p-4 space-y-3">
              <p class="text-sm text-[#8b949e]">{{ ep.description }}</p>

              <div v-if="ep.body">
                <p class="text-[10px] font-semibold text-[#6e7681] uppercase tracking-wider mb-1.5">Body (JSON)</p>
                <pre class="bg-[#0d1117] rounded-lg p-3 text-xs text-[#e6edf3] font-mono overflow-x-auto border border-[#2d333b]">{{ ep.body }}</pre>
              </div>

              <div>
                <p class="text-[10px] font-semibold text-[#6e7681] uppercase tracking-wider mb-1.5">Resposta</p>
                <pre class="bg-[#0d1117] rounded-lg p-3 text-xs text-[#3fb950] font-mono overflow-x-auto border border-[#2d333b]">{{ ep.response }}</pre>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: Exemplos de Codigo -->
      <div v-if="activeTab === 'examples'">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
          <!-- Code block - 2 cols -->
          <div class="lg:col-span-2 bg-[#161b22] border border-[#444c56] rounded-2xl overflow-hidden">
            <div class="flex border-b border-[#2d333b]">
              <button
                v-for="(example, key) in codeExamples"
                :key="key"
                class="flex items-center gap-2 px-5 py-3 text-sm transition-colors border-b-2"
                :class="activeCodeTab === key
                  ? 'text-[#e6edf3] border-[#6366f1] bg-[#1c2128]'
                  : 'text-[#8b949e] border-transparent hover:text-[#e6edf3] hover:bg-[#1c2128]'"
                @click="activeCodeTab = key"
              >
                <Terminal v-if="key === 'curl'" :size="14" />
                <FileJson v-else-if="key === 'javascript'" :size="14" />
                <Code v-else :size="14" />
                {{ example.label }}
              </button>
            </div>

            <div class="relative">
              <button
                class="absolute top-3 right-3 p-1.5 rounded-lg bg-[#2d333b] hover:bg-[#444c56] transition-colors z-10"
                :class="copied ? 'text-[#3fb950]' : 'text-[#8b949e]'"
                title="Copiar codigo"
                @click="copyToClipboard(codeExamples[activeCodeTab].code)"
              >
                <CheckIcon v-if="copied" :size="14" />
                <Copy v-else :size="14" />
              </button>
              <pre class="p-5 text-xs text-[#e6edf3] font-mono overflow-x-auto leading-relaxed whitespace-pre-wrap">{{ codeExamples[activeCodeTab].code }}</pre>
            </div>
          </div>

          <!-- Tips - 1 col -->
          <div class="space-y-4">
            <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-5">
              <h4 class="text-sm font-semibold text-[#e6edf3] mb-3">Dicas importantes</h4>
              <ul class="space-y-3 text-xs text-[#8b949e]">
                <li class="flex gap-2">
                  <span class="text-[#d29922] flex-shrink-0">&#9679;</span>
                  <span>Nunca exponha sua API Key no frontend ou em repositorios publicos. Use variaveis de ambiente.</span>
                </li>
                <li class="flex gap-2">
                  <span class="text-[#d29922] flex-shrink-0">&#9679;</span>
                  <span>Se uma chave for comprometida, revogue-a imediatamente e gere uma nova.</span>
                </li>
                <li class="flex gap-2">
                  <span class="text-[#d29922] flex-shrink-0">&#9679;</span>
                  <span>O limite e de 60 req/min. Se exceder, recebera status 429 (Too Many Requests).</span>
                </li>
                <li class="flex gap-2">
                  <span class="text-[#d29922] flex-shrink-0">&#9679;</span>
                  <span>Todas as respostas sao JSON. Erros retornam um campo "error" com "message" e "code".</span>
                </li>
              </ul>
            </div>

            <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-5">
              <h4 class="text-sm font-semibold text-[#e6edf3] mb-2">Teste rapido</h4>
              <p class="text-xs text-[#8b949e] mb-3">Cole no terminal:</p>
              <div class="bg-[#0d1117] rounded-xl p-3 font-mono text-xs text-[#8b949e] overflow-x-auto border border-[#2d333b]">
                <span class="text-[#a5b4fc]">curl</span> -H <span class="text-[#3fb950]">"X-Api-Key: flw_..."</span> \<br />
                &nbsp;&nbsp;{{ apiBaseUrl }}/api/v1/external/boards
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB: Minhas Chaves -->
      <div v-if="activeTab === 'keys'">
        <!-- Action bar -->
        <div class="flex justify-end mb-4">
          <button
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium rounded-xl bg-[#6366f1] text-white hover:bg-[#4f46e5] transition-colors"
            @click="showCreateModal = true"
          >
            <Plus :size="16" />
            Gerar nova chave
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-16">
          <div class="animate-spin w-8 h-8 border-2 border-[#6366f1] border-t-transparent rounded-full" />
        </div>

        <!-- Keys Table - full width -->
        <div v-else class="bg-[#161b22] border border-[#444c56] rounded-2xl p-6">
          <h3 class="text-sm font-semibold text-[#e6edf3] mb-4">Chaves ativas</h3>

          <div v-if="keys.length === 0" class="py-8 text-center">
            <Key :size="32" class="text-[#6e7681] mx-auto mb-3" />
            <p class="text-sm text-[#8b949e]">Nenhuma API Key criada</p>
            <p class="text-xs text-[#6e7681] mt-1">Clique em "Gerar nova chave" para comecar.</p>
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="border-b border-[#444c56]">
                  <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Nome</th>
                  <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Chave</th>
                  <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Ultimo uso</th>
                  <th class="text-left py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium">Criada em</th>
                  <th class="text-right py-2 px-3 text-[10px] text-[#6e7681] uppercase tracking-wider font-medium"></th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="key in keys"
                  :key="key.id"
                  class="border-b border-[#2d333b] hover:bg-[#1c2128] transition-colors"
                >
                  <td class="py-2.5 px-3 text-[#e6edf3] font-medium">{{ key.name }}</td>
                  <td class="py-2.5 px-3">
                    <code class="text-xs text-[#8b949e] bg-[#0d1117] px-2 py-0.5 rounded">{{ key.key_prefix }}****</code>
                  </td>
                  <td class="py-2.5 px-3 text-[#8b949e]">{{ formatDateTime(key.last_used_at) }}</td>
                  <td class="py-2.5 px-3 text-[#8b949e]">{{ formatDate(key.created_at) }}</td>
                  <td class="py-2.5 px-3 text-right">
                    <button
                      class="p-1.5 rounded-lg hover:bg-[#f85149]/10 text-[#8b949e] hover:text-[#f85149] transition-colors"
                      title="Revogar chave"
                      @click="revokeKey(key)"
                    >
                      <Trash2 :size="14" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>

    <!-- Create Modal -->
    <Teleport to="body">
      <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black/60" @click="closeModal" />
        <div class="relative bg-[#161b22] border border-[#444c56] rounded-2xl p-6 w-full max-w-md mx-4 shadow-xl">
          <!-- Creating step -->
          <template v-if="!rawKey">
            <h3 class="text-lg font-bold text-[#e6edf3] mb-1">Gerar nova API Key</h3>
            <p class="text-xs text-[#8b949e] mb-4">De um nome para identificar onde esta chave sera usada.</p>
            <div class="mb-4">
              <label class="block text-xs text-[#8b949e] mb-1.5">Nome da chave</label>
              <input
                v-model="newKeyName"
                type="text"
                maxlength="100"
                placeholder="Ex: Zapier, Power BI, Integracao CRM, n8n"
                class="w-full px-3 py-2 bg-[#0d1117] border border-[#444c56] rounded-xl text-sm text-[#e6edf3] placeholder:text-[#6e7681] outline-none focus:border-[#388bfd] transition-colors"
                @keydown.enter="createKey"
              />
            </div>
            <div class="flex justify-end gap-2">
              <button
                class="px-4 py-2 text-sm rounded-xl bg-[#2d333b] text-[#8b949e] hover:bg-[#444c56] transition-colors"
                @click="closeModal"
              >
                Cancelar
              </button>
              <button
                class="px-4 py-2 text-sm font-medium rounded-xl bg-[#6366f1] text-white hover:bg-[#4f46e5] transition-colors disabled:opacity-50"
                :disabled="!newKeyName.trim() || creating"
                @click="createKey"
              >
                {{ creating ? 'Gerando...' : 'Gerar chave' }}
              </button>
            </div>
          </template>

          <!-- Show key step -->
          <template v-else>
            <h3 class="text-lg font-bold text-[#e6edf3] mb-2">Chave criada com sucesso!</h3>
            <div class="flex items-start gap-2 mb-4 p-3 bg-[#d29922]/10 border border-[#d29922]/30 rounded-xl">
              <AlertTriangle :size="16" class="text-[#d29922] flex-shrink-0 mt-0.5" />
              <p class="text-xs text-[#d29922]">
                Copie esta chave agora. Por seguranca, ela nao sera exibida novamente.
              </p>
            </div>
            <div class="flex items-center gap-2 mb-4">
              <code class="flex-1 bg-[#0d1117] border border-[#444c56] rounded-xl px-3 py-2.5 text-xs text-[#3fb950] font-mono break-all select-all">
                {{ rawKey }}
              </code>
              <button
                class="p-2.5 rounded-xl hover:bg-[#2d333b] transition-colors flex-shrink-0"
                :class="copied ? 'text-[#3fb950]' : 'text-[#8b949e]'"
                title="Copiar"
                @click="copyToClipboard(rawKey)"
              >
                <CheckIcon v-if="copied" :size="18" />
                <Copy v-else :size="18" />
              </button>
            </div>
            <div class="flex justify-end">
              <button
                class="px-4 py-2 text-sm font-medium rounded-xl bg-[#6366f1] text-white hover:bg-[#4f46e5] transition-colors"
                @click="closeModal"
              >
                Fechar
              </button>
            </div>
          </template>
        </div>
      </div>
    </Teleport>

    <ConfirmModal
      :show="!!confirmRevoke"
      title="Revogar API Key"
      :message="`Revogar a chave &quot;${confirmRevoke?.name || ''}&quot;? Integracoes que usam esta chave deixarao de funcionar.`"
      confirm-text="Revogar"
      cancel-text="Cancelar"
      variant="danger"
      @confirm="confirmRevokeKey"
      @cancel="confirmRevoke = null"
    />
  </div>
</template>

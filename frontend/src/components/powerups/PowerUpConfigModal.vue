<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { usePowerUpsStore } from '../../stores/powerups'
import { powerupsApi } from '../../api/powerups'
import { useNotificationsStore } from '../../stores/notifications'
import { X, ExternalLink, Check, AlertCircle, ChevronDown, ChevronUp, BookOpen, Copy } from 'lucide-vue-next'

const props = defineProps({
  slug: { type: String, required: true },
})
const emit = defineEmits(['close'])

const powerUpsStore = usePowerUpsStore()
const notifications = useNotificationsStore()

const saving = ref(false)
const testing = ref(false)
const syncing = ref(false)
const showTutorial = ref(false)

const powerUp = computed(() => powerUpsStore.powerUps.find(p => p.slug === props.slug))
const config = computed(() => powerUp.value?.config || {})

// Slack config
const webhookUrl = ref('')
const notifyOn = ref(['card_created', 'card_moved', 'comment_added', 'due_date_approaching'])

// Google Calendar config
const autoSync = ref(true)

onMounted(() => {
  if (props.slug === 'slack') {
    webhookUrl.value = config.value.webhook_url || ''
    notifyOn.value = config.value.notify_on || ['card_created', 'card_moved', 'comment_added', 'due_date_approaching']
    // Auto-show tutorial if not configured
    if (!config.value.webhook_url) showTutorial.value = true
  }
  if (props.slug === 'google_calendar') {
    autoSync.value = config.value.auto_sync !== false
    if (!config.value.email) showTutorial.value = true
  }
  if (props.slug === 'google_drive') {
    if (!config.value.email) showTutorial.value = true
  }
  document.addEventListener('keydown', onEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onEscape)
})

function onEscape(e) {
  if (e.key === 'Escape') emit('close')
}

// Slack handlers
async function saveSlackConfig() {
  saving.value = true
  try {
    await powerUpsStore.updateConfig('slack', {
      webhook_url: webhookUrl.value,
      notify_on: notifyOn.value,
    })
    notifications.add('Configuracoes do Slack salvas!', 'success')
  } catch {
    notifications.add('Erro ao salvar configuracoes.', 'error')
  } finally {
    saving.value = false
  }
}

async function testSlack() {
  if (!webhookUrl.value) return
  testing.value = true
  try {
    await powerupsApi.testSlack(webhookUrl.value)
    notifications.add('Mensagem de teste enviada!', 'success')
  } catch {
    notifications.add('Falha ao enviar mensagem. Verifique a URL.', 'error')
  } finally {
    testing.value = false
  }
}

function toggleNotifyEvent(event) {
  const idx = notifyOn.value.indexOf(event)
  if (idx !== -1) {
    notifyOn.value.splice(idx, 1)
  } else {
    notifyOn.value.push(event)
  }
}

// Google Calendar handlers
async function connectGoogleCalendar() {
  try {
    const res = await powerupsApi.googleCalendarAuth()
    window.open(res.data.data.url, '_blank', 'width=600,height=700')
  } catch {
    notifications.add('Erro ao iniciar autorizacao. Verifique se as credenciais do Google estao configuradas.', 'error')
  }
}

async function saveCalendarConfig() {
  saving.value = true
  try {
    await powerUpsStore.updateConfig('google_calendar', { auto_sync: autoSync.value })
    notifications.add('Configuracoes salvas!', 'success')
  } catch {
    notifications.add('Erro ao salvar.', 'error')
  } finally {
    saving.value = false
  }
}

async function syncCalendar() {
  syncing.value = true
  try {
    const res = await powerupsApi.syncCalendar()
    notifications.add(res.data.message, 'success')
  } catch {
    notifications.add('Erro ao sincronizar.', 'error')
  } finally {
    syncing.value = false
  }
}

// Google Drive handlers
async function connectGoogleDrive() {
  try {
    const res = await powerupsApi.googleDriveAuth()
    window.open(res.data.data.url, '_blank', 'width=600,height=700')
  } catch {
    notifications.add('Erro ao iniciar autorizacao. Verifique se as credenciais do Google estao configuradas.', 'error')
  }
}

const slackEvents = [
  { key: 'card_created', label: 'Card criado' },
  { key: 'card_moved', label: 'Card movido entre listas' },
  { key: 'comment_added', label: 'Comentario adicionado' },
  { key: 'due_date_approaching', label: 'Data limite se aproximando' },
]

function copyText(text) {
  navigator.clipboard.writeText(text)
  notifications.add('Copiado!', 'info')
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-center justify-center px-4" @click.self="emit('close')">
      <div class="fixed inset-0 bg-black/60" @click="emit('close')" />

      <div class="relative bg-[#161b22] border border-[#444c56] rounded-xl shadow-2xl w-full max-w-xl max-h-[85vh] overflow-y-auto z-10">
        <!-- Header -->
        <div class="sticky top-0 bg-[#161b22] flex items-center justify-between p-5 border-b border-[#444c56] z-10">
          <h3 class="text-lg font-semibold text-[#e6edf3]">
            Configurar {{ powerUp?.name }}
          </h3>
          <button class="w-8 h-8 rounded-full flex items-center justify-center bg-[#2d333b] hover:bg-[#444c56] text-[#8b949e] transition-colors" @click="emit('close')">
            <X :size="16" />
          </button>
        </div>

        <div class="p-5 space-y-5">

          <!-- ============== SLACK ============== -->
          <template v-if="slug === 'slack'">
            <!-- Tutorial -->
            <div class="rounded-lg border border-[#444c56] overflow-hidden">
              <button
                class="w-full flex items-center justify-between p-3 text-left hover:bg-[#1c2128] transition-colors"
                @click="showTutorial = !showTutorial"
              >
                <span class="flex items-center gap-2 text-sm font-medium text-[#388bfd]">
                  <BookOpen :size="14" />
                  Como configurar o Slack
                </span>
                <component :is="showTutorial ? ChevronUp : ChevronDown" :size="16" class="text-[#8b949e]" />
              </button>

              <div v-if="showTutorial" class="px-4 pb-4 space-y-3 border-t border-[#444c56] pt-3">
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">1</span>
                  <div>
                    <p class="text-sm text-[#e6edf3]">Acesse o <a href="https://api.slack.com/apps" target="_blank" class="text-[#388bfd] hover:underline">Slack API</a> e clique em <strong>"Create New App"</strong></p>
                    <p class="text-xs text-[#8b949e] mt-0.5">Escolha "From scratch" e selecione seu workspace do Slack</p>
                  </div>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">2</span>
                  <div>
                    <p class="text-sm text-[#e6edf3]">No menu lateral, clique em <strong>"Incoming Webhooks"</strong></p>
                    <p class="text-xs text-[#8b949e] mt-0.5">Ative o toggle "Activate Incoming Webhooks"</p>
                  </div>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">3</span>
                  <div>
                    <p class="text-sm text-[#e6edf3]">Clique em <strong>"Add New Webhook to Workspace"</strong></p>
                    <p class="text-xs text-[#8b949e] mt-0.5">Selecione o canal onde deseja receber as notificacoes</p>
                  </div>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">4</span>
                  <div>
                    <p class="text-sm text-[#e6edf3]">Copie a <strong>Webhook URL</strong> gerada e cole abaixo</p>
                    <p class="text-xs text-[#8b949e] mt-0.5">A URL comeca com https://hooks.slack.com/services/...</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Webhook URL input -->
            <div>
              <label class="block text-sm font-medium text-[#e6edf3] mb-1.5">Webhook URL</label>
              <input
                v-model="webhookUrl"
                type="url"
                placeholder="https://hooks.slack.com/services/T00000/B00000/XXXXXXX"
                class="w-full px-3 py-2.5 rounded-lg bg-[#0d1117] border border-[#444c56] text-[#e6edf3] text-sm placeholder-[#545d68] focus:border-[#6366f1] focus:outline-none transition-colors"
              />
            </div>

            <!-- Events -->
            <div>
              <label class="block text-sm font-medium text-[#e6edf3] mb-2">Notificar quando:</label>
              <div class="space-y-2">
                <label
                  v-for="evt in slackEvents"
                  :key="evt.key"
                  class="flex items-center gap-2.5 cursor-pointer p-2 rounded-lg hover:bg-[#1c2128] transition-colors -mx-2"
                >
                  <input
                    type="checkbox"
                    :checked="notifyOn.includes(evt.key)"
                    class="rounded border-[#444c56] bg-[#0d1117] text-[#6366f1] focus:ring-[#6366f1]"
                    @change="toggleNotifyEvent(evt.key)"
                  />
                  <span class="text-sm text-[#e6edf3]">{{ evt.label }}</span>
                </label>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2 pt-2">
              <button
                class="flex-1 px-4 py-2.5 rounded-lg bg-[#6366f1] text-white text-sm font-medium hover:bg-[#5558e6] transition-colors disabled:opacity-50"
                :disabled="saving || !webhookUrl"
                @click="saveSlackConfig"
              >
                {{ saving ? 'Salvando...' : 'Salvar configuracao' }}
              </button>
              <button
                class="px-4 py-2.5 rounded-lg bg-[#2d333b] text-[#e6edf3] text-sm font-medium hover:bg-[#444c56] transition-colors disabled:opacity-50"
                :disabled="testing || !webhookUrl"
                @click="testSlack"
              >
                {{ testing ? 'Enviando...' : 'Enviar teste' }}
              </button>
            </div>
          </template>

          <!-- ============== GOOGLE CALENDAR ============== -->
          <template v-if="slug === 'google_calendar'">
            <!-- Status -->
            <div v-if="config.email" class="flex items-center gap-2.5 p-3.5 rounded-lg bg-[#3fb950]/10 border border-[#3fb950]/30">
              <Check :size="18" class="text-[#3fb950] flex-shrink-0" />
              <div>
                <span class="text-sm font-medium text-[#3fb950]">Conectado</span>
                <p class="text-xs text-[#3fb950]/70">{{ config.email }}</p>
              </div>
            </div>
            <div v-else class="flex items-center gap-2.5 p-3.5 rounded-lg bg-[#f0883e]/10 border border-[#f0883e]/30">
              <AlertCircle :size="18" class="text-[#f0883e] flex-shrink-0" />
              <div>
                <span class="text-sm font-medium text-[#f0883e]">Nao conectado</span>
                <p class="text-xs text-[#f0883e]/70">Conecte sua conta Google para sincronizar</p>
              </div>
            </div>

            <!-- Tutorial -->
            <div class="rounded-lg border border-[#444c56] overflow-hidden">
              <button
                class="w-full flex items-center justify-between p-3 text-left hover:bg-[#1c2128] transition-colors"
                @click="showTutorial = !showTutorial"
              >
                <span class="flex items-center gap-2 text-sm font-medium text-[#388bfd]">
                  <BookOpen :size="14" />
                  Como funciona o Google Calendar
                </span>
                <component :is="showTutorial ? ChevronUp : ChevronDown" :size="16" class="text-[#8b949e]" />
              </button>

              <div v-if="showTutorial" class="px-4 pb-4 space-y-3 border-t border-[#444c56] pt-3">
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">1</span>
                  <p class="text-sm text-[#e6edf3]">Clique em <strong>"Conectar com Google"</strong> abaixo e autorize o acesso ao seu calendario</p>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">2</span>
                  <p class="text-sm text-[#e6edf3]">Quando voce adicionar uma <strong>data de entrega (due date)</strong> a um card, ela sera criada como evento no seu Google Calendar</p>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">3</span>
                  <p class="text-sm text-[#e6edf3]">Alteracoes na due date do card serao refletidas automaticamente no evento do calendario</p>
                </div>

                <div class="mt-2 p-3 rounded-lg bg-[#0d1117] border border-[#444c56]">
                  <p class="text-xs text-[#8b949e]">
                    <strong class="text-[#e6edf3]">Requisito:</strong> O administrador do sistema precisa configurar as credenciais Google OAuth
                    (GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET) no arquivo .env do servidor.
                  </p>
                </div>
              </div>
            </div>

            <!-- Connect button -->
            <button
              class="w-full px-4 py-2.5 rounded-lg bg-white text-gray-800 text-sm font-medium hover:bg-gray-100 transition-colors flex items-center justify-center gap-2"
              @click="connectGoogleCalendar"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
              {{ config.email ? 'Reconectar com Google' : 'Conectar com Google' }}
            </button>

            <!-- Settings (only when connected) -->
            <div v-if="config.email" class="space-y-4">
              <div class="border-t border-[#444c56] pt-4">
                <label class="flex items-center gap-2.5 cursor-pointer p-2 rounded-lg hover:bg-[#1c2128] transition-colors -mx-2">
                  <input
                    type="checkbox"
                    v-model="autoSync"
                    class="rounded border-[#444c56] bg-[#0d1117] text-[#6366f1] focus:ring-[#6366f1]"
                  />
                  <div>
                    <span class="text-sm text-[#e6edf3]">Sincronizar due dates automaticamente</span>
                    <p class="text-xs text-[#8b949e]">Cria eventos no calendario sempre que um card recebe uma due date</p>
                  </div>
                </label>
              </div>

              <div class="flex gap-2">
                <button
                  class="flex-1 px-4 py-2.5 rounded-lg bg-[#6366f1] text-white text-sm font-medium hover:bg-[#5558e6] transition-colors disabled:opacity-50"
                  :disabled="saving"
                  @click="saveCalendarConfig"
                >
                  {{ saving ? 'Salvando...' : 'Salvar configuracao' }}
                </button>
                <button
                  class="px-4 py-2.5 rounded-lg bg-[#2d333b] text-[#e6edf3] text-sm font-medium hover:bg-[#444c56] transition-colors disabled:opacity-50"
                  :disabled="syncing"
                  @click="syncCalendar"
                >
                  {{ syncing ? 'Sincronizando...' : 'Sincronizar agora' }}
                </button>
              </div>
            </div>
          </template>

          <!-- ============== GOOGLE DRIVE ============== -->
          <template v-if="slug === 'google_drive'">
            <!-- Status -->
            <div v-if="config.email" class="flex items-center gap-2.5 p-3.5 rounded-lg bg-[#3fb950]/10 border border-[#3fb950]/30">
              <Check :size="18" class="text-[#3fb950] flex-shrink-0" />
              <div>
                <span class="text-sm font-medium text-[#3fb950]">Conectado</span>
                <p class="text-xs text-[#3fb950]/70">{{ config.email }}</p>
              </div>
            </div>
            <div v-else class="flex items-center gap-2.5 p-3.5 rounded-lg bg-[#f0883e]/10 border border-[#f0883e]/30">
              <AlertCircle :size="18" class="text-[#f0883e] flex-shrink-0" />
              <div>
                <span class="text-sm font-medium text-[#f0883e]">Nao conectado</span>
                <p class="text-xs text-[#f0883e]/70">Conecte sua conta Google para acessar seus arquivos</p>
              </div>
            </div>

            <!-- Tutorial -->
            <div class="rounded-lg border border-[#444c56] overflow-hidden">
              <button
                class="w-full flex items-center justify-between p-3 text-left hover:bg-[#1c2128] transition-colors"
                @click="showTutorial = !showTutorial"
              >
                <span class="flex items-center gap-2 text-sm font-medium text-[#388bfd]">
                  <BookOpen :size="14" />
                  Como funciona o Google Drive
                </span>
                <component :is="showTutorial ? ChevronUp : ChevronDown" :size="16" class="text-[#8b949e]" />
              </button>

              <div v-if="showTutorial" class="px-4 pb-4 space-y-3 border-t border-[#444c56] pt-3">
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">1</span>
                  <p class="text-sm text-[#e6edf3]">Clique em <strong>"Conectar com Google"</strong> e autorize o acesso aos seus arquivos do Drive</p>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">2</span>
                  <p class="text-sm text-[#e6edf3]">Ao abrir um card, voce vera o botao <strong>"Google Drive"</strong> na barra lateral</p>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">3</span>
                  <p class="text-sm text-[#e6edf3]">Busque e selecione arquivos do Drive para <strong>anexar diretamente nos cards</strong></p>
                </div>
                <div class="flex gap-3">
                  <span class="flex-shrink-0 w-6 h-6 rounded-full bg-[#6366f1]/15 text-[#a5b4fc] text-xs font-bold flex items-center justify-center">4</span>
                  <p class="text-sm text-[#e6edf3]">Os arquivos do Drive aparecerao na lista de anexos do card com um indicador especial</p>
                </div>

                <div class="mt-2 p-3 rounded-lg bg-[#0d1117] border border-[#444c56]">
                  <p class="text-xs text-[#8b949e]">
                    <strong class="text-[#e6edf3]">Requisito:</strong> O administrador do sistema precisa configurar as credenciais Google OAuth
                    (GOOGLE_CLIENT_ID e GOOGLE_CLIENT_SECRET) e ativar a API do Google Drive no Google Cloud Console.
                  </p>
                </div>
              </div>
            </div>

            <!-- Connect button -->
            <button
              class="w-full px-4 py-2.5 rounded-lg bg-white text-gray-800 text-sm font-medium hover:bg-gray-100 transition-colors flex items-center justify-center gap-2"
              @click="connectGoogleDrive"
            >
              <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 01-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
              {{ config.email ? 'Reconectar com Google' : 'Conectar com Google' }}
            </button>

            <!-- How to use (only when connected) -->
            <div v-if="config.email" class="border-t border-[#444c56] pt-4">
              <p class="text-sm text-[#3fb950] flex items-center gap-1.5">
                <Check :size="14" />
                Tudo pronto! Abra qualquer card para anexar arquivos do Drive.
              </p>
            </div>
          </template>

        </div>
      </div>
    </div>
  </Teleport>
</template>

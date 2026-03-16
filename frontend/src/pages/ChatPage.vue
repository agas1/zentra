<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { useChatStore } from '../stores/chat'
import { useWorkspaceStore } from '../stores/workspace'
import { useAuthStore } from '../stores/auth'
import { useNotificationsStore } from '../stores/notifications'
import {
  MessageSquare, Plus, Send, Hash, User as UserIcon,
  Search, ArrowLeft, Users, Loader2,
} from 'lucide-vue-next'
import UpgradeBanner from '../components/shared/UpgradeBanner.vue'

const chatStore = useChatStore()
const workspaceStore = useWorkspaceStore()
const authStore = useAuthStore()
const notifications = useNotificationsStore()

const messageInput = ref('')
const messagesContainer = ref(null)
const inputRef = ref(null)
const showNewConvModal = ref(false)
const searchQuery = ref('')
const mobileShowMessages = ref(false)

// New conversation form
const newConvType = ref('direct')
const newConvName = ref('')
const selectedParticipants = ref([])
const memberSearch = ref('')
const creatingConv = ref(false)

onMounted(async () => {
  await chatStore.fetchConversations()
  if (!workspaceStore.members.length) {
    await workspaceStore.fetchMembers()
  }
})

const filteredConversations = computed(() => {
  if (!searchQuery.value) return chatStore.sortedConversations
  const q = searchQuery.value.toLowerCase()
  return chatStore.sortedConversations.filter(c => {
    if (c.name?.toLowerCase().includes(q)) return true
    return c.participants?.some(p => p.name?.toLowerCase().includes(q))
  })
})

const filteredMembers = computed(() => {
  const myId = authStore.user?.id
  return workspaceStore.members
    .filter(m => m.id !== myId)
    .filter(m => {
      if (!memberSearch.value) return true
      return m.name?.toLowerCase().includes(memberSearch.value.toLowerCase())
    })
})

function getConversationName(conv) {
  if (conv.name) return conv.name
  // For DMs, show other person's name
  const other = conv.participants?.find(p => p.id !== authStore.user?.id)
  return other?.name || 'Conversa'
}

function getConversationAvatar(conv) {
  if (conv.type === 'channel') return null
  const other = conv.participants?.find(p => p.id !== authStore.user?.id)
  return other
}

function formatTime(dateStr) {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  const now = new Date()
  const diffMs = now - d
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffDays === 0) {
    return d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
  }
  if (diffDays === 1) return 'Ontem'
  if (diffDays < 7) {
    return d.toLocaleDateString('pt-BR', { weekday: 'short' })
  }
  return d.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

function formatMessageTime(dateStr) {
  if (!dateStr) return ''
  return new Date(dateStr).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
}

function formatDateSeparator(dateStr) {
  const d = new Date(dateStr)
  const now = new Date()
  const diffMs = now - d
  const diffDays = Math.floor(diffMs / 86400000)

  if (diffDays === 0) return 'Hoje'
  if (diffDays === 1) return 'Ontem'
  return d.toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function shouldShowDateSeparator(index) {
  if (index === 0) return true
  const curr = new Date(chatStore.messages[index].created_at).toDateString()
  const prev = new Date(chatStore.messages[index - 1].created_at).toDateString()
  return curr !== prev
}

function isMyMessage(msg) {
  return msg.user?.id === authStore.user?.id || msg.user?.id === 'me'
}

async function selectConversation(conv) {
  await chatStore.openConversation(conv)
  mobileShowMessages.value = true
  await nextTick()
  scrollToBottom()
  inputRef.value?.focus()
}

function scrollToBottom() {
  if (messagesContainer.value) {
    messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight
  }
}

watch(() => chatStore.messages.length, async () => {
  await nextTick()
  scrollToBottom()
})

async function handleSend() {
  if (!messageInput.value.trim()) return
  const text = messageInput.value
  messageInput.value = ''
  try {
    await chatStore.sendMessage(text)
  } catch {
    notifications.add('Erro ao enviar mensagem', 'error')
    messageInput.value = text
  }
}

function handleKeydown(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault()
    handleSend()
  }
}

function toggleParticipant(member) {
  const idx = selectedParticipants.value.findIndex(p => p.id === member.id)
  if (idx !== -1) {
    selectedParticipants.value.splice(idx, 1)
  } else {
    selectedParticipants.value.push(member)
  }
}

async function createConversation() {
  if (selectedParticipants.value.length === 0) return
  creatingConv.value = true
  try {
    const conv = await chatStore.createConversation({
      type: newConvType.value,
      name: newConvType.value === 'channel' ? newConvName.value : null,
      participant_ids: selectedParticipants.value.map(p => p.id),
    })
    showNewConvModal.value = false
    newConvName.value = ''
    selectedParticipants.value = []
    memberSearch.value = ''
    await selectConversation(conv)
  } catch {
    notifications.add('Erro ao criar conversa', 'error')
  } finally {
    creatingConv.value = false
  }
}

function getInitials(name) {
  if (!name) return '?'
  return name.charAt(0).toUpperCase()
}

const avatarColors = ['#6366f1', '#2563eb', '#059669', '#d97706', '#dc2626', '#8b5cf6', '#0891b2', '#be185d']
function getColor(name) {
  if (!name) return avatarColors[0]
  let h = 0
  for (let i = 0; i < name.length; i++) h = name.charCodeAt(i) + ((h << 5) - h)
  return avatarColors[Math.abs(h) % avatarColors.length]
}
</script>

<template>
  <div class="flex h-full bg-[#0d1117]">
    <!-- Conversations sidebar -->
    <div
      class="w-80 flex-shrink-0 bg-[#161b22] border-r border-[#444c56] flex flex-col"
      :class="{ 'hidden md:flex': mobileShowMessages }"
    >
      <!-- Header -->
      <div class="px-4 py-3 border-b border-[#444c56]">
        <div class="flex items-center justify-between mb-3">
          <h2 class="text-lg font-bold text-[#e6edf3]">Chat</h2>
          <button
            @click="showNewConvModal = true"
            class="w-8 h-8 rounded-lg bg-[#6366f1] hover:bg-[#4f46e5] text-white flex items-center justify-center transition-colors"
          >
            <Plus :size="16" />
          </button>
        </div>
        <!-- Search -->
        <div class="relative">
          <Search :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#6e7681]" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Buscar conversas..."
            class="w-full bg-[#0d1117] border border-[#444c56] rounded-lg pl-9 pr-3 py-2 text-sm text-[#e6edf3] placeholder-[#545d68] focus:border-[#388bfd] focus:outline-none transition-colors"
          />
        </div>
      </div>

      <!-- Conversations list -->
      <div class="flex-1 overflow-y-auto">
        <div v-if="chatStore.loading" class="flex items-center justify-center py-12">
          <Loader2 :size="20" class="text-[#8b949e] animate-spin" />
        </div>
        <div v-else-if="filteredConversations.length === 0" class="text-center py-12 px-4">
          <MessageSquare :size="32" class="text-[#6e7681] mx-auto mb-2" />
          <p class="text-sm text-[#6e7681]">Nenhuma conversa ainda</p>
          <button @click="showNewConvModal = true" class="text-sm text-[#388bfd] hover:text-[#a5b4fc] mt-1 transition-colors">
            Iniciar uma conversa
          </button>
        </div>
        <div v-else>
          <div
            v-for="conv in filteredConversations"
            :key="conv.id"
            @click="selectConversation(conv)"
            class="flex items-center gap-3 px-4 py-3 cursor-pointer transition-colors border-l-2"
            :class="chatStore.activeConversation?.id === conv.id
              ? 'bg-[#6366f1]/10 border-[#6366f1]'
              : 'border-transparent hover:bg-[#2d333b]'"
          >
            <!-- Avatar -->
            <div class="relative flex-shrink-0">
              <div
                v-if="conv.type === 'channel'"
                class="w-10 h-10 rounded-xl bg-[#2d333b] border border-[#444c56] flex items-center justify-center"
              >
                <Hash :size="16" class="text-[#8b949e]" />
              </div>
              <div
                v-else
                class="w-10 h-10 rounded-xl flex items-center justify-center text-white text-sm font-bold"
                :style="{ backgroundColor: getColor(getConversationAvatar(conv)?.name) }"
              >
                {{ getInitials(getConversationAvatar(conv)?.name) }}
              </div>
              <!-- Unread dot -->
              <div
                v-if="conv.unread_count > 0"
                class="absolute -top-0.5 -right-0.5 w-3 h-3 rounded-full bg-[#388bfd] border-2 border-[#161b22]"
              />
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
              <div class="flex items-center justify-between">
                <p class="text-sm font-semibold truncate" :class="conv.unread_count > 0 ? 'text-[#e6edf3]' : 'text-[#8b949e]'">
                  {{ getConversationName(conv) }}
                </p>
                <span class="text-[10px] text-[#6e7681] flex-shrink-0 ml-2">
                  {{ formatTime(conv.last_message?.created_at) }}
                </span>
              </div>
              <p class="text-xs truncate mt-0.5" :class="conv.unread_count > 0 ? 'text-[#8b949e]' : 'text-[#6e7681]'">
                <span v-if="conv.last_message?.type === 'system'" class="italic">{{ conv.last_message.body }}</span>
                <template v-else-if="conv.last_message">
                  <span class="font-medium">{{ conv.last_message.user?.name?.split(' ')[0] }}:</span>
                  {{ conv.last_message.body }}
                </template>
                <span v-else class="italic">Sem mensagens</span>
              </p>
            </div>

            <!-- Unread badge -->
            <span
              v-if="conv.unread_count > 0"
              class="bg-[#388bfd] text-white text-[10px] font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 flex-shrink-0"
            >
              {{ conv.unread_count }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <!-- Messages panel -->
    <div
      class="flex-1 flex flex-col"
      :class="{ 'hidden md:flex': !mobileShowMessages && !chatStore.activeConversation }"
    >
      <!-- Empty state -->
      <div v-if="!chatStore.activeConversation" class="flex-1 flex items-center justify-center">
        <div class="text-center animate-fade-in">
          <div class="w-16 h-16 rounded-2xl bg-[#388bfd]/10 flex items-center justify-center mx-auto mb-4">
            <MessageSquare :size="28" class="text-[#388bfd]" />
          </div>
          <h3 class="text-lg font-semibold text-[#e6edf3] mb-1">Suas mensagens</h3>
          <p class="text-sm text-[#8b949e] max-w-xs">Selecione uma conversa ou inicie uma nova para comecar a conversar com sua equipe.</p>
          <button
            @click="showNewConvModal = true"
            class="mt-4 px-4 py-2 bg-[#6366f1] hover:bg-[#4f46e5] text-white text-sm font-medium rounded-lg transition-colors"
          >
            Nova Conversa
          </button>
        </div>
      </div>

      <!-- Active conversation -->
      <template v-else>
        <!-- Conversation header -->
        <div class="px-4 py-3 border-b border-[#444c56] bg-[#161b22] flex items-center gap-3">
          <button
            @click="mobileShowMessages = false; chatStore.closeConversation()"
            class="md:hidden w-8 h-8 rounded-lg hover:bg-[#2d333b] flex items-center justify-center text-[#8b949e] transition-colors"
          >
            <ArrowLeft :size="16" />
          </button>
          <div
            v-if="chatStore.activeConversation.type === 'channel'"
            class="w-9 h-9 rounded-xl bg-[#2d333b] border border-[#444c56] flex items-center justify-center"
          >
            <Hash :size="15" class="text-[#8b949e]" />
          </div>
          <div
            v-else
            class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold"
            :style="{ backgroundColor: getColor(getConversationAvatar(chatStore.activeConversation)?.name) }"
          >
            {{ getInitials(getConversationAvatar(chatStore.activeConversation)?.name) }}
          </div>
          <div class="flex-1 min-w-0">
            <h3 class="text-sm font-semibold text-[#e6edf3] truncate">
              {{ getConversationName(chatStore.activeConversation) }}
            </h3>
            <p class="text-xs text-[#6e7681]">
              <Users :size="10" class="inline mr-1" />
              {{ chatStore.activeConversation.participants?.length || 0 }} membros
            </p>
          </div>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto px-4 py-4 space-y-1">
          <!-- Load more -->
          <div v-if="chatStore.hasMoreMessages" class="text-center py-2">
            <button
              @click="chatStore.loadMoreMessages()"
              class="text-xs text-[#6366f1] hover:text-[#a5b4fc] transition-colors"
              :disabled="chatStore.messagesLoading"
            >
              Carregar mensagens anteriores
            </button>
          </div>

          <div v-if="chatStore.messagesLoading && chatStore.messages.length === 0" class="flex items-center justify-center py-12">
            <Loader2 :size="20" class="text-[#8b949e] animate-spin" />
          </div>

          <template v-for="(msg, index) in chatStore.messages" :key="msg.id">
            <!-- Date separator -->
            <div v-if="shouldShowDateSeparator(index)" class="flex items-center gap-3 py-3">
              <div class="flex-1 h-px bg-[#2d333b]" />
              <span class="text-[10px] font-medium text-[#6e7681] uppercase tracking-wider">
                {{ formatDateSeparator(msg.created_at) }}
              </span>
              <div class="flex-1 h-px bg-[#2d333b]" />
            </div>

            <!-- System message -->
            <div v-if="msg.type === 'system'" class="text-center py-1">
              <span class="text-xs text-[#6e7681] italic">{{ msg.user?.name?.split(' ')[0] }} {{ msg.body }}</span>
            </div>

            <!-- Regular message -->
            <div v-else class="flex gap-2.5 group py-1 px-2 -mx-2 rounded-lg hover:bg-[#161b22] transition-colors">
              <div
                class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold flex-shrink-0 mt-0.5"
                :style="{ backgroundColor: getColor(msg.user?.name) }"
              >
                {{ getInitials(msg.user?.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-baseline gap-2">
                  <span class="text-sm font-semibold" :class="isMyMessage(msg) ? 'text-[#a5b4fc]' : 'text-[#e6edf3]'">
                    {{ isMyMessage(msg) ? 'Voce' : msg.user?.name }}
                  </span>
                  <span class="text-[10px] text-[#6e7681]">{{ formatMessageTime(msg.created_at) }}</span>
                  <span v-if="msg._sending" class="text-[10px] text-[#6e7681] italic">enviando...</span>
                </div>
                <p class="text-sm text-[#e6edf3] whitespace-pre-wrap break-words leading-relaxed">{{ msg.body }}</p>
              </div>
            </div>
          </template>
        </div>

        <!-- Message input -->
        <div class="px-4 py-3 border-t border-[#444c56] bg-[#161b22]">
          <div class="flex items-end gap-2">
            <textarea
              ref="inputRef"
              v-model="messageInput"
              @keydown="handleKeydown"
              placeholder="Escreva uma mensagem..."
              rows="1"
              class="flex-1 bg-[#0d1117] border border-[#444c56] rounded-xl px-4 py-2.5 text-sm text-[#e6edf3] placeholder-[#545d68] resize-none focus:border-[#388bfd] focus:outline-none focus:ring-1 focus:ring-[#6366f1]/30 transition-colors max-h-32"
              style="field-sizing: content;"
            />
            <button
              @click="handleSend"
              :disabled="!messageInput.trim()"
              class="w-10 h-10 rounded-xl flex items-center justify-center transition-all flex-shrink-0"
              :class="messageInput.trim()
                ? 'bg-[#6366f1] hover:bg-[#4f46e5] text-white'
                : 'bg-[#2d333b] text-[#6e7681] cursor-not-allowed'"
            >
              <Send :size="16" />
            </button>
          </div>
          <p class="text-[10px] text-[#6e7681] mt-1.5 ml-1">Enter para enviar, Shift+Enter para nova linha</p>
        </div>
      </template>
    </div>

    <!-- New conversation modal -->
    <Teleport to="body">
      <div v-if="showNewConvModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="showNewConvModal = false" />
        <div class="relative bg-[#161b22] border border-[#444c56] rounded-2xl w-full max-w-md max-h-[80vh] flex flex-col animate-scale-in shadow-xl shadow-black/30">
          <!-- Header -->
          <div class="px-5 py-4 border-b border-[#444c56]">
            <h3 class="text-lg font-semibold text-[#e6edf3]">Nova Conversa</h3>
            <p class="text-sm text-[#8b949e] mt-0.5">Selecione membros para iniciar uma conversa</p>
          </div>

          <!-- Type selector -->
          <div class="px-5 py-3 border-b border-[#2d333b]">
            <div class="flex gap-2">
              <button
                @click="newConvType = 'direct'"
                class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="newConvType === 'direct' ? 'bg-[#6366f1] text-white' : 'bg-[#2d333b] text-[#8b949e] hover:bg-[#444c56]'"
              >
                <UserIcon :size="14" class="inline mr-1.5" />
                Mensagem Direta
              </button>
              <button
                @click="newConvType = 'channel'"
                class="flex-1 py-2 text-sm font-medium rounded-lg transition-colors"
                :class="newConvType === 'channel' ? 'bg-[#6366f1] text-white' : 'bg-[#2d333b] text-[#8b949e] hover:bg-[#444c56]'"
              >
                <Hash :size="14" class="inline mr-1.5" />
                Canal
              </button>
            </div>
            <!-- Channel name -->
            <input
              v-if="newConvType === 'channel'"
              v-model="newConvName"
              type="text"
              placeholder="Nome do canal..."
              class="w-full bg-[#0d1117] border border-[#444c56] rounded-lg px-3 py-2 mt-3 text-sm text-[#e6edf3] placeholder-[#545d68] focus:border-[#388bfd] focus:outline-none transition-colors"
            />
          </div>

          <!-- Member search -->
          <div class="px-5 py-3 border-b border-[#2d333b]">
            <div class="relative">
              <Search :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#6e7681]" />
              <input
                v-model="memberSearch"
                type="text"
                placeholder="Buscar membros..."
                class="w-full bg-[#0d1117] border border-[#444c56] rounded-lg pl-9 pr-3 py-2 text-sm text-[#e6edf3] placeholder-[#545d68] focus:border-[#388bfd] focus:outline-none transition-colors"
              />
            </div>
            <!-- Selected chips -->
            <div v-if="selectedParticipants.length > 0" class="flex flex-wrap gap-1.5 mt-2">
              <span
                v-for="p in selectedParticipants"
                :key="p.id"
                class="inline-flex items-center gap-1 px-2 py-0.5 bg-[#6366f1]/20 text-[#a5b4fc] rounded-md text-xs font-medium"
              >
                {{ p.name.split(' ')[0] }}
                <button @click="toggleParticipant(p)" class="hover:text-white transition-colors">&times;</button>
              </span>
            </div>
          </div>

          <!-- Members list -->
          <div class="flex-1 overflow-y-auto px-2 py-2 max-h-60">
            <div
              v-for="member in filteredMembers"
              :key="member.id"
              @click="toggleParticipant(member)"
              class="flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-colors"
              :class="selectedParticipants.find(p => p.id === member.id) ? 'bg-[#6366f1]/10' : 'hover:bg-[#2d333b]'"
            >
              <div
                class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-xs font-bold"
                :style="{ backgroundColor: getColor(member.name) }"
              >
                {{ getInitials(member.name) }}
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-[#e6edf3] truncate">{{ member.name }}</p>
                <p class="text-xs text-[#6e7681] truncate">{{ member.email }}</p>
              </div>
              <div
                v-if="selectedParticipants.find(p => p.id === member.id)"
                class="w-5 h-5 rounded bg-[#6366f1] flex items-center justify-center flex-shrink-0"
              >
                <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </div>
            <p v-if="filteredMembers.length === 0" class="text-sm text-[#6e7681] text-center py-4">Nenhum membro encontrado</p>
          </div>

          <!-- Footer -->
          <div class="px-5 py-3 border-t border-[#444c56] flex justify-end gap-2">
            <button
              @click="showNewConvModal = false"
              class="px-4 py-2 text-sm font-medium text-[#8b949e] hover:text-[#e6edf3] bg-[#2d333b] hover:bg-[#444c56] rounded-lg transition-colors"
            >
              Cancelar
            </button>
            <button
              @click="createConversation"
              :disabled="selectedParticipants.length === 0 || (newConvType === 'channel' && !newConvName.trim()) || creatingConv"
              class="px-4 py-2 text-sm font-medium text-white bg-[#6366f1] hover:bg-[#4f46e5] rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ creatingConv ? 'Criando...' : 'Criar Conversa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

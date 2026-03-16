<script setup>
import { ref, watch, onMounted, onUnmounted, inject, computed } from 'vue'
import { useCardStore } from '../../stores/card'
import { useBoardsStore } from '../../stores/boards'
import { useWorkspaceStore } from '../../stores/workspace'
import { usePowerUpsStore } from '../../stores/powerups'
import { usePlanLimits } from '../../composables/usePlanLimits'
import { powerupsApi } from '../../api/powerups'
import LabelBadge from './LabelBadge.vue'
import ChecklistWidget from './ChecklistWidget.vue'
import AttachmentList from './AttachmentList.vue'
import CardComments from './CardComments.vue'
import MemberSelector from './MemberSelector.vue'
import LabelSelector from './LabelSelector.vue'
import DueDatePicker from './DueDatePicker.vue'
import CustomFieldsWidget from './CustomFieldsWidget.vue'
import GoogleDrivePicker from '../powerups/GoogleDrivePicker.vue'
import ArchiveCardModal from './ArchiveCardModal.vue'

const props = defineProps({
  cardId: { type: String, required: true },
})
const emit = defineEmits(['close', 'updated', 'archive'])

const cardStore = useCardStore()
const boardsStore = useBoardsStore()
const workspaceStore = useWorkspaceStore()
const powerUpsStore = usePowerUpsStore()
const isDark = inject('boardIsDark', ref(true))
const { maxAttachmentSizeMb, planName, hasPowerUps } = usePlanLimits()

const editingTitle = ref(false)
const titleInput = ref('')
const editingDesc = ref(false)
const descInput = ref('')
const newChecklistTitle = ref('')
const showChecklistForm = ref(false)
const fileInput = ref(null)
const activeTab = ref('details')
const isDragging = ref(false)
const uploading = ref(false)
const showDrivePicker = ref(false)
const showArchiveModal = ref(false)
const newSubCardTitle = ref('')
const showSubCardForm = ref(false)
const openSubCardId = ref(null)

const subCardsProgress = computed(() => {
  const subs = cardStore.card?.sub_cards || []
  if (!subs.length) return null
  const done = subs.filter(s => s.due_completed).length
  return { total: subs.length, done, percent: Math.round((done / subs.length) * 100) }
})

const hasDriveIntegration = computed(() => powerUpsStore.isInstalled('google_drive'))

onMounted(async () => {
  await cardStore.openCard(props.cardId)
  if (workspaceStore.members.length === 0) {
    await workspaceStore.fetchMembers()
  }
  cardStore.fetchActivities()
  document.addEventListener('keydown', onEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onEscape)
})

function onEscape(e) {
  if (e.key === 'Escape') {
    close()
  }
}

watch(() => cardStore.card, (c) => {
  if (c) {
    titleInput.value = c.title
    descInput.value = c.description || ''
  }
}, { immediate: true })

async function saveTitle() {
  if (titleInput.value.trim() && titleInput.value !== cardStore.card.title) {
    await cardStore.updateCard({ title: titleInput.value.trim() })
    emit('updated')
  }
  editingTitle.value = false
}

async function saveDescription() {
  await cardStore.updateCard({ description: descInput.value || null })
  editingDesc.value = false
  emit('updated')
}

async function handleMemberToggle(userId, isAssigned) {
  if (isAssigned) {
    await cardStore.removeMember(userId)
  } else {
    await cardStore.addMember(userId)
  }
  emit('updated')
}

async function handleLabelToggle(labelId) {
  await cardStore.toggleLabel(labelId)
  emit('updated')
}

async function handleDueDateSave(date) {
  await cardStore.updateCard({ due_date: date })
  emit('updated')
}

async function handleDueDateRemove() {
  await cardStore.updateCard({ due_date: null, due_completed: false })
  emit('updated')
}

async function toggleDueCompleted() {
  await cardStore.updateCard({ due_completed: !cardStore.card.due_completed })
  emit('updated')
}

async function handleAddChecklist() {
  const title = newChecklistTitle.value.trim()
  if (!title) return
  await cardStore.addChecklist(title)
  newChecklistTitle.value = ''
  showChecklistForm.value = false
}

const uploadError = ref('')

async function uploadSingleFile(file) {
  const fileSizeMb = file.size / (1024 * 1024)
  if (fileSizeMb > maxAttachmentSizeMb.value) {
    uploadError.value = `Arquivo muito grande (${fileSizeMb.toFixed(1)} MB). Limite do plano ${planName.value}: ${maxAttachmentSizeMb.value} MB.`
    return
  }

  try {
    await cardStore.uploadAttachment(file)
    emit('updated')
  } catch (err) {
    uploadError.value = err.response?.data?.error?.message || 'Erro ao enviar arquivo.'
  }
}

async function handleFileUpload(e) {
  const files = Array.from(e.target.files)
  if (!files.length) return
  uploadError.value = ''
  uploading.value = true

  try {
    for (const file of files) {
      await uploadSingleFile(file)
    }
  } finally {
    uploading.value = false
    fileInput.value.value = ''
  }
}

// Drag and drop handlers
let dragCounter = 0

function onDragEnter(e) {
  e.preventDefault()
  dragCounter++
  if (e.dataTransfer?.types?.includes('Files')) {
    isDragging.value = true
  }
}

function onDragLeave(e) {
  e.preventDefault()
  dragCounter--
  if (dragCounter <= 0) {
    isDragging.value = false
    dragCounter = 0
  }
}

function onDragOver(e) {
  e.preventDefault()
}

async function onDrop(e) {
  e.preventDefault()
  isDragging.value = false
  dragCounter = 0

  const files = Array.from(e.dataTransfer?.files || [])
  if (!files.length) return

  uploadError.value = ''
  uploading.value = true

  try {
    for (const file of files) {
      await uploadSingleFile(file)
    }
  } finally {
    uploading.value = false
  }
}

// Google Drive
async function handleDriveSelect(fileData) {
  try {
    await powerupsApi.attachDriveFile(cardStore.card.id, fileData)
    // Refresh card to get updated attachments
    await cardStore.openCard(cardStore.card.id)
    emit('updated')
    showDrivePicker.value = false
  } catch (err) {
    uploadError.value = err.response?.data?.error?.message || 'Erro ao anexar arquivo do Google Drive.'
  }
}

async function handleAddSubCard() {
  const title = newSubCardTitle.value.trim()
  if (!title) return
  await cardStore.addSubCard(title)
  newSubCardTitle.value = ''
  showSubCardForm.value = false
  emit('updated')
}

async function handleToggleSubCard(subCardId) {
  await cardStore.toggleSubCardDone(subCardId)
  emit('updated')
}

async function handleDeleteSubCard(subCardId) {
  await cardStore.deleteSubCard(subCardId)
  emit('updated')
}

function handleOpenSubCard(subCardId) {
  openSubCardId.value = subCardId
}

function handleSubCardClose() {
  openSubCardId.value = null
  cardStore.openCard(props.cardId)
}

async function handleCustomFieldUpdate(fieldId, value) {
  await cardStore.updateCustomFieldValue(fieldId, value)
}

const duplicating = ref(false)

async function handleDuplicate() {
  duplicating.value = true
  try {
    await cardStore.duplicateCard()
    emit('updated')
  } finally {
    duplicating.value = false
  }
}

function handleArchive() {
  showArchiveModal.value = true
}

async function confirmArchive(options) {
  showArchiveModal.value = false
  const id = await cardStore.archiveCard(options)
  emit('archive', id)
  emit('close')
}

function close() {
  cardStore.closeCard()
  emit('close')
}

function formatDueDate(date) {
  if (!date) return ''
  return new Date(date).toLocaleDateString('pt-BR', { day: 'numeric', month: 'long', year: 'numeric' })
}

function formatActivity(activity) {
  const d = activity.data || {}
  const actions = {
    card_created: () => `criou este cartao na lista <strong>${d.list_name || '...'}</strong>`,
    card_moved: () => `moveu de <strong>${d.from_list || '...'}</strong> para <strong>${d.to_list || '...'}</strong>`,
    card_archived: () => d.archive_reason ? `arquivou este cartao: "${d.archive_reason}"` : 'arquivou este cartao',
    card_restored: () => `restaurou este cartao na lista <strong>${d.list_name || '...'}</strong>`,
    card_auto_restored: () => `foi restaurado automaticamente na lista <strong>${d.list_name || '...'}</strong>`,
    description_updated: () => 'atualizou a descricao',
    attachment_added: () => `anexou <strong>${d.filename || 'um arquivo'}</strong>`,
    comment_added: () => 'adicionou um comentario',
    member_added: () => `adicionou <strong>${d.member_name || 'um membro'}</strong>`,
    member_removed: () => `removeu <strong>${d.member_name || 'um membro'}</strong>`,
    checklist_added: () => `criou a checklist <strong>${d.title || '...'}</strong>`,
    subcard_added: () => `adicionou a sub-tarefa <strong>${d.subcard_title || '...'}</strong>`,
  }
  return actions[activity.action]?.() || activity.action
}

const activityIcons = {
  card_created: { icon: 'M12 4v16m8-8H4', color: 'bg-green-500' },
  card_moved: { icon: 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', color: 'bg-blue-500' },
  card_archived: { icon: 'M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4', color: 'bg-gray-500' },
  card_restored: { icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', color: 'bg-blue-500' },
  card_auto_restored: { icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15', color: 'bg-green-500' },
  description_updated: { icon: 'M4 6h16M4 12h16M4 18h7', color: 'bg-indigo-500' },
  attachment_added: { icon: 'M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13', color: 'bg-yellow-500' },
  comment_added: { icon: 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z', color: 'bg-indigo-500' },
  member_added: { icon: 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z', color: 'bg-teal-500' },
  member_removed: { icon: 'M13 7a4 4 0 11-8 0 4 4 0 018 0zM9 14a6 6 0 00-6 6v1h12v-1a6 6 0 00-6-6zM21 12h-6', color: 'bg-red-400' },
  checklist_added: { icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4', color: 'bg-orange-500' },
  subcard_added: { icon: 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10', color: 'bg-purple-500' },
}

const avatarColors = ['#0073ea', '#00c875', '#e44258', '#fdab3d', '#a25ddc', '#037f4c', '#579bfc', '#ff642e']
function getAvatarColor(name) {
  const hash = name?.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) || 0
  return avatarColors[hash % avatarColors.length]
}

function timeAgo(date) {
  const seconds = Math.floor((new Date() - new Date(date)) / 1000)
  if (seconds < 60) return 'agora mesmo'
  const minutes = Math.floor(seconds / 60)
  if (minutes < 60) return `${minutes} min atras`
  const hours = Math.floor(minutes / 60)
  if (hours < 24) return `${hours}h atras`
  const days = Math.floor(hours / 24)
  if (days < 30) return `${days}d atras`
  return new Date(date).toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-50 flex items-start justify-center pt-12 px-4 animate-fade-in" role="dialog" aria-modal="true" :aria-label="cardStore.card ? cardStore.card.title : 'Carregando cartao'" @click.self="close">
      <div class="fixed inset-0 bg-black/60" aria-hidden="true" @click="close" />

      <!-- Card loaded -->
      <div v-if="cardStore.card" class="relative rounded-xl shadow-2xl w-full max-w-3xl max-h-[85vh] overflow-y-auto z-10 animate-scale-in" :class="isDark ? 'bg-[#161b22] border border-[#444c56]' : 'bg-white border border-gray-200'" @dragenter="onDragEnter" @dragleave="onDragLeave" @dragover="onDragOver" @drop="onDrop">
        <!-- Drag overlay -->
        <div v-if="isDragging" class="absolute inset-0 z-30 bg-[#6366f1]/20 border-2 border-dashed border-[#6366f1] rounded-xl flex items-center justify-center pointer-events-none">
          <div class="text-center">
            <svg class="w-12 h-12 text-[#6366f1] mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
            <p class="text-sm font-medium text-[#6366f1]">Solte para anexar</p>
          </div>
        </div>
        <!-- Cover -->
        <div v-if="cardStore.card.cover_url" class="h-36 bg-cover bg-center rounded-t-xl" :style="{ backgroundImage: `url(${cardStore.card.cover_url})` }" />

        <!-- Close -->
        <button class="absolute top-3 right-3 w-8 h-8 rounded-full flex items-center justify-center transition-colors z-20" :class="isDark ? 'bg-[#2d333b]/80 hover:bg-[#2d333b] text-[#8b949e]' : 'bg-gray-200/80 hover:bg-gray-200 text-gray-500'" aria-label="Fechar" @click="close">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>

        <div class="flex gap-4 p-6">
          <!-- Main content -->
          <div class="flex-1 min-w-0">
            <!-- Title -->
            <div class="mb-1">
              <input
                v-if="editingTitle"
                v-model="titleInput"
                aria-label="Titulo do cartao"
                class="text-xl font-bold w-full border border-[#6366f1] rounded px-2 py-1"
                :class="isDark ? 'text-[#e6edf3] bg-[#0d1117]' : 'text-gray-900 bg-white'"
                @blur="saveTitle"
                @keyup.enter="saveTitle"
              />
              <h2 v-else class="text-xl font-bold cursor-pointer rounded px-1 -mx-1 transition-colors" :class="isDark ? 'text-[#e6edf3] hover:bg-[#2d333b]' : 'text-gray-900 hover:bg-gray-100'" @click="editingTitle = true">
                {{ cardStore.card.title }}
              </h2>
            </div>
            <p class="text-xs mb-4" :class="isDark ? 'text-[#6e7681]' : 'text-[#8b949e]'">
              na lista <strong>{{ cardStore.card.list?.name }}</strong>
            </p>

            <!-- Labels -->
            <div v-if="cardStore.card.labels?.length" class="mb-4">
              <h4 class="text-xs font-semibold uppercase mb-1" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Etiquetas</h4>
              <div class="flex flex-wrap gap-1">
                <LabelBadge v-for="label in cardStore.card.labels" :key="label.id" :label="label" />
              </div>
            </div>

            <!-- Members -->
            <div v-if="cardStore.card.members?.length" class="mb-4">
              <h4 class="text-xs font-semibold uppercase mb-1" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Membros</h4>
              <div class="flex -space-x-1">
                <div
                  v-for="m in cardStore.card.members"
                  :key="m.id"
                  class="w-8 h-8 rounded-full text-white text-sm flex items-center justify-center ring-2 font-medium"
                  :class="isDark ? 'ring-[#161b22]' : 'ring-white'"
                  :style="{ backgroundColor: getAvatarColor(m.name) }"
                  :title="m.name"
                >
                  {{ m.name?.charAt(0)?.toUpperCase() }}
                </div>
              </div>
            </div>

            <!-- Due date -->
            <div v-if="cardStore.card.due_date" class="mb-4">
              <h4 class="text-xs font-semibold uppercase mb-1" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Data Limite</h4>
              <div class="flex items-center gap-2">
                <input type="checkbox" :checked="cardStore.card.due_completed" @change="toggleDueCompleted" class="rounded" :class="isDark ? 'border-[#444c56] bg-[#0d1117]' : 'border-gray-300 bg-white'" :aria-label="cardStore.card.due_completed ? 'Marcar como pendente' : 'Marcar como concluido'" />
                <span class="text-sm" :class="cardStore.card.due_completed ? 'text-[#3fb950] line-through' : (isDark ? 'text-[#e6edf3]' : 'text-gray-900')">
                  {{ formatDueDate(cardStore.card.due_date) }}
                </span>
              </div>
            </div>

            <!-- Description -->
            <div class="mb-4">
              <div class="flex items-center gap-2 mb-1">
                <svg class="w-4 h-4" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                <h4 class="font-semibold text-sm" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">Descricao</h4>
              </div>
              <div v-if="editingDesc">
                <textarea v-model="descInput" aria-label="Descricao do cartao" class="monday-input text-sm resize-none w-full rounded-lg px-3 py-2 border" :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'" rows="4" placeholder="Adicione uma descricao..." />
                <div class="flex gap-2 mt-1">
                  <button class="monday-btn monday-btn-primary text-xs !py-1" @click="saveDescription">Salvar</button>
                  <button class="text-sm" :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" @click="editingDesc = false">Cancelar</button>
                </div>
              </div>
              <div v-else class="text-sm rounded-lg p-3 min-h-[60px] cursor-pointer transition-colors" :class="isDark ? 'text-[#8b949e] bg-[#0d1117] hover:bg-[#2d333b]' : 'text-gray-500 bg-[#f6f8fa] hover:bg-gray-100'" @click="editingDesc = true">
                {{ cardStore.card.description || 'Adicione uma descricao mais detalhada...' }}
              </div>
            </div>

            <!-- Sub-cards -->
            <div class="mb-4">
              <div class="flex items-center gap-2 mb-2">
                <svg class="w-4 h-4" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                <h4 class="font-semibold text-sm" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">Sub-tarefas</h4>
                <span v-if="subCardsProgress" class="text-xs ml-auto" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
                  {{ subCardsProgress.done }}/{{ subCardsProgress.total }}
                </span>
              </div>

              <!-- Progress bar -->
              <div v-if="subCardsProgress" class="w-full h-1.5 rounded-full mb-2" :class="isDark ? 'bg-[#2d333b]' : 'bg-gray-200'">
                <div class="h-full rounded-full transition-all duration-300" :class="subCardsProgress.percent === 100 ? 'bg-[#3fb950]' : 'bg-[#6366f1]'" :style="{ width: subCardsProgress.percent + '%' }" />
              </div>

              <!-- Sub-card list -->
              <div v-if="cardStore.card.sub_cards?.length" class="space-y-1 mb-2">
                <div
                  v-for="sub in cardStore.card.sub_cards"
                  :key="sub.id"
                  class="flex items-center gap-2 py-1.5 px-2 rounded-lg group/sub transition-colors cursor-pointer"
                  :class="isDark ? 'hover:bg-[#2d333b]' : 'hover:bg-gray-100'"
                  @click="handleOpenSubCard(sub.id)"
                >
                  <input
                    type="checkbox"
                    :checked="sub.due_completed"
                    class="rounded flex-shrink-0"
                    :class="isDark ? 'border-[#444c56] bg-[#0d1117]' : 'border-gray-300 bg-white'"
                    :aria-label="`${sub.due_completed ? 'Desmarcar' : 'Concluir'} sub-tarefa: ${sub.title}`"
                    @click.stop="handleToggleSubCard(sub.id)"
                  />
                  <span class="text-sm flex-1 min-w-0 truncate" :class="sub.due_completed ? 'line-through ' + (isDark ? 'text-[#6e7681]' : 'text-gray-400') : (isDark ? 'text-[#e6edf3]' : 'text-gray-900')">
                    {{ sub.title }}
                  </span>
                  <div v-if="sub.members?.length" class="flex -space-x-1">
                    <div v-for="m in sub.members.slice(0, 2)" :key="m.id" class="w-5 h-5 rounded-full text-white text-[8px] flex items-center justify-center ring-1 font-semibold" :class="isDark ? 'ring-[#161b22]' : 'ring-white'" :style="{ backgroundColor: getAvatarColor(m.name) }">
                      {{ m.name?.charAt(0)?.toUpperCase() }}
                    </div>
                  </div>
                  <button
                    class="w-5 h-5 rounded flex items-center justify-center opacity-0 group-hover/sub:opacity-100 transition-opacity flex-shrink-0"
                    :class="isDark ? 'hover:bg-[#444c56] text-[#8b949e]' : 'hover:bg-gray-200 text-gray-400'"
                    @click.stop="handleDeleteSubCard(sub.id)"
                    :aria-label="`Remover sub-tarefa: ${sub.title}`"
                  >
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>

              <!-- Add sub-card form -->
              <div v-if="showSubCardForm" class="flex gap-2 items-center">
                <input
                  v-model="newSubCardTitle"
                  class="flex-1 text-sm rounded-lg px-3 py-1.5 border focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
                  :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
                  aria-label="Titulo da nova sub-tarefa"
                  placeholder="Titulo da sub-tarefa..."
                  @keyup.enter="handleAddSubCard"
                  @keyup.escape="showSubCardForm = false"
                />
                <button class="text-xs px-3 py-1.5 rounded-lg bg-[#6366f1] text-white hover:bg-[#5558e6] transition-colors font-medium" @click="handleAddSubCard">Adicionar</button>
                <button class="text-xs px-2 py-1.5" :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" @click="showSubCardForm = false">
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
              </div>
              <button
                v-else
                class="text-xs flex items-center gap-1 py-1 transition-colors"
                :class="isDark ? 'text-[#8b949e] hover:text-[#e6edf3]' : 'text-gray-500 hover:text-gray-700'"
                @click="showSubCardForm = true"
              >
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Adicionar sub-tarefa
              </button>
            </div>

            <!-- Custom Fields -->
            <CustomFieldsWidget
              v-if="boardsStore.currentBoard?.custom_fields?.length"
              :custom-fields="boardsStore.currentBoard?.custom_fields || []"
              :custom-field-values="cardStore.card.custom_field_values || []"
              @update-value="handleCustomFieldUpdate"
            />

            <!-- Upload error -->
            <div v-if="uploadError" class="mb-4 p-2 bg-[#f8514926] border border-[#f85149] rounded-lg text-xs text-[#f85149]">
              {{ uploadError }}
            </div>

            <!-- Tabs: Details / Activity -->
            <div class="flex gap-4 border-b mb-4" :class="isDark ? 'border-[#444c56]' : 'border-gray-200'">
              <button
                class="pb-2 text-sm font-medium transition-colors border-b-2"
                :class="activeTab === 'details' ? 'text-[#6366f1] border-[#6366f1]' : (isDark ? 'text-[#8b949e] border-transparent hover:text-[#e6edf3]' : 'text-gray-500 border-transparent hover:text-gray-900')"
                @click="activeTab = 'details'"
              >
                Detalhes
              </button>
              <button
                class="pb-2 text-sm font-medium transition-colors border-b-2"
                :class="activeTab === 'activity' ? 'text-[#6366f1] border-[#6366f1]' : (isDark ? 'text-[#8b949e] border-transparent hover:text-[#e6edf3]' : 'text-gray-500 border-transparent hover:text-gray-900')"
                @click="activeTab = 'activity'"
              >
                Atividade
                <span v-if="cardStore.activities.length" class="ml-1 text-xs" :class="isDark ? 'text-[#6e7681]' : 'text-[#8b949e]'">({{ cardStore.activities.length }})</span>
              </button>
            </div>

            <!-- Details tab -->
            <div v-if="activeTab === 'details'">
              <!-- Checklists -->
              <ChecklistWidget
                v-for="cl in cardStore.card.checklists"
                :key="cl.id"
                :checklist="cl"
                @toggle-item="cardStore.toggleChecklistItem"
                @add-item="cardStore.addChecklistItem"
                @delete-item="cardStore.deleteChecklistItem"
                @delete-checklist="cardStore.deleteChecklist"
              />

              <!-- Attachments -->
              <AttachmentList
                :attachments="cardStore.card.attachments"
                @set-cover="cardStore.setAttachmentCover"
                @delete="cardStore.deleteAttachment"
              />

              <!-- Comments -->
              <CardComments
                :comments="cardStore.card.comments"
                @add="cardStore.addComment"
                @update="cardStore.updateComment"
                @delete="cardStore.deleteComment"
              />
            </div>

            <!-- Activity tab -->
            <div v-if="activeTab === 'activity'">
              <div v-if="cardStore.activitiesLoading" class="text-center py-8">
                <div class="animate-spin w-6 h-6 border-2 border-[#6366f1] border-t-transparent rounded-full mx-auto" role="status" aria-label="Carregando atividades" />
                <p class="text-xs mt-2" :class="isDark ? 'text-[#6e7681]' : 'text-[#8b949e]'" aria-hidden="true">Carregando atividades...</p>
              </div>
              <div v-else-if="cardStore.activities.length === 0" class="text-center py-8">
                <svg class="w-10 h-10 mx-auto mb-2" :class="isDark ? 'text-[#6e7681]' : 'text-[#8b949e]'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-sm" :class="isDark ? 'text-[#6e7681]' : 'text-[#8b949e]'">Nenhuma atividade registrada</p>
              </div>
              <div v-else class="space-y-1">
                <div v-for="activity in cardStore.activities" :key="activity.id" class="flex gap-2.5 py-2 px-1 rounded-lg transition-colors" :class="isDark ? 'hover:bg-[#2d333b]' : 'hover:bg-gray-100'">
                  <!-- Action icon -->
                  <div
                    class="w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5"
                    :class="activityIcons[activity.action]?.color || 'bg-gray-400'"
                  >
                    <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" :d="activityIcons[activity.action]?.icon || 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'" />
                    </svg>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
                      <span class="font-semibold">{{ activity.user?.name }}</span>
                      <span v-html="' ' + formatActivity(activity)"></span>
                    </p>
                    <span class="text-xs" :class="isDark ? 'text-[#6e7681]' : 'text-[#8b949e]'" :title="new Date(activity.created_at).toLocaleString('pt-BR')">
                      {{ timeAgo(activity.created_at) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Sidebar -->
          <div class="w-44 flex-shrink-0 space-y-2 sticky top-0 self-start">
            <p class="text-xs font-semibold uppercase mb-1" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">Adicionar ao cartao</p>

            <MemberSelector
              :card-members="cardStore.card.members || []"
              @toggle="handleMemberToggle"
            />

            <LabelSelector
              :board-labels="boardsStore.currentBoard?.labels || []"
              :card-labels="cardStore.card.labels || []"
              @toggle="handleLabelToggle"
            />

            <DueDatePicker
              :due-date="cardStore.card.due_date"
              :due-completed="cardStore.card.due_completed"
              @save="handleDueDateSave"
              @remove="handleDueDateRemove"
            />

            <div>
              <button class="sidebar-btn w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg transition-colors" :class="isDark ? 'bg-[#1c2128] text-[#e6edf3] hover:bg-[#2d333b]' : 'bg-[#f6f8fa] text-gray-700 hover:bg-gray-100'" @click="fileInput?.click()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                Anexo
              </button>
              <p class="text-[10px] px-3 mt-0.5" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">Ate {{ maxAttachmentSizeMb }} MB</p>
            </div>
            <input ref="fileInput" type="file" class="hidden" multiple aria-label="Selecionar arquivos para anexar" @change="handleFileUpload" />

            <!-- Google Drive button -->
            <button
              v-if="hasDriveIntegration"
              class="sidebar-btn w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg transition-colors"
              :class="isDark ? 'bg-[#1c2128] text-[#e6edf3] hover:bg-[#2d333b]' : 'bg-[#f6f8fa] text-gray-700 hover:bg-gray-100'"
              @click="showDrivePicker = true"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
              Google Drive
            </button>

            <button class="sidebar-btn w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg transition-colors" :class="isDark ? 'bg-[#1c2128] text-[#e6edf3] hover:bg-[#2d333b]' : 'bg-[#f6f8fa] text-gray-700 hover:bg-gray-100'" @click="showChecklistForm = !showChecklistForm">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
              Checklist
            </button>

            <div v-if="showChecklistForm" class="rounded-lg p-2 shadow-sm" :class="isDark ? 'bg-[#1c2128] border border-[#444c56]' : 'bg-[#f6f8fa] border border-gray-200'">
              <input v-model="newChecklistTitle" aria-label="Titulo da nova checklist" class="monday-input text-sm mb-1 w-full rounded-lg px-3 py-2 border" :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'" placeholder="Titulo da checklist..." @keyup.enter="handleAddChecklist" />
              <button class="monday-btn monday-btn-primary text-xs !py-1 w-full" @click="handleAddChecklist">Adicionar</button>
            </div>

            <button
              class="sidebar-btn w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg transition-colors"
              :class="isDark ? 'bg-[#1c2128] text-[#e6edf3] hover:bg-[#2d333b]' : 'bg-[#f6f8fa] text-gray-700 hover:bg-gray-100'"
              :disabled="duplicating"
              @click="handleDuplicate"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" /></svg>
              {{ duplicating ? 'Copiando...' : 'Copiar card' }}
            </button>

            <hr class="my-2" :class="isDark ? 'border-[#444c56]' : 'border-gray-200'" />

            <button class="sidebar-btn w-full flex items-center gap-2 text-sm px-3 py-1.5 rounded-lg transition-colors text-[#f85149] hover:bg-[#f8514926]" @click="handleArchive">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
              Arquivar
            </button>
          </div>
        </div>
      </div>

      <!-- Loading skeleton -->
      <div v-else class="relative rounded-xl shadow-2xl w-full max-w-3xl z-10 animate-scale-in overflow-hidden" :class="isDark ? 'bg-[#161b22] border border-[#444c56]' : 'bg-white border border-gray-200'">
        <!-- Skeleton cover -->
        <div class="h-8" :class="isDark ? 'bg-[#2d333b]' : 'bg-[#e2e8f0]'" />

        <div class="flex gap-4 p-6">
          <!-- Main skeleton -->
          <div class="flex-1 space-y-4">
            <div class="h-7 rounded w-3/4 animate-pulse" :class="isDark ? 'bg-[#2d333b]' : 'bg-[#e2e8f0]'" />
            <div class="h-4 rounded w-1/4 animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
            <div class="space-y-2 mt-6">
              <div class="h-4 rounded w-1/3 animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
              <div class="h-20 rounded-lg animate-pulse" :class="isDark ? 'bg-[#0d1117]' : 'bg-white'" />
            </div>
            <div class="space-y-2">
              <div class="h-4 rounded w-1/4 animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
              <div class="h-12 rounded-lg animate-pulse" :class="isDark ? 'bg-[#0d1117]' : 'bg-white'" />
            </div>
          </div>
          <!-- Sidebar skeleton -->
          <div class="w-44 space-y-2">
            <div class="h-3 rounded w-2/3 animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
            <div class="h-8 rounded animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
            <div class="h-8 rounded animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
            <div class="h-8 rounded animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
            <div class="h-8 rounded animate-pulse" :class="isDark ? 'bg-[#1c2128]' : 'bg-[#f6f8fa]'" />
          </div>
        </div>
      </div>
    </div>

    <!-- Google Drive Picker -->
    <GoogleDrivePicker
      v-if="showDrivePicker"
      @close="showDrivePicker = false"
      @select="handleDriveSelect"
    />

    <!-- Sub-card Detail Modal -->
    <CardDetailModal
      v-if="openSubCardId"
      :card-id="openSubCardId"
      @close="handleSubCardClose"
      @updated="emit('updated')"
      @archive="handleSubCardClose"
    />

    <!-- Archive Modal -->
    <ArchiveCardModal
      :show="showArchiveModal"
      @confirm="confirmArchive"
      @cancel="showArchiveModal = false"
    />
  </Teleport>
</template>

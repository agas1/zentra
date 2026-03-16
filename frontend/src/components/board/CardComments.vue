<script setup>
import { ref, inject } from 'vue'
import { useAuthStore } from '../../stores/auth'

const props = defineProps({
  comments: { type: Array, default: () => [] },
})
const emit = defineEmits(['add', 'update', 'delete'])

const authStore = useAuthStore()
const isDark = inject('boardIsDark', ref(true))
const newComment = ref('')
const editingId = ref(null)
const editBody = ref('')

function submitComment() {
  const body = newComment.value.trim()
  if (!body) return
  emit('add', body)
  newComment.value = ''
}

function startEdit(comment) {
  editingId.value = comment.id
  editBody.value = comment.body
}

function saveEdit() {
  if (editBody.value.trim()) {
    emit('update', editingId.value, editBody.value.trim())
  }
  editingId.value = null
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
  <div class="mb-4">
    <div class="flex items-center gap-2 mb-3">
      <svg :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
      <h4 :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-semibold text-sm">Comentarios</h4>
      <span v-if="comments.length" :class="isDark ? 'text-[#6e7681]' : 'text-gray-500'" class="text-xs">({{ comments.length }})</span>
    </div>

    <!-- New comment -->
    <div class="flex gap-2 mb-4">
      <div
        class="w-8 h-8 rounded-full text-white text-xs flex items-center justify-center flex-shrink-0 font-medium"
        :style="{ backgroundColor: getAvatarColor(authStore.user?.name) }"
      >
        {{ authStore.user?.name?.charAt(0)?.toUpperCase() }}
      </div>
      <div class="flex-1">
        <textarea
          v-model="newComment"
          class="monday-input text-sm resize-none"
          rows="2"
          placeholder="Escreva um comentario..."
          @keydown.ctrl.enter="submitComment"
        />
        <div class="flex items-center gap-2 mt-1">
          <button
            v-if="newComment.trim()"
            class="monday-btn monday-btn-primary text-xs !py-1"
            @click="submitComment"
          >
            Enviar
          </button>
          <span v-if="newComment.trim()" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="text-[10px]">Ctrl+Enter para enviar</span>
        </div>
      </div>
    </div>

    <!-- Comments list -->
    <div class="space-y-3">
      <div v-for="comment in comments" :key="comment.id" class="flex gap-2 group">
        <div
          class="w-8 h-8 rounded-full text-white text-xs flex items-center justify-center flex-shrink-0 font-medium"
          :style="{ backgroundColor: getAvatarColor(comment.user?.name) }"
        >
          {{ comment.user?.name?.charAt(0)?.toUpperCase() }}
        </div>
        <div class="flex-1">
          <div class="flex items-center gap-2">
            <span :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-medium">{{ comment.user?.name }}</span>
            <span :class="isDark ? 'text-[#6e7681]' : 'text-gray-500'" class="text-xs" :title="new Date(comment.created_at).toLocaleString('pt-BR')">{{ timeAgo(comment.created_at) }}</span>
          </div>

          <div v-if="editingId === comment.id" class="mt-1">
            <textarea v-model="editBody" class="monday-input text-sm resize-none" rows="2" />
            <div class="flex gap-2 mt-1">
              <button class="monday-btn monday-btn-primary text-xs !py-1" @click="saveEdit">Salvar</button>
              <button :class="isDark ? 'text-[#6e7681] hover:text-[#8b949e]' : 'text-gray-400 hover:text-gray-600'" class="text-sm" @click="editingId = null">Cancelar</button>
            </div>
          </div>
          <div v-else>
            <p :class="isDark ? 'text-[#8b949e] bg-[#0d1117] border-[#444c56]' : 'text-gray-600 bg-white border-gray-200'" class="text-sm mt-0.5 border rounded-lg p-2.5">{{ comment.body }}</p>
            <div v-if="comment.user_id === authStore.user?.id" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'" class="flex gap-2 mt-1 text-xs opacity-0 group-hover:opacity-100 transition-opacity">
              <button class="hover:text-[#6366f1] transition-colors" @click="startEdit(comment)">Editar</button>
              <button class="hover:text-[#f85149] transition-colors" @click="emit('delete', comment.id)">Excluir</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, inject, computed } from 'vue'

const props = defineProps({
  attachments: { type: Array, default: () => [] },
})
const emit = defineEmits(['set-cover', 'delete'])

const isDark = inject('boardIsDark', ref(true))
const searchQuery = ref('')
const previewUrl = ref(null)

const filteredAttachments = computed(() => {
  if (!searchQuery.value) return props.attachments
  const q = searchQuery.value.toLowerCase()
  return props.attachments.filter(a => a.filename?.toLowerCase().includes(q))
})

function isImage(mime) {
  return mime?.startsWith('image/')
}

function formatSize(bytes) {
  if (!bytes || bytes === 0) return ''
  if (bytes < 1024) return bytes + ' B'
  if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
  return (bytes / 1048576).toFixed(1) + ' MB'
}

function getFileIcon(mime) {
  if (!mime) return 'file'
  if (mime.includes('pdf')) return 'pdf'
  if (mime.includes('word') || mime.includes('document')) return 'doc'
  if (mime.includes('excel') || mime.includes('spreadsheet')) return 'xls'
  if (mime.includes('presentation') || mime.includes('powerpoint')) return 'ppt'
  if (mime.includes('video')) return 'video'
  if (mime.includes('audio')) return 'audio'
  if (mime.includes('zip') || mime.includes('rar') || mime.includes('archive')) return 'zip'
  return 'file'
}

const fileIconColors = {
  pdf: 'text-red-400',
  doc: 'text-blue-400',
  xls: 'text-green-400',
  ppt: 'text-orange-400',
  video: 'text-purple-400',
  audio: 'text-pink-400',
  zip: 'text-yellow-400',
  file: isDark.value ? 'text-[#6e7681]' : 'text-gray-400',
}

function openPreview(att) {
  if (isImage(att.mime_type)) {
    previewUrl.value = att.url
  }
}

function isExternal(att) {
  return att.source && att.source !== 'local'
}
</script>

<template>
  <div v-if="attachments.length" class="mb-4">
    <div class="flex items-center gap-2 mb-2">
      <svg :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
      <h4 :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="font-semibold text-sm">Anexos</h4>
      <span class="text-xs" :class="isDark ? 'text-[#6e7681]' : 'text-gray-400'">({{ attachments.length }})</span>
    </div>

    <!-- Search -->
    <div v-if="attachments.length > 3" class="mb-2">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Buscar anexo..."
        class="w-full px-3 py-1.5 rounded-lg text-xs border"
        :class="isDark ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]' : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
      />
    </div>

    <div class="space-y-2">
      <div
        v-for="att in filteredAttachments"
        :key="att.id"
        :class="isDark ? 'bg-[#2d333b] border-[#444c56] hover:border-[#6e7681]' : 'bg-[#f6f8fa] border-gray-200 hover:border-gray-300'"
        class="flex items-center gap-3 p-2 rounded-lg border group transition-colors"
      >
        <!-- Preview / Icon -->
        <div
          :class="isDark ? 'bg-[#0d1117]' : 'bg-white'"
          class="w-20 h-14 rounded flex-shrink-0 flex items-center justify-center overflow-hidden cursor-pointer"
          @click="openPreview(att)"
        >
          <img v-if="isImage(att.mime_type) && !isExternal(att)" :src="att.url" class="w-full h-full object-cover" />
          <!-- Google Drive icon -->
          <div v-else-if="isExternal(att)" class="flex flex-col items-center gap-0.5">
            <svg class="w-5 h-5 text-[#4285F4]" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
            <span class="text-[8px] text-[#4285F4] font-medium">Drive</span>
          </div>
          <!-- File type icon -->
          <div v-else class="flex flex-col items-center gap-0.5">
            <svg :class="fileIconColors[getFileIcon(att.mime_type)]" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="text-[8px] uppercase font-bold" :class="fileIconColors[getFileIcon(att.mime_type)]">{{ getFileIcon(att.mime_type) }}</span>
          </div>
        </div>

        <!-- Info -->
        <div class="flex-1 min-w-0">
          <p :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'" class="text-sm font-medium truncate">{{ att.filename }}</p>
          <div :class="isDark ? 'text-[#6e7681]' : 'text-gray-500'" class="flex items-center gap-2 text-xs mt-0.5 flex-wrap">
            <span v-if="att.size">{{ formatSize(att.size) }}</span>
            <span v-if="isExternal(att)" class="text-[#4285F4]">Google Drive</span>
            <a :href="att.url" target="_blank" class="hover:text-[#6366f1]">{{ isExternal(att) ? 'Abrir' : 'Download' }}</a>
            <button v-if="isImage(att.mime_type) && !isExternal(att)" class="hover:text-[#6366f1]" @click="emit('set-cover', att.id)">
              {{ att.is_cover ? 'Capa atual' : 'Usar como capa' }}
            </button>
            <button class="hover:text-[#f85149] opacity-0 group-hover:opacity-100" @click="emit('delete', att.id)">Excluir</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Image preview lightbox -->
    <Teleport to="body">
      <div v-if="previewUrl" class="fixed inset-0 z-[70] flex items-center justify-center bg-black/80" @click="previewUrl = null">
        <button class="absolute top-4 right-4 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition-colors" @click="previewUrl = null">
          <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <img :src="previewUrl" class="max-w-[90vw] max-h-[85vh] rounded-lg shadow-2xl object-contain" @click.stop />
      </div>
    </Teleport>
  </div>
</template>

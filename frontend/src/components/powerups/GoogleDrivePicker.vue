<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { powerupsApi } from '../../api/powerups'
import { X, Search, FileText, Image, FileSpreadsheet, Presentation, Folder, File } from 'lucide-vue-next'

const emit = defineEmits(['close', 'select'])

const searchQuery = ref('')
const files = ref([])
const loading = ref(false)
const selecting = ref(null)
let searchTimeout = null

onMounted(() => {
  loadFiles()
  document.addEventListener('keydown', onEscape)
})

onUnmounted(() => {
  document.removeEventListener('keydown', onEscape)
  if (searchTimeout) clearTimeout(searchTimeout)
})

function onEscape(e) {
  if (e.key === 'Escape') emit('close')
}

async function loadFiles() {
  loading.value = true
  try {
    const res = await powerupsApi.searchDriveFiles(searchQuery.value || undefined)
    files.value = res.data.data || []
  } catch {
    files.value = []
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(loadFiles, 400)
}

async function selectFile(file) {
  selecting.value = file.id
  emit('select', {
    file_id: file.id,
    name: file.name,
    mime_type: file.mimeType,
    size: parseInt(file.size || 0),
    web_view_link: file.webViewLink,
  })
}

function getFileIcon(mimeType) {
  if (!mimeType) return File
  if (mimeType.startsWith('image/')) return Image
  if (mimeType.includes('spreadsheet') || mimeType.includes('excel')) return FileSpreadsheet
  if (mimeType.includes('presentation') || mimeType.includes('powerpoint')) return Presentation
  if (mimeType.includes('folder')) return Folder
  if (mimeType.includes('document') || mimeType.includes('word') || mimeType.includes('text') || mimeType.includes('pdf')) return FileText
  return File
}

function formatSize(bytes) {
  if (!bytes || bytes === 0) return ''
  const num = parseInt(bytes)
  if (num < 1024) return num + ' B'
  if (num < 1048576) return (num / 1024).toFixed(1) + ' KB'
  return (num / 1048576).toFixed(1) + ' MB'
}
</script>

<template>
  <Teleport to="body">
    <div class="fixed inset-0 z-[60] flex items-center justify-center px-4" @click.self="emit('close')">
      <div class="fixed inset-0 bg-black/60" @click="emit('close')" />

      <div class="relative bg-[#161b22] border border-[#444c56] rounded-xl shadow-2xl w-full max-w-lg max-h-[70vh] flex flex-col z-10">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-[#444c56] flex-shrink-0">
          <h3 class="text-base font-semibold text-[#e6edf3]">Google Drive</h3>
          <button class="w-7 h-7 rounded-full flex items-center justify-center bg-[#2d333b] hover:bg-[#444c56] text-[#8b949e]" @click="emit('close')">
            <X :size="14" />
          </button>
        </div>

        <!-- Search -->
        <div class="p-3 border-b border-[#444c56] flex-shrink-0">
          <div class="relative">
            <Search :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#545d68]" />
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Buscar arquivos..."
              class="w-full pl-9 pr-3 py-2 rounded-lg bg-[#0d1117] border border-[#444c56] text-[#e6edf3] text-sm placeholder-[#545d68] focus:border-[#6366f1] focus:outline-none"
              @input="onSearchInput"
            />
          </div>
        </div>

        <!-- Files list -->
        <div class="flex-1 overflow-y-auto p-2">
          <div v-if="loading" class="flex items-center justify-center py-8">
            <div class="animate-spin w-6 h-6 border-2 border-[#6366f1] border-t-transparent rounded-full" />
          </div>

          <div v-else-if="files.length === 0" class="text-center py-8">
            <File :size="32" class="text-[#6e7681] mx-auto mb-2" />
            <p class="text-sm text-[#6e7681]">Nenhum arquivo encontrado</p>
          </div>

          <div v-else class="space-y-0.5">
            <button
              v-for="file in files"
              :key="file.id"
              class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-[#2d333b] transition-colors text-left disabled:opacity-50"
              :disabled="selecting === file.id"
              @click="selectFile(file)"
            >
              <component :is="getFileIcon(file.mimeType)" :size="18" class="text-[#8b949e] flex-shrink-0" />
              <div class="flex-1 min-w-0">
                <p class="text-sm text-[#e6edf3] truncate">{{ file.name }}</p>
                <p class="text-xs text-[#6e7681]">{{ formatSize(file.size) }}</p>
              </div>
              <span v-if="selecting === file.id" class="text-xs text-[#6366f1]">Anexando...</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

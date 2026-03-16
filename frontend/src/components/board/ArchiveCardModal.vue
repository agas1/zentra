<script setup>
import { ref, inject, computed } from 'vue'
import { useBoardsStore } from '../../stores/boards'

const props = defineProps({
  show: { type: Boolean, default: false },
})

const emit = defineEmits(['confirm', 'cancel'])

const isDark = inject('boardIsDark', ref(true))
const boardsStore = useBoardsStore()

const reason = ref('')
const unarchiveAt = ref('')
const unarchiveListId = ref('')

const availableLists = computed(() => {
  return boardsStore.currentBoard?.lists?.filter(l => !l.is_archived) || []
})

function setQuickDate(days) {
  const d = new Date()
  d.setDate(d.getDate() + days)
  unarchiveAt.value = d.toISOString().slice(0, 16)
}

function handleConfirm() {
  emit('confirm', {
    archive_reason: reason.value || null,
    unarchive_at: unarchiveAt.value || null,
    unarchive_list_id: unarchiveListId.value || null,
  })
  reason.value = ''
  unarchiveAt.value = ''
  unarchiveListId.value = ''
}

function handleCancel() {
  emit('cancel')
  reason.value = ''
  unarchiveAt.value = ''
  unarchiveListId.value = ''
}
</script>

<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center">
      <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" @click="handleCancel" />
      <div
        class="relative rounded-xl shadow-2xl w-full max-w-md mx-4 overflow-hidden border"
        :class="isDark ? 'bg-[#161b22] border-[#444c56]' : 'bg-white border-gray-200'"
      >
        <!-- Header -->
        <div class="flex items-center gap-3 px-5 pt-5 pb-2">
          <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 bg-[#f85149]/10">
            <svg class="w-5 h-5 text-[#f85149]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
          </div>
          <h3 class="text-base font-semibold" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">
            Arquivar cartao
          </h3>
        </div>

        <!-- Body -->
        <div class="px-5 py-3 space-y-4">
          <!-- Reason -->
          <div>
            <label class="text-xs font-semibold uppercase mb-1 block"
              :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
              Motivo do arquivamento
            </label>
            <textarea
              v-model="reason"
              class="w-full text-sm rounded-lg px-3 py-2 border resize-none focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
              :class="isDark
                ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3] placeholder-[#545d68]'
                : 'bg-white border-gray-200 text-gray-900 placeholder-gray-400'"
              rows="2"
              placeholder="Opcional: descreva por que este card esta sendo arquivado..."
            />
          </div>

          <!-- Unarchive date -->
          <div>
            <label class="text-xs font-semibold uppercase mb-1 block"
              :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
              Desarquivar automaticamente em
            </label>
            <div class="flex gap-1.5 mb-2">
              <button
                v-for="opt in [
                  { label: '1 dia', days: 1 },
                  { label: '7 dias', days: 7 },
                  { label: '30 dias', days: 30 },
                ]"
                :key="opt.days"
                class="flex-1 text-xs py-1.5 px-2 rounded-lg transition-colors font-medium"
                :class="isDark
                  ? 'bg-[#2d333b] hover:bg-[#444c56] text-[#e6edf3]'
                  : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                @click="setQuickDate(opt.days)"
              >
                {{ opt.label }}
              </button>
              <button
                class="flex-1 text-xs py-1.5 px-2 rounded-lg transition-colors font-medium"
                :class="isDark
                  ? 'bg-[#2d333b] hover:bg-[#444c56] text-[#e6edf3]'
                  : 'bg-gray-100 hover:bg-gray-200 text-gray-700'"
                @click="unarchiveAt = ''"
              >
                Limpar
              </button>
            </div>
            <input
              v-model="unarchiveAt"
              type="datetime-local"
              class="w-full text-sm rounded-lg px-3 py-2 border focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
              :class="isDark
                ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3]'
                : 'bg-white border-gray-200 text-gray-900'"
            />
          </div>

          <!-- Target list -->
          <div>
            <label class="text-xs font-semibold uppercase mb-1 block"
              :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
              Restaurar na lista
            </label>
            <select
              v-model="unarchiveListId"
              class="w-full text-sm rounded-lg px-3 py-2 border appearance-none cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#6366f1]/50"
              :class="isDark
                ? 'bg-[#0d1117] border-[#444c56] text-[#e6edf3]'
                : 'bg-white border-gray-200 text-gray-900'"
            >
              <option value="">Lista original (padrao)</option>
              <option
                v-for="list in availableLists"
                :key="list.id"
                :value="list.id"
              >
                {{ list.name }}
              </option>
            </select>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-2 px-5 py-4 border-t"
          :class="isDark ? 'border-[#2d333b]' : 'border-gray-200'">
          <button
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors"
            :class="isDark
              ? 'bg-[#2d333b] text-[#e6edf3] hover:bg-[#3d444d]'
              : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
            @click="handleCancel"
          >
            Cancelar
          </button>
          <button
            class="px-4 py-2 text-sm font-medium rounded-lg transition-colors bg-[#f85149] hover:bg-[#da3633] text-white"
            @click="handleConfirm"
          >
            Arquivar
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

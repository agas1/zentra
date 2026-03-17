<script setup>
import { computed, ref, inject } from 'vue'
import { useMediaUrl } from '../../composables/useMediaUrl'

const props = defineProps({
  card: { type: Object, required: true },
})
const emit = defineEmits(['click'])

const isDark = inject('boardIsDark', ref(true))
const { fullUrl } = useMediaUrl()

const hasChecklist = computed(() => props.card.checklists?.some(c => c.items?.length > 0))
const checklistTotal = computed(() => props.card.checklists?.reduce((sum, c) => sum + (c.items?.length || 0), 0) || 0)
const checklistChecked = computed(() => props.card.checklists?.reduce((sum, c) => sum + (c.items?.filter(i => i.is_checked).length || 0), 0) || 0)

const hasSubCards = computed(() => props.card.sub_cards?.length > 0)
const subCardsTotal = computed(() => props.card.sub_cards?.length || 0)
const subCardsDone = computed(() => props.card.sub_cards?.filter(s => s.due_completed).length || 0)

const isOverdue = computed(() => props.card.due_date && !props.card.due_completed && new Date(props.card.due_date) < new Date())
const isDueSoon = computed(() => props.card.due_date && !props.card.due_completed && !isOverdue.value &&
  (new Date(props.card.due_date) - new Date()) < 86400000)

const hasBadges = computed(() => props.card.due_date || props.card.description || props.card.attachments?.length || hasChecklist.value || hasSubCards.value || props.card.members?.length || props.card.comments?.length)

const avatarColors = ['#0073ea', '#00c875', '#e44258', '#fdab3d', '#a25ddc', '#037f4c', '#579bfc', '#ff642e']
function getAvatarColor(name) {
  const hash = name?.split('').reduce((acc, c) => acc + c.charCodeAt(0), 0) || 0
  return avatarColors[hash % avatarColors.length]
}

function formatDueDate(date) {
  if (!date) return ''
  const d = new Date(date)
  return d.toLocaleDateString('pt-BR', { day: 'numeric', month: 'short' })
}
</script>

<template>
  <article
    class="board-card-item group/card rounded-xl cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0 active:shadow-sm relative border backdrop-blur-md"
    :class="isDark
      ? 'bg-white/[0.05] border-white/[0.06] shadow-black/10 hover:bg-white/[0.08] hover:border-white/[0.12]'
      : 'bg-white/60 border-black/[0.05] shadow-gray-200/50 hover:bg-white/80 hover:border-black/[0.1]'"
    role="button"
    tabindex="0"
    :aria-label="card.title"
    @click="emit('click', card)"
    @keydown.enter="emit('click', card)"
    @keydown.space.prevent="emit('click', card)"
  >
    <!-- Edit pencil on hover -->
    <button
      class="absolute top-1.5 right-1.5 w-7 h-7 rounded-md flex items-center justify-center opacity-0 group-hover/card:opacity-100 transition-opacity z-10"
      :class="isDark ? 'bg-[#2d333b] hover:bg-[#444c56]' : 'bg-gray-200 hover:bg-gray-300'"
      @click.stop="emit('click', card)"
      aria-label="Editar cartao"
    >
      <svg class="w-3.5 h-3.5" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
    </button>

    <!-- Cover Image -->
    <div v-if="card.cover_url" class="h-36 rounded-t-lg bg-cover bg-center" role="img" :aria-label="`Capa do cartao ${card.title}`" :style="{ backgroundImage: `url(${fullUrl(card.cover_url)})` }" />

    <div class="p-2">
      <!-- Labels as colored bars (Trello style) -->
      <div v-if="card.labels?.length" class="flex flex-wrap gap-1 mb-1.5">
        <span
          v-for="label in card.labels"
          :key="label.id"
          class="inline-flex items-center h-4 min-w-[40px] rounded-sm px-2 text-[10px] font-semibold text-white"
          :style="{ backgroundColor: label.color }"
        >
          {{ label.name || '' }}
        </span>
      </div>

      <!-- Title -->
      <p class="text-sm leading-snug mb-1" :class="isDark ? 'text-[#e6edf3]' : 'text-gray-900'">{{ card.title }}</p>

      <!-- Badges row -->
      <div v-if="hasBadges" class="flex items-center gap-1.5 text-xs flex-wrap" :class="isDark ? 'text-[#8b949e]' : 'text-gray-500'">
        <!-- Due Date -->
        <span
          v-if="card.due_date"
          class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-sm text-[11px] font-medium"
          :class="card.due_completed ? 'bg-[#3fb950] text-white' : isOverdue ? 'bg-[#f85149] text-white' : isDueSoon ? 'bg-[#d29922] text-white' : (isDark ? 'bg-[#2d333b] text-[#8b949e]' : 'bg-gray-200 text-gray-600')"
        >
          <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          {{ formatDueDate(card.due_date) }}
        </span>

        <!-- Description -->
        <span v-if="card.description" class="inline-flex" aria-label="Tem descricao">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h7"/></svg>
        </span>

        <!-- Comments -->
        <span v-if="card.comments?.length" class="inline-flex items-center gap-0.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
          {{ card.comments.length }}
        </span>

        <!-- Attachments -->
        <span v-if="card.attachments?.length" class="inline-flex items-center gap-0.5">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
          {{ card.attachments.length }}
        </span>

        <!-- Checklist progress -->
        <span
          v-if="hasChecklist"
          class="inline-flex items-center gap-0.5 px-1 rounded-sm"
          :class="checklistChecked === checklistTotal && checklistTotal > 0 ? 'bg-[#3fb950] text-white' : ''"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
          {{ checklistChecked }}/{{ checklistTotal }}
        </span>

        <!-- Sub-cards -->
        <span
          v-if="hasSubCards"
          class="inline-flex items-center gap-0.5 px-1 rounded-sm"
          :class="subCardsDone === subCardsTotal && subCardsTotal > 0 ? 'bg-[#3fb950] text-white' : ''"
          :aria-label="`Sub-tarefas: ${subCardsDone} de ${subCardsTotal}`"
        >
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
          {{ subCardsDone }}/{{ subCardsTotal }}
        </span>

        <div class="flex-1" />

        <!-- Members -->
        <div v-if="card.members?.length" class="flex -space-x-1.5">
          <div
            v-for="member in card.members.slice(0, 3)"
            :key="member.id"
            class="w-7 h-7 rounded-full text-white text-[10px] flex items-center justify-center ring-2 font-semibold"
            :class="isDark ? 'ring-[#1c2128]' : 'ring-white'"
            :style="{ backgroundColor: getAvatarColor(member.name) }"
            :aria-label="member.name"
          >
            {{ member.name?.charAt(0)?.toUpperCase() }}
          </div>
          <div
            v-if="card.members.length > 3"
            class="w-7 h-7 rounded-full text-[10px] flex items-center justify-center ring-2 font-medium"
            :class="isDark ? 'bg-[#444c56] text-[#8b949e] ring-[#1c2128]' : 'bg-gray-300 text-gray-600 ring-white'"
          >
            +{{ card.members.length - 3 }}
          </div>
        </div>
      </div>
    </div>
  </article>
</template>

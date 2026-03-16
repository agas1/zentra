<script setup>
import { computed } from 'vue'
import { usePlanLimits } from '../../composables/usePlanLimits'
import {
  LayoutGrid, Paintbrush, HardDrive, Users, Tags, HeadphonesIcon,
  Zap, FormInput, KeyRound, ShieldCheck, ArrowRight, Sparkles,
} from 'lucide-vue-next'

const props = defineProps({
  context: { type: String, default: 'dashboard' },
})

const { planName } = usePlanLimits()

const isFreePlan = computed(() => planName.value === 'Free')

const variants = {
  dashboard: {
    title: 'Potencialize sua gestao',
    subtitle: 'Desbloqueie ferramentas avancadas para gerenciar seus projetos com mais eficiencia.',
    benefits: [
      { icon: LayoutGrid, label: 'Quadros ilimitados', desc: 'Sem limite de projetos' },
      { icon: Zap, label: 'Automacoes', desc: 'Automatize tarefas repetitivas' },
      { icon: FormInput, label: 'Campos personalizados', desc: 'Adapte cards ao seu fluxo' },
    ],
  },
  boards: {
    title: 'Precisa de mais espaco?',
    subtitle: 'Expanda seus limites e personalize a experiencia do seu workspace.',
    benefits: [
      { icon: LayoutGrid, label: 'Quadros ilimitados', desc: 'Crie quantos precisar' },
      { icon: Paintbrush, label: 'Backgrounds custom', desc: 'Personalize seus quadros' },
      { icon: HardDrive, label: 'Ate 100 GB storage', desc: 'Mais espaco para arquivos' },
    ],
  },
  settings: {
    title: 'Expanda seu time',
    subtitle: 'Convide mais pessoas e desbloqueie recursos de equipe.',
    benefits: [
      { icon: Users, label: 'Membros ilimitados', desc: 'Time sem restricoes' },
      { icon: Tags, label: 'Etiquetas ilimitadas', desc: 'Organize sem limites' },
      { icon: HeadphonesIcon, label: 'Suporte prioritario', desc: 'Atendimento dedicado' },
    ],
  },
  chat: {
    title: 'Comunique-se melhor',
    subtitle: 'Recursos avancados para equipes que precisam de mais.',
    benefits: [
      { icon: HeadphonesIcon, label: 'Suporte prioritario', desc: 'Resposta rapida' },
      { icon: KeyRound, label: 'Integracoes via API', desc: 'Conecte suas ferramentas' },
      { icon: ShieldCheck, label: 'SSO corporativo', desc: 'Login unificado seguro' },
    ],
  },
}

const variant = computed(() => variants[props.context] || variants.dashboard)
</script>

<template>
  <div v-if="isFreePlan" class="bg-[#161b22] border border-[#388bfd]/20 rounded-xl overflow-hidden">
    <div class="flex border-l-[3px] border-l-[#388bfd]">
      <div class="flex-1 p-5">
        <!-- Header -->
        <div class="flex items-center gap-2 mb-1">
          <Sparkles :size="16" class="text-[#388bfd]" />
          <h3 class="text-sm font-bold text-[#e6edf3]">{{ variant.title }}</h3>
        </div>
        <p class="text-xs text-[#8b949e] mb-4">{{ variant.subtitle }}</p>

        <!-- Benefits grid -->
        <div class="grid grid-cols-3 gap-3 mb-4">
          <div v-for="b in variant.benefits" :key="b.label" class="flex items-start gap-2">
            <div class="w-7 h-7 rounded-lg bg-[#388bfd]/10 flex items-center justify-center flex-shrink-0 mt-0.5">
              <component :is="b.icon" :size="14" class="text-[#388bfd]" />
            </div>
            <div class="min-w-0">
              <p class="text-xs font-medium text-[#e6edf3] leading-tight">{{ b.label }}</p>
              <p class="text-[10px] text-[#6e7681] leading-tight mt-0.5">{{ b.desc }}</p>
            </div>
          </div>
        </div>

        <!-- CTA -->
        <router-link
          to="/plans"
          class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-[#6366f1] text-white hover:bg-[#4f46e5] transition-colors"
        >
          Ver planos
          <ArrowRight :size="14" />
        </router-link>
      </div>
    </div>
  </div>
</template>

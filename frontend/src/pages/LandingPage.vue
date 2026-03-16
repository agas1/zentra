<template>
  <div class="min-h-screen bg-[#0d1117] text-[#e6edf3] overflow-hidden">
    <!-- Navbar -->
    <nav
      class="fixed top-0 w-full z-50 transition-all duration-300"
      :class="scrolled ? 'bg-[#0d1117]/80 backdrop-blur-xl border-b border-[#444c56]/50' : ''"
    >
      <div class="max-w-7xl mx-auto px-6 lg:px-8 h-16 flex items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-2.5">
          <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-[#6366f1] to-[#388bfd] flex items-center justify-center">
            <Palette :size="18" color="#fff" />
          </div>
          <span class="text-lg font-bold">Zentra</span>
        </div>

        <!-- Desktop Nav -->
        <div class="hidden md:flex items-center gap-8">
          <a href="#features" class="text-sm text-[#8b949e] hover:text-[#e6edf3] transition-colors duration-200">Recursos</a>
          <a href="#how-it-works" class="text-sm text-[#8b949e] hover:text-[#e6edf3] transition-colors duration-200">Como funciona</a>
          <a href="#pricing" class="text-sm text-[#8b949e] hover:text-[#e6edf3] transition-colors duration-200">Planos</a>
        </div>

        <!-- CTA -->
        <div class="flex items-center gap-3">
          <router-link
            to="/login"
            class="text-sm text-[#8b949e] hover:text-[#e6edf3] transition-colors duration-200 hidden sm:block"
          >
            Entrar
          </router-link>
          <router-link
            to="/register"
            class="h-9 px-5 bg-[#6366f1] hover:bg-[#4f46e5] text-white text-sm font-medium rounded-lg transition-all duration-200 flex items-center gap-1.5 hover:shadow-lg hover:shadow-[#6366f1]/25 hover:-translate-y-0.5 active:translate-y-0"
          >
            Comece gratis
            <ArrowRight :size="14" />
          </router-link>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex flex-col items-center justify-center px-6 pt-16">
      <!-- Grid pattern background -->
      <div
        class="absolute inset-0 opacity-[0.03]"
        :style="{
          backgroundImage: 'linear-gradient(rgba(255,255,255,0.5) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.5) 1px, transparent 1px)',
          backgroundSize: '80px 80px'
        }"
      />

      <!-- Radial glow -->
      <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[800px] h-[600px] bg-[#6366f1]/[0.07] rounded-full blur-[120px]" />
      <div class="absolute bottom-1/4 right-1/4 w-[400px] h-[400px] bg-[#388bfd]/[0.05] rounded-full blur-[100px]" />

      <!-- Floating shapes -->
      <div class="absolute top-[15%] left-[10%] w-20 h-20 rounded-full border border-[#6366f1]/20 animate-float-slow" />
      <div class="absolute bottom-[20%] right-[12%] w-32 h-32 rounded-full border border-[#388bfd]/15 animate-float-delay" />
      <div class="absolute top-[60%] left-[8%] w-6 h-6 rounded-full bg-[#6366f1]/15 animate-float animate-blob" />
      <div class="absolute top-[25%] right-[20%] w-4 h-4 rounded-full bg-[#388bfd]/20 animate-pulse-soft" />

      <!-- Content -->
      <div class="relative z-10 text-center max-w-5xl mx-auto">
        <!-- Badge -->
        <div class="inline-flex items-center gap-2 bg-[#161b22] border border-[#444c56]/60 rounded-full px-4 py-1.5 mb-8 animate-fade-in-down overflow-hidden relative">
          <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/[0.06] to-transparent lp-shimmer" />
          <span class="relative w-2 h-2 rounded-full bg-[#3fb950]">
            <span class="absolute inset-0 rounded-full bg-[#3fb950] animate-ping opacity-75" />
          </span>
          <span class="relative text-xs font-medium text-[#8b949e]">Plataforma de gestao criativa</span>
        </div>

        <!-- Title -->
        <h1 class="text-5xl sm:text-6xl md:text-7xl lg:text-[5.5rem] font-bold leading-[1.05] tracking-tight mb-6">
          <span class="lp-title-line animate-fade-in-up stagger-1">Organize.</span>
          <br />
          <span class="lp-title-line animate-fade-in-up stagger-3"><span class="italic font-light">Colabore</span>.</span>
          <br />
          <span class="lp-title-line animate-fade-in-up stagger-5"><span class="gradient-text">Entregue</span>.</span>
        </h1>

        <!-- Subtitle -->
        <p class="text-lg sm:text-xl text-[#8b949e] max-w-2xl mx-auto mb-10 animate-fade-in-up stagger-6 leading-relaxed">
          A plataforma que simplifica a gestao de projetos criativos.
          <span class="text-[#e6edf3]">Quadros, automacoes, chat e muito mais</span> — tudo em um so lugar.
        </p>

        <!-- CTAs -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-16 animate-fade-in-up stagger-7">
          <router-link
            to="/register"
            class="group h-12 px-8 bg-[#6366f1] hover:bg-[#4f46e5] text-white font-semibold rounded-xl transition-all duration-300 flex items-center gap-2 hover:shadow-xl hover:shadow-[#6366f1]/30 hover:-translate-y-1 active:translate-y-0"
          >
            Comece gratuitamente
            <ArrowRight :size="16" class="group-hover:translate-x-1 transition-transform duration-200" />
          </router-link>
          <a
            href="#how-it-works"
            class="h-12 px-8 border border-[#444c56] hover:border-[#6e7681] text-[#e6edf3] font-semibold rounded-xl transition-all duration-200 flex items-center gap-2 hover:bg-[#161b22]"
          >
            <Play :size="16" />
            Como funciona
          </a>
        </div>

        <!-- Hero visual - App preview -->
        <div class="relative animate-fade-in-up stagger-8">
          <div class="relative rounded-2xl overflow-hidden border border-[#444c56]/60 bg-[#161b22] shadow-2xl shadow-black/40 max-w-4xl mx-auto">
            <!-- Window bar -->
            <div class="flex items-center gap-2 px-4 py-3 bg-[#1c2128] border-b border-[#444c56]/50">
              <div class="w-3 h-3 rounded-full bg-[#f85149]/60" />
              <div class="w-3 h-3 rounded-full bg-[#d29922]/60" />
              <div class="w-3 h-3 rounded-full bg-[#3fb950]/60" />
              <div class="ml-4 flex-1 h-6 bg-[#0d1117] rounded-md flex items-center px-3">
                <span class="text-[10px] text-[#6e7681]">app.zentra.io</span>
              </div>
            </div>

            <!-- Mockup content - Kanban board -->
            <div class="p-6">
              <div class="flex items-center gap-3 mb-6">
                <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-[#6366f1] to-[#388bfd] flex items-center justify-center">
                  <Palette :size="14" color="#fff" />
                </div>
                <div>
                  <div class="text-sm font-semibold text-[#e6edf3]">Campanha de Marketing</div>
                  <div class="text-xs text-[#6e7681]">12 tarefas · 3 membros</div>
                </div>
              </div>

              <div class="grid grid-cols-3 gap-4">
                <!-- Column 1 -->
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#d29922]" />
                    <span class="text-xs font-medium text-[#8b949e]">A FAZER</span>
                    <span class="text-[10px] text-[#6e7681] bg-[#2d333b] px-1.5 py-0.5 rounded-full ml-auto">3</span>
                  </div>
                  <div class="space-y-2">
                    <div class="p-3 rounded-lg bg-[#0d1117] border border-[#2d333b] hover:border-[#444c56] transition-colors">
                      <div class="text-xs text-[#e6edf3] mb-2">Criar briefing visual</div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] text-[#6e7681] bg-[#2d333b] px-2 py-0.5 rounded-full">Design</span>
                        <div class="w-5 h-5 rounded-full bg-[#6366f1]/20 border border-[#6366f1]/40" />
                      </div>
                    </div>
                    <div class="p-3 rounded-lg bg-[#0d1117] border border-[#2d333b]">
                      <div class="text-xs text-[#e6edf3] mb-2">Definir paleta de cores</div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] text-[#6e7681] bg-[#2d333b] px-2 py-0.5 rounded-full">Branding</span>
                        <div class="w-5 h-5 rounded-full bg-[#388bfd]/20 border border-[#388bfd]/40" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Column 2 -->
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#388bfd]" />
                    <span class="text-xs font-medium text-[#8b949e]">EM PROGRESSO</span>
                    <span class="text-[10px] text-[#6e7681] bg-[#2d333b] px-1.5 py-0.5 rounded-full ml-auto">2</span>
                  </div>
                  <div class="space-y-2">
                    <div class="p-3 rounded-lg bg-[#0d1117] border border-[#388bfd]/30 shadow-sm shadow-[#388bfd]/10">
                      <div class="text-xs text-[#e6edf3] mb-2">Landing page design</div>
                      <div class="w-full h-1.5 bg-[#2d333b] rounded-full mb-2">
                        <div class="h-full w-3/5 bg-[#388bfd] rounded-full" />
                      </div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] text-[#6e7681]">60%</span>
                        <div class="w-5 h-5 rounded-full bg-[#3fb950]/20 border border-[#3fb950]/40" />
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Column 3 -->
                <div>
                  <div class="flex items-center gap-2 mb-3">
                    <div class="w-2.5 h-2.5 rounded-full bg-[#3fb950]" />
                    <span class="text-xs font-medium text-[#8b949e]">CONCLUIDO</span>
                    <span class="text-[10px] text-[#6e7681] bg-[#2d333b] px-1.5 py-0.5 rounded-full ml-auto">4</span>
                  </div>
                  <div class="space-y-2">
                    <div class="p-3 rounded-lg bg-[#0d1117] border border-[#2d333b] opacity-70">
                      <div class="text-xs text-[#e6edf3] mb-2 line-through">Pesquisa de mercado</div>
                      <div class="flex items-center justify-between">
                        <span class="text-[10px] text-[#3fb950]">Aprovado</span>
                        <div class="w-5 h-5 rounded-full bg-[#d29922]/20 border border-[#d29922]/40" />
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Glow under preview -->
          <div class="absolute -bottom-12 left-1/2 -translate-x-1/2 w-3/4 h-24 bg-[#6366f1]/10 blur-[60px] rounded-full" />
        </div>
      </div>

      <!-- Scroll indicator -->
      <div class="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-2 animate-fade-in" style="animation-delay: 1.2s">
        <span class="text-[10px] text-[#6e7681] uppercase tracking-[0.3em]">Scroll</span>
        <div class="w-5 h-8 rounded-full border border-[#444c56] flex items-start justify-center p-1">
          <div class="w-1 h-2 rounded-full bg-[#8b949e] lp-scroll-dot" />
        </div>
      </div>
    </section>

    <!-- Logos / Social Proof -->
    <section class="py-16 border-y border-[#444c56]/30">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <p class="text-center text-xs text-[#6e7681] uppercase tracking-[0.3em] mb-8">Para quem e o Zentra</p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
          <div v-for="persona in personas" :key="persona.title" class="flex flex-col items-center text-center p-6 rounded-xl border border-transparent hover:border-[#444c56]/50 hover:bg-[#161b22]/50 transition-all duration-300">
            <div class="w-12 h-12 rounded-xl bg-[#161b22] border border-[#444c56]/50 flex items-center justify-center mb-3">
              <component :is="persona.icon" :size="22" class="text-[#6366f1]" />
            </div>
            <span class="text-sm font-medium text-[#e6edf3] mb-1">{{ persona.title }}</span>
            <span class="text-xs text-[#6e7681]">{{ persona.desc }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-24 sm:py-32 relative">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <!-- Section header -->
        <div class="text-center mb-16">
          <p class="text-xs text-[#6366f1] uppercase tracking-[0.3em] font-medium mb-3">Recursos</p>
          <h2 class="text-4xl sm:text-5xl md:text-6xl font-light tracking-tight mb-4">
            Tudo que voce <span class="gradient-text font-semibold">precisa</span>
          </h2>
          <p class="text-[#8b949e] text-lg max-w-xl mx-auto">Ferramentas poderosas para cada etapa do seu fluxo criativo.</p>
        </div>

        <!-- Bento Grid -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 md:gap-5">
          <!-- Feature 1: Kanban Boards - Large -->
          <div class="md:col-span-7 group relative rounded-2xl border border-[#444c56]/40 bg-[#161b22]/80 p-8 overflow-hidden hover:border-[#444c56] transition-all duration-500 lp-card-reveal">
            <div class="absolute inset-0 bg-gradient-to-br from-[#6366f1]/[0.03] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#6366f1]/10 border border-[#6366f1]/20 flex items-center justify-center mb-5">
                <LayoutDashboard :size="20" class="text-[#6366f1]" />
              </div>
              <h3 class="text-xl font-semibold mb-2">Quadros Kanban</h3>
              <p class="text-[#8b949e] text-sm leading-relaxed mb-6">
                Visualize seu fluxo de trabalho com quadros personalizaveis. Arraste e solte para mover tarefas entre colunas.
              </p>
              <!-- Mini kanban visual -->
              <div class="flex gap-3">
                <div v-for="col in ['A Fazer', 'Fazendo', 'Pronto']" :key="col" class="flex-1 rounded-lg bg-[#0d1117]/60 border border-[#2d333b] p-2">
                  <div class="text-[10px] text-[#6e7681] font-medium mb-2">{{ col }}</div>
                  <div v-for="i in (col === 'Fazendo' ? 1 : 2)" :key="i" class="h-6 rounded bg-[#2d333b]/60 mb-1.5 group-hover:bg-[#2d333b] transition-colors" />
                </div>
              </div>
            </div>
          </div>

          <!-- Feature 2: Real-time Chat -->
          <div class="md:col-span-5 group relative rounded-2xl border border-[#444c56]/40 bg-[#161b22]/80 p-8 overflow-hidden hover:border-[#444c56] transition-all duration-500 lp-card-reveal" style="animation-delay: 0.1s">
            <div class="absolute inset-0 bg-gradient-to-br from-[#388bfd]/[0.03] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#388bfd]/10 border border-[#388bfd]/20 flex items-center justify-center mb-5">
                <MessageCircle :size="20" class="text-[#388bfd]" />
              </div>
              <h3 class="text-xl font-semibold mb-2">Chat integrado</h3>
              <p class="text-[#8b949e] text-sm leading-relaxed mb-6">
                Comunique-se com sua equipe sem sair da plataforma. Mensagens em tempo real.
              </p>
              <!-- Chat preview -->
              <div class="space-y-2">
                <div class="flex items-start gap-2">
                  <div class="w-6 h-6 rounded-full bg-[#6366f1]/30 flex-shrink-0 mt-0.5" />
                  <div class="bg-[#0d1117]/60 border border-[#2d333b] rounded-lg rounded-tl-none px-3 py-2">
                    <span class="text-[11px] text-[#8b949e]">O layout ficou otimo!</span>
                  </div>
                </div>
                <div class="flex items-start gap-2 justify-end">
                  <div class="bg-[#6366f1]/10 border border-[#6366f1]/20 rounded-lg rounded-tr-none px-3 py-2">
                    <span class="text-[11px] text-[#8b949e]">Obrigado! Vou ajustar as cores</span>
                  </div>
                  <div class="w-6 h-6 rounded-full bg-[#388bfd]/30 flex-shrink-0 mt-0.5" />
                </div>
              </div>
            </div>
          </div>

          <!-- Feature 3: Automations -->
          <div class="md:col-span-4 group relative rounded-2xl border border-[#444c56]/40 bg-[#161b22]/80 p-8 overflow-hidden hover:border-[#444c56] transition-all duration-500 lp-card-reveal" style="animation-delay: 0.2s">
            <div class="absolute inset-0 bg-gradient-to-br from-[#3fb950]/[0.03] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#3fb950]/10 border border-[#3fb950]/20 flex items-center justify-center mb-5">
                <Zap :size="20" class="text-[#3fb950]" />
              </div>
              <h3 class="text-xl font-semibold mb-2">Automacoes</h3>
              <p class="text-[#8b949e] text-sm leading-relaxed">
                Automatize tarefas repetitivas. Mova cards, notifique pessoas, atualize status automaticamente.
              </p>
            </div>
          </div>

          <!-- Feature 4: Templates -->
          <div class="md:col-span-4 group relative rounded-2xl border border-[#444c56]/40 bg-[#161b22]/80 p-8 overflow-hidden hover:border-[#444c56] transition-all duration-500 lp-card-reveal" style="animation-delay: 0.3s">
            <div class="absolute inset-0 bg-gradient-to-br from-[#d29922]/[0.03] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#d29922]/10 border border-[#d29922]/20 flex items-center justify-center mb-5">
                <FileText :size="20" class="text-[#d29922]" />
              </div>
              <h3 class="text-xl font-semibold mb-2">Templates</h3>
              <p class="text-[#8b949e] text-sm leading-relaxed">
                Comece rapido com templates prontos para agencias, studios e freelancers.
              </p>
            </div>
          </div>

          <!-- Feature 5: Dashboard -->
          <div class="md:col-span-4 group relative rounded-2xl border border-[#444c56]/40 bg-[#161b22]/80 p-8 overflow-hidden hover:border-[#444c56] transition-all duration-500 lp-card-reveal" style="animation-delay: 0.4s">
            <div class="absolute inset-0 bg-gradient-to-br from-[#f85149]/[0.03] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500" />
            <div class="relative z-10">
              <div class="w-10 h-10 rounded-xl bg-[#f85149]/10 border border-[#f85149]/20 flex items-center justify-center mb-5">
                <BarChart3 :size="20" class="text-[#f85149]" />
              </div>
              <h3 class="text-xl font-semibold mb-2">Dashboard</h3>
              <p class="text-[#8b949e] text-sm leading-relaxed">
                Metricas de producao, prazos e performance da equipe em uma visao centralizada.
              </p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How it Works -->
    <section id="how-it-works" class="py-24 sm:py-32 relative">
      <!-- Subtle divider glow -->
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-px bg-gradient-to-r from-transparent via-[#444c56] to-transparent" />

      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16">
          <p class="text-xs text-[#388bfd] uppercase tracking-[0.3em] font-medium mb-3">Como funciona</p>
          <h2 class="text-4xl sm:text-5xl md:text-6xl font-light tracking-tight mb-4">
            Simples de <span class="italic">comecar</span>
          </h2>
          <p class="text-[#8b949e] text-lg max-w-xl mx-auto">Tres passos para transformar a gestao dos seus projetos criativos.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-8 md:gap-12">
          <div v-for="(step, index) in steps" :key="step.title" class="relative text-center lp-card-reveal" :style="{ animationDelay: index * 0.15 + 's' }">
            <!-- Step number -->
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl border border-[#444c56]/60 bg-[#161b22] mb-6 relative">
              <span class="text-2xl font-light text-[#6e7681]">{{ index + 1 }}</span>
              <div class="absolute -inset-px rounded-2xl bg-gradient-to-br from-[#6366f1]/20 to-transparent opacity-0 hover:opacity-100 transition-opacity" />
            </div>

            <!-- Connector line -->
            <div v-if="index < 2" class="hidden md:block absolute top-7 left-[calc(50%+40px)] w-[calc(100%-80px)] h-px bg-gradient-to-r from-[#444c56]/60 to-[#444c56]/20" />

            <h3 class="text-lg font-semibold mb-2">{{ step.title }}</h3>
            <p class="text-[#8b949e] text-sm leading-relaxed">{{ step.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Stats -->
    <section class="py-16 border-y border-[#444c56]/30 bg-[#161b22]/30">
      <div class="max-w-5xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
          <div v-for="stat in stats" :key="stat.label" class="text-center">
            <div class="text-3xl sm:text-4xl font-light text-[#e6edf3] mb-1">{{ stat.value }}</div>
            <div class="text-xs text-[#6e7681] uppercase tracking-[0.15em]">{{ stat.label }}</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-24 sm:py-32 relative">
      <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16">
          <p class="text-xs text-[#3fb950] uppercase tracking-[0.3em] font-medium mb-3">Planos</p>
          <h2 class="text-4xl sm:text-5xl md:text-6xl font-light tracking-tight mb-4">
            Preco por <span class="gradient-text font-semibold">workspace</span>
          </h2>
          <p class="text-[#8b949e] text-lg max-w-xl mx-auto">Nao por usuario. Convide seu time todo sem pagar mais.</p>
        </div>

        <div class="grid md:grid-cols-3 gap-6 max-w-5xl mx-auto">
          <div
            v-for="plan in plans"
            :key="plan.name"
            class="relative rounded-2xl border p-8 transition-all duration-300 lp-card-reveal"
            :class="plan.featured
              ? 'border-[#6366f1]/60 bg-[#161b22] shadow-lg shadow-[#6366f1]/10'
              : 'border-[#444c56]/40 bg-[#161b22]/50 hover:border-[#444c56]'"
          >
            <!-- Popular badge -->
            <div v-if="plan.featured" class="absolute -top-3 left-1/2 -translate-x-1/2">
              <span class="bg-[#6366f1] text-white text-xs font-medium px-3 py-1 rounded-full">Popular</span>
            </div>

            <h3 class="text-lg font-semibold mb-1">{{ plan.name }}</h3>
            <p class="text-xs text-[#6e7681] mb-6">{{ plan.desc }}</p>

            <div class="mb-6">
              <span class="text-4xl font-light text-[#e6edf3]">{{ plan.price }}</span>
              <span v-if="plan.period" class="text-sm text-[#6e7681]"> / {{ plan.period }}</span>
            </div>

            <router-link
              to="/register"
              class="block w-full h-11 rounded-xl font-medium text-sm transition-all duration-200 flex items-center justify-center mb-8"
              :class="plan.featured
                ? 'bg-[#6366f1] hover:bg-[#4f46e5] text-white hover:shadow-lg hover:shadow-[#6366f1]/25'
                : 'bg-[#2d333b] hover:bg-[#444c56] text-[#e6edf3] border border-[#444c56]'"
            >
              {{ plan.cta }}
            </router-link>

            <ul class="space-y-3">
              <li v-for="feature in plan.features" :key="feature" class="flex items-start gap-2.5 text-sm text-[#8b949e]">
                <Check :size="16" class="text-[#3fb950] flex-shrink-0 mt-0.5" />
                <span>{{ feature }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </section>

    <!-- Differentials -->
    <section class="py-24 sm:py-32 relative">
      <div class="absolute top-0 left-1/2 -translate-x-1/2 w-1/2 h-px bg-gradient-to-r from-transparent via-[#444c56] to-transparent" />

      <div class="max-w-5xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-16">
          <h2 class="text-4xl sm:text-5xl font-light tracking-tight mb-4">
            Por que <span class="gradient-text font-semibold">Zentra</span>?
          </h2>
        </div>

        <div class="grid sm:grid-cols-3 gap-8">
          <div v-for="diff in differentials" :key="diff.title" class="text-center group">
            <div class="w-14 h-14 rounded-2xl border border-[#444c56]/50 bg-[#161b22] flex items-center justify-center mx-auto mb-4 group-hover:border-[#6366f1]/40 transition-colors duration-300">
              <component :is="diff.icon" :size="22" class="text-[#6366f1] group-hover:scale-110 transition-transform duration-300" />
            </div>
            <h3 class="text-base font-semibold mb-2">{{ diff.title }}</h3>
            <p class="text-sm text-[#8b949e] leading-relaxed">{{ diff.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Final CTA -->
    <section class="py-24 sm:py-32 relative">
      <div class="absolute inset-0 bg-gradient-to-b from-transparent via-[#6366f1]/[0.03] to-transparent" />

      <div class="relative z-10 max-w-3xl mx-auto px-6 text-center">
        <h2 class="text-4xl sm:text-5xl md:text-6xl font-light tracking-tight mb-6">
          Pronto para <span class="italic">comecar</span>?
        </h2>
        <p class="text-[#8b949e] text-lg mb-10 max-w-xl mx-auto leading-relaxed">
          Junte-se a equipes criativas que ja usam o Zentra para entregar projetos incriveis.
        </p>
        <router-link
          to="/register"
          class="group inline-flex items-center gap-2 h-14 px-10 bg-[#6366f1] hover:bg-[#4f46e5] text-white font-semibold text-lg rounded-xl transition-all duration-300 hover:shadow-2xl hover:shadow-[#6366f1]/30 hover:-translate-y-1 active:translate-y-0"
        >
          Crie sua conta gratis
          <ArrowRight :size="18" class="group-hover:translate-x-1 transition-transform duration-200" />
        </router-link>
        <p class="text-xs text-[#6e7681] mt-4">Nao precisa de cartao de credito</p>
      </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-[#444c56]/30 py-8">
      <div class="max-w-7xl mx-auto px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-2">
          <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-[#6366f1] to-[#388bfd] flex items-center justify-center">
            <Palette :size="12" color="#fff" />
          </div>
          <span class="text-sm font-semibold">Zentra</span>
        </div>
        <p class="text-xs text-[#6e7681]">&copy; {{ new Date().getFullYear() }} Zentra. Todos os direitos reservados.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import {
  Palette,
  ArrowRight,
  Play,
  LayoutDashboard,
  MessageCircle,
  Zap,
  FileText,
  BarChart3,
  Check,
  Building2,
  User,
  Package,
  DollarSign,
  Shield,
  Sparkles,
} from 'lucide-vue-next'

// Scroll state for navbar
const scrolled = ref(false)

function handleScroll() {
  scrolled.value = window.scrollY > 50
}

// Intersection Observer for scroll-triggered animations
let observer = null

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
  handleScroll()

  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('lp-visible')
          observer.unobserve(entry.target)
        }
      })
    },
    { threshold: 0.1, rootMargin: '0px 0px -50px 0px' }
  )

  document.querySelectorAll('.lp-card-reveal').forEach((el) => {
    observer.observe(el)
  })
})

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
  if (observer) observer.disconnect()
})

// Data
const personas = [
  { icon: Building2, title: 'Agencias', desc: 'Gerencie clientes e equipes' },
  { icon: Palette, title: 'Studios', desc: 'Organize projetos criativos' },
  { icon: User, title: 'Freelancers', desc: 'Controle seus projetos' },
  { icon: Package, title: 'Clientes', desc: 'Acompanhe pedidos e aprovacoes' },
]

const steps = [
  { title: 'Crie seu workspace', desc: 'Cadastre-se gratuitamente e crie um espaco para sua equipe em segundos.' },
  { title: 'Monte seus quadros', desc: 'Crie quadros com colunas personalizadas para cada tipo de projeto.' },
  { title: 'Convide sua equipe', desc: 'Adicione membros, defina permissoes e comece a colaborar.' },
]

const stats = [
  { value: '10k+', label: 'Tarefas criadas' },
  { value: '500+', label: 'Projetos entregues' },
  { value: '99.9%', label: 'Uptime' },
  { value: '4.9', label: 'Avaliacao' },
]

const plans = [
  {
    name: 'Free',
    desc: 'Para testar a plataforma',
    price: 'R$0',
    period: 'mes',
    cta: 'Comece gratis',
    featured: false,
    features: [
      '1 quadro ativo',
      'Ate 50 cards',
      'Chat integrado',
      '1 membro',
    ],
  },
  {
    name: 'Pro',
    desc: 'Para equipes em crescimento',
    price: 'R$97',
    period: 'mes',
    cta: 'Comecar agora',
    featured: true,
    features: [
      'Quadros ilimitados',
      'Cards ilimitados',
      'Membros ilimitados',
      'Automacoes',
      'Campos customizados',
      'Suporte prioritario',
    ],
  },
  {
    name: 'Enterprise',
    desc: 'Para grandes operacoes',
    price: 'Sob consulta',
    period: null,
    cta: 'Fale conosco',
    featured: false,
    features: [
      'Tudo do Pro',
      'SSO / SAML',
      'Acesso a API',
      'SLA dedicado',
      'Onboarding personalizado',
    ],
  },
]

const differentials = [
  { icon: DollarSign, title: 'Preco justo', desc: 'Pague por workspace, nao por usuario. Convide todos sem custo extra.' },
  { icon: Sparkles, title: 'Simples de usar', desc: 'Interface intuitiva sem curva de aprendizado. Comece em minutos.' },
  { icon: Shield, title: 'Dados seguros', desc: 'Infraestrutura segura com backups automaticos e criptografia.' },
]
</script>

<style scoped>
/* LP-specific animations */
.lp-shimmer {
  animation: lpShimmer 3s linear infinite;
}
@keyframes lpShimmer {
  0% { transform: translateX(-100%); }
  100% { transform: translateX(200%); }
}

.lp-scroll-dot {
  animation: lpScrollDot 2s ease-in-out infinite;
}
@keyframes lpScrollDot {
  0%, 100% { transform: translateY(0); opacity: 1; }
  50% { transform: translateY(8px); opacity: 0.3; }
}

/* Scroll-triggered reveal */
.lp-card-reveal {
  opacity: 0;
  transform: translateY(40px);
  transition: opacity 0.7s ease-out, transform 0.7s ease-out;
}
.lp-card-reveal.lp-visible {
  opacity: 1;
  transform: translateY(0);
}

/* Smooth scroll */
:deep(html) {
  scroll-behavior: smooth;
}
</style>

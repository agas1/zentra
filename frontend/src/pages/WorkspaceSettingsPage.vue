<template>
  <div>
    <PageHeader title="Configuracoes" />

    <!-- Tabs -->
    <div class="flex gap-1 mb-6 border-b border-[#444c56]">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        @click="activeTab = tab.key"
        class="flex items-center gap-2 px-4 py-2.5 text-sm font-medium transition-colors border-b-2 -mb-px"
        :class="activeTab === tab.key
          ? 'text-[#6366f1] border-[#6366f1]'
          : 'text-[#8b949e] border-transparent hover:text-[#e6edf3]'"
      >
        <component :is="tab.icon" :size="16" />
        {{ tab.label }}
      </button>
    </div>

    <!-- Tab: Geral -->
    <div v-if="activeTab === 'general'" class="monday-card max-w-lg">
      <h3 class="text-base font-semibold text-[#e6edf3] mb-4">Informacoes do Workspace</h3>
      <form @submit.prevent="handleSaveGeneral" class="space-y-4">
        <div>
          <label class="monday-label">Nome do Workspace</label>
          <input
            v-model="workspaceName"
            type="text"
            required
            class="monday-input"
            placeholder="Nome do workspace"
          />
        </div>
        <button
          type="submit"
          :disabled="savingGeneral"
          class="monday-btn monday-btn-primary disabled:opacity-50"
        >
          <Loader2 v-if="savingGeneral" :size="16" class="animate-spin" />
          {{ savingGeneral ? 'Salvando...' : 'Salvar' }}
        </button>
      </form>
    </div>

    <!-- Tab: Membros -->
    <div v-if="activeTab === 'members'">
      <div class="monday-card">
      <h3 class="text-base font-semibold text-[#e6edf3] mb-4">Membros do Workspace</h3>

      <div v-if="loadingMembers" class="flex items-center justify-center py-8 text-[#8b949e]">
        <Loader2 :size="20" class="animate-spin mr-2" />
        Carregando...
      </div>

      <div v-else class="overflow-x-auto">
        <table class="monday-table w-full">
          <thead>
            <tr>
              <th class="text-left px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Membro</th>
              <th class="text-left px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Email</th>
              <th class="text-center px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Funcao</th>
              <th v-if="workspaceStore.isAdmin" class="text-right px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Acoes</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="member in workspaceStore.members"
              :key="member.id"
              class="border-t border-[#444c56]"
            >
              <td class="px-4 py-3">
                <div class="flex items-center gap-2">
                  <UserAvatar :name="member.name" size="sm" />
                  <span class="font-medium text-sm text-[#e6edf3]">{{ member.name }}</span>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-[#8b949e]">{{ member.email }}</td>
              <td class="px-4 py-3 text-center">
                <span
                  class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                  :style="{
                    backgroundColor: WORKSPACE_ROLES[member.pivot?.role]?.bg || '#c4c4c4',
                    color: WORKSPACE_ROLES[member.pivot?.role]?.text || '#fff',
                  }"
                >
                  {{ WORKSPACE_ROLES[member.pivot?.role]?.label || member.pivot?.role }}
                </span>
              </td>
              <td v-if="workspaceStore.isAdmin" class="px-4 py-3 text-right">
                <div v-if="member.pivot?.role !== 'owner'" class="flex items-center justify-end gap-2">
                  <select
                    :value="member.pivot?.role"
                    @change="handleRoleChange(member.id, ($event.target).value)"
                    class="monday-input text-xs py-1 px-2 w-auto"
                  >
                    <option value="admin">Admin</option>
                    <option value="member">Membro</option>
                  </select>
                  <button
                    @click="confirmRemoveMember(member)"
                    class="monday-btn monday-btn-secondary text-[#f85149] hover:bg-[#f8514926] p-1.5"
                    title="Remover membro"
                  >
                    <Trash2 :size="14" />
                  </button>
                </div>
                <span v-else class="text-xs text-[#6e7681]">-</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      </div>
    </div>

    <!-- Tab: Convites -->
    <div v-if="activeTab === 'invitations'" class="space-y-6">
      <!-- Member limit banner -->
      <div v-if="!canAddMember" class="bg-[#d2992226] border border-[#d2992233] rounded-xl p-4 max-w-lg flex items-center gap-3">
        <svg class="w-5 h-5 text-[#d29922] flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        <div>
          <p class="text-sm text-[#d29922] font-medium">Limite de membros atingido ({{ membersUsed }}/{{ membersLimit }})</p>
          <p class="text-xs text-[#8b949e] mt-0.5">Faca upgrade para adicionar mais membros ao workspace.</p>
          <router-link to="/plans" class="text-xs text-[#6366f1] hover:underline font-medium">Ver planos</router-link>
        </div>
      </div>

      <!-- Invite form -->
      <div class="monday-card max-w-lg">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-base font-semibold text-[#e6edf3]">Convidar Membro</h3>
          <span v-if="!membersUnlimited" class="text-xs text-[#8b949e]">{{ membersUsed }}/{{ membersLimit }} membros</span>
        </div>
        <form @submit.prevent="handleInvite" class="space-y-4">
          <div>
            <label class="monday-label">Email</label>
            <div class="relative">
              <Mail :size="18" class="absolute left-3 top-1/2 -translate-y-1/2 text-[#6e7681]" />
              <input
                v-model="inviteForm.email"
                type="email"
                required
                class="monday-input pl-10"
                placeholder="email@exemplo.com"
              />
            </div>
          </div>
          <div>
            <label class="monday-label">Funcao</label>
            <select v-model="inviteForm.role" required class="monday-input">
              <option value="admin">Admin</option>
              <option value="member">Membro</option>
            </select>
          </div>
          <button
            type="submit"
            :disabled="sendingInvite || !canAddMember"
            class="monday-btn monday-btn-primary disabled:opacity-50"
          >
            <Loader2 v-if="sendingInvite" :size="16" class="animate-spin" />
            {{ sendingInvite ? 'Enviando...' : !canAddMember ? 'Limite atingido' : 'Enviar Convite' }}
          </button>
        </form>
      </div>

      <!-- Pending invitations (only shown when there are invitations) -->
      <div v-if="loadingInvitations || workspaceStore.invitations.length > 0" class="monday-card">
        <h3 class="text-base font-semibold text-[#e6edf3] mb-4">Convites Pendentes</h3>

        <div v-if="loadingInvitations" class="flex items-center justify-center py-8 text-[#8b949e]">
          <Loader2 :size="20" class="animate-spin mr-2" />
          Carregando...
        </div>

        <div v-else class="overflow-x-auto">
          <table class="monday-table w-full">
            <thead>
              <tr>
                <th class="text-left px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Email</th>
                <th class="text-center px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Funcao</th>
                <th class="text-right px-4 py-2.5 text-xs font-medium text-[#8b949e] uppercase tracking-wide">Acoes</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="inv in workspaceStore.invitations"
                :key="inv.id"
                class="border-t border-[#444c56]"
              >
                <td class="px-4 py-3 text-sm text-[#e6edf3]">{{ inv.email }}</td>
                <td class="px-4 py-3 text-center">
                  <span
                    class="inline-block px-2 py-0.5 rounded text-xs font-medium"
                    :style="{
                      backgroundColor: WORKSPACE_ROLES[inv.role]?.bg || '#c4c4c4',
                      color: WORKSPACE_ROLES[inv.role]?.text || '#fff',
                    }"
                  >
                    {{ WORKSPACE_ROLES[inv.role]?.label || inv.role }}
                  </span>
                </td>
                <td class="px-4 py-3 text-right">
                  <button
                    @click="handleCancelInvitation(inv.id)"
                    class="monday-btn monday-btn-secondary text-[#f85149] hover:bg-[#f8514926] text-xs"
                  >
                    Cancelar
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tab: Plano -->
    <div v-if="activeTab === 'plan'" class="max-w-2xl">
      <div v-if="workspaceStore.currentWorkspace?.plan" class="monday-card">
        <!-- Plan header -->
        <div class="flex items-center justify-between pb-4 border-b border-[#444c56]">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-[#6366f1]/20 flex items-center justify-center">
              <Package :size="20" class="text-[#a5b4fc]" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-bold text-[#e6edf3]">{{ workspaceStore.currentWorkspace.plan.name }}</h3>
                <span class="px-2 py-0.5 bg-[#238636]/20 text-[#3fb950] rounded-md text-[10px] font-bold uppercase tracking-wider">Ativo</span>
              </div>
              <p class="text-xs text-[#8b949e]">{{ workspaceStore.currentWorkspace.plan.description }}</p>
            </div>
          </div>
          <div class="text-right">
            <span v-if="parseFloat(workspaceStore.currentWorkspace.plan.price_monthly) === 0" class="text-xl font-bold text-[#3fb950]">Gratis</span>
            <template v-else>
              <span class="text-xl font-bold text-[#e6edf3]">R$ {{ parseFloat(workspaceStore.currentWorkspace.plan.price_monthly).toFixed(2).replace('.', ',') }}</span>
              <span class="text-xs text-[#8b949e]">/mes</span>
            </template>
          </div>
        </div>

        <!-- Usage section -->
        <div class="py-4 border-b border-[#444c56]">
          <h4 class="text-xs font-semibold text-[#8b949e] uppercase tracking-wider mb-3">Uso do plano</h4>

          <div v-if="loadingUsage" class="flex items-center justify-center py-4 text-[#8b949e]">
            <Loader2 :size="16" class="animate-spin mr-2" />
            Carregando...
          </div>

          <div v-else-if="workspaceStore.planUsage" class="space-y-3">
            <!-- Members -->
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-[#2d333b] flex items-center justify-center flex-shrink-0">
                <Users :size="14" class="text-[#8b949e]" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-sm text-[#e6edf3]">Membros</span>
                  <span class="text-sm">
                    <strong class="text-[#e6edf3]">{{ workspaceStore.planUsage.usage.members.used }}</strong>
                    <span class="text-[#8b949e]"> / {{ workspaceStore.planUsage.usage.members.unlimited ? 'Ilimitado' : workspaceStore.planUsage.usage.members.limit }}</span>
                  </span>
                </div>
                <div class="w-full bg-[#2d333b] rounded-full h-1.5">
                  <div
                    class="h-1.5 rounded-full bg-[#6366f1] transition-all duration-500"
                    :style="{ width: workspaceStore.planUsage.usage.members.unlimited ? '8%' : `${Math.min(100, Math.round((workspaceStore.planUsage.usage.members.used / workspaceStore.planUsage.usage.members.limit) * 100))}%` }"
                  />
                </div>
              </div>
            </div>

            <!-- Boards -->
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-[#2d333b] flex items-center justify-center flex-shrink-0">
                <LayoutGrid :size="14" class="text-[#8b949e]" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-sm text-[#e6edf3]">Quadros</span>
                  <span class="text-sm">
                    <strong class="text-[#e6edf3]">{{ workspaceStore.planUsage.usage.boards.used }}</strong>
                    <span class="text-[#8b949e]"> / {{ workspaceStore.planUsage.usage.boards.unlimited ? 'Ilimitado' : workspaceStore.planUsage.usage.boards.limit }}</span>
                  </span>
                </div>
                <div class="w-full bg-[#2d333b] rounded-full h-1.5">
                  <div
                    class="h-1.5 rounded-full bg-[#6366f1] transition-all duration-500"
                    :style="{ width: workspaceStore.planUsage.usage.boards.unlimited ? '8%' : `${Math.min(100, Math.round((workspaceStore.planUsage.usage.boards.used / workspaceStore.planUsage.usage.boards.limit) * 100))}%` }"
                  />
                </div>
              </div>
            </div>

            <!-- Storage -->
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-[#2d333b] flex items-center justify-center flex-shrink-0">
                <HardDrive :size="14" class="text-[#8b949e]" />
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-sm text-[#e6edf3]">Armazenamento</span>
                  <span class="text-sm">
                    <strong class="text-[#e6edf3]">{{ formatStorage(workspaceStore.planUsage.usage.storage_mb.used) }}</strong>
                    <span class="text-[#8b949e]"> / {{ workspaceStore.planUsage.usage.storage_mb.unlimited ? 'Ilimitado' : formatStorage(workspaceStore.planUsage.usage.storage_mb.limit) }}</span>
                  </span>
                </div>
                <div class="w-full bg-[#2d333b] rounded-full h-1.5">
                  <div
                    class="h-1.5 rounded-full bg-[#6366f1] transition-all duration-500"
                    :style="{ width: workspaceStore.planUsage.usage.storage_mb.unlimited ? '8%' : `${Math.min(100, Math.round((workspaceStore.planUsage.usage.storage_mb.used / workspaceStore.planUsage.usage.storage_mb.limit) * 100))}%` }"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Features section -->
        <div v-if="workspaceStore.currentWorkspace.plan.features?.length" class="py-4 border-b border-[#444c56]">
          <h4 class="text-xs font-semibold text-[#8b949e] uppercase tracking-wider mb-3">Recursos incluidos</h4>
          <div class="space-y-2">
            <div
              v-for="feature in workspaceStore.currentWorkspace.plan.features"
              :key="feature"
              class="flex items-center gap-3"
            >
              <div class="w-5 h-5 rounded-full bg-[#238636]/20 flex items-center justify-center flex-shrink-0">
                <svg class="w-3 h-3 text-[#3fb950]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
              </div>
              <span class="text-sm text-[#e6edf3]">{{ featureLabels[feature] || feature }}</span>
              <span v-if="comingSoonFeatures.has(feature)" class="px-1.5 py-0.5 bg-[#d29922]/15 text-[#d29922] rounded text-[10px] font-medium">Em breve</span>
            </div>
          </div>
        </div>

        <!-- Footer actions -->
        <div class="pt-4">
          <div v-if="workspaceStore.currentWorkspace?.plan?.slug === 'free'" class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-[#e6edf3]">Precisa de mais recursos?</p>
              <p class="text-xs text-[#8b949e]">Faca upgrade para desbloquear mais funcionalidades.</p>
            </div>
            <router-link to="/plans" class="monday-btn monday-btn-primary whitespace-nowrap">
              Fazer Upgrade
            </router-link>
          </div>
          <div v-else class="flex justify-center">
            <router-link to="/plans" class="monday-btn monday-btn-secondary inline-flex items-center gap-2">
              <Package :size="16" />
              Ver todos os planos
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Tab: SSO / SAML -->
    <div v-if="activeTab === 'sso'" class="max-w-2xl space-y-6">
      <!-- Loading -->
      <div v-if="ssoLoading" class="flex items-center justify-center py-12 text-[#8b949e]">
        <Loader2 :size="20" class="animate-spin mr-2" />
        Carregando...
      </div>

      <template v-else>
        <!-- Success / Error messages -->
        <div v-if="ssoSuccess" class="p-3 bg-[#23863626] border border-[#23863633] rounded-xl text-sm text-[#3fb950]">
          {{ ssoSuccess }}
        </div>
        <div v-if="ssoError" class="p-3 bg-[#f8514926] border border-[#f8514933] rounded-xl text-sm text-[#f85149]">
          {{ ssoError }}
        </div>

        <!-- SP URLs info card (shown when config exists) -->
        <div v-if="ssoConfig" class="monday-card">
          <h3 class="text-base font-semibold text-[#e6edf3] mb-3">URLs do Service Provider (SP)</h3>
          <p class="text-xs text-[#8b949e] mb-4">Copie estas URLs e configure no seu Identity Provider (IdP).</p>
          <div class="space-y-3">
            <div>
              <label class="monday-label">SP Entity ID / Metadata URL</label>
              <div class="flex gap-2">
                <input :value="ssoConfig.sp_entity_id" readonly class="monday-input flex-1 text-xs" />
                <button class="monday-btn monday-btn-secondary text-xs" @click="copyToClipboard(ssoConfig.sp_entity_id)">Copiar</button>
              </div>
            </div>
            <div>
              <label class="monday-label">ACS URL (Assertion Consumer Service)</label>
              <div class="flex gap-2">
                <input :value="ssoConfig.sp_acs_url" readonly class="monday-input flex-1 text-xs" />
                <button class="monday-btn monday-btn-secondary text-xs" @click="copyToClipboard(ssoConfig.sp_acs_url)">Copiar</button>
              </div>
            </div>
          </div>
        </div>

        <!-- SSO Config form -->
        <div class="monday-card">
          <h3 class="text-base font-semibold text-[#e6edf3] mb-4">Configuracao do Identity Provider (IdP)</h3>

          <form @submit.prevent="handleSaveSso" class="space-y-4">
            <div>
              <label class="monday-label">IdP Entity ID</label>
              <input v-model="ssoForm.idp_entity_id" class="monday-input" placeholder="https://idp.exemplo.com/entity-id" required />
            </div>

            <div>
              <label class="monday-label">IdP SSO URL (Login)</label>
              <input v-model="ssoForm.idp_sso_url" type="url" class="monday-input" placeholder="https://idp.exemplo.com/sso/saml" required />
            </div>

            <div>
              <label class="monday-label">IdP SLO URL (Logout) <span class="text-[#6e7681]">- Opcional</span></label>
              <input v-model="ssoForm.idp_slo_url" type="url" class="monday-input" placeholder="https://idp.exemplo.com/slo/saml" />
            </div>

            <div>
              <label class="monday-label">Certificado X.509 do IdP</label>
              <textarea v-model="ssoForm.idp_x509_cert" class="monday-input resize-none font-mono text-xs" rows="6" placeholder="-----BEGIN CERTIFICATE-----&#10;MIIDqDCCApCgAwIBAgIGAX...&#10;-----END CERTIFICATE-----" required />
            </div>

            <div>
              <label class="monday-label">Dominio do email</label>
              <input v-model="ssoForm.domain" class="monday-input" placeholder="empresa.com" required />
              <p class="text-xs text-[#6e7681] mt-1">Usuarios com este dominio no email poderao fazer login via SSO.</p>
            </div>

            <div class="border-t border-[#444c56] pt-4">
              <h4 class="text-sm font-medium text-[#e6edf3] mb-3">Mapeamento de atributos</h4>
              <div class="space-y-3">
                <div>
                  <label class="monday-label text-xs">Atributo de Email</label>
                  <input v-model="ssoForm.attribute_mapping.email" class="monday-input text-xs" placeholder="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress" />
                </div>
                <div>
                  <label class="monday-label text-xs">Atributo de Nome</label>
                  <input v-model="ssoForm.attribute_mapping.first_name" class="monday-input text-xs" placeholder="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname" />
                </div>
                <div>
                  <label class="monday-label text-xs">Atributo de Sobrenome</label>
                  <input v-model="ssoForm.attribute_mapping.last_name" class="monday-input text-xs" placeholder="http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname" />
                </div>
              </div>
            </div>

            <!-- Toggles -->
            <div class="border-t border-[#444c56] pt-4 space-y-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-[#e6edf3]">Ativar SSO</p>
                  <p class="text-xs text-[#8b949e]">Habilita login via SSO para o dominio configurado.</p>
                </div>
                <button
                  type="button"
                  class="relative w-11 h-6 rounded-full transition-colors flex-shrink-0"
                  :class="ssoForm.is_active ? 'bg-[#238636]' : 'bg-[#2d333b]'"
                  @click="ssoForm.is_active = !ssoForm.is_active"
                >
                  <span class="absolute top-[2px] left-[2px] w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200" :style="{ transform: ssoForm.is_active ? 'translateX(20px)' : 'translateX(0)' }" />
                </button>
              </div>

              <div class="flex items-center justify-between">
                <div>
                  <p class="text-sm font-medium text-[#e6edf3]">Forcar SSO</p>
                  <p class="text-xs text-[#8b949e]">Se ativo, usuarios do dominio so podem entrar via SSO (login por senha e Google ficam bloqueados).</p>
                </div>
                <button
                  type="button"
                  class="relative w-11 h-6 rounded-full transition-colors flex-shrink-0"
                  :class="ssoForm.sso_enforced ? 'bg-[#d29922]' : 'bg-[#2d333b]'"
                  @click="ssoForm.sso_enforced = !ssoForm.sso_enforced"
                >
                  <span class="absolute top-[2px] left-[2px] w-5 h-5 bg-white rounded-full shadow-sm transition-transform duration-200" :style="{ transform: ssoForm.sso_enforced ? 'translateX(20px)' : 'translateX(0)' }" />
                </button>
              </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3 pt-2">
              <button type="submit" :disabled="ssoSaving" class="monday-btn monday-btn-primary disabled:opacity-50">
                <Loader2 v-if="ssoSaving" :size="16" class="animate-spin" />
                {{ ssoSaving ? 'Salvando...' : 'Salvar configuracao' }}
              </button>

              <button
                v-if="ssoConfig"
                type="button"
                :disabled="ssoTesting"
                class="monday-btn monday-btn-secondary disabled:opacity-50"
                @click="handleTestSso"
              >
                <Loader2 v-if="ssoTesting" :size="16" class="animate-spin" />
                {{ ssoTesting ? 'Testando...' : 'Testar conexao' }}
              </button>
            </div>
          </form>

          <!-- Test result -->
          <div v-if="ssoTestResult" class="mt-4 p-3 rounded-xl text-sm" :class="ssoTestResult.status === 'ok' ? 'bg-[#23863626] border border-[#23863633] text-[#3fb950]' : 'bg-[#f8514926] border border-[#f8514933] text-[#f85149]'">
            {{ ssoTestResult.message }}
          </div>
        </div>

        <!-- Delete SSO config -->
        <div v-if="ssoConfig" class="monday-card border-[#f85149]/30">
          <div class="flex items-center justify-between">
            <div>
              <p class="text-sm font-medium text-[#f85149]">Remover configuracao SSO</p>
              <p class="text-xs text-[#8b949e]">Isso desabilitara o login via SSO para todos os usuarios do dominio.</p>
            </div>
            <button class="monday-btn monday-btn-secondary text-[#f85149] hover:bg-[#f8514926]" @click="showDeleteSsoConfirm = true">
              Remover SSO
            </button>
          </div>
        </div>
      </template>
    </div>

    <!-- Delete SSO confirmation modal -->
    <AppModal :show="showDeleteSsoConfirm" title="Remover SSO" @close="showDeleteSsoConfirm = false">
      <p class="text-sm text-[#8b949e] mb-4">
        Tem certeza que deseja remover a configuracao SSO? Usuarios do dominio <strong class="text-[#e6edf3]">{{ ssoForm.domain }}</strong> nao poderao mais fazer login via SSO.
      </p>
      <div class="flex gap-2 justify-end">
        <button class="monday-btn monday-btn-secondary" @click="showDeleteSsoConfirm = false">Cancelar</button>
        <button class="monday-btn monday-btn-primary bg-[#f85149] hover:bg-[#da3633]" @click="handleDeleteSso">Remover</button>
      </div>
    </AppModal>

    <!-- Remove member confirmation modal -->
    <AppModal :show="!!memberToRemove" title="Remover Membro" @close="memberToRemove = null">
      <p class="text-sm text-[#8b949e] mb-4">
        Tem certeza que deseja remover <strong class="text-[#e6edf3]">{{ memberToRemove?.name }}</strong> do workspace?
      </p>
      <div class="flex gap-2 justify-end">
        <button class="monday-btn monday-btn-secondary" @click="memberToRemove = null">Cancelar</button>
        <button
          class="monday-btn monday-btn-primary bg-[#f85149] hover:bg-[#da3633]"
          @click="handleRemoveMember"
        >
          Remover
        </button>
      </div>
    </AppModal>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useWorkspaceStore } from '../stores/workspace'
import { usePlanLimits } from '../composables/usePlanLimits'
import { ssoApi } from '../api/sso'
import { WORKSPACE_ROLES } from '../lib/constants'
import { Settings, Users, Mail, Package, Loader2, Trash2, LayoutGrid, HardDrive, Shield } from 'lucide-vue-next'
import UserAvatar from '../components/shared/UserAvatar.vue'
import AppModal from '../components/shared/AppModal.vue'
import PageHeader from '../components/shared/PageHeader.vue'

const workspaceStore = useWorkspaceStore()
const { canAddMember, membersUsed, membersLimit, membersUnlimited, hasSso } = usePlanLimits()

const activeTab = ref('general')
const baseTabs = [
  { key: 'general', label: 'Geral', icon: Settings },
  { key: 'members', label: 'Membros', icon: Users },
  { key: 'invitations', label: 'Convites', icon: Mail },
  { key: 'plan', label: 'Plano', icon: Package },
]

const tabs = computed(() => {
  const t = [...baseTabs]
  if (hasSso.value && workspaceStore.isOwner) {
    t.push({ key: 'sso', label: 'SSO / SAML', icon: Shield })
  }
  return t
})

const featureLabels = {
  custom_backgrounds: 'Backgrounds customizados',
  automations: 'Automacoes',
  custom_fields: 'Campos customizados',
  priority_support: 'Suporte prioritario',
  api_access: 'Acesso a API',
  sso: 'SSO / SAML',
}

const comingSoonFeatures = new Set([])

// General tab
const workspaceName = ref('')
const savingGeneral = ref(false)

// Members tab
const loadingMembers = ref(false)
const memberToRemove = ref(null)

// Invitations tab
const inviteForm = ref({ email: '', role: 'member' })
const sendingInvite = ref(false)
const loadingInvitations = ref(false)

// Plan tab
const loadingUsage = ref(false)

// SSO tab
const ssoConfig = ref(null)
const ssoLoading = ref(false)
const ssoSaving = ref(false)
const ssoTesting = ref(false)
const ssoError = ref('')
const ssoSuccess = ref('')
const ssoTestResult = ref(null)
const showDeleteSsoConfirm = ref(false)

const ssoForm = ref({
  idp_entity_id: '',
  idp_sso_url: '',
  idp_slo_url: '',
  idp_x509_cert: '',
  domain: '',
  sso_enforced: false,
  is_active: false,
  attribute_mapping: {
    email: 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
    first_name: 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
    last_name: 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
  },
})

async function loadSsoConfig() {
  ssoLoading.value = true
  ssoError.value = ''
  try {
    const res = await ssoApi.getConfig()
    ssoConfig.value = res.data.data
    if (ssoConfig.value) {
      ssoForm.value = {
        idp_entity_id: ssoConfig.value.idp_entity_id || '',
        idp_sso_url: ssoConfig.value.idp_sso_url || '',
        idp_slo_url: ssoConfig.value.idp_slo_url || '',
        idp_x509_cert: ssoConfig.value.idp_x509_cert || '',
        domain: ssoConfig.value.domain || '',
        sso_enforced: ssoConfig.value.sso_enforced || false,
        is_active: ssoConfig.value.is_active || false,
        attribute_mapping: ssoConfig.value.attribute_mapping || ssoForm.value.attribute_mapping,
      }
    }
  } catch (err) {
    ssoError.value = err.response?.data?.error?.message || 'Erro ao carregar configuracao SSO.'
  } finally {
    ssoLoading.value = false
  }
}

async function handleSaveSso() {
  ssoSaving.value = true
  ssoError.value = ''
  ssoSuccess.value = ''
  try {
    const res = await ssoApi.saveConfig(ssoForm.value)
    ssoConfig.value = res.data.data
    ssoSuccess.value = 'Configuracao SSO salva com sucesso!'
  } catch (err) {
    ssoError.value = err.response?.data?.error?.message || 'Erro ao salvar configuracao SSO.'
  } finally {
    ssoSaving.value = false
  }
}

async function handleTestSso() {
  ssoTesting.value = true
  ssoTestResult.value = null
  try {
    const res = await ssoApi.testConnection()
    ssoTestResult.value = res.data.data
  } catch (err) {
    ssoTestResult.value = { status: 'error', message: err.response?.data?.error?.message || 'Erro ao testar conexao.' }
  } finally {
    ssoTesting.value = false
  }
}

async function handleDeleteSso() {
  try {
    await ssoApi.deleteConfig()
    ssoConfig.value = null
    ssoForm.value = {
      idp_entity_id: '',
      idp_sso_url: '',
      idp_slo_url: '',
      idp_x509_cert: '',
      domain: '',
      sso_enforced: false,
      is_active: false,
      attribute_mapping: {
        email: 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/emailaddress',
        first_name: 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/givenname',
        last_name: 'http://schemas.xmlsoap.org/ws/2005/05/identity/claims/surname',
      },
    }
    ssoSuccess.value = 'Configuracao SSO removida.'
    showDeleteSsoConfirm.value = false
  } catch (err) {
    ssoError.value = err.response?.data?.error?.message || 'Erro ao remover configuracao SSO.'
  }
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text)
}

function formatStorage(mb) {
  if (mb >= 1024) return `${(mb / 1024).toFixed(1)} GB`
  return `${mb} MB`
}

function initWorkspaceName() {
  workspaceName.value = workspaceStore.currentWorkspace?.name || ''
}

async function handleSaveGeneral() {
  if (!workspaceStore.currentWorkspace) return
  savingGeneral.value = true
  try {
    await workspaceStore.updateWorkspace(workspaceStore.currentWorkspace.id, { name: workspaceName.value })
  } finally {
    savingGeneral.value = false
  }
}

async function loadMembers() {
  loadingMembers.value = true
  try {
    await workspaceStore.fetchMembers()
  } finally {
    loadingMembers.value = false
  }
}

async function handleRoleChange(userId, role) {
  await workspaceStore.updateMemberRole(userId, role)
}

function confirmRemoveMember(member) {
  memberToRemove.value = member
}

async function handleRemoveMember() {
  if (!memberToRemove.value) return
  await workspaceStore.removeMember(memberToRemove.value.id)
  memberToRemove.value = null
}

async function loadInvitations() {
  loadingInvitations.value = true
  try {
    await workspaceStore.fetchInvitations()
  } finally {
    loadingInvitations.value = false
  }
}

async function handleInvite() {
  sendingInvite.value = true
  try {
    await workspaceStore.invite(inviteForm.value)
    inviteForm.value = { email: '', role: 'member' }
  } finally {
    sendingInvite.value = false
  }
}

async function handleCancelInvitation(invId) {
  await workspaceStore.cancelInvitation(invId)
}

async function loadPlanUsage() {
  loadingUsage.value = true
  try {
    await workspaceStore.fetchPlanUsage()
  } finally {
    loadingUsage.value = false
  }
}

// Load data when tab changes
watch(activeTab, (tab) => {
  if (tab === 'members') loadMembers()
  if (tab === 'invitations') { loadInvitations(); loadPlanUsage() }
  if (tab === 'plan') loadPlanUsage()
  if (tab === 'sso') loadSsoConfig()
})

onMounted(() => {
  initWorkspaceName()
})
</script>

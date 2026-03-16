<script setup>
import { ref, computed } from 'vue'
import { useAuthStore } from '../stores/auth'
import { useNotificationsStore } from '../stores/notifications'
import { userApi } from '../api/user'
import { Camera, User as UserIcon, Lock, Mail, Save, Eye, EyeOff } from 'lucide-vue-next'

const authStore = useAuthStore()
const notifications = useNotificationsStore()

// Profile
const name = ref(authStore.user?.name || '')
const avatarPreview = ref(null)
const avatarFile = ref(null)
const savingProfile = ref(false)

// Password
const currentPassword = ref('')
const newPassword = ref('')
const newPasswordConfirm = ref('')
const savingPassword = ref(false)
const showCurrentPw = ref(false)
const showNewPw = ref(false)

const isGoogleUser = computed(() => !!(authStore.user?.google_id && !authStore.user?.password))

const avatarUrl = computed(() => {
  if (avatarPreview.value) return avatarPreview.value
  if (authStore.user?.avatar_url) {
    const url = authStore.user.avatar_url
    if (url.startsWith('http')) return url
    return `${import.meta.env.VITE_API_URL}${url}`
  }
  return null
})

function handleAvatarChange(e) {
  const file = e.target.files?.[0]
  if (!file) return
  if (file.size > 2 * 1024 * 1024) {
    notifications.add('A imagem deve ter no maximo 2MB', 'error')
    return
  }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

async function saveProfile() {
  savingProfile.value = true
  try {
    const formData = new FormData()
    if (name.value !== authStore.user?.name) {
      formData.append('name', name.value)
    }
    if (avatarFile.value) {
      formData.append('avatar', avatarFile.value)
    }
    const res = await userApi.updateProfile(formData)
    authStore.user = res.data.data
    avatarFile.value = null
    avatarPreview.value = null
    notifications.add('Perfil atualizado com sucesso!')
  } catch (err) {
    notifications.add(err.response?.data?.error?.message || 'Erro ao atualizar perfil', 'error')
  } finally {
    savingProfile.value = false
  }
}

async function savePassword() {
  if (newPassword.value !== newPasswordConfirm.value) {
    notifications.add('As senhas nao coincidem', 'error')
    return
  }
  if (newPassword.value.length < 8) {
    notifications.add('A nova senha deve ter no minimo 8 caracteres', 'error')
    return
  }
  savingPassword.value = true
  try {
    await userApi.updatePassword({
      current_password: currentPassword.value,
      new_password: newPassword.value,
      new_password_confirmation: newPasswordConfirm.value,
    })
    currentPassword.value = ''
    newPassword.value = ''
    newPasswordConfirm.value = ''
    notifications.add('Senha atualizada com sucesso!')
  } catch (err) {
    notifications.add(err.response?.data?.error?.message || 'Erro ao atualizar senha', 'error')
  } finally {
    savingPassword.value = false
  }
}

function getInitials(n) {
  if (!n) return '?'
  return n.split(' ').map(w => w[0]).join('').substring(0, 2).toUpperCase()
}
</script>

<template>
  <div class="max-w-3xl mx-auto py-2">
    <div class="animate-fade-in-up">
      <h1 class="text-3xl font-bold text-[#e6edf3] mb-2">Meu Perfil</h1>
      <p class="text-base text-[#8b949e] mb-8">Gerencie suas informacoes pessoais</p>
    </div>

    <!-- Avatar & Name section -->
    <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-6 mb-5 animate-fade-in-up stagger-1">
      <div class="flex items-start gap-6">
        <!-- Avatar -->
        <div class="relative group">
          <div
            v-if="avatarUrl"
            class="w-24 h-24 rounded-2xl bg-cover bg-center border-2 border-[#444c56] group-hover:border-[#6366f1] transition-colors"
            :style="{ backgroundImage: `url(${avatarUrl})` }"
          />
          <div
            v-else
            class="w-24 h-24 rounded-2xl bg-gradient-to-br from-[#6366f1] to-[#3730a3] flex items-center justify-center text-white text-2xl font-bold border-2 border-[#444c56] group-hover:border-[#6366f1] transition-colors"
          >
            {{ getInitials(authStore.user?.name) }}
          </div>
          <label class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
            <Camera :size="20" class="text-white" />
            <input type="file" accept="image/*" class="hidden" @change="handleAvatarChange" />
          </label>
        </div>

        <!-- Name & Email -->
        <div class="flex-1">
          <label class="text-xs font-medium text-[#8b949e] uppercase tracking-wider mb-1.5 block">Nome</label>
          <input
            v-model="name"
            type="text"
            class="w-full bg-[#0d1117] border border-[#444c56] rounded-xl px-4 py-2.5 text-[#e6edf3] text-sm focus:border-[#388bfd] focus:outline-none focus:ring-1 focus:ring-[#6366f1]/30 transition-colors mb-4"
          />

          <label class="text-xs font-medium text-[#8b949e] uppercase tracking-wider mb-1.5 block">
            <Mail :size="12" class="inline mr-1" />
            Email
          </label>
          <div class="w-full bg-[#0d1117]/50 border border-[#2d333b] rounded-xl px-4 py-2.5 text-[#6e7681] text-sm">
            {{ authStore.user?.email }}
          </div>
        </div>
      </div>

      <div class="flex justify-end mt-5">
        <button
          @click="saveProfile"
          :disabled="savingProfile"
          class="flex items-center gap-2 px-5 py-2.5 bg-[#6366f1] hover:bg-[#4f46e5] text-white text-sm font-medium rounded-xl transition-colors disabled:opacity-50"
        >
          <Save :size="14" />
          {{ savingProfile ? 'Salvando...' : 'Salvar Perfil' }}
        </button>
      </div>
    </div>

    <!-- Password section -->
    <div class="bg-[#161b22] border border-[#444c56] rounded-2xl p-6 animate-fade-in-up stagger-2">
      <div class="flex items-center gap-2 mb-4">
        <Lock :size="16" class="text-[#8b949e]" />
        <h3 class="text-base font-semibold text-[#e6edf3]">Alterar Senha</h3>
      </div>

      <div v-if="isGoogleUser" class="bg-[#2d333b] border border-[#444c56] rounded-xl p-4 text-sm text-[#8b949e]">
        <p>Sua conta esta vinculada ao Google. Nao e possivel alterar a senha por aqui.</p>
      </div>

      <template v-else>
        <div class="space-y-4">
          <!-- Current password -->
          <div>
            <label class="text-xs font-medium text-[#8b949e] uppercase tracking-wider mb-1.5 block">Senha Atual</label>
            <div class="relative">
              <input
                v-model="currentPassword"
                :type="showCurrentPw ? 'text' : 'password'"
                placeholder="Digite sua senha atual"
                class="w-full bg-[#0d1117] border border-[#444c56] rounded-xl px-4 py-2.5 pr-10 text-[#e6edf3] text-sm placeholder-[#545d68] focus:border-[#388bfd] focus:outline-none focus:ring-1 focus:ring-[#6366f1]/30 transition-colors"
              />
              <button
                type="button"
                @click="showCurrentPw = !showCurrentPw"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6e7681] hover:text-[#8b949e] transition-colors"
              >
                <EyeOff v-if="showCurrentPw" :size="16" />
                <Eye v-else :size="16" />
              </button>
            </div>
          </div>

          <!-- New password -->
          <div>
            <label class="text-xs font-medium text-[#8b949e] uppercase tracking-wider mb-1.5 block">Nova Senha</label>
            <div class="relative">
              <input
                v-model="newPassword"
                :type="showNewPw ? 'text' : 'password'"
                placeholder="Minimo 8 caracteres"
                class="w-full bg-[#0d1117] border border-[#444c56] rounded-xl px-4 py-2.5 pr-10 text-[#e6edf3] text-sm placeholder-[#545d68] focus:border-[#388bfd] focus:outline-none focus:ring-1 focus:ring-[#6366f1]/30 transition-colors"
              />
              <button
                type="button"
                @click="showNewPw = !showNewPw"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-[#6e7681] hover:text-[#8b949e] transition-colors"
              >
                <EyeOff v-if="showNewPw" :size="16" />
                <Eye v-else :size="16" />
              </button>
            </div>
          </div>

          <!-- Confirm password -->
          <div>
            <label class="text-xs font-medium text-[#8b949e] uppercase tracking-wider mb-1.5 block">Confirmar Nova Senha</label>
            <input
              v-model="newPasswordConfirm"
              type="password"
              placeholder="Repita a nova senha"
              class="w-full bg-[#0d1117] border border-[#444c56] rounded-xl px-4 py-2.5 text-[#e6edf3] text-sm placeholder-[#545d68] focus:border-[#388bfd] focus:outline-none focus:ring-1 focus:ring-[#6366f1]/30 transition-colors"
            />
          </div>
        </div>

        <div class="flex justify-end mt-5">
          <button
            @click="savePassword"
            :disabled="savingPassword || !currentPassword || !newPassword || !newPasswordConfirm"
            class="flex items-center gap-2 px-5 py-2.5 bg-[#6366f1] hover:bg-[#4f46e5] text-white text-sm font-medium rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Lock :size="14" />
            {{ savingPassword ? 'Salvando...' : 'Alterar Senha' }}
          </button>
        </div>
      </template>
    </div>
  </div>
</template>

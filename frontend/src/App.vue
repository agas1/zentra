<template>
  <!-- Skip navigation link (WCAG 2.4.1) -->
  <a href="#main-content" class="skip-nav">Pular para o conteudo principal</a>

  <!-- Live region for screen reader announcements -->
  <div id="aria-live-region" aria-live="polite" aria-atomic="true" class="sr-only"></div>

  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from './stores/auth'

const authStore = useAuthStore()

onMounted(async () => {
  if (authStore.token) {
    try {
      await authStore.fetchMe()
    } catch {
      authStore.logout()
    }
  }
})
</script>

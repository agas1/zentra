import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { powerupsApi } from '../api/powerups'

export const usePowerUpsStore = defineStore('powerups', () => {
  const powerUps = ref([])
  const loading = ref(false)

  async function fetchPowerUps() {
    loading.value = true
    try {
      const res = await powerupsApi.list()
      powerUps.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function install(slug) {
    const res = await powerupsApi.install(slug)
    const idx = powerUps.value.findIndex(p => p.slug === slug)
    if (idx !== -1) {
      powerUps.value[idx].is_installed = true
      powerUps.value[idx].config = res.data.data.config
      powerUps.value[idx].connected_by = res.data.data.connected_by
      powerUps.value[idx].connected_at = res.data.data.connected_at
    }
    return res.data.data
  }

  async function uninstall(slug) {
    await powerupsApi.uninstall(slug)
    const idx = powerUps.value.findIndex(p => p.slug === slug)
    if (idx !== -1) {
      powerUps.value[idx].is_installed = false
      powerUps.value[idx].config = null
      powerUps.value[idx].connected_by = null
      powerUps.value[idx].connected_at = null
    }
  }

  async function updateConfig(slug, config) {
    const res = await powerupsApi.updateConfig(slug, config)
    const idx = powerUps.value.findIndex(p => p.slug === slug)
    if (idx !== -1) {
      powerUps.value[idx].config = res.data.data.config
    }
    return res.data.data
  }

  function isInstalled(slug) {
    return powerUps.value.find(p => p.slug === slug)?.is_installed ?? false
  }

  function getConfig(slug) {
    return powerUps.value.find(p => p.slug === slug)?.config ?? {}
  }

  return {
    powerUps, loading,
    fetchPowerUps, install, uninstall, updateConfig,
    isInstalled, getConfig,
  }
})

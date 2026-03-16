import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { cardsApi } from '../api/cards'
import { boardsApi } from '../api/boards'

export const useCardStore = defineStore('card', () => {
  const card = ref(null)
  const loading = ref(false)
  const activities = ref([])
  const activitiesLoading = ref(false)

  const checklistProgress = computed(() => {
    if (!card.value?.checklists) return { total: 0, checked: 0, percent: 0 }
    let total = 0
    let checked = 0
    card.value.checklists.forEach(cl => {
      cl.items?.forEach(item => {
        total++
        if (item.is_checked) checked++
      })
    })
    return { total, checked, percent: total > 0 ? Math.round((checked / total) * 100) : 0 }
  })

  async function openCard(id) {
    loading.value = true
    try {
      const res = await cardsApi.show(id)
      card.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function updateCard(data) {
    const res = await cardsApi.update(card.value.id, data)
    Object.assign(card.value, res.data.data)
  }

  function closeCard() {
    card.value = null
    activities.value = []
  }

  async function archiveCard(options = {}) {
    await boardsApi.archiveCard(card.value.id, options)
    const id = card.value.id
    card.value = null
    activities.value = []
    return id
  }

  async function duplicateCard() {
    const res = await cardsApi.duplicate(card.value.id)
    return res.data.data
  }

  async function fetchActivities() {
    if (!card.value) return
    activitiesLoading.value = true
    try {
      const res = await cardsApi.listActivities(card.value.id)
      activities.value = res.data.data || res.data || []
    } catch {
      activities.value = []
    } finally {
      activitiesLoading.value = false
    }
  }

  async function addMember(userId) {
    const res = await cardsApi.addMember(card.value.id, userId)
    card.value.members = res.data.data
  }

  async function removeMember(userId) {
    await cardsApi.removeMember(card.value.id, userId)
    card.value.members = card.value.members.filter(m => m.id !== userId)
  }

  async function toggleLabel(labelId) {
    const hasLabel = card.value.labels?.some(l => l.id === labelId)
    if (hasLabel) {
      await cardsApi.removeLabel(card.value.id, labelId)
      card.value.labels = card.value.labels.filter(l => l.id !== labelId)
    } else {
      const res = await cardsApi.addLabel(card.value.id, labelId)
      card.value.labels = res.data.data
    }
  }

  async function addChecklist(title) {
    const res = await cardsApi.createChecklist(card.value.id, { title })
    if (!card.value.checklists) card.value.checklists = []
    card.value.checklists.push(res.data.data)
  }

  async function deleteChecklist(checklistId) {
    await cardsApi.deleteChecklist(checklistId)
    card.value.checklists = card.value.checklists.filter(c => c.id !== checklistId)
  }

  async function addChecklistItem(checklistId, title) {
    const res = await cardsApi.createChecklistItem(checklistId, { title })
    const cl = card.value.checklists.find(c => c.id === checklistId)
    if (cl) {
      if (!cl.items) cl.items = []
      cl.items.push(res.data.data)
    }
  }

  async function toggleChecklistItem(itemId) {
    const res = await cardsApi.toggleChecklistItem(itemId)
    for (const cl of card.value.checklists) {
      const item = cl.items?.find(i => i.id === itemId)
      if (item) {
        item.is_checked = res.data.data.is_checked
        break
      }
    }
  }

  async function deleteChecklistItem(itemId) {
    await cardsApi.deleteChecklistItem(itemId)
    for (const cl of card.value.checklists) {
      const idx = cl.items?.findIndex(i => i.id === itemId)
      if (idx !== undefined && idx !== -1) {
        cl.items.splice(idx, 1)
        break
      }
    }
  }

  async function addComment(body) {
    const res = await cardsApi.createComment(card.value.id, { body })
    if (!card.value.comments) card.value.comments = []
    card.value.comments.unshift(res.data.data)
  }

  async function updateComment(commentId, body) {
    const res = await cardsApi.updateComment(commentId, { body })
    const comment = card.value.comments.find(c => c.id === commentId)
    if (comment) comment.body = res.data.data.body
  }

  async function deleteComment(commentId) {
    await cardsApi.deleteComment(commentId)
    card.value.comments = card.value.comments.filter(c => c.id !== commentId)
  }

  async function uploadAttachment(file) {
    const res = await cardsApi.uploadAttachment(card.value.id, file)
    if (!card.value.attachments) card.value.attachments = []
    card.value.attachments.push(res.data.data)
    return res.data.data
  }

  async function setAttachmentCover(attachmentId) {
    const res = await cardsApi.setAttachmentCover(attachmentId)
    card.value.attachments.forEach(a => a.is_cover = a.id === attachmentId)
    card.value.cover_url = res.data.data.url
  }

  async function deleteAttachment(attachmentId) {
    await cardsApi.deleteAttachment(attachmentId)
    const att = card.value.attachments.find(a => a.id === attachmentId)
    if (att?.is_cover) card.value.cover_url = null
    card.value.attachments = card.value.attachments.filter(a => a.id !== attachmentId)
  }

  async function addSubCard(title) {
    const res = await cardsApi.createSubCard(card.value.id, { title })
    if (!card.value.sub_cards) card.value.sub_cards = []
    card.value.sub_cards.push(res.data.data)
  }

  async function toggleSubCardDone(subCardId) {
    const sub = card.value.sub_cards?.find(s => s.id === subCardId)
    if (!sub) return
    const res = await cardsApi.update(subCardId, { due_completed: !sub.due_completed })
    Object.assign(sub, res.data.data)
  }

  async function deleteSubCard(subCardId) {
    await cardsApi.destroy(subCardId)
    card.value.sub_cards = card.value.sub_cards.filter(s => s.id !== subCardId)
  }

  async function updateCustomFieldValue(fieldId, value) {
    const res = await cardsApi.updateCustomFieldValue(card.value.id, fieldId, { value })
    if (!card.value.custom_field_values) card.value.custom_field_values = []
    const idx = card.value.custom_field_values.findIndex(v => v.custom_field_id === fieldId)
    if (idx !== -1) {
      card.value.custom_field_values[idx] = res.data.data
    } else {
      card.value.custom_field_values.push(res.data.data)
    }
  }

  return {
    card, loading, activities, activitiesLoading, checklistProgress,
    openCard, updateCard, closeCard, archiveCard, duplicateCard, fetchActivities,
    addMember, removeMember, toggleLabel,
    addChecklist, deleteChecklist, addChecklistItem, toggleChecklistItem, deleteChecklistItem,
    addComment, updateComment, deleteComment,
    uploadAttachment, setAttachmentCover, deleteAttachment,
    updateCustomFieldValue,
    addSubCard, toggleSubCardDone, deleteSubCard,
  }
})

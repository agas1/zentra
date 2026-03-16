import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { chatApi } from '../api/chat'

export const useChatStore = defineStore('chat', () => {
  const conversations = ref([])
  const activeConversation = ref(null)
  const messages = ref([])
  const loading = ref(false)
  const messagesLoading = ref(false)
  const currentPage = ref(1)
  const hasMoreMessages = ref(false)

  const totalUnread = computed(() => {
    return conversations.value.reduce((sum, c) => sum + (c.unread_count || 0), 0)
  })

  const sortedConversations = computed(() => {
    return [...conversations.value].sort((a, b) => {
      const aDate = a.last_message?.created_at || a.created_at
      const bDate = b.last_message?.created_at || b.created_at
      return new Date(bDate) - new Date(aDate)
    })
  })

  async function fetchConversations() {
    loading.value = true
    try {
      const res = await chatApi.getConversations()
      conversations.value = res.data.data
    } finally {
      loading.value = false
    }
  }

  async function openConversation(conversation) {
    activeConversation.value = conversation
    messages.value = []
    currentPage.value = 1
    messagesLoading.value = true
    try {
      const res = await chatApi.getMessages(conversation.id, 1)
      messages.value = (res.data.data || []).reverse()
      hasMoreMessages.value = res.data.current_page < res.data.last_page
      currentPage.value = res.data.current_page

      // Mark as read
      await chatApi.markAsRead(conversation.id)
      const conv = conversations.value.find(c => c.id === conversation.id)
      if (conv) conv.unread_count = 0
    } finally {
      messagesLoading.value = false
    }
  }

  async function loadMoreMessages() {
    if (!activeConversation.value || !hasMoreMessages.value || messagesLoading.value) return
    messagesLoading.value = true
    try {
      const nextPage = currentPage.value + 1
      const res = await chatApi.getMessages(activeConversation.value.id, nextPage)
      const olderMessages = (res.data.data || []).reverse()
      messages.value = [...olderMessages, ...messages.value]
      hasMoreMessages.value = res.data.current_page < res.data.last_page
      currentPage.value = res.data.current_page
    } finally {
      messagesLoading.value = false
    }
  }

  async function sendMessage(body) {
    if (!activeConversation.value || !body.trim()) return

    // Optimistic push
    const tempId = 'temp-' + Date.now()
    const optimistic = {
      id: tempId,
      conversation_id: activeConversation.value.id,
      body: body.trim(),
      type: 'text',
      user: { id: 'me', name: 'Eu' },
      created_at: new Date().toISOString(),
      _sending: true,
    }
    messages.value.push(optimistic)

    try {
      const res = await chatApi.sendMessage(activeConversation.value.id, body.trim())
      // Replace optimistic with real
      const idx = messages.value.findIndex(m => m.id === tempId)
      if (idx !== -1) messages.value[idx] = res.data.data

      // Update conversation's last message
      const conv = conversations.value.find(c => c.id === activeConversation.value.id)
      if (conv) {
        conv.last_message = res.data.data
      }
    } catch {
      // Remove optimistic on error
      messages.value = messages.value.filter(m => m.id !== tempId)
      throw new Error('Falha ao enviar mensagem')
    }
  }

  async function createConversation(data) {
    const res = await chatApi.createConversation(data)
    const conv = res.data.data
    // Add to list if not already there
    if (!conversations.value.find(c => c.id === conv.id)) {
      conversations.value.unshift(conv)
    }
    return conv
  }

  function closeConversation() {
    activeConversation.value = null
    messages.value = []
  }

  return {
    conversations, activeConversation, messages, loading, messagesLoading,
    hasMoreMessages, totalUnread, sortedConversations,
    fetchConversations, openConversation, loadMoreMessages, sendMessage,
    createConversation, closeConversation,
  }
})

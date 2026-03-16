import api from './axios'

export const chatApi = {
  getConversations: () => api.get('/chat/conversations'),
  createConversation: (data) => api.post('/chat/conversations', data),
  getMessages: (conversationId, page = 1) => api.get(`/chat/conversations/${conversationId}/messages?page=${page}`),
  sendMessage: (conversationId, body) => api.post(`/chat/conversations/${conversationId}/messages`, { body }),
  markAsRead: (conversationId) => api.patch(`/chat/conversations/${conversationId}/read`),
}

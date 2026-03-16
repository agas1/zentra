import api from './axios'

export const boardsApi = {
  list: () => api.get('/boards'),
  create: (data) => {
    if (data instanceof FormData) {
      return api.post('/boards', data)
    }
    return api.post('/boards', data)
  },
  show: (id) => api.get(`/boards/${id}`),
  update: (id, data) => {
    if (data instanceof FormData) {
      data.append('_method', 'PUT')
      return api.post(`/boards/${id}`, data)
    }
    return api.put(`/boards/${id}`, data)
  },
  destroy: (id) => api.delete(`/boards/${id}`),

  // Lists
  createList: (boardId, data) => api.post(`/boards/${boardId}/lists`, data),
  updateList: (boardId, listId, data) => api.put(`/boards/${boardId}/lists/${listId}`, data),
  reorderLists: (boardId, positions) => api.post(`/boards/${boardId}/lists/reorder`, { positions }),
  archiveList: (boardId, listId) => api.delete(`/boards/${boardId}/lists/${listId}`),

  // Cards
  createCard: (boardId, listId, data) => api.post(`/boards/${boardId}/lists/${listId}/cards`, data),
  reorderCards: (boardId, listId, positions) => api.post(`/boards/${boardId}/cards/reorder`, { list_id: listId, positions }),
  moveCard: (cardId, data) => api.patch(`/cards/${cardId}/move`, data),
  archiveCard: (cardId, data = {}) => api.patch(`/cards/${cardId}/archive`, data),
  restoreCard: (cardId) => api.patch(`/cards/${cardId}/restore`),
  duplicateCard: (cardId) => api.post(`/cards/${cardId}/duplicate`),
  getArchived: (boardId) => api.get(`/boards/${boardId}/archived`),

  // Labels
  listLabels: (boardId) => api.get(`/boards/${boardId}/labels`),
  createLabel: (boardId, data) => api.post(`/boards/${boardId}/labels`, data),
  updateLabel: (boardId, labelId, data) => api.put(`/boards/${boardId}/labels/${labelId}`, data),
  deleteLabel: (boardId, labelId) => api.delete(`/boards/${boardId}/labels/${labelId}`),

  // Custom Fields
  listCustomFields: (boardId) => api.get(`/boards/${boardId}/custom-fields`),
  createCustomField: (boardId, data) => api.post(`/boards/${boardId}/custom-fields`, data),
  updateCustomField: (boardId, fieldId, data) => api.put(`/boards/${boardId}/custom-fields/${fieldId}`, data),
  deleteCustomField: (boardId, fieldId) => api.delete(`/boards/${boardId}/custom-fields/${fieldId}`),

  // Automations
  getAutomations: (boardId) => api.get(`/boards/${boardId}/automations`),
  getAutomationPresets: (boardId) => api.get(`/boards/${boardId}/automations/presets`),
  createAutomation: (boardId, data) => api.post(`/boards/${boardId}/automations`, data),
  updateAutomation: (id, data) => api.put(`/automations/${id}`, data),
  toggleAutomation: (id) => api.patch(`/automations/${id}/toggle`),
  deleteAutomation: (id) => api.delete(`/automations/${id}`),

  // Templates
  listTemplates: () => api.get('/templates'),
  createTemplate: (data) => api.post('/templates', data),
  updateTemplate: (id, data) => api.put(`/templates/${id}`, data),
  deleteTemplate: (id) => api.delete(`/templates/${id}`),
  saveAsTemplate: (boardId, data) => api.post(`/boards/${boardId}/save-as-template`, data),
  applyTemplate: (templateId, boardId) => api.post(`/templates/${templateId}/apply/${boardId}`),
}

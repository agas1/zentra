import api from './axios'

export const cardsApi = {
  show: (id) => api.get(`/cards/${id}`),
  createSubCard: (cardId, data) => api.post(`/cards/${cardId}/subcards`, data),
  update: (id, data) => api.put(`/cards/${id}`, data),
  destroy: (id) => api.delete(`/cards/${id}`),
  duplicate: (id) => api.post(`/cards/${id}/duplicate`),

  // Members
  addMember: (cardId, userId) => api.post(`/cards/${cardId}/members`, { user_id: userId }),
  removeMember: (cardId, userId) => api.delete(`/cards/${cardId}/members/${userId}`),

  // Labels
  addLabel: (cardId, labelId) => api.post(`/cards/${cardId}/labels`, { label_id: labelId }),
  removeLabel: (cardId, labelId) => api.delete(`/cards/${cardId}/labels/${labelId}`),

  // Checklists
  createChecklist: (cardId, data) => api.post(`/cards/${cardId}/checklists`, data),
  updateChecklist: (checklistId, data) => api.put(`/checklists/${checklistId}`, data),
  deleteChecklist: (checklistId) => api.delete(`/checklists/${checklistId}`),

  // Checklist Items
  createChecklistItem: (checklistId, data) => api.post(`/checklists/${checklistId}/items`, data),
  updateChecklistItem: (itemId, data) => api.put(`/checklist-items/${itemId}`, data),
  toggleChecklistItem: (itemId) => api.patch(`/checklist-items/${itemId}/toggle`),
  deleteChecklistItem: (itemId) => api.delete(`/checklist-items/${itemId}`),

  // Attachments
  uploadAttachment: (cardId, file) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post(`/cards/${cardId}/attachments`, formData)
  },
  setAttachmentCover: (attachmentId) => api.patch(`/attachments/${attachmentId}/cover`),
  deleteAttachment: (attachmentId) => api.delete(`/attachments/${attachmentId}`),

  // Comments
  listComments: (cardId) => api.get(`/cards/${cardId}/comments`),
  createComment: (cardId, data) => api.post(`/cards/${cardId}/comments`, data),
  updateComment: (commentId, data) => api.put(`/comments/${commentId}`, data),
  deleteComment: (commentId) => api.delete(`/comments/${commentId}`),

  // Activities
  listActivities: (cardId) => api.get(`/cards/${cardId}/activities`),

  // Custom Field Values
  listCustomFieldValues: (cardId) => api.get(`/cards/${cardId}/custom-field-values`),
  updateCustomFieldValue: (cardId, fieldId, data) => api.put(`/cards/${cardId}/custom-field-values/${fieldId}`, data),
}

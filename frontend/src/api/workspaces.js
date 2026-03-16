import api from './axios'

export const workspacesApi = {
  list: () => api.get('/workspaces'),
  create: (data) => api.post('/workspaces', data),
  show: (id) => api.get(`/workspaces/${id}`),
  update: (id, data) => api.put(`/workspaces/${id}`, data),
  destroy: (id) => api.delete(`/workspaces/${id}`),
  members: (id) => api.get(`/workspaces/${id}/members`),
  removeMember: (id, userId) => api.delete(`/workspaces/${id}/members/${userId}`),
  updateMemberRole: (id, userId, data) => api.patch(`/workspaces/${id}/members/${userId}/role`, data),
  invitations: (id) => api.get(`/workspaces/${id}/invitations`),
  invite: (id, data) => api.post(`/workspaces/${id}/invitations`, data),
  cancelInvitation: (id, invId) => api.delete(`/workspaces/${id}/invitations/${invId}`),
  acceptInvitation: (token) => api.post(`/invitations/${token}/accept`),
  upgrade: (id, data) => api.post(`/workspaces/${id}/upgrade`, data),
}

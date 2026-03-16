import api from './axios'

export const apiKeysApi = {
  list: () => api.get('/api-keys'),
  create: (data) => api.post('/api-keys', data),
  delete: (id) => api.delete(`/api-keys/${id}`),
}

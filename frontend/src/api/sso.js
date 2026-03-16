import api from './axios'

export const ssoApi = {
  getConfig: () => api.get('/sso/config'),
  saveConfig: (data) => api.post('/sso/config', data),
  testConnection: () => api.post('/sso/config/test'),
  deleteConfig: () => api.delete('/sso/config'),
  discover: (email) => api.post('/sso/discover', { email }),
}

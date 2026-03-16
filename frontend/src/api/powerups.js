import api from './axios'

export const powerupsApi = {
  // Power-Ups management
  list: () => api.get('/power-ups'),
  installed: () => api.get('/power-ups/installed'),
  install: (slug) => api.post(`/power-ups/${slug}/install`),
  uninstall: (slug) => api.delete(`/power-ups/${slug}/uninstall`),
  updateConfig: (slug, config) => api.put(`/power-ups/${slug}/config`, { config }),

  // Slack
  testSlack: (webhookUrl) => api.post('/power-ups/slack/test', { webhook_url: webhookUrl }),

  // Google Calendar
  googleCalendarAuth: () => api.get('/power-ups/google-calendar/auth'),
  googleCalendarCallback: (code, state) => api.get(`/power-ups/google-calendar/callback?code=${code}&state=${state}`),
  syncCalendar: () => api.post('/power-ups/google-calendar/sync'),

  // Google Drive
  googleDriveAuth: () => api.get('/power-ups/google-drive/auth'),
  googleDriveCallback: (code, state) => api.get(`/power-ups/google-drive/callback?code=${code}&state=${state}`),
  searchDriveFiles: (query) => api.get('/power-ups/google-drive/files', { params: { q: query } }),
  attachDriveFile: (cardId, fileData) => api.post(`/cards/${cardId}/attachments/drive`, fileData),
}

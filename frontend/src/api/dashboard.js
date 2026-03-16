import api from './axios'

export const dashboardApi = {
  getMetrics: () => api.get('/dashboard/metrics'),
}

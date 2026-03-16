import api from './axios'

export const plansApi = {
  list: () => api.get('/plans'),
  usage: () => api.get('/plans/usage'),
}

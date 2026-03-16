import api from './axios'

export const billingApi = {
  createCheckout: (data) => api.post('/billing/checkout', data),
  checkoutStatus: (sessionId) => api.get(`/billing/checkout/status?session_id=${sessionId}`),
  changePlan: (data) => api.post('/billing/change-plan', data),
  cancel: () => api.post('/billing/cancel'),
  resume: () => api.post('/billing/resume'),
  status: () => api.get('/billing/status'),
  invoices: () => api.get('/billing/invoices'),
  portalUrl: () => api.get('/billing/portal'),
}

import api from './axios'

export const userApi = {
  updateProfile: (formData) => api.post('/user/profile', formData),
  updatePassword: (data) => api.put('/user/password', data),
}

const apiUrl = import.meta.env.VITE_API_URL || ''

export function useMediaUrl() {
  function fullUrl(url) {
    if (!url) return ''
    if (url.startsWith('http')) return url
    return `${apiUrl}${url}`
  }

  return { fullUrl }
}

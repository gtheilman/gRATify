import { ofetch } from 'ofetch'
import { getXsrfToken } from '@/utils/csrf'

export const $api = ofetch.create({
  baseURL: '/api',
  credentials: 'same-origin',
  async onRequest({ options }) {
    const xsrfToken = getXsrfToken()
    if (xsrfToken) {
      options.headers = {
        ...options.headers,
        'X-XSRF-TOKEN': xsrfToken,
      }
    }
  },
})

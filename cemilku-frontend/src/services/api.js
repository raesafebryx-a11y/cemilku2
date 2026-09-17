import axios from 'axios'

const api = axios.create({
    baseURL: 'http://127.0.0.1:8000/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json'
    }
})

// =====================================================
// REQUEST INTERCEPTOR
// =====================================================
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token')

        if (token) {
            config.headers.Authorization = `Bearer ${token}`
        }

        return config
    },
    (error) => {
        return Promise.reject(error)
    }
)

// =====================================================
// RESPONSE INTERCEPTOR
// =====================================================
api.interceptors.response.use(
    (response) => {
        return response
    },
    (error) => {
        // Jangan auto-logout jika request berasal dari endpoint /user saat proses callback
        const isUserEndpoint = error.config?.url?.includes('/user')

        if (error.response?.status === 401 && !isUserEndpoint) {
            localStorage.removeItem('token')
            localStorage.removeItem('user')
            localStorage.removeItem('username')
            localStorage.removeItem('role')
            localStorage.removeItem('userRole')
            localStorage.removeItem('isLoggedIn')
            delete api.defaults.headers.common['Authorization']
        }

        return Promise.reject(error)
    }
)

export default api
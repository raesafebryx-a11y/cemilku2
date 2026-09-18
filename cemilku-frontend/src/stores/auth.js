import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import api from '../services/api'

export const useAuthStore = defineStore('auth', () => {
    // =====================================================
    // STATE
    // =====================================================

    const user = ref(
        JSON.parse(localStorage.getItem('user') || 'null')
    )

    const token = ref(
        localStorage.getItem('token') || ''
    )

    const loading = ref(false)

    // =====================================================
    // COMPUTED
    // =====================================================

    const isLoggedIn = computed(() => {
        return !!token.value
    })

    const username = computed(() => {
        return user.value?.name || ''
    })

    const userRole = computed(() => {
        return user.value?.role || 'user'
    })

    const isAdmin = computed(() => {
        return userRole.value === 'admin'
    })

    // =====================================================
    // SIMPAN DATA LOGIN
    // =====================================================

    const saveAuth = (userData, tokenData) => {
        user.value = userData
        token.value = tokenData

        // Set Authorization Header di Axios Instance
        if (tokenData) {
            api.defaults.headers.common['Authorization'] = `Bearer ${tokenData}`
        }

        localStorage.setItem(
            'user',
            JSON.stringify(userData)
        )

        localStorage.setItem(
            'token',
            tokenData
        )

        // Untuk kompatibilitas dengan kode lama
        localStorage.setItem(
            'username',
            userData?.name || ''
        )

        localStorage.setItem(
            'role',
            userData?.role || 'user'
        )

        localStorage.setItem(
            'userRole',
            userData?.role || 'user'
        )

        localStorage.setItem(
            'isLoggedIn',
            'true'
        )
    }

    // =====================================================
    // REGISTER
    // =====================================================

    const register = async (data) => {
        loading.value = true

        try {
            const response = await api.post(
                '/register',
                data
            )

            const userData = response.data.user
            const tokenData = response.data.token

            // Laravel register langsung memberikan token.
            // Jadi user langsung dianggap login.
            saveAuth(
                userData,
                tokenData
            )

            return response
        } catch (error) {
            console.error(
                'Auth register error:',
                error
            )

            throw error
        } finally {
            loading.value = false
        }
    }

    // =====================================================
    // LOGIN
    // =====================================================

    const login = async (data) => {
        loading.value = true

        try {
            const response = await api.post(
                '/login',
                data
            )

            const userData = response.data.user
            const tokenData = response.data.token

            saveAuth(
                userData,
                tokenData
            )

            return response
        } catch (error) {
            console.error(
                'Auth login error:',
                error
            )

            throw error
        } finally {
            loading.value = false
        }
    }

    // =====================================================
    // LOGIN DENGAN TOKEN (dari Google OAuth callback)
    // =====================================================

    const loginWithToken = async (tokenData) => {
        token.value = tokenData

        // 1. Set Authorization Header di Axios sebelum fetchUser dipanggil
        api.defaults.headers.common['Authorization'] = `Bearer ${tokenData}`

        // 2. Simpan token ke localStorage
        localStorage.setItem('token', tokenData)
        localStorage.setItem('isLoggedIn', 'true')

        // 3. Ambil data user dari backend
        const userData = await fetchUser()

        if (!userData) {
            throw new Error('Gagal mengambil data user setelah login Google.')
        }

        // 4. Update data user ke localStorage
        saveAuth(userData, tokenData)

        return userData
    }

    // =====================================================
    // LOGOUT
    // =====================================================

    const logout = async () => {
        try {
            if (token.value) {
                await api.post('/logout')
            }
        } catch (error) {
            console.error(
                'Logout error:',
                error
            )
        } finally {
            user.value = null
            token.value = ''

            // Hapus Header Axios
            delete api.defaults.headers.common['Authorization']

            localStorage.removeItem('token')
            localStorage.removeItem('user')
            localStorage.removeItem('username')
            localStorage.removeItem('role')
            localStorage.removeItem('userRole')
            localStorage.removeItem('isLoggedIn')
        }
    }

    // =====================================================
    // CEK USER LOGIN
    // =====================================================

    const fetchUser = async () => {
        if (!token.value) {
            return null
        }

        try {
            // Pastikan Authorization header selalu terpasang
            api.defaults.headers.common['Authorization'] = `Bearer ${token.value}`

            const response = await api.get('/user')

            // Tangani fleksibilitas struktur JSON response dari Laravel
            const fetchedUserData = response.data?.data || response.data

            user.value = fetchedUserData

            localStorage.setItem(
                'user',
                JSON.stringify(fetchedUserData)
            )

            localStorage.setItem(
                'username',
                fetchedUserData?.name || ''
            )

            localStorage.setItem(
                'role',
                fetchedUserData?.role || 'user'
            )

            localStorage.setItem(
                'userRole',
                fetchedUserData?.role || 'user'
            )

            return fetchedUserData
        } catch (error) {
            console.error(
                'Fetch user error:',
                error
            )

            user.value = null
            token.value = ''

            delete api.defaults.headers.common['Authorization']

            localStorage.removeItem('token')
            localStorage.removeItem('user')
            localStorage.removeItem('username')
            localStorage.removeItem('role')
            localStorage.removeItem('userRole')
            localStorage.removeItem('isLoggedIn')

            return null
        }
    }

    // =====================================================
    // RETURN
    // =====================================================

    return {
        user,
        token,
        loading,

        isLoggedIn,
        username,
        userRole,
        isAdmin,

        register,
        login,
        loginWithToken,
        logout,
        fetchUser
    }
})
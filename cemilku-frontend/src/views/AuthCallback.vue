<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const errorMessage = ref('')

onMounted(async () => {
  const token = route.query.token

  if (!token) {
    errorMessage.value = 'Token tidak ditemukan.'

    await Swal.fire({
      icon: 'error',
      title: 'Login Google Gagal',
      text: 'Token tidak diterima dari server. Silakan coba lagi.',
      confirmButtonColor: '#2563eb'
    })

    router.replace('/login')
    return
  }

  try {
    await auth.loginWithToken(token)

    await Swal.fire({
      icon: 'success',
      title: 'Login Berhasil! 🎉',
      text: `Selamat datang, ${auth.username}!`,
      timer: 1500,
      showConfirmButton: false,
      timerProgressBar: true
    })

    if (auth.userRole === 'admin') {
      router.replace('/admin')
    } else {
      router.replace('/')
    }
  } catch (error) {
    console.error('Google login callback error:', error)

    await Swal.fire({
      icon: 'error',
      title: 'Login Google Gagal',
      text: 'Terjadi kesalahan saat memproses login. Silakan coba lagi.',
      confirmButtonColor: '#2563eb'
    })

    router.replace('/login')
  }
})
</script>

<template>
  <div class="callback-wrapper">
    <div class="spinner"></div>
    <p>Memproses login dengan Google...</p>
  </div>
</template>

<style scoped>
.callback-wrapper {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  background: #f6f8fb;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.callback-wrapper p {
  color: #64748b;
  font-size: 0.9rem;
}
</style>
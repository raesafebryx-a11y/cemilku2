<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const router = useRouter()
const auth = useAuthStore()


// ==========================================
// AUTH
// ==========================================

const isLoggedIn = computed(() => auth.isLoggedIn)
const userRole = computed(() => auth.userRole)
const username = computed(() => auth.username)


// ==========================================
// DARK MODE
// ==========================================

const isDarkMode = ref(false)

const toggleDarkMode = () => {
  isDarkMode.value = !isDarkMode.value

  localStorage.setItem(
    'theme',
    isDarkMode.value ? 'dark' : 'light'
  )
}

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

const loadTheme = () => {
  const savedTheme = localStorage.getItem('theme')

  if (savedTheme === 'dark') {
    isDarkMode.value = true
  }
}


// ==========================================
// LOGO
// ==========================================

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  event.target.src =
    'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'
}


// ==========================================
// FORM CONTACT
// ==========================================

const contactForm = ref({
  name: '',
  email: '',
  message: ''
})

const isSending = ref(false)


// ==========================================
// DATA USER
// ==========================================

const fillUserData = () => {
  if (!auth.user) {
    return
  }

  contactForm.value.name = auth.user.name || ''
  contactForm.value.email = auth.user.email || ''
}


// ==========================================
// KIRIM PESAN
// ==========================================

const handleSendMessage = async () => {
  const isDark = isDarkMode.value

  const name = contactForm.value.name.trim()
  const email = contactForm.value.email.trim()
  const message = contactForm.value.message.trim()

  // Validasi
  if (!name || !email || !message) {
    await Swal.fire({
      icon: 'warning',
      title: 'Form Belum Lengkap',
      text: 'Harap isi nama, email, dan pesan terlebih dahulu.',
      confirmButtonColor: '#2563eb',
      background: isDark ? '#1e293b' : '#ffffff',
      color: isDark ? '#f8fafc' : '#1e293b'
    })

    return
  }

  isSending.value = true

  try {
    const response = await api.post('/contacts', {
      name: name,
      email: email,
      message: message
    })

    console.log('Pesan berhasil dikirim:', response.data)

    await Swal.fire({
      icon: 'success',
      title: 'Pesan Terkirim! 🚀',
      text:
        response.data?.message ||
        'Terima kasih telah menghubungi Cemilku.',
      confirmButtonColor: '#2563eb',
      background: isDark ? '#1e293b' : '#ffffff',
      color: isDark ? '#f8fafc' : '#1e293b'
    })

    // Pesan dikosongkan setelah berhasil
    contactForm.value.message = ''

  } catch (error) {
    console.error('Gagal mengirim pesan:', error)

    let errorMessage =
      'Pesan gagal dikirim. Silakan coba lagi.'

    // Laravel Validation Error
    if (error.response?.status === 422) {
      const errors = error.response?.data?.errors

      if (errors) {
        const firstError = Object.values(errors)[0]

        errorMessage = Array.isArray(firstError)
          ? firstError[0]
          : firstError
      } else {
        errorMessage =
          error.response?.data?.message ||
          'Data yang dikirim tidak valid.'
      }
    }

    // Unauthorized
    else if (error.response?.status === 401) {
      errorMessage =
        'Sesi login kamu sudah berakhir. Silakan login kembali.'
    }

    // Server Error
    else if (error.response?.status >= 500) {
      errorMessage =
        'Terjadi kesalahan pada server Laravel.'
    }

    // Error lainnya
    else if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    }

    await Swal.fire({
      icon: 'error',
      title: 'Gagal Mengirim',
      text: errorMessage,
      confirmButtonColor: '#2563eb',
      background: isDark ? '#1e293b' : '#ffffff',
      color: isDark ? '#f8fafc' : '#1e293b'
    })

  } finally {
    isSending.value = false
  }
}


// ==========================================
// LOGOUT
// ==========================================

const handleLogout = async () => {
  const isDark = isDarkMode.value

  const result = await Swal.fire({
    title: 'Konfirmasi Keluar',
    text: 'Apakah Anda yakin ingin keluar dari akun ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal',
    background: isDark ? '#1e293b' : '#ffffff',
    color: isDark ? '#f8fafc' : '#1e293b'
  })

  if (!result.isConfirmed) {
    return
  }

  try {
    await auth.logout()
  } catch (error) {
    console.error('Logout error:', error)
  }

  await Swal.fire({
    icon: 'success',
    title: 'Berhasil Keluar 👋',
    text: 'Anda telah keluar dari akun.',
    timer: 1500,
    showConfirmButton: false,
    timerProgressBar: true,
    background: isDark ? '#1e293b' : '#ffffff',
    color: isDark ? '#f8fafc' : '#1e293b'
  })

  router.push('/')
}


// ==========================================
// SAAT HALAMAN DIBUKA
// ==========================================

onMounted(async () => {
  loadTheme()
  window.addEventListener('cemilku-theme-change', handleThemeChange)

  // Kalau token ada tetapi data user belum ada,
  // ambil data user dari Laravel.
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch (error) {
      console.error('Gagal mengambil data user:', error)
    }
  }

  fillUserData()
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="page-wrapper" :class="{ 'dark-mode': isDarkMode }">
    <!-- BACKGROUND AMBIENT GLOW -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <!-- MAIN CONTENT -->
    <main class="main-content">
      <div class="container">
        
        <!-- HERO PAGE HEADER -->
        <section class="page-hero">
          <div class="hero-badge">
            <span class="pulse-dot"></span>
            Layanan Pelanggan
          </div>
          <h1 class="page-title">
            Hubungi Tim <span class="gradient-text">Cemilku Store</span>
          </h1>
          <p class="page-desc">
            Punya pertanyaan seputar produk, pesanan khusus, atau tawaran kerja sama? Kami siap mendengarkan dan membantu Anda.
          </p>
        </section>

        <!-- CONTACT CONTENT GRID -->
        <section class="contact-grid">
          
          <!-- LEFT CONTACT CARDS -->
          <div class="contact-info-list">
            <div class="contact-info-card">
              <div class="info-icon">📍</div>
              <div>
                <h4>Alamat Utama</h4>
                <p>Jl. Camilan Lezat No. 12, Bandung, Jawa Barat</p>
              </div>
            </div>

            <div class="contact-info-card">
              <div class="info-icon">💬</div>
              <div>
                <h4>WhatsApp CS</h4>
                <p>+62 961-986-9600 (Respon Cepat)</p>
              </div>
            </div>

            <div class="contact-info-card">
              <div class="info-icon">✉️</div>
              <div>
                <h4>Email Resmi</h4>
                <p>admin@cemilku.com</p>
              </div>
            </div>

            <div class="contact-info-card">
              <div class="info-icon">⏰</div>
              <div>
                <h4>Jam Operasional</h4>
                <p>Senin - Sabtu: 08:00 - 20:00 WIB</p>
              </div>
            </div>
          </div>

          <!-- RIGHT CONTACT FORM CARD -->
          <div class="contact-form-card">
            <h3>Kirim Pesan Direct</h3>
            <p class="form-sub">Isi formulir di bawah ini untuk terhubung langsung.</p>

            <form @submit.prevent="handleSendMessage" class="contact-form">
              <div class="form-group">
                <label>Nama Lengkap</label>
                <input 
                  type="text" 
                  v-model="contactForm.name"
                  placeholder="Masukkan nama Anda..."
                />
              </div>

              <div class="form-group">
                <label>Alamat Email</label>
                <input 
                  type="email" 
                  v-model="contactForm.email"
                  placeholder="nama@email.com"
                />
              </div>

              <div class="form-group">
                <label>Pesan Anda</label>
                <textarea 
                  rows="4" 
                  v-model="contactForm.message"
                  placeholder="Tuliskan pertanyaan atau pesan Anda di sini..."
                ></textarea>
              </div>

              <button type="submit" class="btn-send-message" :disabled="isSending">
                {{ isSending ? 'Mengirim...' : 'Kirim Pesan 🚀' }}
              </button>
            </form>
          </div>

        </section>

      </div>
    </main>

  </div>
</template>

<style scoped>
/* GLOBAL & BASE STYLES */
.page-wrapper {
  min-height: 100vh;
  background-color: #f6f8fb;
  color: #1e293b;
  position: relative;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
  scroll-behavior: smooth;
  transition: background-color 0.3s ease, color 0.3s ease;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  width: 100%;
  box-sizing: border-box;
}

/* SOFT ICE BLUE BACKGROUND SHAPES */
.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(140px);
  pointer-events: none;
  z-index: 0;
  opacity: 0.7;
}

.bg-shape-1 {
  top: -120px;
  right: -80px;
  width: 550px;
  height: 550px;
  background: radial-gradient(circle, rgba(147, 197, 253, 0.45) 0%, rgba(191, 219, 254, 0.15) 70%);
}

.bg-shape-2 {
  top: 350px;
  left: -120px;
  width: 500px;
  height: 500px;
  background: radial-gradient(circle, rgba(96, 165, 250, 0.3) 0%, rgba(224, 242, 254, 0.1) 70%);
}

.bg-shape-3 {
  bottom: 100px;
  right: 15%;
  width: 400px;
  height: 400px;
  background: radial-gradient(circle, rgba(186, 230, 253, 0.4) 0%, rgba(240, 249, 255, 0.05) 70%);
}

/* NAVBAR GLASSMORPHISM */
.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  background: rgba(255, 255, 255, 0.75);
  border-bottom: 1px solid rgba(226, 232, 240, 0.8);
  transition: all 0.3s ease;
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  height: 76px;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.brand-logo:hover {
  transform: translateY(-2px) scale(1.02);
}

.brand-mark {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 6px;
  box-shadow: 0 8px 16px -4px rgba(37, 99, 235, 0.3);
}

.brand-mark.small {
  width: 34px;
  height: 34px;
  border-radius: 10px;
}

.cemilku-logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.brand-text {
  color: #0f172a;
  font-size: 1.35rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  line-height: 1;
}

.brand-info small {
  display: block;
  margin-top: 3px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: #2563eb;
  font-size: 0.6rem;
  font-weight: 800;
}

/* NAVIGATION MENU */
.nav-links {
  display: flex;
  align-items: center;
  gap: 4px;
  background: rgba(241, 245, 249, 0.7);
  padding: 5px;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
}

.nav-btn {
  background: transparent;
  border: none;
  color: #64748b;
  padding: 8px 18px;
  font-size: 0.85rem;
  font-weight: 600;
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.nav-btn:hover {
  color: #2563eb;
  background: rgba(255, 255, 255, 0.5);
}

.nav-btn.active {
  background: #ffffff;
  color: #2563eb;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
}

.btn-dashboard {
  background: #2563eb !important;
  color: #ffffff !important;
}

.btn-dashboard:hover {
  background: #1d4ed8 !important;
  box-shadow: 0 6px 16px rgba(37, 99, 235, 0.25);
}

/* USER AUTH & THEME BUTTONS */
.nav-auth {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-theme-toggle {
  background: rgba(241, 245, 249, 0.8);
  border: 1px solid #e2e8f0;
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  font-size: 1.1rem;
  transition: all 0.25s ease;
}

.btn-theme-toggle:hover {
  transform: scale(1.08);
}

.user-greeting {
  display: flex;
  flex-direction: column;
  text-align: right;
  line-height: 1.2;
}

.greet-label {
  font-size: 0.65rem;
  color: #64748b;
}

.user-email {
  font-size: 0.85rem;
  color: #1e293b;
  font-weight: 700;
}

.btn-profile {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: all 0.25s ease;
}

.btn-profile:hover {
  background: #2563eb;
  color: #ffffff;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
}

.btn-logout {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
  padding: 8px 18px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}

.btn-logout:hover {
  background: #dc2626;
  color: #ffffff;
  box-shadow: 0 6px 14px rgba(220, 38, 38, 0.2);
}

.btn-outline {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
  padding: 8px 20px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}

.btn-outline:hover {
  border-color: #3b82f6;
  color: #2563eb;
  background: #f0f7ff;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  border: none;
  color: #ffffff;
  padding: 8px 20px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.25);
  transition: all 0.25s ease;
}

.btn-primary:hover {
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.35);
  transform: translateY(-1.5px);
}

/* MAIN CONTENT */
.main-content {
  flex: 1;
  padding: 50px 0 80px;
  position: relative;
  z-index: 1;
}

/* HERO PAGE HEADER */
.page-hero {
  text-align: center;
  max-width: 760px;
  margin: 0 auto 50px auto;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 700;
  padding: 6px 14px;
  border-radius: 20px;
  margin-bottom: 20px;
  border: 1px solid #bfdbfe;
  box-shadow: 0 2px 8px rgba(37, 99, 235, 0.06);
}

.pulse-dot {
  width: 7px;
  height: 7px;
  background-color: #2563eb;
  border-radius: 50%;
  box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7);
  animation: pulse 1.8s infinite;
}

@keyframes pulse {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(37, 99, 235, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
}

.page-title {
  font-size: 2.8rem;
  font-weight: 800;
  line-height: 1.2;
  margin-bottom: 16px;
  letter-spacing: -0.03em;
  color: #0f172a;
}

.gradient-text {
  background: linear-gradient(135deg, #1d4ed8, #3b82f6);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.page-desc {
  color: #64748b;
  font-size: 1.05rem;
  line-height: 1.65;
  margin: 0 auto;
}

/* CONTACT GRID */
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1.2fr;
  gap: 32px;
  align-items: start;
}

.contact-info-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.contact-info-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  padding: 22px 24px;
  display: flex;
  align-items: center;
  gap: 18px;
  box-shadow: 0 6px 20px -5px rgba(37, 99, 235, 0.04);
  transition: transform 0.3s ease;
}

.contact-info-card:hover {
  transform: translateY(-3px);
  border-color: #cbd5e1;
}

.info-icon {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #eff6ff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  flex-shrink: 0;
  border: 1px solid #dbeafe;
}

.contact-info-card h4 {
  font-size: 1.05rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 3px 0;
}

.contact-info-card p {
  font-size: 0.875rem;
  color: #64748b;
  margin: 0;
}

/* CONTACT FORM CARD */
.contact-form-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 28px;
  padding: 36px 32px;
  box-shadow: 0 16px 35px -10px rgba(37, 99, 235, 0.08);
}

.contact-form-card h3 {
  font-size: 1.45rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.form-sub {
  color: #64748b;
  font-size: 0.9rem;
  margin: 0 0 24px 0;
}

.contact-form {
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.form-group label {
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
}

.form-group input,
.form-group textarea {
  width: 100%;
  padding: 14px 16px;
  background: #f8fafc;
  border: 1.5px solid #e2e8f0;
  border-radius: 14px;
  font-size: 0.9rem;
  color: #0f172a;
  outline: none;
  font-family: inherit;
  transition: all 0.25s ease;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group textarea:focus {
  border-color: #3b82f6;
  background: #ffffff;
  box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
}

.btn-send-message {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #ffffff;
  border: none;
  padding: 14px;
  border-radius: 14px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(37, 99, 235, 0.25);
  transition: all 0.25s ease;
  margin-top: 6px;
}

.btn-send-message:hover:not(:disabled) {
  box-shadow: 0 10px 24px rgba(37, 99, 235, 0.35);
  transform: translateY(-2px);
}

.btn-send-message:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* FOOTER */
.footer {
  border-top: 1px solid #e2e8f0;
  padding: 52px 0 28px;
  background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
  margin-top: auto;
  transition: all 0.3s ease;
}

.footer-content {
  display: flex;
  flex-direction: column;
  gap: 26px;
}

.footer-grid {
  display: grid;
  grid-template-columns: 1.4fr 0.8fr 1fr;
  gap: 32px;
  align-items: start;
}

.footer-brand-block {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.footer-brand {
  display: flex;
  align-items: center;
  gap: 8px;
}

.footer-desc {
  color: #64748b;
  font-size: 0.875rem;
  line-height: 1.7;
  max-width: 420px;
  margin: 0;
  text-align: left;
}

.social-row {
  display: flex;
  align-items: center;
  gap: 10px;
}

.social-row a {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 1rem;
  transition: all 0.25s ease;
}

.social-row a:hover {
  background: #2563eb;
  color: #ffffff;
  transform: translateY(-2px);
}

.footer-column h4 {
  font-size: 0.92rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 14px;
  text-align: left;
}

.footer-column ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.footer-column a,
.contact-list li {
  color: #64748b;
  font-size: 0.85rem;
  text-decoration: none;
  line-height: 1.6;
  text-align: left;
}

.footer-column a:hover {
  color: #2563eb;
}

.contact-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.footer-bottom {
  border-top: 1px solid #e2e8f0;
  width: 100%;
  padding-top: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
}

.footer-bottom small {
  color: #94a3b8;
  font-size: 0.75rem;
}

.footer-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  color: #64748b;
  font-size: 0.75rem;
}

/* ============================================================ */
/* DARK MODE STYLES                                            */
/* ============================================================ */
.dark-mode {
  background-color: #0f172a !important;
  color: #f8fafc !important;
}

.dark-mode .bg-shape-1 {
  background: radial-gradient(circle, rgba(37, 99, 235, 0.25) 0%, rgba(15, 23, 42, 0.1) 70%);
}

.dark-mode .bg-shape-2 {
  background: radial-gradient(circle, rgba(29, 78, 216, 0.2) 0%, rgba(15, 23, 42, 0.05) 70%);
}

.dark-mode .navbar {
  background: rgba(15, 23, 42, 0.85) !important;
  border-bottom-color: rgba(51, 65, 85, 0.8) !important;
}

.dark-mode .brand-text {
  color: #f8fafc !important;
}

.dark-mode .nav-links {
  background: rgba(30, 41, 59, 0.7) !important;
  border-color: #334155 !important;
}

.dark-mode .nav-btn {
  color: #94a3b8 !important;
}

.dark-mode .nav-btn:hover {
  color: #60a5fa !important;
  background: rgba(51, 65, 85, 0.5) !important;
}

.dark-mode .nav-btn.active {
  background: #1e293b !important;
  color: #60a5fa !important;
}

.dark-mode .btn-theme-toggle {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

.dark-mode .user-email {
  color: #f8fafc !important;
}

.dark-mode .greet-label {
  color: #94a3b8 !important;
}

.dark-mode .btn-profile {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #60a5fa !important;
}

.dark-mode .btn-profile:hover {
  background: #2563eb !important;
  color: #ffffff !important;
}

.dark-mode .btn-outline {
  background: #1e293b !important;
  border-color: #334155 !important;
  color: #cbd5e1 !important;
}

.dark-mode .page-title {
  color: #f8fafc !important;
}

.dark-mode .page-desc {
  color: #94a3b8 !important;
}

.dark-mode .hero-badge {
  background: rgba(30, 41, 59, 0.8) !important;
  color: #60a5fa !important;
  border-color: #1e3a8a !important;
}

.dark-mode .contact-info-card {
  background: #1e293b !important;
  border-color: #334155 !important;
}

.dark-mode .info-icon {
  background: #0f172a !important;
  border-color: #334155 !important;
}

.dark-mode .contact-info-card h4 {
  color: #f8fafc !important;
}

.dark-mode .contact-info-card p {
  color: #94a3b8 !important;
}

.dark-mode .contact-form-card {
  background: #1e293b !important;
  border-color: #334155 !important;
}

.dark-mode .contact-form-card h3 {
  color: #f8fafc !important;
}

.dark-mode .form-sub {
  color: #94a3b8 !important;
}

.dark-mode .form-group label {
  color: #cbd5e1 !important;
}

.dark-mode .form-group input,
.dark-mode .form-group textarea {
  background: #0f172a !important;
  border-color: #334155 !important;
  color: #f8fafc !important;
}

.dark-mode .form-group input:focus,
.dark-mode .form-group textarea:focus {
  border-color: #3b82f6 !important;
}

.dark-mode .footer {
  background: linear-gradient(180deg, rgba(15, 23, 42, 1) 0%, rgba(15, 23, 42, 0.96) 100%) !important;
  border-top-color: #1e293b !important;
}

.dark-mode .footer-desc,
.dark-mode .footer-column a,
.dark-mode .contact-list li,
.dark-mode .footer-meta {
  color: #94a3b8 !important;
}

.dark-mode .footer-column h4 {
  color: #f8fafc !important;
}

.dark-mode .social-row a {
  background: rgba(30, 41, 59, 0.9) !important;
  color: #60a5fa !important;
}

.dark-mode .footer-bottom {
  border-top-color: #1e293b !important;
}

/* RESPONSIVE */
@media (max-width: 900px) {
  .contact-grid {
    grid-template-columns: 1fr;
  }

  .footer-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>
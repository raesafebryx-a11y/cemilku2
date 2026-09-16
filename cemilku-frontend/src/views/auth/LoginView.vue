<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

// =====================================================
// STATE
// =====================================================

const email = ref('')
const password = ref('')

const errorMessage = ref('')
const loading = ref(false)
const googleLoading = ref(false)

// =====================================================
// LOGO
// =====================================================

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  event.target.src =
    'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'
}

// =====================================================
// LOGIN
// =====================================================

const handleLogin = async () => {
  errorMessage.value = ''

  const loginEmail = email.value.trim()
  const loginPassword = password.value

  // ===================================================
  // VALIDASI
  // ===================================================

  if (!loginEmail || !loginPassword) {
    errorMessage.value =
      'Email dan password wajib diisi.'

    await Swal.fire({
      icon: 'warning',
      title: 'Data Belum Lengkap',
      text: 'Silakan masukkan email dan password terlebih dahulu.',
      confirmButtonColor: '#2563eb',
      background: '#ffffff',
      color: '#1e293b'
    })

    return
  }

  // ===================================================
  // LOADING
  // ===================================================

  loading.value = true

  try {
    // =================================================
    // LOGIN KE BACKEND
    // =================================================

    await auth.login({
      email: loginEmail,
      password: loginPassword
    })

    console.log('Login berhasil:', auth.user)
    console.log('Role user:', auth.userRole)

    // =================================================
    // LOGIN BERHASIL
    // =================================================

    await Swal.fire({
      icon: 'success',
      title: 'Login Berhasil! 🎉',
      text: `Selamat datang kembali, ${auth.username}!`,
      timer: 1500,
      showConfirmButton: false,
      timerProgressBar: true,
      background: '#ffffff',
      color: '#1e293b'
    })

    // =================================================
    // REDIRECT BERDASARKAN ROLE
    // =================================================

    if (auth.userRole === 'admin') {
      router.push('/admin')
    } else {
      router.push('/')
    }

  } catch (error) {
    console.error('Login error:', error)

    // =================================================
    // ERROR VALIDASI 422
    // =================================================

    if (error.response?.status === 422) {
      const errors =
        error.response?.data?.errors

      if (errors) {
        const firstError =
          Object.values(errors)[0]

        if (Array.isArray(firstError)) {
          errorMessage.value =
            firstError[0]
        } else {
          errorMessage.value =
            firstError
        }
      } else {
        errorMessage.value =
          error.response?.data?.message ||
          'Data login tidak valid.'
      }

    // =================================================
    // EMAIL / PASSWORD SALAH
    // =================================================

    } else if (error.response?.status === 401) {
      errorMessage.value =
        error.response?.data?.message ||
        'Email atau password salah.'

    // =================================================
    // ERROR SERVER
    // =================================================

    } else if (error.response?.status >= 500) {
      errorMessage.value =
        'Terjadi kesalahan pada server Laravel. Pastikan backend berjalan dengan benar.'

    // =================================================
    // ERROR LAINNYA
    // =================================================

    } else {
      errorMessage.value =
        error.response?.data?.message ||
        'Login gagal. Pastikan server Laravel sedang berjalan.'
    }

    // =================================================
    // SWEETALERT ERROR
    // =================================================

    await Swal.fire({
      icon: 'error',
      title: 'Login Gagal',
      text: errorMessage.value,
      confirmButtonColor: '#2563eb',
      background: '#ffffff',
      color: '#1e293b'
    })

  } finally {
    loading.value = false
  }
}

// =====================================================
// LOGIN DENGAN GOOGLE
// =====================================================

const loginWithGoogle = async () => {
  googleLoading.value = true

  try {
    const response = await api.get('/auth/google/redirect')
    window.location.href = response.data.url
  } catch (error) {
    console.error('Gagal memulai login Google:', error)

    await Swal.fire({
      icon: 'error',
      title: 'Gagal Membuka Login Google',
      text: 'Pastikan server Laravel sedang berjalan.',
      confirmButtonColor: '#2563eb',
      background: '#ffffff',
      color: '#1e293b'
    })

    googleLoading.value = false
  }
}

// =====================================================
// KEMBALI KE HOME
// =====================================================

const goHome = () => {
  router.push('/')
}
</script>

<template>
  <div class="auth-wrapper">

    <!-- ==========================================
         BACKGROUND GLOW
    =========================================== -->

    <div class="hero-bg-glow"></div>

    <!-- ==========================================
         NAVBAR
    =========================================== -->

    <header class="navbar">
      <div class="container nav-content">

        <!-- BRAND -->

        <div
          class="brand-logo"
          @click="goHome"
        >
          <div class="brand-mark">
            <img
              :src="logoCemilku"
              alt="Logo Cemilku"
              class="cemilku-logo-img"
              draggable="false"
              @error="handleLogoError"
            />
          </div>

          <div class="brand-info">
            <span class="brand-text">
              Cemilku
            </span>

            <small>
              SNACK STORE
            </small>
          </div>
        </div>

        <!-- KEMBALI -->

        <button
          class="btn-outline"
          type="button"
          @click="goHome"
        >
          ← Kembali
        </button>

      </div>
    </header>

    <!-- ==========================================
         MAIN
    =========================================== -->

    <main class="auth-main">

      <div class="container auth-container">

        <!-- ======================================
             LEFT CONTENT
        ======================================= -->

        <section class="auth-intro">

          <span class="hero-label">
            ✨ SELAMAT DATANG
          </span>

          <h1 class="hero-title">
            Masuk untuk Melanjutkan
            <br>

            <span class="gradient-text">
              Pemesanan Anda.
            </span>
          </h1>

          <p class="hero-subtitle">
            Nikmati pengalaman pemesanan camilan
            yang lebih mudah, cepat, dan terorganisir
            bersama Cemilku.
          </p>

          <!-- BENEFITS -->

          <div class="auth-benefit">

            <!-- BENEFIT 01 -->

            <div class="benefit-item">

              <div class="benefit-icon">
                01
              </div>

              <div>
                <strong>
                  Produk Pilihan
                </strong>

                <span>
                  Berbagai camilan terfavorit
                  kualitas terbaik.
                </span>
              </div>

            </div>

            <!-- BENEFIT 02 -->

            <div class="benefit-item">

              <div class="benefit-icon">
                02
              </div>

              <div>
                <strong>
                  Checkout Sistem Langsung
                </strong>

                <span>
                  Pesanan tercatat otomatis
                  di sistem dashboard admin.
                </span>
              </div>

            </div>

            <!-- BENEFIT 03 -->

            <div class="benefit-item">

              <div class="benefit-icon">
                03
              </div>

              <div>
                <strong>
                  Akun Pelanggan
                </strong>

                <span>
                  Kelola dan pantau pesanan
                  Anda dengan praktis.
                </span>
              </div>

            </div>

          </div>

        </section>

        <!-- ======================================
             LOGIN CARD
        ======================================= -->

        <section class="auth-card hover-lift">

          <div class="card-glass-shine"></div>

          <!-- CARD HEADER -->

          <div class="auth-card-header">

            <span class="card-badge">
              LOGIN
            </span>

            <h2>
              Masuk Akun
            </h2>

            <p>
              Silakan masukkan akun Anda
              untuk melanjutkan.
            </p>

          </div>

          <!-- ====================================
               FORM
          ===================================== -->

          <form @submit.prevent="handleLogin">

            <!-- USERNAME / EMAIL -->

            <div class="auth-form-group">

              <label>
                Username atau Email
              </label>

              <input
                v-model="email"
                type="email"
                autocomplete="email"
                placeholder="nama@email.com"
                :disabled="loading"
                required
            />

            </div>

            <!-- PASSWORD -->

            <div class="auth-form-group">

              <label>
                Password
              </label>

              <input
                v-model="password"
                type="password"
                autocomplete="current-password"
                placeholder="Masukkan password"
                :disabled="loading"
                required
            />

            </div>

            <!-- ERROR -->

            <div
              v-if="errorMessage"
              class="auth-error"
            >
              <span>
                ⚠️
              </span>

              <span>
                {{ errorMessage }}
              </span>
            </div>

            <!-- LOGIN BUTTON -->

            <button
              type="submit"
              class="btn-primary auth-submit glow-on-hover"
              :disabled="loading"
            >

              <span v-if="loading">
                ⏳ Memproses...
              </span>

              <span v-else>
                Masuk ke Akun
              </span>

            </button>

          </form>

          <!-- ====================================
               INFORMATION
          ===================================== -->

          <div class="login-info">

            <div class="info-icon">
              🔐
            </div>

            <div>
              <strong>
                Login Aman
              </strong>

              <small>
                Gunakan akun yang sudah terdaftar
                pada sistem Cemilku.
              </small>
            </div>

          </div>

          <!-- DIVIDER -->

          <div class="auth-divider">

            <span class="line"></span>

            <p>
              atau
            </p>

            <span class="line"></span>

          </div>

          <!-- GOOGLE LOGIN -->

          <button
            type="button"
            class="btn-google"
            :disabled="googleLoading"
            @click="loginWithGoogle"
          >
            <svg viewBox="0 0 24 24" width="18" height="18">
              <path fill="#4285F4" d="M23.49 12.27c0-.79-.07-1.54-.19-2.27H12v4.51h6.47c-.28 1.48-1.13 2.73-2.4 3.58v2.98h3.88c2.27-2.09 3.58-5.17 3.58-8.8z"/>
              <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-2.98c-1.08.72-2.45 1.15-4.05 1.15-3.11 0-5.75-2.1-6.69-4.92H1.29v3.07C3.26 21.3 7.31 24 12 24z"/>
              <path fill="#FBBC05" d="M5.31 14.34C5.07 13.62 4.94 12.83 4.94 12s.13-1.62.37-2.34V6.59H1.29C.47 8.24 0 10.06 0 12s.47 3.76 1.29 5.41l4.02-3.07z"/>
              <path fill="#EA4335" d="M12 4.75c1.76 0 3.34.61 4.58 1.79l3.44-3.44C17.94 1.19 15.24 0 12 0 7.31 0 3.26 2.7 1.29 6.59l4.02 3.07C6.25 6.85 8.89 4.75 12 4.75z"/>
            </svg>
            <span v-if="googleLoading">Mengalihkan...</span>
            <span v-else>Login dengan Google</span>
          </button>

          <!-- REGISTER -->

          <p class="auth-switch">

            Belum memiliki akun?

            <router-link
              to="/register"
            >
              Daftar sekarang
            </router-link>

          </p>

        </section>

      </div>

    </main>

  </div>
</template>

<style scoped>

/* =====================================================
   AUTH WRAPPER
===================================================== */

.auth-wrapper {
  min-height: 100vh;

  background-color: #f6f8fb;

  color: #1e293b;

  position: relative;

  font-family:
    'Plus Jakarta Sans',
    system-ui,
    -apple-system,
    BlinkMacSystemFont,
    'Segoe UI',
    sans-serif;

  display: flex;
  flex-direction: column;

  overflow-x: hidden;
}

/* =====================================================
   BACKGROUND GLOW
===================================================== */

.auth-wrapper::before,
.auth-wrapper::after {
  content: '';

  position: absolute;

  border-radius: 50%;

  filter: blur(140px);

  pointer-events: none;

  z-index: 0;
}

.auth-wrapper::before {
  width: 520px;
  height: 520px;

  top: -120px;
  right: -80px;

  background:
    radial-gradient(
      circle,
      rgba(147, 197, 253, 0.45) 0%,
      rgba(191, 219, 254, 0.15) 70%
    );
}

.auth-wrapper::after {
  width: 480px;
  height: 480px;

  left: -100px;
  bottom: 150px;

  background:
    radial-gradient(
      circle,
      rgba(96, 165, 250, 0.3) 0%,
      rgba(224, 242, 254, 0.1) 70%
    );
}

/* =====================================================
   CONTAINER
===================================================== */

.container {
  width: 100%;

  max-width: 1200px;

  margin: 0 auto;

  padding: 0 24px;

  box-sizing: border-box;
}

/* =====================================================
   NAVBAR
===================================================== */

.navbar {
  position: sticky;

  top: 0;

  z-index: 50;

  backdrop-filter: blur(16px);

  -webkit-backdrop-filter: blur(16px);

  background:
    rgba(255, 255, 255, 0.75);

  border-bottom:
    1px solid rgba(226, 232, 240, 0.8);
}

.nav-content {
  height: 76px;

  display: flex;

  align-items: center;

  justify-content: space-between;

  gap: 20px;

  position: relative;

  z-index: 1;
}

/* =====================================================
   BRAND
===================================================== */

.brand-logo {
  display: flex;

  align-items: center;

  gap: 12px;

  cursor: pointer;

  transition:
    transform 0.3s
    cubic-bezier(
      0.34,
      1.56,
      0.64,
      1
    );
}

.brand-logo:hover {
  transform:
    translateY(-2px)
    scale(1.02);
}

.brand-mark {
  width: 42px;
  height: 42px;

  border-radius: 14px;

  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );

  display: flex;

  align-items: center;
  justify-content: center;

  overflow: hidden;

  padding: 6px;

  box-shadow:
    0 8px 16px -4px
    rgba(37, 99, 235, 0.3);
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

  display: block;
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

/* =====================================================
   MAIN
===================================================== */

.auth-main {
  flex: 1;

  display: flex;

  align-items: center;

  padding: 60px 0;

  position: relative;

  z-index: 1;
}

/* =====================================================
   AUTH CONTAINER
===================================================== */

.auth-container {
  display: grid;

  grid-template-columns:
    1.2fr 1fr;

  gap: 60px;

  align-items: center;
}

/* =====================================================
   HERO LABEL
===================================================== */

.hero-label {
  display: inline-flex;

  align-items: center;

  background: #eff6ff;

  color: #2563eb;

  font-size: 0.75rem;

  font-weight: 700;

  letter-spacing: 0.12em;

  text-transform: uppercase;

  padding: 7px 14px;

  border-radius: 20px;

  border:
    1px solid #bfdbfe;
}

/* =====================================================
   HERO TITLE
===================================================== */

.hero-title {
  font-size: 3rem;

  font-weight: 800;

  line-height: 1.15;

  margin: 20px 0 16px;

  letter-spacing: -0.03em;

  color: #0f172a;
}

.gradient-text {
  background:
    linear-gradient(
      135deg,
      #1d4ed8,
      #3b82f6
    );

  -webkit-background-clip: text;

  -webkit-text-fill-color: transparent;
}

/* =====================================================
   HERO SUBTITLE
===================================================== */

.hero-subtitle {
  color: #64748b;

  font-size: 1.05rem;

  line-height: 1.65;

  margin-bottom: 32px;

  max-width: 600px;
}

/* =====================================================
   BENEFITS
===================================================== */

.auth-benefit {
  display: flex;

  flex-direction: column;

  gap: 18px;
}

.benefit-item {
  display: flex;

  align-items: center;

  gap: 16px;

  background:
    rgba(255, 255, 255, 0.8);

  border:
    1px solid #e2e8f0;

  padding: 14px 18px;

  border-radius: 16px;

  transition:
    all 0.3s ease;
}

.benefit-item:hover {
  background: #ffffff;

  border-color: #cbd5e1;

  transform:
    translateX(4px);

  box-shadow:
    0 10px 24px
    rgba(15, 23, 42, 0.04);
}

.benefit-icon {
  background: #eff6ff;

  color: #2563eb;

  font-weight: 800;

  font-size: 0.85rem;

  width: 42px;
  height: 42px;

  border-radius: 12px;

  display: flex;

  align-items: center;
  justify-content: center;

  border:
    1px solid #bfdbfe;

  flex-shrink: 0;
}

.benefit-item strong {
  display: block;

  font-size: 0.95rem;

  color: #0f172a;

  margin-bottom: 2px;
}

.benefit-item span {
  font-size: 0.85rem;

  color: #64748b;

  line-height: 1.5;
}

/* =====================================================
   LOGIN CARD
===================================================== */

.auth-card {
  background:
    rgba(255, 255, 255, 0.88);

  backdrop-filter:
    blur(20px);

  -webkit-backdrop-filter:
    blur(20px);

  border:
    1px solid
    rgba(226, 232, 240, 0.9);

  border-radius: 28px;

  padding: 40px;

  box-shadow:
    0 20px 40px -15px
    rgba(37, 99, 235, 0.07);

  position: relative;

  overflow: hidden;

  transition:
    all 0.3s ease;
}

.auth-card:hover {
  box-shadow:
    0 28px 55px -18px
    rgba(37, 99, 235, 0.12);
}

/* =====================================================
   GLASS SHINE
===================================================== */

.card-glass-shine {
  position: absolute;

  top: -100px;

  right: -100px;

  width: 220px;
  height: 220px;

  border-radius: 50%;

  background:
    radial-gradient(
      circle,
      rgba(219, 234, 254, 0.55),
      rgba(255, 255, 255, 0)
    );

  pointer-events: none;
}

/* =====================================================
   CARD HEADER
===================================================== */

.auth-card-header {
  margin-bottom: 24px;

  position: relative;

  z-index: 1;
}

.card-badge {
  color: #2563eb;

  font-size: 0.75rem;

  font-weight: 800;

  letter-spacing: 0.15em;

  display: block;

  margin-bottom: 6px;
}

.auth-card-header h2 {
  font-size: 1.8rem;

  font-weight: 800;

  color: #0f172a;

  margin-bottom: 6px;
}

.auth-card-header p {
  color: #64748b;

  font-size: 0.9rem;

  line-height: 1.5;
}

/* =====================================================
   FORM
===================================================== */

.auth-form-group {
  margin-bottom: 20px;

  position: relative;

  z-index: 1;
}

.auth-form-group label {
  display: block;

  font-size: 0.85rem;

  font-weight: 700;

  color: #334155;

  margin-bottom: 8px;
}

.auth-form-group input {
  width: 100%;

  padding: 14px 16px;

  background: #ffffff;

  border:
    1.5px solid #e2e8f0;

  border-radius: 12px;

  font-size: 0.95rem;

  color: #0f172a;

  box-sizing: border-box;

  outline: none;

  transition:
    all 0.3s ease;
}

.auth-form-group input::placeholder {
  color: #94a3b8;
}

.auth-form-group input:focus {
  border-color: #3b82f6;

  box-shadow:
    0 0 0 4px
    rgba(59, 130, 246, 0.12);

  background: #ffffff;
}

.auth-form-group input:disabled {
  background: #f8fafc;

  cursor: not-allowed;

  opacity: 0.7;
}

/* =====================================================
   ERROR
===================================================== */

.auth-error {
  display: flex;

  align-items: center;

  gap: 8px;

  color: #dc2626;

  font-size: 0.85rem;

  margin-bottom: 16px;

  font-weight: 600;

  background: #fef2f2;

  padding: 11px 14px;

  border-radius: 10px;

  border:
    1px solid #fecaca;

  line-height: 1.4;
}

/* =====================================================
   BUTTON OUTLINE
===================================================== */

.btn-outline {
  height: 42px;

  padding: 0 20px;

  border-radius: 12px;

  font-size: 0.85rem;

  font-weight: 700;

  background: #ffffff;

  color: #334155;

  border:
    1px solid #cbd5e1;

  cursor: pointer;

  transition:
    all 0.25s ease;
}

.btn-outline:hover {
  border-color: #3b82f6;

  color: #2563eb;

  background: #f0f7ff;

  transform:
    translateY(-1px);
}

/* =====================================================
   PRIMARY BUTTON
===================================================== */

.btn-primary {
  width: 100%;

  height: 48px;

  border-radius: 12px;

  font-size: 0.95rem;

  font-weight: 700;

  border: none;

  cursor: pointer;

  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );

  color: #ffffff;

  box-shadow:
    0 4px 14px
    rgba(37, 99, 235, 0.25);

  transition:
    all 0.25s ease;
}

.btn-primary:hover:not(:disabled) {
  transform:
    translateY(-1.5px);

  box-shadow:
    0 8px 20px
    rgba(37, 99, 235, 0.35);
}

.btn-primary:disabled {
  opacity: 0.65;

  cursor: not-allowed;

  transform: none;
}

/* =====================================================
   GOOGLE BUTTON
===================================================== */

.btn-google {
  width: 100%;

  height: 48px;

  border-radius: 12px;

  font-size: 0.9rem;

  font-weight: 700;

  background: #ffffff;

  color: #334155;

  border:
    1.5px solid #e2e8f0;

  cursor: pointer;

  display: flex;

  align-items: center;
  justify-content: center;

  gap: 10px;

  transition:
    all 0.25s ease;
}

.btn-google:hover:not(:disabled) {
  border-color: #cbd5e1;

  background: #f8fafc;

  transform:
    translateY(-1px);
}

.btn-google:disabled {
  opacity: 0.65;

  cursor: not-allowed;

  transform: none;
}

/* =====================================================
   LOGIN INFO
===================================================== */

.login-info {
  display: flex;

  align-items: center;

  gap: 12px;

  margin-top: 20px;

  padding: 14px;

  border-radius: 14px;

  background:
    #f8fafc;

  border:
    1px solid #e2e8f0;
}

.info-icon {
  width: 38px;
  height: 38px;

  flex-shrink: 0;

  border-radius: 11px;

  background: #eff6ff;

  display: flex;

  align-items: center;
  justify-content: center;

  font-size: 1rem;
}

.login-info strong {
  display: block;

  color: #334155;

  font-size: 0.82rem;

  margin-bottom: 2px;
}

.login-info small {
  display: block;

  color: #64748b;

  font-size: 0.72rem;

  line-height: 1.4;
}

/* =====================================================
   DIVIDER
===================================================== */

.auth-divider {
  display: flex;

  align-items: center;

  margin: 24px 0;
}

.auth-divider .line {
  flex: 1;

  height: 1px;

  background: #e2e8f0;
}

.auth-divider p {
  padding: 0 14px;

  font-size: 0.8rem;

  color: #64748b;

  margin: 0;
}

/* =====================================================
   REGISTER
===================================================== */

.auth-switch {
  text-align: center;

  font-size: 0.85rem;

  color: #64748b;

  margin: 16px 0 0;

  line-height: 1.5;
}

.auth-switch a {
  color: #2563eb;

  font-weight: 700;

  text-decoration: none;

  transition:
    color 0.2s;
}

.auth-switch a:hover {
  color: #1d4ed8;

  text-decoration: underline;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1000px) {

  .auth-container {
    grid-template-columns: 1fr;

    gap: 40px;

    max-width: 700px;
  }

  .auth-intro {
    text-align: center;
  }

  .hero-label {
    margin: 0 auto;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-subtitle {
    margin-left: auto;
    margin-right: auto;
  }

  .benefit-item {
    text-align: left;
  }
}

/* =====================================================
   TABLET
===================================================== */

@media (max-width: 650px) {

  .container {
    padding: 0 16px;
  }

  .nav-content {
    height: 68px;
  }

  .brand-mark {
    width: 38px;
    height: 38px;

    border-radius: 12px;
  }

  .brand-text {
    font-size: 1.15rem;
  }

  .brand-info small {
    font-size: 0.52rem;
  }

  .btn-outline {
    height: 40px;

    padding: 0 14px;

    font-size: 0.78rem;
  }

  .auth-main {
    padding: 40px 0;
  }

  .auth-container {
    gap: 32px;
  }

  .hero-title {
    font-size: 2.15rem;
  }

  .hero-subtitle {
    font-size: 0.95rem;
  }

  .auth-card {
    padding: 28px 22px;

    border-radius: 22px;
  }
}

/* =====================================================
   SMALL MOBILE
===================================================== */

@media (max-width: 420px) {

  .brand-info {
    display: none;
  }

  .hero-title {
    font-size: 1.9rem;
  }

  .hero-label {
    font-size: 0.65rem;

    padding: 6px 11px;
  }

  .benefit-item {
    padding: 12px 14px;
  }

  .benefit-icon {
    width: 38px;
    height: 38px;
  }

  .benefit-item strong {
    font-size: 0.85rem;
  }

  .benefit-item span {
    font-size: 0.75rem;
  }

  .auth-card {
    padding: 24px 18px;
  }
}

</style>
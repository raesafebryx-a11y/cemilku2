<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

// =====================================================
// STATE
// =====================================================

const registerName = ref('')
const registerEmail = ref('')
const registerPhone = ref('')
const registerPassword = ref('')
const registerPasswordConfirmation = ref('')

const errorMessage = ref('')
const loading = ref(false)

// =====================================================
// LOGO
// =====================================================

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  event.target.src =
    'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'
}

// =====================================================
// REGISTER
// =====================================================

const handleRegister = async () => {
  errorMessage.value = ''

  const name = registerName.value.trim()
  const email = registerEmail.value.trim()
  const phone = registerPhone.value.trim()
  const password = registerPassword.value
  const passwordConfirmation =
    registerPasswordConfirmation.value

  // ===================================================
  // VALIDASI DATA
  // ===================================================

  if (
    !name ||
    !email ||
    !phone ||
    !password ||
    !passwordConfirmation
  ) {
    errorMessage.value =
      'Semua data pendaftaran wajib diisi.'

    await Swal.fire({
      icon: 'warning',
      title: 'Data Belum Lengkap',
      text:
        'Harap lengkapi seluruh kolom formulir terlebih dahulu.',
      confirmButtonColor: '#2563eb',
      background: '#ffffff',
      color: '#1e293b'
    })

    return
  }

  // ===================================================
  // VALIDASI PASSWORD
  // ===================================================

  if (password.length < 8) {
    errorMessage.value =
      'Password minimal 8 karakter.'

    await Swal.fire({
      icon: 'warning',
      title: 'Password Terlalu Pendek',
      text:
        'Password harus memiliki minimal 8 karakter.',
      confirmButtonColor: '#2563eb',
      background: '#ffffff',
      color: '#1e293b'
    })

    return
  }

  // ===================================================
  // VALIDASI KONFIRMASI PASSWORD
  // ===================================================

  if (password !== passwordConfirmation) {
    errorMessage.value =
      'Konfirmasi password tidak cocok.'

    await Swal.fire({
      icon: 'warning',
      title: 'Password Tidak Cocok',
      text:
        'Password dan konfirmasi password harus sama.',
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
    // REGISTER KE BACKEND
    // =================================================

    await auth.register({
      name,
      email,
      phone,
      password,
      password_confirmation: passwordConfirmation
    })

    // =================================================
    // BERHASIL
    // =================================================

    await Swal.fire({
      icon: 'success',
      title: 'Pendaftaran Berhasil! 🎉',
      text:
        `Selamat datang di Cemilku, ${name}!`,
      timer: 1800,
      showConfirmButton: false,
      timerProgressBar: true,
      background: '#ffffff',
      color: '#1e293b'
    })

    router.push('/')
  } catch (error) {
    console.error(
      'Register error:',
      error
    )

    // =================================================
    // ERROR BACKEND
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
          'Data pendaftaran tidak valid.'
      }
    } else {
      errorMessage.value =
        error.response?.data?.message ||
        'Pendaftaran gagal. Pastikan server Laravel sedang berjalan.'
    }

    await Swal.fire({
      icon: 'error',
      title: 'Pendaftaran Gagal',
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
// KEMBALI KE HOME
// =====================================================

const goHome = () => {
  router.push('/')
}
</script>

<template>
  <div class="auth-wrapper">

    <!-- =================================================
         BACKGROUND GLOW
    ================================================== -->

    <div class="hero-bg-glow"></div>

    <!-- =================================================
         NAVBAR
    ================================================== -->

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
          type="button"
          class="btn-outline"
          @click="goHome"
        >
          ← Kembali
        </button>

      </div>
    </header>

    <!-- =================================================
         MAIN
    ================================================== -->

    <main class="auth-main">

      <div class="container auth-container">

        <!-- =================================================
             LEFT CONTENT
        ================================================== -->

        <section class="auth-intro">

          <span class="hero-label">
            ✨ BERGABUNG BERSAMA KAMI
          </span>

          <h1 class="hero-title">
            Buat Akun dan Mulai
            <br>

            <span class="gradient-text">
              Berbelanja Mudah.
            </span>
          </h1>

          <p class="hero-subtitle">
            Cemilku menyediakan pengalaman
            pemesanan camilan yang sederhana
            dengan tampilan modern dan proses
            checkout yang praktis.
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
                  Registrasi Cepat
                </strong>

                <span>
                  Pendaftaran hanya membutuhkan
                  beberapa data dasar.
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
                  Pilih Produk Favorit
                </strong>

                <span>
                  Akses penuh ke seluruh katalog
                  camilan lezat Cemilku.
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
                  Pesan Kapan Saja
                </strong>

                <span>
                  Proses pemesanan praktis,
                  cepat, dan nyaman setiap saat.
                </span>
              </div>

            </div>

          </div>

        </section>

        <!-- =================================================
             REGISTER CARD
        ================================================== -->

        <section class="auth-card hover-lift">

          <div class="card-glass-shine"></div>

          <!-- CARD HEADER -->

          <div class="auth-card-header">

            <span class="card-badge">
              REGISTER
            </span>

            <h2>
              Buat Akun
            </h2>

            <p>
              Lengkapi data berikut untuk
              membuat akun baru.
            </p>

          </div>

          <!-- =================================================
               FORM
          ================================================== -->

          <form
            @submit.prevent="handleRegister"
          >

            <!-- NAMA -->

            <div class="auth-form-group">

              <label>
                Nama Lengkap
              </label>

              <input
                v-model="registerName"
                type="text"
                autocomplete="name"
                placeholder="Masukkan nama lengkap"
                :disabled="loading"
                required
              />

            </div>

            <!-- EMAIL -->

            <div class="auth-form-group">

              <label>
                Email
              </label>

              <input
                v-model="registerEmail"
                type="email"
                autocomplete="email"
                placeholder="nama@email.com"
                :disabled="loading"
                required
              />

            </div>

            <!-- NO HP -->

            <div class="auth-form-group">

              <label>
                No. HP
              </label>

              <input
                v-model="registerPhone"
                type="tel"
                autocomplete="tel"
                placeholder="08xxxxxxxxxx"
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
                v-model="registerPassword"
                type="password"
                autocomplete="new-password"
                placeholder="Buat password baru"
                :disabled="loading"
                minlength="8"
                required
              />

              <small class="password-hint">
                Minimal 8 karakter.
              </small>

            </div>

            <!-- KONFIRMASI PASSWORD -->

            <div class="auth-form-group">

              <label>
                Konfirmasi Password
              </label>

              <input
                v-model="registerPasswordConfirmation"
                type="password"
                autocomplete="new-password"
                placeholder="Ulangi password"
                :disabled="loading"
                minlength="8"
                required
              />

              <small class="password-hint">
                Masukkan kembali password yang sama.
              </small>

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

            <!-- SUBMIT -->

            <button
              type="submit"
              class="btn-primary auth-submit glow-on-hover"
              :disabled="loading"
            >

              <span v-if="loading">
                ⏳ Memproses...
              </span>

              <span v-else>
                Daftar Akun Baru
              </span>

            </button>

          </form>

          <!-- =================================================
               SECURITY INFO
          ================================================== -->

          <div class="register-info">

            <div class="info-icon">
              🔐
            </div>

            <div>

              <strong>
                Data Aman
              </strong>

              <small>
                Data akun akan diproses oleh
                sistem Cemilku.
              </small>

            </div>

          </div>

          <!-- =================================================
               DIVIDER
          ================================================== -->

          <div class="auth-divider">

            <span class="line"></span>

            <p>
              atau
            </p>

            <span class="line"></span>

          </div>

          <!-- LOGIN -->

          <p class="auth-switch">

            Sudah memiliki akun?

            <router-link to="/login">
              Masuk di sini
            </router-link>

          </p>

        </section>

      </div>

    </main>

    <!-- =================================================
         FOOTER
    ================================================== -->

    <footer class="auth-footer">

      <div class="container footer-content">
        © 2026 Cemilku Snack Store.
        All rights reserved.
      </div>

    </footer>

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
   BACKGROUND
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
    1px solid
    rgba(226, 232, 240, 0.8);
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
   CARD
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
  margin-bottom: 18px;

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

  opacity: 0.7;

  cursor: not-allowed;
}

.password-hint {
  display: block;

  margin-top: 6px;

  font-size: 0.72rem;

  color: #64748b;
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
   BUTTON
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
   REGISTER INFO
===================================================== */

.register-info {
  display: flex;

  align-items: center;

  gap: 12px;

  margin-top: 20px;

  padding: 14px;

  border-radius: 14px;

  background: #f8fafc;

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
}

.register-info strong {
  display: block;

  color: #334155;

  font-size: 0.82rem;

  margin-bottom: 2px;
}

.register-info small {
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
   SWITCH
===================================================== */

.auth-switch {
  text-align: center;

  font-size: 0.85rem;

  color: #64748b;

  margin: 0;

  line-height: 1.5;
}

.auth-switch a {
  color: #2563eb;

  font-weight: 700;

  text-decoration: none;

  transition:
    color 0.2s ease;
}

.auth-switch a:hover {
  color: #1d4ed8;

  text-decoration: underline;
}

/* =====================================================
   FOOTER
===================================================== */

.auth-footer {
  border-top:
    1px solid
    rgba(226, 232, 240, 0.9);

  padding: 24px 0;

  background:
    rgba(255, 255, 255, 0.5);

  z-index: 10;
}

.footer-content {
  text-align: center;

  font-size: 0.85rem;

  color: #64748b;
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

  .hero-subtitle {
    margin-left: auto;
    margin-right: auto;
  }

  .benefit-item {
    text-align: left;
  }

  .hero-title {
    font-size: 2.5rem;
  }
}

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

  .footer-content {
    font-size: 0.75rem;
  }
}

</style>
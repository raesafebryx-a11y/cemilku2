<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import Swal from 'sweetalert2'
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

// ==========================================
// LOGO
// ==========================================

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  event.target.src =
    'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'
}

// ==========================================
// DATA CEMILKU DARI API
// ==========================================

const jumlahProduk = ref(0)
const jumlahKategori = ref(0)

const loadingStats = ref(true)

const fetchDataCemilku = async () => {
  loadingStats.value = true

  try {
    const [produkResponse, kategoriResponse] = await Promise.all([
      api.get('/produk'),
      api.get('/kategoris')
    ])

    // ======================================
    // DATA PRODUK
    // ======================================

    const produkData =
      produkResponse.data?.data?.data ||
      produkResponse.data?.data ||
      []

    if (Array.isArray(produkData)) {
      jumlahProduk.value = produkData.length
    }

    // ======================================
    // DATA KATEGORI
    // ======================================

    const kategoriData =
      kategoriResponse.data?.data ||
      kategoriResponse.data ||
      []

    if (Array.isArray(kategoriData)) {
      jumlahKategori.value = kategoriData.length
    }
  } catch (error) {
    console.error('Gagal mengambil data Cemilku:', error)

    jumlahProduk.value = 0
    jumlahKategori.value = 0
  } finally {
    loadingStats.value = false
  }
}

// ==========================================
// MAP
// ==========================================

let mapInstance = null

const initMap = async () => {
  await nextTick()

  const mapElement = document.getElementById('map')

  if (!mapElement) {
    return
  }

  /*
   * Tidak menggunakan koordinat Bandung lagi.
   *
   * Karena lokasi toko asli belum disediakan,
   * kita tidak menampilkan marker palsu.
   */

  if (mapInstance) {
    mapInstance.remove()
    mapInstance = null
  }
}

// ==========================================
// NAVIGATION
// ==========================================

const goHome = () => {
  router.push('/')
}

const goLogin = () => {
  router.push('/login')
}

const goRegister = () => {
  router.push('/register')
}

const goProfile = () => {
  router.push('/profile')
}

const goContact = () => {
  router.push('/kontak')
}

const goDashboard = () => {
  router.push('/admin')
}

// ==========================================
// LOGOUT
// ==========================================

const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Konfirmasi Keluar',
    text: 'Apakah Anda yakin ingin keluar dari akun ini?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal',
    background: isDarkMode.value
      ? '#1e293b'
      : '#ffffff',
    color: isDarkMode.value
      ? '#f8fafc'
      : '#1e293b'
  })

  if (!result.isConfirmed) {
    return
  }

  try {
    await auth.logout()

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil Keluar 👋',
      text: 'Anda telah keluar dari akun.',
      timer: 1800,
      showConfirmButton: false,
      timerProgressBar: true,
      background: isDarkMode.value
        ? '#1e293b'
        : '#ffffff',
      color: isDarkMode.value
        ? '#f8fafc'
        : '#1e293b'
    })

    router.push('/')
  } catch (error) {
    console.error('Logout error:', error)

    await Swal.fire({
      icon: 'error',
      title: 'Logout Gagal',
      text: 'Terjadi kesalahan saat keluar dari akun.',
      confirmButtonColor: '#2563eb'
    })
  }
}

// ==========================================
// THEME
// ==========================================

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

const checkTheme = () => {
  const savedTheme = localStorage.getItem('theme')

  if (savedTheme === 'dark') {
    isDarkMode.value = true
  } else {
    isDarkMode.value = false
  }
}

// ==========================================
// LIFECYCLE
// ==========================================

onMounted(async () => {
  checkTheme()

  window.addEventListener('cemilku-theme-change', handleThemeChange)

  await fetchDataCemilku()

  await initMap()
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <div
    class="page-wrapper"
    :class="{ 'dark-mode': isDarkMode }"
  >

    <!-- ====================================== -->
    <!-- BACKGROUND -->
    <!-- ====================================== -->

    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>


    <!-- ====================================== -->
    <!-- MAIN -->
    <!-- ====================================== -->

    <main class="main-content">

      <div class="container">


        <!-- HERO -->

        <section class="page-hero">

          <div class="hero-badge">

            <span class="pulse-dot"></span>

            Tentang Cemilku Store

          </div>


          <h1 class="page-title">

            Menghadirkan Kelezatan Jajanan

            <br>

            <span class="gradient-text">
              Nusantara Kualitas Terbaik
            </span>

          </h1>


          <p class="page-desc">

            Cemilku Snack Store hadir sebagai
            tempat untuk menemukan berbagai
            pilihan camilan yang lezat,
            berkualitas, dan mudah dipesan.

          </p>

        </section>


        <!-- ================================== -->
        <!-- ABOUT CARDS -->
        <!-- ================================== -->

        <section class="about-grid">


          <!-- VISI -->

          <div class="info-card">

            <div class="card-icon-box">
              🎯
            </div>

            <h3>
              Visi Kami
            </h3>

            <p>

              Menjadi toko camilan pilihan
              yang menyediakan produk berkualitas
              dengan pelayanan yang mudah,
              cepat, dan terpercaya.

            </p>

          </div>


          <!-- MISI -->

          <div class="info-card">

            <div class="card-icon-box">
              🚀
            </div>

            <h3>
              Misi Utama
            </h3>

            <p>

              Menyediakan berbagai pilihan
              camilan dengan kualitas terbaik
              serta memberikan pengalaman
              belanja yang nyaman bagi pelanggan.

            </p>

          </div>


          <!-- KEUNGGULAN -->

          <div class="info-card">

            <div class="card-icon-box">
              ⭐
            </div>

            <h3>
              Keunggulan Cemilku
            </h3>

            <p>

              Cemilku berusaha memberikan
              produk yang berkualitas,
              tampilan produk yang jelas,
              serta proses pemesanan yang mudah
              melalui platform online.

            </p>

          </div>

        </section>


        <!-- ================================== -->
        <!-- STATS -->
        <!-- ================================== -->

        <section class="stats-banner">


          <!-- PRODUK -->

          <div class="stat-item">

            <strong v-if="!loadingStats">
              {{ jumlahProduk }}+
            </strong>

            <strong
              v-else
              class="loading-number"
            >
              ...
            </strong>

            <span>
              Produk Tersedia
            </span>

          </div>


          <div class="stat-divider"></div>


          <!-- KATEGORI -->

          <div class="stat-item">

            <strong v-if="!loadingStats">
              {{ jumlahKategori }}
            </strong>

            <strong
              v-else
              class="loading-number"
            >
              ...
            </strong>

            <span>
              Kategori Produk
            </span>

          </div>


          <div class="stat-divider"></div>


          <!-- ONLINE -->

          <div class="stat-item">

            <strong>
              24/7
            </strong>

            <span>
              Akses Toko Online
            </span>

          </div>

        </section>


        <!-- ================================== -->
        <!-- LOKASI -->
        <!-- ================================== -->

        <section class="map-section">

          <div class="section-header">

            <div class="hero-badge">
              🛍️ CEMILKU STORE
            </div>

            <h2>
              Belanja Camilan Favoritmu
            </h2>

            <p class="page-desc">

              Temukan berbagai pilihan camilan
              Cemilku melalui toko online kami.
              Pilih produk favoritmu dan lakukan
              pemesanan dengan mudah.

            </p>

          </div>


          <!-- STORE INFORMATION -->

          <div class="store-info-card">

            <div class="store-info-icon">
              🛒
            </div>

            <div class="store-info-content">

              <h3>
                Cemilku Snack Store
              </h3>

              <p>
                Nikmati kemudahan berbelanja
                berbagai macam camilan melalui
                platform Cemilku.
              </p>

              <button
                class="store-button"
                @click="goHome"
              >
                Lihat Produk
                <span>→</span>
              </button>

            </div>

          </div>

        </section>

      </div>

    </main>

  </div>
</template>


<style scoped>

/* ==========================================
   GLOBAL
========================================== */

.page-wrapper {
  min-height: 100vh;
  background-color: #f6f8fb;
  color: #1e293b;
  position: relative;
  font-family:
    'Plus Jakarta Sans',
    system-ui,
    -apple-system,
    sans-serif;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
  scroll-behavior: smooth;
  transition:
    background-color 0.3s ease,
    color 0.3s ease;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 24px;
  width: 100%;
  box-sizing: border-box;
}


/* ==========================================
   BACKGROUND
========================================== */

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
  background:
    radial-gradient(
      circle,
      rgba(147, 197, 253, 0.45) 0%,
      rgba(191, 219, 254, 0.15) 70%
    );
}

.bg-shape-2 {
  top: 350px;
  left: -120px;
  width: 500px;
  height: 500px;
  background:
    radial-gradient(
      circle,
      rgba(96, 165, 250, 0.3) 0%,
      rgba(224, 242, 254, 0.1) 70%
    );
}

.bg-shape-3 {
  bottom: 100px;
  right: 15%;
  width: 400px;
  height: 400px;
  background:
    radial-gradient(
      circle,
      rgba(186, 230, 253, 0.4) 0%,
      rgba(240, 249, 255, 0.05) 70%
    );
}


/* ==========================================
   NAVBAR
========================================== */

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


/* BRAND */

.brand-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition:
    transform 0.3s
    cubic-bezier(0.34, 1.56, 0.64, 1);
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


/* ==========================================
   NAVIGATION
========================================== */

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
  transition: all 0.25s ease;
}

.nav-btn:hover {
  color: #2563eb;
  background: rgba(255, 255, 255, 0.5);
}

.nav-btn.active {
  background: #ffffff;
  color: #2563eb;
  box-shadow:
    0 4px 12px
    rgba(37, 99, 235, 0.08);
}

.btn-dashboard {
  background: #2563eb !important;
  color: #ffffff !important;
}

.btn-dashboard:hover {
  background: #1d4ed8 !important;
  box-shadow:
    0 6px 16px
    rgba(37, 99, 235, 0.25);
}


/* ==========================================
   AUTH
========================================== */

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
  box-shadow:
    0 4px 12px
    rgba(37, 99, 235, 0.25);
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
  box-shadow:
    0 6px 14px
    rgba(220, 38, 38, 0.2);
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
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );
  border: none;
  color: #ffffff;
  padding: 8px 20px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow:
    0 4px 14px
    rgba(37, 99, 235, 0.25);
  transition: all 0.25s ease;
}

.btn-primary:hover {
  box-shadow:
    0 8px 20px
    rgba(37, 99, 235, 0.35);
  transform: translateY(-1.5px);
}


/* ==========================================
   MAIN
========================================== */

.main-content {
  flex: 1;
  padding: 50px 0 80px;
  position: relative;
  z-index: 1;
}


/* ==========================================
   HERO
========================================== */

.page-hero {
  text-align: center;
  max-width: 760px;
  margin: 0 auto 50px;
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
  box-shadow:
    0 2px 8px
    rgba(37, 99, 235, 0.06);
}

.pulse-dot {
  width: 7px;
  height: 7px;
  background-color: #2563eb;
  border-radius: 50%;
  box-shadow:
    0 0 0 0
    rgba(37, 99, 235, 0.7);
  animation: pulse 1.8s infinite;
}

@keyframes pulse {
  0% {
    transform: scale(0.95);
    box-shadow:
      0 0 0 0
      rgba(37, 99, 235, 0.7);
  }

  70% {
    transform: scale(1);
    box-shadow:
      0 0 0 8px
      rgba(37, 99, 235, 0);
  }

  100% {
    transform: scale(0.95);
    box-shadow:
      0 0 0 0
      rgba(37, 99, 235, 0);
  }
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
  background:
    linear-gradient(
      135deg,
      #1d4ed8,
      #3b82f6
    );
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.page-desc {
  color: #64748b;
  font-size: 1.05rem;
  line-height: 1.65;
  margin: 0 auto;
}


/* ==========================================
   ABOUT GRID
========================================== */

.about-grid {
  display: grid;
  grid-template-columns:
    repeat(
      auto-fit,
      minmax(300px, 1fr)
    );
  gap: 24px;
  margin-bottom: 50px;
}

.info-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 24px;
  padding: 32px 28px;
  box-shadow:
    0 10px 25px -5px
    rgba(37, 99, 235, 0.04);
  transition:
    all 0.3s
    cubic-bezier(0.4, 0, 0.2, 1);
}

.info-card:hover {
  transform: translateY(-6px);
  box-shadow:
    0 20px 35px -10px
    rgba(37, 99, 235, 0.12);
  border-color: #cbd5e1;
}

.card-icon-box {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  background: #eff6ff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.6rem;
  margin-bottom: 20px;
  border: 1px solid #dbeafe;
}

.info-card h3 {
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 10px;
}

.info-card p {
  color: #64748b;
  font-size: 0.925rem;
  line-height: 1.6;
  margin: 0;
}


/* ==========================================
   STATS
========================================== */

.stats-banner {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(226, 232, 240, 0.9);
  border-radius: 24px;
  padding: 30px;
  display: flex;
  align-items: center;
  justify-content: space-around;
  box-shadow:
    0 12px 30px -10px
    rgba(37, 99, 235, 0.06);
  margin-bottom: 60px;
}

.stat-item {
  text-align: center;
}

.stat-item strong {
  display: block;
  font-size: 1.8rem;
  font-weight: 800;
  color: #2563eb;
}

.stat-item span {
  font-size: 0.875rem;
  color: #64748b;
  font-weight: 600;
}

.loading-number {
  min-width: 45px;
}

.stat-divider {
  width: 1px;
  height: 40px;
  background: #e2e8f0;
}


/* ==========================================
   STORE SECTION
========================================== */

.map-section {
  margin-top: 20px;
  margin-bottom: 20px;
}

.section-header {
  text-align: center;
  max-width: 600px;
  margin: 0 auto 30px;
}

.section-header h2 {
  font-size: 2rem;
  font-weight: 800;
  color: #0f172a;
  margin: 10px 0;
}


/* STORE INFO */

.store-info-card {
  max-width: 850px;
  margin: 0 auto;
  padding: 38px;
  border-radius: 28px;
  border: 1px solid #dbeafe;
  background:
    linear-gradient(
      135deg,
      #ffffff,
      #eff6ff
    );
  display: flex;
  align-items: center;
  gap: 28px;
  box-shadow:
    0 20px 40px -15px
    rgba(37, 99, 235, 0.12);
}

.store-info-icon {
  width: 82px;
  height: 82px;
  flex-shrink: 0;
  border-radius: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );
  font-size: 2.3rem;
  box-shadow:
    0 12px 24px
    rgba(37, 99, 235, 0.25);
}

.store-info-content {
  flex: 1;
}

.store-info-content h3 {
  margin: 0 0 8px;
  color: #0f172a;
  font-size: 1.4rem;
  font-weight: 800;
}

.store-info-content p {
  margin: 0 0 18px;
  color: #64748b;
  line-height: 1.6;
  font-size: 0.95rem;
}

.store-button {
  border: none;
  background: #2563eb;
  color: #ffffff;
  padding: 10px 18px;
  border-radius: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.store-button:hover {
  background: #1d4ed8;
  transform: translateY(-2px);
  box-shadow:
    0 8px 18px
    rgba(37, 99, 235, 0.25);
}


/* ==========================================
   FOOTER
========================================== */

.footer {
  border-top: 1px solid #e2e8f0;
  padding: 52px 0 28px;
  background:
    linear-gradient(
      180deg,
      #f8fbff 0%,
      #ffffff 100%
    );
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
  grid-template-columns:
    1.4fr 0.8fr 1fr;
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
.footer-column span {
  color: #64748b;
  font-size: 0.85rem;
  text-decoration: none;
  line-height: 1.6;
  text-align: left;
}

.footer-column a:hover {
  color: #2563eb;
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


/* ==========================================
   DARK MODE
========================================== */

.dark-mode {
  background-color: #0f172a !important;
  color: #f8fafc !important;
}

.dark-mode .bg-shape-1 {
  background:
    radial-gradient(
      circle,
      rgba(37, 99, 235, 0.25) 0%,
      rgba(15, 23, 42, 0.1) 70%
    );
}

.dark-mode .bg-shape-2 {
  background:
    radial-gradient(
      circle,
      rgba(29, 78, 216, 0.2) 0%,
      rgba(15, 23, 42, 0.05) 70%
    );
}

.dark-mode .navbar {
  background:
    rgba(15, 23, 42, 0.85) !important;
  border-bottom-color:
    rgba(51, 65, 85, 0.8) !important;
}

.dark-mode .brand-text {
  color: #f8fafc !important;
}

.dark-mode .nav-links {
  background:
    rgba(30, 41, 59, 0.7) !important;
  border-color: #334155 !important;
}

.dark-mode .nav-btn {
  color: #94a3b8 !important;
}

.dark-mode .nav-btn:hover {
  color: #60a5fa !important;
  background:
    rgba(51, 65, 85, 0.5) !important;
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

.dark-mode .page-title,
.dark-mode .section-header h2 {
  color: #f8fafc !important;
}

.dark-mode .page-desc {
  color: #94a3b8 !important;
}

.dark-mode .hero-badge {
  background:
    rgba(30, 41, 59, 0.8) !important;
  color: #60a5fa !important;
  border-color: #1e3a8a !important;
}

.dark-mode .info-card {
  background: #1e293b !important;
  border-color: #334155 !important;
}

.dark-mode .card-icon-box {
  background: #0f172a !important;
  border-color: #334155 !important;
}

.dark-mode .info-card h3 {
  color: #f8fafc !important;
}

.dark-mode .info-card p {
  color: #94a3b8 !important;
}

.dark-mode .stats-banner {
  background:
    rgba(30, 41, 59, 0.8) !important;
  border-color: #334155 !important;
}

.dark-mode .stat-item span {
  color: #94a3b8 !important;
}

.dark-mode .stat-divider {
  background: #334155 !important;
}

.dark-mode .store-info-card {
  background:
    linear-gradient(
      135deg,
      #1e293b,
      #172554
    );
  border-color: #334155;
}

.dark-mode .store-info-content h3 {
  color: #f8fafc;
}

.dark-mode .store-info-content p {
  color: #94a3b8;
}

.dark-mode .footer {
  background:
    linear-gradient(
      180deg,
      rgba(15, 23, 42, 1) 0%,
      rgba(15, 23, 42, 0.96) 100%
    ) !important;
  border-top-color: #1e293b !important;
}

.dark-mode .footer-desc,
.dark-mode .footer-column a,
.dark-mode .footer-column span,
.dark-mode .footer-meta {
  color: #94a3b8 !important;
}

.dark-mode .footer-column h4 {
  color: #f8fafc !important;
}

.dark-mode .social-row a {
  background:
    rgba(30, 41, 59, 0.9) !important;
  color: #60a5fa !important;
}

.dark-mode .footer-bottom {
  border-top-color: #1e293b !important;
}


/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 1050px) {

  .nav-links {
    gap: 2px;
  }

  .nav-btn {
    padding: 8px 12px;
  }

  .nav-auth {
    gap: 7px;
  }

  .user-greeting {
    display: none;
  }

}


@media (max-width: 768px) {

  .nav-content {
    height: auto;
    min-height: 76px;
    flex-wrap: wrap;
    padding-top: 12px;
    padding-bottom: 12px;
  }

  .nav-links {
    order: 3;
    width: 100%;
    justify-content: center;
    overflow-x: auto;
  }

  .nav-auth {
    margin-left: auto;
  }

  .page-title {
    font-size: 2.1rem;
  }

  .stats-banner {
    flex-direction: column;
    gap: 20px;
  }

  .stat-divider {
    width: 60px;
    height: 1px;
  }

  .store-info-card {
    flex-direction: column;
    text-align: center;
  }

  .store-info-content {
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  .footer-grid {
    grid-template-columns: 1fr;
  }

}


@media (max-width: 520px) {

  .container {
    padding: 0 16px;
  }

  .brand-info {
    display: none;
  }

  .btn-profile {
    padding: 8px 10px;
  }

  .btn-logout {
    padding: 8px 12px;
  }

  .btn-outline,
  .btn-primary {
    padding: 8px 12px;
  }

  .nav-btn {
    white-space: nowrap;
    padding: 8px 10px;
  }

  .page-title {
    font-size: 1.8rem;
  }

  .page-desc {
    font-size: 0.95rem;
  }

  .about-grid {
    grid-template-columns: 1fr;
  }

  .stats-banner {
    padding: 24px 16px;
  }

  .store-info-card {
    padding: 28px 20px;
  }

  .footer-bottom {
    flex-direction: column;
    align-items: flex-start;
  }

}

</style>
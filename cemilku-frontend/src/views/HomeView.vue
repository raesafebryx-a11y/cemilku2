<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

// =====================================================
// API
// =====================================================

const API_URL = (
  import.meta.env.VITE_API_URL ||
  'http://127.0.0.1:8000/api'
).replace(/\/api\/?$/, '')

const fallbackImage =
  'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'

// =====================================================
// AUTH
// =====================================================

const currentUser = computed(() => {
  if (auth.user) {
    return auth.user
  }

  try {
    return JSON.parse(localStorage.getItem('user')) || null
  } catch {
    return null
  }
})

const isLoggedIn = computed(() => {
  return !!auth.token || !!localStorage.getItem('token')
})

const username = computed(() => {
  return (
    currentUser.value?.name ||
    currentUser.value?.username ||
    currentUser.value?.email ||
    'Pengguna'
  )
})

const userRole = computed(() => {
  return currentUser.value?.role || 'user'
})

// =====================================================
// LOGO
// =====================================================

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  if (event.target.dataset.fallbackApplied) {
    return
  }

  event.target.dataset.fallbackApplied = 'true'
  event.target.src = fallbackImage
}

// =====================================================
// PRODUCT IMAGE
// =====================================================

const getProductImage = (image) => {
  if (!image) {
    return fallbackImage
  }

  const value = String(image).trim()

  if (!value) {
    return fallbackImage
  }

  if (/^https?:\/\//i.test(value)) {
    return value
  }

  if (value.startsWith('/storage/')) {
    return `${API_URL}${value}`
  }

  if (value.startsWith('storage/')) {
    return `${API_URL}/${value}`
  }

  if (value.startsWith('/')) {
    return `${API_URL}${value}`
  }

  return `${API_URL}/storage/${value.replace(/^\/+/, '')}`
}

const handleProductImageError = (event) => {
  if (event.target.dataset.fallbackApplied) {
    return
  }

  event.target.dataset.fallbackApplied = 'true'
  event.target.src = fallbackImage
}

// =====================================================
// DARK MODE
// =====================================================

const isDarkMode = ref(false)

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

// =====================================================
// SEARCH & CATEGORY
// =====================================================

const searchQuery = ref('')
const activeCategory = ref('Semua')

const categories = ref([
  {
    id: 'Semua',
    label: 'Semua',
    icon: '🧺'
  }
])

// =====================================================
// PRODUCTS
// =====================================================

const products = ref([])
const loadingProducts = ref(false)
const productError = ref('')

// =====================================================
// FETCH CATEGORIES
// =====================================================

async function fetchCategories() {
  try {
    const response = await api.get('/categories')

    const data = Array.isArray(response.data)
      ? response.data
      : response.data?.data || []

    categories.value = [
      {
        id: 'Semua',
        label: 'Semua',
        icon: '🧺'
      },
      ...data.map((category) => ({
        id: category.id,
        label: category.name,
        icon: category.icon || '🍪'
      }))
    ]
  } catch (error) {
    console.error('Gagal mengambil kategori:', error)

    categories.value = [
      {
        id: 'Semua',
        label: 'Semua',
        icon: '🧺'
      }
    ]
  }
}

// =====================================================
// FETCH PRODUCTS
// =====================================================

async function fetchProducts() {
  loadingProducts.value = true
  productError.value = ''

  try {
    const response = await api.get('/products')

    let data = response.data

    if (data?.data?.data) {
      data = data.data.data
    } else if (
      data?.data &&
      Array.isArray(data.data)
    ) {
      data = data.data
    } else if (Array.isArray(data)) {
      data = data
    } else {
      data = []
    }

    products.value = data.map((product) => ({
      id: product.id,

      name:
        product.name ||
        product.nama ||
        product.nama_barang ||
        'Produk Cemilku',

      description:
        product.description ||
        product.deskripsi ||
        'Camilan lezat pilihan Cemilku.',

      price: Number(
        product.price ??
        product.harga ??
        product.harga_barang ??
        0
      ),

      image: getProductImage(
        product.image ||
        product.foto ||
        product.gambar ||
        ''
      ),

      category:
        product.category?.name ||
        product.kategori?.name ||
        product.category_name ||
        '',

      category_id:
        product.category_id ??
        product.id_kategori ??
        product.kategori_id ??
        null,

      stock:
        Number(
          product.stock ??
          product.stok ??
          0
        )
    }))
  } catch (error) {
    console.error('Gagal mengambil produk:', error)

    productError.value =
      'Produk belum dapat dimuat. Silakan coba lagi.'

    products.value = []
  } finally {
    loadingProducts.value = false
  }
}

// =====================================================
// FILTER PRODUCTS
// =====================================================

const filteredProducts = computed(() => {
  const search = searchQuery.value
    .toLowerCase()
    .trim()

  return products.value.filter((product) => {
    const matchesCategory =
      activeCategory.value === 'Semua' ||
      String(product.category_id) ===
        String(activeCategory.value)

    const matchesSearch =
      product.name
        .toLowerCase()
        .includes(search) ||

      product.description
        .toLowerCase()
        .includes(search) ||

      product.category
        .toLowerCase()
        .includes(search)

    return (
      matchesCategory &&
      matchesSearch
    )
  })
})

// =====================================================
// NAVIGATION
// =====================================================

const goHome = () => {
  router.push('/')
}

const goToProducts = () => {
  router.push('/products')
}

const goToLogin = () => {
  router.push('/login')
}

const goToRegister = () => {
  router.push('/register')
}

const goToProfile = () => {
  router.push('/profile')
}

const goToAdmin = () => {
  router.push('/admin')
}

const goToAbout = () => {
  router.push('/tentang-kami')
}

const goToContact = () => {
  router.push('/kontak')
}

// =====================================================
// PRODUCT DETAIL
// =====================================================

const goToDetail = (id) => {
  if (!id) {
    console.error('ID produk tidak ditemukan.')
    return
  }

  router.push({
    name: 'product-detail',
    params: {
      id: String(id)
    }
  })
}

// =====================================================
// LOGOUT
// =====================================================

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
    console.error('Logout gagal:', error)

    localStorage.removeItem('token')
    localStorage.removeItem('user')

    router.push('/')
  }
}

// =====================================================
// RETRY
// =====================================================

const retryProducts = () => {
  fetchProducts()
}

// =====================================================
// ON MOUNTED
// =====================================================

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')

  if (savedTheme === 'dark') {
    isDarkMode.value = true
  }

  window.addEventListener('cemilku-theme-change', handleThemeChange)

  fetchCategories()
  fetchProducts()
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="home-wrapper" :class="{ 'dark-mode': isDarkMode }">

    <!-- BACKGROUND -->
    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <!-- ================================================= -->
    <!-- HERO -->
    <!-- ================================================= -->

    <section class="hero-section">
      <div class="container hero-container">

        <div class="hero-left">

          <div class="hero-badge">
            <span class="badge-dot"></span>
            Camilan Nusantara Terfavorit
          </div>

          <h1 class="hero-title">
            Cita Rasa Authentic,
            <br />

            <span class="text-gradient">
              Renyah & Nikmat
            </span>
          </h1>

          <p class="hero-subtitle">
            Koleksi jajanan pilihan
            dengan bahan olahan higienis.
            Dikemas rapi dan siap dikirim
            cepat ke seluruh wilayah
            Indonesia.
          </p>

          <div class="search-box">
            <span class="search-icon">
              🔍
            </span>

            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari rasa atau jenis cemilan..."
            />
          </div>

        </div>

        <div class="hero-right">

          <div
            class="glass-card brand-highlight-card"
          >
            <div class="card-logo-box">
              <img
                :src="logoCemilku"
                alt="Cemilku Store"
                class="cemilku-logo-img"
                @error="handleLogoError"
              />
            </div>

            <h2>
              Cemilku Store
            </h2>

            <p>
              Teman Setia Momen
              Santai Anda
            </p>

            <div class="stats-grid">
              <div class="stat-item">
                <h3>100%</h3>
                <span>Higienis</span>
              </div>

              <div class="stat-item">
                <h3>Fast</h3>
                <span>Delivery</span>
              </div>
            </div>
          </div>

        </div>
      </div>
    </section>

    <!-- ================================================= -->
    <!-- PRODUCT CATALOG -->
    <!-- ================================================= -->

    <main class="catalog-section">
      <div class="container">

        <div class="catalog-header">

          <div class="catalog-title">
            <h2>
              Katalog Produk
            </h2>

            <p>
              Pilih kategori camilan
              sesuai selera Anda
            </p>
          </div>

          <!-- LIHAT SEMUA -->

          <button
            class="view-all-button"
            @click="goToProducts"
          >
            Lihat Semua Produk
            <span>→</span>
          </button>

        </div>

        <!-- CATEGORY -->

        <div class="category-pills">
          <button
            v-for="cat in categories"
            :key="cat.id"
            class="pill-btn"
            :class="{
              active:
                activeCategory === cat.id
            }"
            @click="
              activeCategory = cat.id
            "
          >
            <span>
              {{ cat.icon }}
            </span>

            {{ cat.label }}
          </button>
        </div>

        <!-- LOADING -->

        <div
          v-if="loadingProducts"
          class="empty-state-card"
        >
          <div
            class="empty-icon-box loading-icon"
          >
            ⏳
          </div>

          <h3>
            Memuat Produk...
          </h3>

          <p>
            Tunggu sebentar, kami sedang
            mengambil data produk.
          </p>
        </div>

        <!-- ERROR -->

        <div
          v-else-if="productError"
          class="empty-state-card"
        >
          <div class="empty-icon-box">
            ⚠️
          </div>

          <h3>
            Gagal Memuat Produk
          </h3>

          <p>
            {{ productError }}
          </p>

          <button
            class="btn-primary retry-btn"
            @click="retryProducts"
          >
            🔄 Coba Lagi
          </button>
        </div>

        <!-- PRODUCT -->

        <div
          v-else-if="filteredProducts.length > 0"
          class="product-grid"
        >
          <div
            v-for="product in filteredProducts.slice(0, 6)"
            :key="product.id"
            class="product-card"
            @click="goToDetail(product.id)"
          >

            <div class="product-img-box">
              <img
                :src="product.image"
                :alt="product.name"
                @error="handleProductImageError"
              />

              <span
                v-if="product.category"
                class="category-tag"
              >
                {{ product.category }}
              </span>
            </div>

            <div class="product-info">

              <h3>
                {{ product.name }}
              </h3>

              <p class="product-desc">
                {{ product.description }}
              </p>

              <div class="product-bottom">

                <span class="product-price">
                  Rp
                  {{
                    product.price.toLocaleString(
                      'id-ID'
                    )
                  }}
                </span>

                <button
                  class="btn-detail"
                  @click.stop="
                    goToDetail(product.id)
                  "
                >
                  Lihat Detail
                </button>

              </div>
            </div>
          </div>
        </div>

        <!-- EMPTY -->

        <div
          v-else
          class="empty-state-card"
        >
          <div class="empty-icon-box">
            📥
          </div>

          <h3>
            Belum Ada Produk
          </h3>

          <p>
            {{
              searchQuery
                ? 'Produk tidak ditemukan sesuai pencarian Anda.'
                : 'Produk belum tersedia saat ini.'
            }}
          </p>

          <button
            v-if="searchQuery"
            class="btn-primary retry-btn"
            @click="searchQuery = ''"
          >
            🔄 Reset Pencarian
          </button>
        </div>

      </div>
    </main>

  </div>
</template>

<style scoped>
/* =====================================================
   GLOBAL
===================================================== */

.home-wrapper {
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

/* =====================================================
   BACKGROUND
===================================================== */

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(140px);
  pointer-events: none;
  z-index: 0;
  opacity: 0.7;
  transition: all 0.5s ease;
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

/* =====================================================
   NAVBAR
===================================================== */

.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  background: rgba(255, 255, 255, 0.75);
  border-bottom:
    1px solid rgba(226, 232, 240, 0.8);
}

.nav-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  height: 76px;
}

/* =====================================================
   BRAND
===================================================== */

.brand-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: transform 0.3s ease;
}

.brand-logo:hover {
  transform: translateY(-2px);
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

/* =====================================================
   NAVIGATION
===================================================== */

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
    0 2px 8px
    rgba(0, 0, 0, 0.04);
}

.btn-dashboard {
  background: #2563eb !important;
  color: #ffffff !important;
}

.btn-dashboard:hover {
  background: #1d4ed8 !important;
}

/* =====================================================
   AUTH
===================================================== */

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

.btn-profile,
.btn-outline,
.btn-primary,
.btn-logout {
  padding: 8px 18px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}

.btn-profile {
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
}

.btn-profile:hover {
  background: #2563eb;
  color: #ffffff;
}

.btn-logout {
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #dc2626;
}

.btn-logout:hover {
  background: #dc2626;
  color: #ffffff;
}

.btn-outline {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
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
  box-shadow:
    0 4px 14px
    rgba(37, 99, 235, 0.25);
}

.btn-primary:hover {
  box-shadow:
    0 8px 20px
    rgba(37, 99, 235, 0.35);
  transform: translateY(-1.5px);
}

/* =====================================================
   HERO
===================================================== */

.hero-section {
  padding: 60px 0 40px;
  position: relative;
  z-index: 1;
}

.hero-container {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  align-items: center;
  gap: 40px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  background: #eff6ff;
  color: #2563eb;
  border: 1px solid #bfdbfe;
  padding: 6px 14px;
  border-radius: 20px;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 20px;
}

.badge-dot {
  width: 8px;
  height: 8px;
  background: #2563eb;
  border-radius: 50%;
}

.hero-title {
  font-size: 2.8rem;
  font-weight: 800;
  line-height: 1.25;
  color: #0f172a;
  margin-bottom: 16px;
  letter-spacing: -0.03em;
}

.text-gradient {
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
}

.hero-subtitle {
  color: #64748b;
  font-size: 1rem;
  line-height: 1.6;
  margin-bottom: 28px;
  max-width: 500px;
}

.search-box {
  display: flex;
  align-items: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  padding: 12px 18px;
  max-width: 440px;
  box-shadow:
    0 10px 25px -5px
    rgba(37, 99, 235, 0.06);
}

.search-icon {
  margin-right: 12px;
  opacity: 0.6;
}

.search-box input {
  border: none;
  outline: none;
  width: 100%;
  font-size: 0.9rem;
  background: transparent;
  color: inherit;
}

/* =====================================================
   HERO CARD
===================================================== */

.brand-highlight-card {
  background: rgba(255, 255, 255, 0.85);
  backdrop-filter: blur(16px);
  border:
    1px solid rgba(226, 232, 240, 0.9);
  border-radius: 24px;
  padding: 36px;
  text-align: center;
  box-shadow:
    0 20px 40px -15px
    rgba(37, 99, 235, 0.1);
}

.card-logo-box {
  width: 64px;
  height: 64px;
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );
  border-radius: 18px;
  margin: 0 auto 16px;
  padding: 10px;
}

.brand-highlight-card h2 {
  font-size: 1.3rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 4px;
}

.brand-highlight-card p {
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 24px;
}

.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  background: #f8fafc;
  padding: 16px;
  border-radius: 16px;
  border: 1px solid #f1f5f9;
}

.stat-item h3 {
  font-size: 1.1rem;
  font-weight: 800;
  color: #2563eb;
  margin-bottom: 2px;
}

.stat-item span {
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 600;
}

/* =====================================================
   CATALOG
===================================================== */

.catalog-section {
  padding: 40px 0 80px;
  position: relative;
  z-index: 1;
}

.catalog-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 20px;
  margin-bottom: 18px;
}

.catalog-title h2 {
  font-size: 1.6rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 4px;
}

.catalog-title p {
  color: #64748b;
  font-size: 0.9rem;
}

.view-all-button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 1px solid #bfdbfe;
  border-radius: 12px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.25s ease;
}

.view-all-button:hover {
  background: #2563eb;
  border-color: #2563eb;
  color: #ffffff;
  transform: translateY(-2px);
}

.view-all-button span {
  transition: transform 0.25s ease;
}

.view-all-button:hover span {
  transform: translateX(3px);
}

.category-pills {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  margin-bottom: 28px;
}

.pill-btn {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  color: #475569;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
}

.pill-btn.active {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}

/* =====================================================
   PRODUCTS
===================================================== */

.product-grid {
  display: grid;
  grid-template-columns:
    repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.product-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  overflow: hidden;
  cursor: pointer;
  transition: all 0.25s ease;
}

.product-card:hover {
  transform: translateY(-5px);
  border-color: #bfdbfe;
  box-shadow:
    0 18px 35px -15px
    rgba(37, 99, 235, 0.25);
}

.product-img-box {
  height: 220px;
  background:
    linear-gradient(
      135deg,
      #eff6ff,
      #f8fafc
    );
  position: relative;
  overflow: hidden;
}

.product-img-box img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.3s ease;
}

.product-card:hover
.product-img-box img {
  transform: scale(1.04);
}

.category-tag {
  position: absolute;
  top: 14px;
  left: 14px;
  background: rgba(255, 255, 255, 0.92);
  color: #2563eb;
  border: 1px solid #bfdbfe;
  padding: 6px 10px;
  border-radius: 10px;
  font-size: 0.7rem;
  font-weight: 700;
}

.product-info {
  padding: 20px;
}

.product-info h3 {
  color: #0f172a;
  font-size: 1.05rem;
  font-weight: 800;
  margin-bottom: 8px;
}

.product-desc {
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.6;
  min-height: 42px;
  margin-bottom: 18px;
}

.product-bottom {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.product-price {
  color: #2563eb;
  font-size: 1rem;
  font-weight: 800;
}

.btn-detail {
  border: none;
  background: #eff6ff;
  color: #2563eb;
  padding: 8px 12px;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-detail:hover {
  background: #2563eb;
  color: #ffffff;
}

/* =====================================================
   EMPTY
===================================================== */

.empty-state-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 24px;
  padding: 60px 20px;
  text-align: center;
}

.empty-icon-box {
  width: 56px;
  height: 56px;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
  font-size: 1.5rem;
}

.loading-icon {
  animation: pulse 1.2s infinite ease-in-out;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
    opacity: 1;
  }

  50% {
    transform: scale(1.08);
    opacity: 0.6;
  }
}

.empty-state-card h3 {
  font-size: 1.15rem;
  font-weight: 800;
  color: #0f172a;
  margin-bottom: 6px;
}

.empty-state-card p {
  color: #64748b;
  font-size: 0.875rem;
  margin-bottom: 18px;
}

.retry-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

/* =====================================================
   FOOTER
===================================================== */

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
}

.social-row {
  display: flex;
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
}

.footer-column h4 {
  font-size: 0.92rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 14px;
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
  gap: 16px;
  color: #64748b;
  font-size: 0.75rem;
}

/* =====================================================
   DARK MODE
===================================================== */

.dark-mode {
  background-color: #0f172a !important;
  color: #f8fafc !important;
}

.dark-mode .navbar {
  background: rgba(15, 23, 42, 0.85);
  border-bottom-color: #334155;
}

.dark-mode .brand-text,
.dark-mode .hero-title,
.dark-mode .catalog-title h2,
.dark-mode .brand-highlight-card h2,
.dark-mode .empty-state-card h3,
.dark-mode .product-info h3,
.dark-mode .footer-column h4 {
  color: #f8fafc !important;
}

.dark-mode .nav-links {
  background: rgba(30, 41, 59, 0.7);
  border-color: #334155;
}

.dark-mode .nav-btn {
  color: #94a3b8;
}

.dark-mode .nav-btn.active {
  background: #1e293b;
  color: #60a5fa;
}

.dark-mode .btn-theme-toggle,
.dark-mode .pill-btn {
  background: #1e293b;
  border-color: #334155;
  color: #cbd5e1;
}

.dark-mode .pill-btn.active {
  background: #2563eb;
  color: #ffffff;
}

.dark-mode .user-email {
  color: #f8fafc;
}

.dark-mode .greet-label,
.dark-mode .hero-subtitle,
.dark-mode .catalog-title p,
.dark-mode .empty-state-card p,
.dark-mode .footer-desc,
.dark-mode .footer-column a,
.dark-mode .contact-list li,
.dark-mode .footer-meta,
.dark-mode .product-desc {
  color: #94a3b8;
}

.dark-mode .search-box,
.dark-mode .brand-highlight-card,
.dark-mode .empty-state-card,
.dark-mode .product-card {
  background: rgba(30, 41, 59, 0.8);
  border-color: #334155;
}

.dark-mode .search-box input {
  color: #f8fafc;
}

.dark-mode .product-img-box {
  background:
    linear-gradient(
      135deg,
      #1e293b,
      #0f172a
    );
}

.dark-mode .stats-grid {
  background: #0f172a;
  border-color: #1e293b;
}

.dark-mode .footer {
  background: #0f172a;
  border-top-color: #1e293b;
}

.dark-mode .footer-bottom {
  border-top-color: #1e293b;
}

.dark-mode .category-tag {
  background: rgba(15, 23, 42, 0.9);
  border-color: #334155;
}

.dark-mode .view-all-button {
  background: #1e3a8a;
  border-color: #1d4ed8;
  color: #93c5fd;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1100px) {
  .nav-content {
    gap: 12px;
  }

  .nav-btn {
    padding: 8px 12px;
  }

  .user-greeting {
    display: none;
  }

  .product-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 900px) {
  .nav-content {
    flex-wrap: wrap;
    height: auto;
    min-height: 76px;
    padding-top: 12px;
    padding-bottom: 12px;
  }

  .nav-links {
    order: 3;
    width: 100%;
    justify-content: center;
  }

  .nav-auth {
    margin-left: auto;
  }

  .hero-container {
    grid-template-columns: 1fr;
  }

  .hero-left {
    text-align: center;
  }

  .hero-badge {
    justify-content: center;
  }

  .hero-subtitle {
    margin-left: auto;
    margin-right: auto;
  }

  .search-box {
    margin-left: auto;
    margin-right: auto;
  }

  .hero-right {
    max-width: 500px;
    width: 100%;
    margin: 0 auto;
  }

  .catalog-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .footer-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 650px) {
  .container {
    padding: 0 16px;
  }

  .brand-info {
    display: none;
  }

  .hero-section {
    padding-top: 40px;
  }

  .hero-title {
    font-size: 2.2rem;
  }

  .product-grid {
    grid-template-columns: 1fr;
  }

  .product-img-box {
    height: 240px;
  }

  .category-pills {
    width: 100%;
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 4px;
  }

  .pill-btn {
    flex-shrink: 0;
  }

  .view-all-button {
    width: 100%;
  }
}

@media (max-width: 520px) {
  .nav-auth {
    gap: 5px;
  }

  .btn-theme-toggle {
    width: 34px;
    height: 34px;
  }

  .btn-profile,
  .btn-logout,
  .btn-outline,
  .btn-primary {
    font-size: 0.75rem;
    padding: 8px 12px;
  }

  .hero-title {
    font-size: 1.9rem;
  }

  .hero-subtitle {
    font-size: 0.9rem;
  }

  .brand-highlight-card {
    padding: 28px 20px;
  }
}
</style>
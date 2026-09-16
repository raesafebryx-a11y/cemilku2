<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '@/services/api'

const router = useRouter()

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
// STATE
// =====================================================

const products = ref([])
const categories = ref([])

const searchQuery = ref('')
const selectedCategory = ref('Semua')

const loading = ref(true)
const error = ref('')

// =====================================================
// THEME
// =====================================================

const isDarkMode = ref(false)

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

// =====================================================
// LOGO
// =====================================================

const logoCemilku =
  ref('/images/cemilku-logo.png')

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
// FETCH CATEGORIES
// =====================================================

async function fetchCategories() {
  try {
    const response =
      await api.get('/categories')

    const data =
      Array.isArray(response.data)
        ? response.data
        : response.data?.data || []

    categories.value = [
      {
        id: 'Semua',
        name: 'Semua',
        icon: '🧺'
      },

      ...data.map((category) => ({
        id: category.id,
        name: category.name,
        icon: category.icon || '🍪'
      }))
    ]
  } catch (err) {
    console.error(
      'Gagal mengambil kategori:',
      err
    )

    categories.value = [
      {
        id: 'Semua',
        name: 'Semua',
        icon: '🧺'
      }
    ]
  }
}

// =====================================================
// FETCH PRODUCTS
// =====================================================

async function fetchProducts() {
  loading.value = true
  error.value = ''

  try {
    const response =
      await api.get('/products')

    let data = response.data

    // Laravel paginator:
    // {
    //   data: [...]
    // }

    if (
      data?.data &&
      Array.isArray(data.data)
    ) {
      data = data.data
    }

    // Jika format:
    // {
    //   data: {
    //      data: [...]
    //   }
    // }

    else if (
      data?.data?.data &&
      Array.isArray(data.data.data)
    ) {
      data = data.data.data
    }

    else if (!Array.isArray(data)) {
      data = []
    }

    products.value =
      data.map((product) => ({
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

        stock: Number(
          product.stock ??
          product.stok ??
          0
        )
      }))
  } catch (err) {
    console.error(
      'Gagal mengambil produk:',
      err
    )

    error.value =
      'Produk belum dapat dimuat. Silakan coba lagi.'

    products.value = []
  } finally {
    loading.value = false
  }
}

// =====================================================
// FILTER
// =====================================================

const filteredProducts = computed(() => {
  const search =
    searchQuery.value
      .toLowerCase()
      .trim()

  return products.value.filter(
    (product) => {
      const matchesCategory =
        selectedCategory.value ===
          'Semua' ||
        String(product.category_id) ===
          String(selectedCategory.value)

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
    }
  )
})

// =====================================================
// NAVIGATION
// =====================================================

const goHome = () => {
  router.push('/')
}

const goToAbout = () => {
  router.push('/tentang-kami')
}

const goToContact = () => {
  router.push('/kontak')
}

const goToLogin = () => {
  router.push('/login')
}

const goToRegister = () => {
  router.push('/register')
}

const goToDetail = (id) => {
  router.push({
    name: 'product-detail',
    params: {
      id: String(id)
    }
  })
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
  const savedTheme =
    localStorage.getItem('theme')

  if (savedTheme === 'dark') {
    isDarkMode.value = true
  }

  window.addEventListener(
    'cemilku-theme-change',
    handleThemeChange
  )

  fetchCategories()
  fetchProducts()
})

onUnmounted(() => {
  window.removeEventListener(
    'cemilku-theme-change',
    handleThemeChange
  )
})
</script>

<template>
  <div
    class="products-page"
    :class="{
      'dark-mode': isDarkMode
    }"
  >

    <!-- BACKGROUND -->

    <div class="bg-shape bg-shape-1"></div>
    <div class="bg-shape bg-shape-2"></div>
    <div class="bg-shape bg-shape-3"></div>

    <!-- ================================================= -->
    <!-- PAGE HEADER -->
    <!-- ================================================= -->

    <section class="products-hero">

      <div class="container">

        <div class="page-badge">
          <span>🧺</span>
          Koleksi Cemilku
        </div>

        <h1>
          Semua Produk
          <span>
            Cemilku
          </span>
        </h1>

        <p>
          Temukan berbagai camilan
          favorit dengan rasa renyah,
          nikmat, dan berkualitas.
        </p>

      </div>

    </section>

    <!-- ================================================= -->
    <!-- PRODUCT CONTENT -->
    <!-- ================================================= -->

    <main class="catalog-section">

      <div class="container">

        <!-- FILTER -->

        <div class="filter-card">

          <div class="search-wrapper">

            <span>
              🔍
            </span>

            <input
              v-model="searchQuery"
              type="text"
              placeholder="Cari nama atau jenis camilan..."
            />

          </div>

          <div class="category-wrapper">

            <button
              v-for="category in categories"
              :key="category.id"
              class="category-button"
              :class="{
                active:
                  selectedCategory ===
                  category.id
              }"
              @click="
                selectedCategory =
                  category.id
              "
            >
              <span>
                {{ category.icon }}
              </span>

              {{ category.name }}
            </button>

          </div>

        </div>

        <!-- RESULT INFO -->

        <div class="result-header">

          <div>
            <h2>
              Katalog Produk
            </h2>

            <p>
              Menampilkan
              <strong>
                {{ filteredProducts.length }}
              </strong>
              produk
            </p>
          </div>

          <button
            class="back-home-button"
            @click="goHome"
          >
            ← Kembali ke Beranda
          </button>

        </div>

        <!-- LOADING -->

        <div
          v-if="loading"
          class="state-card"
        >
          <div class="state-icon">
            ⏳
          </div>

          <h3>
            Memuat Produk...
          </h3>

          <p>
            Tunggu sebentar,
            kami sedang mengambil
            data produk.
          </p>
        </div>

        <!-- ERROR -->

        <div
          v-else-if="error"
          class="state-card"
        >
          <div class="state-icon">
            ⚠️
          </div>

          <h3>
            Gagal Memuat Produk
          </h3>

          <p>
            {{ error }}
          </p>

          <button
            class="btn-primary retry-button"
            @click="retryProducts"
          >
            🔄 Coba Lagi
          </button>
        </div>

        <!-- PRODUCTS -->

        <div
          v-else-if="
            filteredProducts.length > 0
          "
          class="product-grid"
        >

          <article
            v-for="product in filteredProducts"
            :key="product.id"
            class="product-card"
            @click="
              goToDetail(product.id)
            "
          >

            <div class="product-image">

              <img
                :src="product.image"
                :alt="product.name"
                @error="
                  handleProductImageError
                "
              />

              <span
                v-if="product.category"
                class="product-category"
              >
                {{ product.category }}
              </span>

            </div>

            <div class="product-content">

              <h3>
                {{ product.name }}
              </h3>

              <p class="description">
                {{ product.description }}
              </p>

              <div class="product-footer">

                <div>
                  <span class="price-label">
                    Harga
                  </span>

                  <strong>
                    Rp
                    {{
                      product.price.toLocaleString(
                        'id-ID'
                      )
                    }}
                  </strong>
                </div>

                <button
                  class="detail-button"
                  @click.stop="
                    goToDetail(product.id)
                  "
                >
                  Detail
                  <span>→</span>
                </button>

              </div>

            </div>

          </article>

        </div>

        <!-- EMPTY -->

        <div
          v-else
          class="state-card"
        >

          <div class="state-icon">
            📭
          </div>

          <h3>
            Produk Tidak Ditemukan
          </h3>

          <p>
            Tidak ada produk yang
            sesuai dengan pencarian
            atau kategori kamu.
          </p>

          <button
            class="btn-primary retry-button"
            @click="
              searchQuery = '';
              selectedCategory = 'Semua'
            "
          >
            🔄 Reset Filter
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

.products-page {
  min-height: 100vh;
  background: #f6f8fb;
  color: #1e293b;
  font-family:
    'Plus Jakarta Sans',
    system-ui,
    -apple-system,
    sans-serif;
  overflow-x: hidden;
  position: relative;
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
   BACKGROUND
===================================================== */

.bg-shape {
  position: absolute;
  border-radius: 50%;
  filter: blur(140px);
  pointer-events: none;
  z-index: 0;
}

.bg-shape-1 {
  top: -120px;
  right: -80px;
  width: 550px;
  height: 550px;
  background:
    radial-gradient(
      circle,
      rgba(147, 197, 253, 0.45),
      rgba(191, 219, 254, 0.1)
    );
}

.bg-shape-2 {
  top: 400px;
  left: -150px;
  width: 500px;
  height: 500px;
  background:
    radial-gradient(
      circle,
      rgba(96, 165, 250, 0.25),
      rgba(224, 242, 254, 0.05)
    );
}

.bg-shape-3 {
  bottom: 100px;
  right: 10%;
  width: 400px;
  height: 400px;
  background:
    radial-gradient(
      circle,
      rgba(186, 230, 253, 0.3),
      rgba(240, 249, 255, 0.05)
    );
}

/* =====================================================
   NAVBAR
===================================================== */

.navbar {
  position: sticky;
  top: 0;
  z-index: 50;
  background: rgba(255, 255, 255, 0.78);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid #e2e8f0;
}

.nav-content {
  min-height: 76px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}

.brand-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
}

.brand-mark {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  padding: 6px;
  box-sizing: border-box;
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );
  box-shadow:
    0 8px 16px -4px
    rgba(37, 99, 235, 0.3);
}

.brand-mark.small {
  width: 34px;
  height: 34px;
  border-radius: 10px;
}

.logo-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.brand-info small {
  display: block;
  margin-top: 3px;
  color: #2563eb;
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.15em;
}

.brand-text {
  color: #0f172a;
  font-size: 1.35rem;
  font-weight: 800;
}

.nav-links {
  display: flex;
  gap: 4px;
  padding: 5px;
  background: rgba(241, 245, 249, 0.7);
  border: 1px solid #e2e8f0;
  border-radius: 14px;
}

.nav-btn {
  border: none;
  background: transparent;
  color: #64748b;
  padding: 8px 18px;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.nav-btn:hover {
  color: #2563eb;
}

.nav-btn.active {
  background: #ffffff;
  color: #2563eb;
  box-shadow:
    0 2px 8px
    rgba(0, 0, 0, 0.04);
}

.nav-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.theme-button {
  width: 38px;
  height: 38px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  cursor: pointer;
}

.btn-outline,
.btn-primary {
  padding: 9px 17px;
  border-radius: 12px;
  font-size: 0.82rem;
  font-weight: 700;
  cursor: pointer;
}

.btn-outline {
  background: #ffffff;
  border: 1px solid #cbd5e1;
  color: #334155;
}

.btn-primary {
  border: none;
  color: #ffffff;
  background:
    linear-gradient(
      135deg,
      #2563eb,
      #3b82f6
    );
  box-shadow:
    0 4px 14px
    rgba(37, 99, 235, 0.25);
}

/* =====================================================
   HERO
===================================================== */

.products-hero {
  position: relative;
  z-index: 1;
  padding: 65px 0 45px;
  text-align: center;
}

.page-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 7px 15px;
  border-radius: 20px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #2563eb;
  font-size: 0.8rem;
  font-weight: 700;
  margin-bottom: 18px;
}

.products-hero h1 {
  margin: 0 0 12px;
  color: #0f172a;
  font-size: 2.7rem;
  font-weight: 800;
  letter-spacing: -0.04em;
}

.products-hero h1 span {
  color: #2563eb;
}

.products-hero p {
  max-width: 600px;
  margin: auto;
  color: #64748b;
  line-height: 1.7;
}

/* =====================================================
   CATALOG
===================================================== */

.catalog-section {
  position: relative;
  z-index: 1;
  padding-bottom: 80px;
}

.filter-card {
  padding: 20px;
  margin-bottom: 32px;
  background: rgba(255, 255, 255, 0.85);
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  box-shadow:
    0 12px 30px -15px
    rgba(37, 99, 235, 0.15);
}

.search-wrapper {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  margin-bottom: 15px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #ffffff;
}

.search-wrapper input {
  width: 100%;
  border: none;
  outline: none;
  background: transparent;
  color: #1e293b;
  font-size: 0.9rem;
}

.category-wrapper {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.category-button {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border: 1px solid #e2e8f0;
  border-radius: 11px;
  background: #ffffff;
  color: #475569;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

.category-button:hover {
  border-color: #93c5fd;
  color: #2563eb;
}

.category-button.active {
  background: #2563eb;
  color: #ffffff;
  border-color: #2563eb;
}

.result-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-bottom: 24px;
}

.result-header h2 {
  margin: 0 0 4px;
  color: #0f172a;
  font-size: 1.5rem;
}

.result-header p {
  margin: 0;
  color: #64748b;
  font-size: 0.85rem;
}

.back-home-button {
  padding: 9px 14px;
  border: 1px solid #bfdbfe;
  border-radius: 11px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.8rem;
  font-weight: 700;
  cursor: pointer;
}

/* =====================================================
   PRODUCT GRID
===================================================== */

.product-grid {
  display: grid;
  grid-template-columns:
    repeat(3, minmax(0, 1fr));
  gap: 24px;
}

.product-card {
  overflow: hidden;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 20px;
  cursor: pointer;
  transition: all 0.25s ease;
}

.product-card:hover {
  transform: translateY(-5px);
  border-color: #bfdbfe;
  box-shadow:
    0 20px 40px -18px
    rgba(37, 99, 235, 0.3);
}

.product-image {
  position: relative;
  height: 240px;
  overflow: hidden;
  background:
    linear-gradient(
      135deg,
      #eff6ff,
      #f8fafc
    );
}

.product-image img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  transition: transform 0.3s ease;
}

.product-card:hover
.product-image img {
  transform: scale(1.05);
}

.product-category {
  position: absolute;
  top: 14px;
  left: 14px;
  padding: 6px 10px;
  border: 1px solid #bfdbfe;
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.92);
  color: #2563eb;
  font-size: 0.7rem;
  font-weight: 700;
}

.product-content {
  padding: 20px;
}

.product-content h3 {
  margin: 0 0 8px;
  color: #0f172a;
  font-size: 1.05rem;
  font-weight: 800;
}

.description {
  min-height: 45px;
  margin: 0 0 20px;
  color: #64748b;
  font-size: 0.82rem;
  line-height: 1.6;
}

.product-footer {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 10px;
}

.price-label {
  display: block;
  margin-bottom: 3px;
  color: #94a3b8;
  font-size: 0.68rem;
}

.product-footer strong {
  color: #2563eb;
  font-size: 1rem;
}

.detail-button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  border: none;
  padding: 8px 12px;
  border-radius: 10px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
}

.detail-button:hover {
  background: #2563eb;
  color: #ffffff;
}

/* =====================================================
   STATE
===================================================== */

.state-card {
  padding: 60px 20px;
  text-align: center;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 24px;
}

.state-icon {
  width: 58px;
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 15px;
  border-radius: 17px;
  background: #eff6ff;
  font-size: 1.5rem;
}

.state-card h3 {
  margin: 0 0 7px;
  color: #0f172a;
  font-size: 1.15rem;
}

.state-card p {
  margin: 0 0 18px;
  color: #64748b;
  font-size: 0.85rem;
}

.retry-button {
  border: none;
}

/* =====================================================
   FOOTER
===================================================== */

.footer {
  position: relative;
  z-index: 1;
  padding: 50px 0 25px;
  background:
    linear-gradient(
      180deg,
      #f8fbff,
      #ffffff
    );
  border-top: 1px solid #e2e8f0;
}

.footer-grid {
  display: grid;
  grid-template-columns:
    1.4fr 0.8fr 1fr;
  gap: 35px;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 15px;
}

.footer-logo span {
  color: #0f172a;
  font-size: 1.1rem;
  font-weight: 800;
}

.footer-brand p {
  max-width: 400px;
  color: #64748b;
  font-size: 0.85rem;
  line-height: 1.7;
}

.footer-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 9px;
}

.footer-column h4 {
  margin: 0 0 6px;
  color: #0f172a;
  font-size: 0.9rem;
}

.footer-column button,
.footer-column span {
  border: none;
  padding: 0;
  background: transparent;
  color: #64748b;
  font-size: 0.82rem;
  text-align: left;
}

.footer-column button {
  cursor: pointer;
}

.footer-column button:hover {
  color: #2563eb;
}

.footer-bottom {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  margin-top: 35px;
  padding-top: 18px;
  border-top: 1px solid #e2e8f0;
  color: #94a3b8;
  font-size: 0.75rem;
}

/* =====================================================
   DARK MODE
===================================================== */

.dark-mode {
  background: #0f172a !important;
  color: #f8fafc;
}

.dark-mode .navbar {
  background: rgba(15, 23, 42, 0.88);
  border-color: #334155;
}

.dark-mode .brand-text,
.dark-mode .products-hero h1,
.dark-mode .result-header h2,
.dark-mode .product-content h3,
.dark-mode .state-card h3,
.dark-mode .footer-logo span,
.dark-mode .footer-column h4 {
  color: #f8fafc;
}

.dark-mode .nav-links {
  background: rgba(30, 41, 59, 0.8);
  border-color: #334155;
}

.dark-mode .nav-btn {
  color: #94a3b8;
}

.dark-mode .nav-btn.active {
  background: #1e293b;
  color: #60a5fa;
}

.dark-mode .theme-button,
.dark-mode .btn-outline {
  background: #1e293b;
  border-color: #334155;
  color: #cbd5e1;
}

.dark-mode .products-hero p,
.dark-mode .result-header p,
.dark-mode .description,
.dark-mode .state-card p,
.dark-mode .footer-brand p,
.dark-mode .footer-column button,
.dark-mode .footer-column span {
  color: #94a3b8;
}

.dark-mode .filter-card,
.dark-mode .product-card,
.dark-mode .state-card {
  background: rgba(30, 41, 59, 0.82);
  border-color: #334155;
}

.dark-mode .search-wrapper {
  background: #0f172a;
  border-color: #334155;
}

.dark-mode .search-wrapper input {
  color: #f8fafc;
}

.dark-mode .category-button {
  background: #1e293b;
  border-color: #334155;
  color: #cbd5e1;
}

.dark-mode .category-button.active {
  background: #2563eb;
  color: #ffffff;
}

.dark-mode .product-image {
  background:
    linear-gradient(
      135deg,
      #1e293b,
      #0f172a
    );
}

.dark-mode .product-category {
  background: rgba(15, 23, 42, 0.92);
  border-color: #334155;
}

.dark-mode .back-home-button,
.dark-mode .detail-button {
  background: #1e3a8a;
  border-color: #1d4ed8;
  color: #93c5fd;
}

.dark-mode .footer {
  background: #0f172a;
  border-color: #1e293b;
}

.dark-mode .footer-bottom {
  border-color: #1e293b;
}

/* =====================================================
   RESPONSIVE
===================================================== */

@media (max-width: 1050px) {
  .nav-links {
    gap: 2px;
  }

  .nav-btn {
    padding: 8px 11px;
  }

  .product-grid {
    grid-template-columns:
      repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 850px) {
  .nav-content {
    flex-wrap: wrap;
    padding-top: 12px;
    padding-bottom: 12px;
  }

  .nav-links {
    order: 3;
    width: 100%;
    justify-content: center;
  }

  .products-hero {
    padding-top: 45px;
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

  .nav-actions .btn-outline,
  .nav-actions .btn-primary {
    padding: 8px 10px;
    font-size: 0.72rem;
  }

  .products-hero h1 {
    font-size: 2.1rem;
  }

  .product-grid {
    grid-template-columns: 1fr;
  }

  .category-wrapper {
    overflow-x: auto;
    flex-wrap: nowrap;
    padding-bottom: 5px;
  }

  .category-button {
    flex-shrink: 0;
  }

  .result-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .back-home-button {
    width: 100%;
  }
}

@media (max-width: 480px) {
  .nav-actions {
    gap: 4px;
  }

  .theme-button {
    width: 34px;
    height: 34px;
  }

  .nav-btn {
    padding: 7px 9px;
    font-size: 0.75rem;
  }

  .products-hero h1 {
    font-size: 1.8rem;
  }
}
</style>
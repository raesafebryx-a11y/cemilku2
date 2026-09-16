<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isNavbarDark = ref(false)

const loading = ref(true)
const error = ref('')

// ==========================================
// DATA DASHBOARD
// ==========================================

const stats = ref([
  {
    id: 1,
    label: 'Total Produk',
    value: 0,
    sub: 'Produk di toko',
    icon: '🍿'
  },
  {
    id: 2,
    label: 'Total Kategori',
    value: 0,
    sub: 'Kategori produk',
    icon: '🏷️'
  },
  {
    id: 3,
    label: 'Total Order',
    value: 0,
    sub: 'Pesanan masuk',
    icon: '📑'
  },
  {
    id: 4,
    label: 'Pesan Kontak',
    value: 0,
    sub: 'Pesan dari pelanggan',
    icon: '💬'
  }
])

const products = ref([])

// ==========================================
// THEME
// ==========================================

const loadTheme = () => {
  const savedTheme = localStorage.getItem('admin-theme-mode')

  if (savedTheme) {
    isNavbarDark.value = savedTheme === 'dark'
  }
}

const handleThemeChange = (event) => {
  if (event.detail?.dark !== undefined) {
    isNavbarDark.value = event.detail.dark
  }
}

const toggleNavbarTheme = () => {
  isNavbarDark.value = !isNavbarDark.value

  localStorage.setItem(
    'admin-theme-mode',
    isNavbarDark.value ? 'dark' : 'light'
  )

  window.dispatchEvent(
    new CustomEvent('admin-theme-change', {
      detail: {
        dark: isNavbarDark.value
      }
    })
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
// FORMAT HARGA
// ==========================================

const formatRupiah = (value) => {
  if (value === null || value === undefined) {
    return '-'
  }

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(value)
}

// ==========================================
// DATA PRODUK
// ==========================================

const getProductName = (product) => {
  return (
    product.name ||
    product.nama_barang ||
    product.nama ||
    'Produk'
  )
}

const getProductCategory = (product) => {
  return (
    product.category?.name ||
    product.kategori?.nama ||
    product.category?.nama ||
    product.category ||
    product.kategori ||
    '-'
  )
}

const getProductPrice = (product) => {
  return (
    product.price ??
    product.harga_barang ??
    product.harga ??
    0
  )
}

const getProductStock = (product) => {
  return (
    product.stock ??
    product.stok ??
    0
  )
}

const getProductStatus = (product) => {
  const stock = Number(getProductStock(product))

  if (stock <= 0) {
    return 'Habis'
  }

  if (stock <= 5) {
    return 'Stok Menipis'
  }

  return 'Tersedia'
}

// ==========================================
// CEK ADMIN
// ==========================================

const checkAdmin = () => {
  if (!auth.isLoggedIn) {
    router.replace('/login')
    return false
  }

  if (auth.userRole !== 'admin') {
    router.replace('/')
    return false
  }

  return true
}

// ==========================================
// FETCH DASHBOARD
// ==========================================

const fetchDashboard = async () => {
  loading.value = true
  error.value = ''

  try {
    const [
      productsResponse,
      categoriesResponse,
      ordersResponse,
      contactsResponse
    ] = await Promise.all([
      api.get('/products'),
      api.get('/categories'),
      api.get('/orders'),
      api.get('/contacts')
    ])

    // ======================================
    // PRODUK
    // ======================================

    const productResponseData = productsResponse.data

    let productData =
      productResponseData?.data ?? productResponseData

    if (
      productData?.data &&
      Array.isArray(productData.data)
    ) {
      productData = productData.data
    }

    if (!Array.isArray(productData)) {
      productData = []
    }

    products.value = productData.slice(0, 5)

    // ======================================
    // KATEGORI
    // ======================================

    const categoryResponseData = categoriesResponse.data

    let categoryData =
      categoryResponseData?.data ??
      categoryResponseData

    if (!Array.isArray(categoryData)) {
      categoryData = []
    }

    // ======================================
    // ORDER
    // ======================================

    const orderResponseData = ordersResponse.data

    let orderData =
      orderResponseData?.data ??
      orderResponseData

    if (
      orderData?.data &&
      Array.isArray(orderData.data)
    ) {
      orderData = orderData.data
    }

    if (!Array.isArray(orderData)) {
      orderData = []
    }

    // ======================================
    // CONTACT
    // ======================================

    const contactResponseData = contactsResponse.data

    let contactData =
      contactResponseData?.data ??
      contactResponseData

    if (!Array.isArray(contactData)) {
      contactData = []
    }

    // ======================================
    // UPDATE STAT
    // ======================================

    stats.value = [
      {
        id: 1,
        label: 'Total Produk',
        value: productData.length,
        sub: 'Produk di toko',
        icon: '🍿'
      },
      {
        id: 2,
        label: 'Total Kategori',
        value: categoryData.length,
        sub: 'Kategori produk',
        icon: '🏷️'
      },
      {
        id: 3,
        label: 'Total Order',
        value: orderData.length,
        sub: 'Pesanan masuk',
        icon: '📑'
      },
      {
        id: 4,
        label: 'Pesan Kontak',
        value: contactData.length,
        sub: 'Pesan dari pelanggan',
        icon: '💬'
      }
    ]
  } catch (err) {
    console.error(
      'Gagal mengambil data dashboard:',
      err
    )

    error.value =
      err.response?.data?.message ||
      'Gagal mengambil data dashboard.'

    await Swal.fire({
      icon: 'error',
      title: 'Dashboard Gagal Dimuat',
      text: error.value,
      confirmButtonColor: '#2563eb'
    })
  } finally {
    loading.value = false
  }
}

// ==========================================
// LOGOUT
// ==========================================

const handleLogout = async () => {
  const result = await Swal.fire({
    title: 'Keluar dari Admin?',
    text: 'Anda akan keluar dari akun admin.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#94a3b8',
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal'
  })

  if (!result.isConfirmed) {
    return
  }

  try {
    await auth.logout()
  } catch (err) {
    console.error('Logout error:', err)
  }

  router.push('/login')
}

// ==========================================
// MOUNT
// ==========================================

onMounted(async () => {
  loadTheme()

  window.addEventListener(
    'admin-theme-change',
    handleThemeChange
  )

  if (!auth.isLoggedIn) {
    router.replace('/login')
    return
  }

  if (!auth.user) {
    try {
      await auth.fetchMe()
    } catch (err) {
      console.error(
        'Gagal mengambil data user:',
        err
      )

      router.replace('/login')
      return
    }
  }

  if (!checkAdmin()) {
    return
  }

  await fetchDashboard()
})

// ==========================================
// UNMOUNT
// ==========================================

onUnmounted(() => {
  window.removeEventListener(
    'admin-theme-change',
    handleThemeChange
  )
})
</script>

<template>
  <div
    class="dashboard-layout"
    :class="{ 'navbar-dark': isNavbarDark }"
  >

    <!-- ==================================================
         SIDEBAR
    =================================================== -->
    <aside class="sidebar">

      <!-- BRAND -->
      <div
        class="sidebar-brand"
        @click="router.push('/')"
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

      <!-- MENU -->
      <nav class="sidebar-menu">

        <!-- MAIN -->
        <div class="menu-category">
          MAIN
        </div>

        <router-link
          to="/admin"
          class="menu-item"
          exact
        >
          <span class="menu-icon">
            📊
          </span>

          <span class="menu-text">
            Dashboard
          </span>
        </router-link>

        <!-- KELOLA TOKO -->
        <div class="menu-category">
          KELOLA TOKO
        </div>

        <router-link
          to="/admin/produk"
          class="menu-item"
        >
          <span class="menu-icon">
            🍿
          </span>

          <span class="menu-text">
            Produk
          </span>
        </router-link>

        <router-link
          to="/admin/kategori"
          class="menu-item"
        >
          <span class="menu-icon">
            🏷️
          </span>

          <span class="menu-text">
            Kategori
          </span>
        </router-link>

        <router-link
          to="/admin/order"
          class="menu-item"
        >
          <span class="menu-icon">
            📑
          </span>

          <span class="menu-text">
            Order
          </span>
        </router-link>

        <router-link
          to="/admin/order-item"
          class="menu-item"
        >
          <span class="menu-icon">
            📋
          </span>

          <span class="menu-text">
            Order Item
          </span>
        </router-link>

        <router-link
          to="/admin/kontak"
          class="menu-item"
        >
          <span class="menu-icon">
            💬
          </span>

          <span class="menu-text">
            Pesan Kontak
          </span>
        </router-link>

        <!-- SISTEM -->
        <div class="menu-category">
          SISTEM
        </div>

        <router-link
          to="/admin/pengaturan"
          class="menu-item"
        >
          <span class="menu-icon">
            ⚙️
          </span>

          <span class="menu-text">
            Pengaturan
          </span>
        </router-link>

        <!-- LOGOUT -->
        <a
          href="#"
          class="menu-item logout"
          @click.prevent="handleLogout"
        >
          <span class="menu-icon">
            🚪
          </span>

          <span class="menu-text">
            Keluar
          </span>
        </a>

      </nav>
    </aside>

    <!-- ==================================================
         MAIN WRAPPER
    =================================================== -->
    <div class="main-wrapper">

      <!-- TOPBAR -->
      <header class="topbar">

        <div class="search-box">
          <span class="search-icon">
            🔍
          </span>

          <input
            type="text"
            placeholder="Cari produk atau pesanan..."
          />
        </div>

        <div class="topbar-right">

          <button
            class="theme-toggle"
            aria-label="Toggle navbar theme"
            @click="toggleNavbarTheme"
          >
            {{ isNavbarDark ? '☀️' : '🌙' }}
          </button>

          <button
            class="icon-btn"
            aria-label="Notifikasi"
          >
            🔔
          </button>

          <div class="user-info">
            <div class="user-name">
              {{ auth.username || 'Admin' }}
            </div>

            <div class="user-role">
              Administrator
            </div>
          </div>

          <div class="user-avatar">
            {{
              (auth.username || 'A')
                .charAt(0)
                .toUpperCase()
            }}
          </div>

        </div>
      </header>

      <!-- ==================================================
           CONTENT
      =================================================== -->
      <main class="content-body">

        <!-- BANNER -->
        <div class="dashboard-banner">

          <div class="banner-content">

            <div>
              <span class="welcome-text">
                Selamat datang kembali 👋
              </span>

              <h1>
                Dashboard Overview
              </h1>

              <p>
                Kelola toko Cemilku dengan mudah dari sini.
              </p>
            </div>

            <button
              class="btn-primary-action"
              @click="router.push('/admin/produk')"
            >
              <span class="btn-icon">
                +
              </span>

              Tambah Produk
            </button>

          </div>

        </div>

        <!-- ERROR -->
        <div
          v-if="!loading && error"
          class="error-state"
        >
          <div class="empty-icon">
            ⚠️
          </div>

          <h3>
            Data Dashboard Bermasalah
          </h3>

          <p>
            {{ error }}
          </p>

          <button
            class="btn-primary-action"
            @click="fetchDashboard"
          >
            🔄 Coba Lagi
          </button>
        </div>

        <!-- LOADING -->
        <div
          v-else-if="loading"
          class="loading-state"
        >
          <div class="loading-spinner"></div>

          <p>
            Memuat data dashboard...
          </p>
        </div>

        <!-- STATS -->
        <div v-else class="analytics-grid">
          <div class="trend-panel">
            <div class="panel-header">
              <div class="mini-calendar">
                <span class="calendar-icon">🗓️</span>
              </div>
              <span class="panel-year">2023-2028</span>
            </div>

            <div class="trend-value">17.3%</div>

            <svg viewBox="0 0 420 220" class="trend-chart" aria-label="Growth trend chart">
              <g class="chart-grid">
                <line x1="0" y1="30" x2="420" y2="30" />
                <line x1="0" y1="70" x2="420" y2="70" />
                <line x1="0" y1="110" x2="420" y2="110" />
                <line x1="0" y1="150" x2="420" y2="150" />
                <line x1="0" y1="190" x2="420" y2="190" />
              </g>

              <path
                d="M 0 175 L 60 168 L 110 152 L 160 128 L 205 113 L 260 102 L 310 82 L 360 60 L 420 38"
                class="trend-line"
              />

              <circle cx="60" cy="168" r="5" class="chart-point" />
              <circle cx="110" cy="152" r="5" class="chart-point" />
              <circle cx="160" cy="128" r="5" class="chart-point" />
              <circle cx="205" cy="113" r="5" class="chart-point" />
              <circle cx="260" cy="102" r="5" class="chart-point" />
              <circle cx="310" cy="82" r="5" class="chart-point" />
              <circle cx="360" cy="60" r="5" class="chart-point" />
            </svg>
          </div>

          <div class="market-panel">
            <h2>Market opportunity<br>over next 5 years</h2>

            <div class="bar-chart-wrap">
              <div class="chart-label chart-label-left">2.5BN</div>
              <div class="chart-label chart-label-right">5.7BN</div>

              <div class="bar-chart">
                <div class="bar-group bar-group-left">
                  <div class="bar bar-navy" style="height: 42%"></div>
                </div>

                <div class="bar-group bar-group-right">
                  <div class="bar bar-orange" style="height: 72%"></div>
                </div>
              </div>

              <div class="axis-labels">
                <span>2023</span>
                <span>2028</span>
              </div>
            </div>
          </div>
        </div>

        <!-- PRODUCTS -->
        <div
          v-if="
            !loading &&
            !error &&
            products.length
          "
          class="table-card"
        >

          <div class="table-header">

            <div>
              <h3>
                Daftar Produk Cemilku
              </h3>

              <p>
                Produk terbaru di toko
              </p>
            </div>

            <button
              class="view-all-btn"
              @click="router.push('/admin/produk')"
            >
              Lihat Semua →
            </button>

          </div>

          <div class="table-responsive">

            <table>

              <thead>
                <tr>
                  <th>
                    Nama Produk
                  </th>

                  <th>
                    Kategori
                  </th>

                  <th>
                    Harga
                  </th>

                  <th>
                    Stok
                  </th>

                  <th>
                    Status
                  </th>
                </tr>
              </thead>

              <tbody>

                <tr
                  v-for="item in products"
                  :key="item.id"
                >
                  <td class="font-bold">
                    {{ getProductName(item) }}
                  </td>

                  <td>
                    {{ getProductCategory(item) }}
                  </td>

                  <td>
                    {{ formatRupiah(getProductPrice(item)) }}
                  </td>

                  <td>
                    {{ getProductStock(item) }} pcs
                  </td>

                  <td>

                    <span
                      class="status-badge"
                      :class="{
                        'status-success':
                          getProductStatus(item) === 'Tersedia',

                        'status-warning':
                          getProductStatus(item) === 'Stok Menipis',

                        'status-danger':
                          getProductStatus(item) === 'Habis'
                      }"
                    >
                      {{ getProductStatus(item) }}
                    </span>

                  </td>
                </tr>

              </tbody>

            </table>

          </div>
        </div>

        <!-- EMPTY -->
        <div
          v-else-if="
            !loading &&
            !error &&
            !products.length
          "
          class="empty-state"
        >
          <div class="empty-icon">
            📦
          </div>

          <h3>
            Belum Ada Produk
          </h3>

          <p>
            Produk yang kamu tambahkan akan muncul di sini.
          </p>

          <button
            class="btn-primary-action"
            @click="router.push('/admin/produk')"
          >
            + Tambah Produk
          </button>
        </div>

      </main>
    </div>
  </div>
</template>

<style scoped>
/* ==================================================
   DASHBOARD
================================================== */

.dashboard-layout {
  --page-bg: linear-gradient(
    180deg,
    #f8fbff 0%,
    #eef5ff 100%
  );

  --sidebar-bg: rgba(255, 255, 255, 0.8);
  --sidebar-border: rgba(226, 232, 240, 0.9);

  --topbar-bg: rgba(255, 255, 255, 0.75);
  --topbar-border: rgba(226, 232, 240, 0.9);

  --surface: rgba(255, 255, 255, 0.9);

  --text: #0f172a;
  --muted: #64748b;
  --nav-text: #475569;

  --nav-hover: #eff6ff;

  --nav-active: linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
  );

  --panel-border: rgba(226, 232, 240, 0.9);

  min-height: 100vh;

  display: flex;

  background: var(--page-bg);

  color: var(--text);

  font-family:
    'Plus Jakarta Sans',
    system-ui,
    -apple-system,
    sans-serif;
}

/* ==================================================
   DARK MODE
================================================== */

.dashboard-layout.navbar-dark {
  --page-bg: linear-gradient(
    180deg,
    #0f172a 0%,
    #111827 100%
  );

  --sidebar-bg: rgba(15, 23, 42, 0.85);
  --sidebar-border: rgba(51, 65, 85, 0.9);

  --topbar-bg: rgba(15, 23, 42, 0.8);
  --topbar-border: rgba(51, 65, 85, 0.9);

  --surface: rgba(15, 23, 42, 0.8);

  --text: #f8fafc;
  --muted: #cbd5e1;
  --nav-text: #cbd5e1;

  --nav-hover: rgba(59, 130, 246, 0.12);

  --nav-active: linear-gradient(
    135deg,
    #1d4ed8,
    #3b82f6
  );

  --panel-border: rgba(51, 65, 85, 0.9);
}

/* ==================================================
   SIDEBAR
================================================== */

.sidebar {
  width: 250px;
  min-width: 250px;

  height: 100vh;

  position: fixed;
  left: 0;
  top: 0;

  background: var(--sidebar-bg);

  border-right: 1px solid var(--sidebar-border);

  display: flex;
  flex-direction: column;

  flex-shrink: 0;

  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);

  z-index: 1000;
}

/* ==================================================
   SIDEBAR BRAND
================================================== */

.sidebar-brand {
  height: 64px;

  padding: 12px 18px;

  display: flex;
  align-items: center;

  gap: 12px;

  border-bottom: 1px solid var(--sidebar-border);

  cursor: pointer;

  transition: opacity 0.2s ease;

  flex-shrink: 0;
}

.sidebar-brand:hover {
  opacity: 0.9;
}

.brand-mark {
  width: 38px;
  height: 38px;

  border-radius: 10px;

  background: linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
  );

  display: flex;
  align-items: center;
  justify-content: center;

  overflow: hidden;

  padding: 3px;

  flex-shrink: 0;

  box-shadow:
    0 8px 20px
    rgba(37, 99, 235, 0.18);
}

.cemilku-logo-img {
  width: 100%;
  height: 100%;

  object-fit: contain;

  display: block;
}

.brand-info {
  display: flex;
  flex-direction: column;

  min-width: 0;
}

.brand-text {
  font-weight: 800;

  font-size: 1.3rem;

  color: var(--text);

  letter-spacing: -0.05em;

  line-height: 1.1;
}

.brand-info small {
  font-size: 0.58rem;

  letter-spacing: 0.16em;

  color: #2563eb;

  font-weight: 700;

  text-transform: uppercase;

  margin-top: 2px;
}

/* ==================================================
   SIDEBAR MENU
================================================== */

.sidebar-menu {
  padding: 16px 12px 20px;

  display: flex;
  flex-direction: column;

  gap: 4px;

  overflow-y: auto;
}

.sidebar-menu::-webkit-scrollbar {
  width: 4px;
}

.sidebar-menu::-webkit-scrollbar-thumb {
  background: rgba(148, 163, 184, 0.4);

  border-radius: 10px;
}

/* ==================================================
   MENU CATEGORY
================================================== */

.menu-category {
  font-size: 10px;

  font-weight: 700;

  color: var(--muted);

  padding: 12px 12px 5px;

  letter-spacing: 0.6px;

  text-transform: uppercase;
}

/* ==================================================
   MENU ITEM
================================================== */

.menu-item {
  width: 100%;

  display: flex;
  align-items: center;

  gap: 10px;

  padding: 10px 12px;

  color: var(--nav-text);

  text-decoration: none;

  font-size: 14px;

  font-weight: 600;

  border-radius: 10px;

  border: none;

  background: transparent;

  cursor: pointer;

  transition:
    background 0.2s ease,
    color 0.2s ease;

  box-sizing: border-box;
}

.menu-item:hover {
  background: var(--nav-hover);

  color: var(--text);
}

/* ==================================================
   ACTIVE MENU
================================================== */

.menu-item.router-link-active {
  background: var(--nav-active);

  color: #ffffff;

  box-shadow:
    0 8px 16px
    rgba(37, 99, 235, 0.18);
}

/*
  Dashboard hanya aktif saat:
  /admin

  Tidak ikut aktif pada:
  /admin/produk
  /admin/kategori
  /admin/order
  /admin/order-item
  /admin/kontak
*/

.menu-item.router-link-exact-active {
  background: var(--nav-active);

  color: #ffffff;

  box-shadow:
    0 8px 16px
    rgba(37, 99, 235, 0.18);
}

/* ==================================================
   ICON
================================================== */

.menu-icon {
  width: 22px;
  min-width: 22px;

  display: flex;
  align-items: center;
  justify-content: center;

  font-size: 17px;

  line-height: 1;
}

/* ==================================================
   TEXT
================================================== */

.menu-text {
  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}

/* ==================================================
   LOGOUT
================================================== */

.menu-item.logout {
  color: #dc2626;

  margin-top: 4px;
}

.menu-item.logout:hover {
  background: rgba(239, 68, 68, 0.08);

  color: #b91c1c;
}

/* ==================================================
   MAIN WRAPPER
================================================== */

.main-wrapper {
  flex: 1;

  min-width: 0;

  display: flex;
  flex-direction: column;

  overflow-x: hidden;

  margin-left: 250px;
}

/* ==================================================
   TOPBAR
================================================== */

.topbar {
  height: 64px;

  background: var(--topbar-bg);

  border-bottom: 1px solid var(--topbar-border);

  display: flex;
  align-items: center;
  justify-content: space-between;

  padding: 0 28px;

  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);

  flex-shrink: 0;
}

/* ==================================================
   SEARCH
================================================== */

.search-box {
  display: flex;
  align-items: center;

  gap: 8px;

  background: rgba(
    148,
    163,
    184,
    0.06
  );

  border: 1px solid var(--panel-border);

  padding: 8px 14px;

  border-radius: 10px;

  width: 300px;
}

.search-icon {
  font-size: 14px;
}

.search-box input {
  background: transparent;

  border: none;

  outline: none;

  color: var(--text);

  font-size: 13px;

  width: 100%;
}

/* ==================================================
   TOPBAR RIGHT
================================================== */

.topbar-right {
  display: flex;
  align-items: center;

  gap: 12px;
}

.theme-toggle,
.icon-btn {
  background: rgba(
    148,
    163,
    184,
    0.08
  );

  border: 1px solid var(--panel-border);

  border-radius: 10px;

  cursor: pointer;

  font-size: 16px;

  width: 36px;
  height: 36px;

  display: grid;
  place-items: center;

  color: var(--text);
}

.user-info {
  text-align: right;

  line-height: 1.2;
}

.user-name {
  font-size: 13px;

  font-weight: 800;

  color: var(--text);
}

.user-role {
  font-size: 10px;

  color: var(--muted);

  margin-top: 3px;
}

.user-avatar {
  width: 36px;
  height: 36px;

  border-radius: 50%;

  background: linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
  );

  color: #ffffff;

  display: grid;
  place-items: center;

  font-weight: 700;
}

/* ==================================================
   CONTENT
================================================== */

.content-body {
  padding: 0 28px 28px;
}

/* ==================================================
   BANNER
================================================== */

.dashboard-banner {
  background: linear-gradient(
    135deg,
    rgba(191, 219, 254, 0.9),
    rgba(239, 246, 255, 0.9)
  );

  border: 1px solid
    rgba(191, 219, 254, 0.9);

  border-radius: 16px;

  padding: 28px;

  margin-top: 24px;

  padding-bottom: 45px;

  box-shadow:
    0 12px 24px
    rgba(37, 99, 235, 0.08);
}

.dashboard-layout.navbar-dark
.dashboard-banner {
  background: linear-gradient(
    135deg,
    rgba(30, 41, 59, 0.9),
    rgba(15, 23, 42, 0.9)
  );

  border-color:
    rgba(51, 65, 85, 0.9);
}

.banner-content {
  display: flex;

  justify-content: space-between;

  align-items: center;

  gap: 20px;
}

.welcome-text {
  display: block;

  font-size: 12px;

  color: #2563eb;

  font-weight: 700;

  margin-bottom: 6px;
}

.banner-content h1 {
  font-size: 22px;

  font-weight: 800;

  margin: 0;

  color: var(--text);
}

.banner-content p {
  margin: 7px 0 0;

  color: var(--muted);

  font-size: 13px;
}

/* ==================================================
   BUTTON
================================================== */

.btn-primary-action {
  background: linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
  );

  color: #ffffff;

  border: none;

  padding: 10px 20px;

  border-radius: 10px;

  font-weight: 700;

  font-size: 14px;

  cursor: pointer;

  display: inline-flex;
  align-items: center;

  gap: 8px;

  box-shadow:
    0 4px 12px
    rgba(37, 99, 235, 0.22);

  transition: all 0.3s ease;

  white-space: nowrap;
}

.btn-primary-action:hover {
  transform: translateY(-2px);

  box-shadow:
    0 8px 20px
    rgba(37, 99, 235, 0.3);
}

.btn-icon {
  font-size: 18px;
}

/* ==================================================
   STATS
================================================== */

.analytics-grid {
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 22px;
  margin-top: 8px;
  margin-bottom: 26px;
}

.trend-panel,
.market-panel {
  background: rgba(255, 255, 255, 0.7);
  border: 1px solid var(--panel-border);
  border-radius: 24px;
  padding: 18px 20px 14px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.05);
}

.dashboard-layout.navbar-dark .trend-panel,
.dashboard-layout.navbar-dark .market-panel {
  background: rgba(15, 23, 42, 0.7);
}

.panel-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
  color: var(--text);
}

.mini-calendar {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.12);
}

.calendar-icon {
  font-size: 18px;
  line-height: 1;
}

.panel-year {
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
}

.trend-value {
  font-size: clamp(2.2rem, 3vw, 3.5rem);
  font-weight: 900;
  letter-spacing: -0.06em;
  color: #1b334d;
  line-height: 1;
  margin: 0 0 8px;
}

.dashboard-layout.navbar-dark .trend-value {
  color: #dbeafe;
}

.trend-chart {
  width: 100%;
  height: 220px;
  display: block;
}

.chart-grid line {
  stroke: rgba(71, 85, 105, 0.18);
  stroke-width: 1;
}

.trend-line {
  fill: none;
  stroke: #1f3a5f;
  stroke-width: 3;
  stroke-linecap: round;
  stroke-linejoin: round;
}

.chart-point {
  fill: #f97316;
  stroke: #ffffff;
  stroke-width: 3;
}

.market-panel {
  background: #dfeaf5;
  padding-top: 20px;
}

.dashboard-layout.navbar-dark .market-panel {
  background: rgba(148, 163, 184, 0.08);
}

.market-panel h2 {
  margin: 0 0 18px;
  font-size: clamp(2rem, 2.2vw, 3.2rem);
  line-height: 1.06;
  letter-spacing: -0.06em;
  color: #1f2937;
}

.dashboard-layout.navbar-dark .market-panel h2 {
  color: #f8fafc;
}

.bar-chart-wrap {
  position: relative;
  padding-top: 12px;
}

.bar-chart {
  display: grid;
  grid-template-columns: 1fr 1fr;
  align-items: end;
  gap: 18px;
  min-height: 220px;
  padding-top: 24px;
}

.bar-group {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  height: 180px;
}

.bar {
  width: 100%;
  max-width: 140px;
  border-radius: 8px 8px 0 0;
  box-shadow: inset 0 -8px 12px rgba(255, 255, 255, 0.12);
}

.bar-navy {
  background: linear-gradient(180deg, #1f3d67, #1e293b);
}

.bar-orange {
  background: linear-gradient(180deg, #fb923c, #f97316);
}

.chart-label {
  position: absolute;
  top: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: #475569;
}

.chart-label-right {
  right: 14px;
}

.chart-label-left {
  left: 14px;
}

.axis-labels {
  display: flex;
  justify-content: space-between;
  margin-top: 10px;
  padding: 0 10px 0 6px;
  font-size: 0.82rem;
  font-weight: 700;
  color: #475569;
}

@media (max-width: 1100px) {
  .analytics-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .analytics-grid {
    margin-top: 0;
  }

  .trend-panel,
  .market-panel {
    padding: 16px 14px;
  }

  .market-panel h2 {
    font-size: 2rem;
  }
}

/* ==================================================
   TABLE
================================================== */

.table-card {
  background: var(--surface);

  border: 1px solid var(--panel-border);

  border-radius: 14px;

  margin-top: 24px;

  overflow: hidden;
}

.table-header {
  padding: 20px 24px;

  border-bottom:
    1px solid var(--panel-border);

  display: flex;

  align-items: center;

  justify-content: space-between;
}

.table-header h3 {
  margin: 0;

  font-size: 16px;

  font-weight: 800;

  color: var(--text);
}

.table-header p {
  margin: 5px 0 0;

  font-size: 12px;

  color: var(--muted);
}

.view-all-btn {
  border: none;

  background: transparent;

  color: #2563eb;

  font-weight: 700;

  cursor: pointer;

  font-size: 13px;
}

.table-responsive {
  overflow-x: auto;
}

table {
  width: 100%;

  border-collapse: collapse;

  text-align: left;

  font-size: 14px;
}

th {
  background: rgba(
    148,
    163,
    184,
    0.06
  );

  color: var(--muted);

  padding: 14px 24px;

  font-weight: 700;

  font-size: 12px;

  text-transform: uppercase;
}

td {
  padding: 16px 24px;

  border-bottom:
    1px solid var(--panel-border);

  color: var(--text);
}

.font-bold {
  font-weight: 700;
}

/* ==================================================
   STATUS
================================================== */

.status-badge {
  padding: 4px 10px;

  border-radius: 20px;

  font-size: 11px;

  font-weight: 700;
}

.status-success {
  background:
    rgba(34, 197, 94, 0.12);

  color: #15803d;
}

.status-warning {
  background:
    rgba(250, 204, 21, 0.14);

  color: #a16207;
}

.status-danger {
  background:
    rgba(239, 68, 68, 0.12);

  color: #b91c1c;
}

/* ==================================================
   LOADING / ERROR
================================================== */

.loading-state,
.error-state {
  margin-top: 24px;

  padding: 50px 20px;

  background: var(--surface);

  border: 1px solid var(--panel-border);

  border-radius: 14px;

  text-align: center;

  color: var(--muted);
}

.error-state h3 {
  margin: 0 0 8px;

  color: var(--text);
}

.error-state p {
  margin: 0 0 20px;

  font-size: 13px;
}

.loading-spinner {
  width: 34px;
  height: 34px;

  margin: 0 auto 12px;

  border: 3px solid
    rgba(37, 99, 235, 0.15);

  border-top-color: #2563eb;

  border-radius: 50%;

  animation:
    spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* ==================================================
   EMPTY
================================================== */

.empty-state {
  margin-top: 24px;

  background: var(--surface);

  border: 1px solid var(--panel-border);

  border-radius: 14px;

  padding: 45px 20px;

  text-align: center;

  color: var(--muted);
}

.empty-icon {
  font-size: 40px;

  margin-bottom: 10px;
}

.empty-state h3 {
  margin: 0 0 6px;

  color: var(--text);
}

.empty-state p {
  margin: 0 0 18px;

  font-size: 13px;
}

/* ==================================================
   RESPONSIVE
================================================== */

@media (max-width: 1100px) {
  .stats-grid {
    grid-template-columns:
      repeat(2, 1fr);
  }
}

@media (max-width: 800px) {
  .sidebar {
    width: 210px;
    min-width: 210px;
  }

  .main-wrapper {
    margin-left: 210px;
  }

  .search-box {
    width: 220px;
  }

  .content-body {
    padding: 0 18px 20px;
  }

  .banner-content {
    flex-direction: column;

    align-items: flex-start;
  }
}

@media (max-width: 600px) {
  .sidebar {
    width: 70px;
    min-width: 70px;
  }

  .main-wrapper {
    margin-left: 70px;
  }

  .sidebar-brand {
    justify-content: center;

    padding: 15px 8px;
  }

  .brand-info,
  .menu-category {
    display: none;
  }

  .sidebar-menu {
    padding: 15px 8px;
  }

  .menu-item {
    justify-content: center;

    padding: 12px;

    font-size: 0;
  }

  .menu-icon {
    font-size: 18px;
  }

  .menu-text {
    display: none;
  }

  .topbar {
    padding: 0 12px;
  }

  .search-box {
    display: none;
  }

  .user-info {
    display: none;
  }

  .stats-grid {
    grid-template-columns: 1fr;

    padding: 0;
  }

  .dashboard-banner {
    padding: 20px;
  }
}
</style>
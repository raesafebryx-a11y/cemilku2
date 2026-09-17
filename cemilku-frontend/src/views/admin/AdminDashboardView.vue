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

    if (
      categoryData?.data &&
      Array.isArray(categoryData.data)
    ) {
      categoryData = categoryData.data
    }

    if (!Array.isArray(categoryData)) {
      categoryData = []
    }

    // ======================================
    // ORDER (PENANGANAN TOTAL ORDER)
    // ======================================

    const orderResponseData = ordersResponse.data
    let totalOrderCount = 0

    // Deteksi jika API mengembalikan objek total angka langsung (misal: { total: 5 })
    if (typeof orderResponseData?.total === 'number') {
      totalOrderCount = orderResponseData.total
    } else if (typeof orderResponseData?.data?.total === 'number') {
      totalOrderCount = orderResponseData.data.total
    } else {
      // Jika merespons Array / List Order
      let orderData = orderResponseData?.data ?? orderResponseData

      if (orderData?.data && Array.isArray(orderData.data)) {
        orderData = orderData.data
      } else if (!Array.isArray(orderData) && Array.isArray(orderData?.orders)) {
        orderData = orderData.orders
      }

      if (Array.isArray(orderData)) {
        totalOrderCount = orderData.length
      }
    }

    // ======================================
    // CONTACT
    // ======================================

    const contactResponseData = contactsResponse.data

    let contactData =
      contactResponseData?.data ??
      contactResponseData

    if (
      contactData?.data &&
      Array.isArray(contactData.data)
    ) {
      contactData = contactData.data
    }

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
        value: totalOrderCount,
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

        <!-- STATS CARDS (RINGKAS & SEDERHANA) -->
        <div v-else-if="stats.length" class="stats-grid">
          <div
            v-for="stat in stats"
            :key="stat.id"
            class="stat-card"
          >
            <div class="stat-icon">
              {{ stat.icon }}
            </div>

            <div class="stat-details">
              <span class="stat-label">
                {{ stat.label }}
              </span>

              <h2 class="stat-value">
                {{ stat.value }}
              </h2>

              <p class="stat-sub">
                {{ stat.sub }}
              </p>
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

.menu-item.router-link-exact-active {
  background: var(--nav-active);

  color: #ffffff;

  box-shadow:
    0 8px 16px
    rgba(37, 99, 235, 0.18);
}

/* ==================================================
   ICON & TEXT
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

  margin-bottom: 24px;

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
   STATS GRID (RINGKAS & SEDERHANA)
================================================== */

.stats-grid {
  display: grid;

  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));

  gap: 20px;

  margin-bottom: 24px;
}

.stat-card {
  background: var(--surface);

  border: 1px solid var(--panel-border);

  border-radius: 16px;

  padding: 20px;

  display: flex;

  align-items: center;

  gap: 16px;

  backdrop-filter: blur(10px);

  box-shadow:
    0 4px 12px
    rgba(15, 23, 42, 0.03);

  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
  transform: translateY(-2px);

  box-shadow:
    0 8px 20px
    rgba(15, 23, 42, 0.06);
}

.stat-icon {
  width: 48px;

  height: 48px;

  border-radius: 12px;

  background: rgba(37, 99, 235, 0.1);

  display: grid;

  place-items: center;

  font-size: 22px;

  flex-shrink: 0;
}

.stat-details {
  min-width: 0;
}

.stat-label {
  font-size: 12px;

  font-weight: 600;

  color: var(--muted);

  display: block;
}

.stat-value {
  font-size: 24px;

  font-weight: 800;

  color: var(--text);

  margin: 2px 0;

  line-height: 1.2;
}

.stat-sub {
  font-size: 11px;

  color: var(--muted);

  margin: 0;

  white-space: nowrap;

  overflow: hidden;

  text-overflow: ellipsis;
}

/* ==================================================
   TABLE CARD
================================================== */

.table-card {
  background: var(--surface);

  border: 1px solid var(--panel-border);

  border-radius: 16px;

  padding: 20px;

  backdrop-filter: blur(10px);

  box-shadow:
    0 4px 12px
    rgba(15, 23, 42, 0.03);
}

.table-header {
  display: flex;

  justify-content: space-between;

  align-items: center;

  margin-bottom: 16px;
}

.table-header h3 {
  font-size: 16px;

  font-weight: 800;

  margin: 0;

  color: var(--text);
}

.table-header p {
  font-size: 12px;

  color: var(--muted);

  margin: 2px 0 0;
}

.view-all-btn {
  background: transparent;

  border: none;

  color: #2563eb;

  font-weight: 700;

  font-size: 13px;

  cursor: pointer;

  transition: color 0.2s ease;
}

.view-all-btn:hover {
  color: #1d4ed8;
}

.table-responsive {
  width: 100%;

  overflow-x: auto;
}

table {
  width: 100%;

  border-collapse: collapse;

  text-align: left;

  font-size: 13px;
}

th {
  padding: 12px 14px;

  color: var(--muted);

  font-weight: 700;

  font-size: 11px;

  text-transform: uppercase;

  border-bottom: 1px solid var(--panel-border);
}

td {
  padding: 14px;

  border-bottom: 1px solid var(--panel-border);

  color: var(--text);
}

tr:last-child td {
  border-bottom: none;
}

.font-bold {
  font-weight: 700;
}

/* ==================================================
   STATUS BADGES
================================================== */

.status-badge {
  padding: 4px 10px;

  border-radius: 20px;

  font-size: 11px;

  font-weight: 700;

  display: inline-block;
}

.status-success {
  background: rgba(34, 197, 94, 0.12);

  color: #16a34a;
}

.status-warning {
  background: rgba(245, 158, 11, 0.12);

  color: #d97706;
}

.status-danger {
  background: rgba(239, 68, 68, 0.12);

  color: #dc2626;
}

/* ==================================================
   STATES (LOADING, EMPTY, ERROR)
================================================== */

.loading-state,
.empty-state,
.error-state {
  text-align: center;

  padding: 48px 20px;

  background: var(--surface);

  border: 1px solid var(--panel-border);

  border-radius: 16px;
}

.empty-icon {
  font-size: 36px;

  margin-bottom: 12px;
}

.loading-spinner {
  width: 32px;

  height: 32px;

  border: 3px solid rgba(37, 99, 235, 0.2);

  border-top-color: #2563eb;

  border-radius: 50%;

  animation: spin 0.8s linear infinite;

  margin: 0 auto 12px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
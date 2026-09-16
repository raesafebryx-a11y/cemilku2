<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import Navbar from '../components/Navbar.vue'
import Footer from '../components/Footer.vue'
import api from '../services/api'
import { useAuthStore } from '../stores/auth'

const router = useRouter()
const auth = useAuthStore()

const orders = ref([])
const loading = ref(true)
const error = ref('')
const filterStatus = ref('all')

const isDarkMode = ref(false)

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

const fetchOrders = async () => {
  loading.value = true
  error.value = ''

  try {
    const response = await api.get('/orders')

    orders.value =
      response.data?.data ||
      response.data ||
      []
  } catch (err) {
    console.error('Gagal mengambil riwayat pesanan:', err)

    error.value =
      err.response?.data?.message ||
      'Gagal mengambil riwayat pesanan.'

    if (err.response?.status === 401) {
      await Swal.fire({
        icon: 'info',
        title: 'Login Diperlukan',
        text: 'Silakan login terlebih dahulu untuk melihat riwayat pesanan.',
        confirmButtonColor: '#2563eb'
      })

      router.push('/login')
    }
  } finally {
    loading.value = false
  }
}

const filteredOrders = computed(() => {
  if (filterStatus.value === 'all') {
    return orders.value
  }

  return orders.value.filter((order) => {
    return order.status === filterStatus.value
  })
})

const formatRupiah = (value) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}

const formatDate = (date) => {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric'
  })
}

const formatDateTime = (date) => {
  if (!date) {
    return '-'
  }

  return new Date(date).toLocaleString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getOrderNumber = (order) => {
  return (
    order.order_number ||
    order.order_code ||
    order.invoice ||
    `#ORDER-${order.id}`
  )
}

const getTotal = (order) => {
  return Number(
    order.total_amount ??
    order.total_price ??
    order.total ??
    0
  )
}

const getItemsCount = (order) => {
  if (!order.items) {
    return 0
  }

  return order.items.reduce((total, item) => {
    return total + Number(
      item.quantity ??
      item.qty ??
      0
    )
  }, 0)
}

const getStatusLabel = (status) => {
  const labels = {
    pending: 'Menunggu Pembayaran',
    paid: 'Sudah Dibayar',
    processing: 'Diproses',
    shipped: 'Dikirim',
    completed: 'Selesai',
    cancelled: 'Dibatalkan'
  }

  return labels[status] || status || 'Tidak Diketahui'
}

const getStatusClass = (status) => {
  return `status-${status || 'unknown'}`
}

const viewDetail = (order) => {
  router.push(`/orders/${order.id}`)
}

const goToProducts = () => {
  router.push('/products')
}

const goToCart = () => {
  router.push('/cart')
}

const totalOrders = computed(() => {
  return orders.value.length
})

const activeOrders = computed(() => {
  return orders.value.filter((order) => {
    return [
      'pending',
      'paid',
      'processing',
      'shipped'
    ].includes(order.status)
  }).length
})

const completedOrders = computed(() => {
  return orders.value.filter((order) => {
    return order.status === 'completed'
  }).length
})

onMounted(() => {
  const savedTheme = localStorage.getItem('theme')

  if (savedTheme === 'dark') {
    isDarkMode.value = true
  }

  window.addEventListener('cemilku-theme-change', handleThemeChange)

  if (!auth.isLoggedIn) {
    router.push('/login')
    return
  }

  fetchOrders()
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="orders-page" :class="{ 'dark-mode': isDarkMode }">

    <!-- CONTENT -->
    <main class="orders-container">

      <!-- HEADER -->
      <section class="page-header">
        <div>
          <span class="eyebrow">
            📦 PESANAN SAYA
          </span>

          <h1>
            Riwayat Pesanan
          </h1>

          <p>
            Lihat semua pesanan yang pernah kamu lakukan di Cemilku.
          </p>
        </div>

        <button
          class="shop-button"
          @click="goToProducts"
        >
          🛍️ Belanja Lagi
        </button>
      </section>

      <!-- STATISTICS -->
      <section class="stats-grid">

        <div class="stat-card">
          <div class="stat-icon blue">
            📦
          </div>

          <div>
            <span>Total Pesanan</span>
            <strong>{{ totalOrders }}</strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon orange">
            🚚
          </div>

          <div>
            <span>Sedang Diproses</span>
            <strong>{{ activeOrders }}</strong>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-icon green">
            ✅
          </div>

          <div>
            <span>Pesanan Selesai</span>
            <strong>{{ completedOrders }}</strong>
          </div>
        </div>

      </section>

      <!-- FILTER -->
      <section class="filter-card">

        <div class="filter-title">
          <h2>
            Pesanan Saya
          </h2>

          <span>
            {{ filteredOrders.length }} pesanan
          </span>
        </div>

        <div class="filter-buttons">

          <button
            :class="{ active: filterStatus === 'all' }"
            @click="filterStatus = 'all'"
          >
            Semua
          </button>

          <button
            :class="{ active: filterStatus === 'pending' }"
            @click="filterStatus = 'pending'"
          >
            Menunggu
          </button>

          <button
            :class="{ active: filterStatus === 'processing' }"
            @click="filterStatus = 'processing'"
          >
            Diproses
          </button>

          <button
            :class="{ active: filterStatus === 'shipped' }"
            @click="filterStatus = 'shipped'"
          >
            Dikirim
          </button>

          <button
            :class="{ active: filterStatus === 'completed' }"
            @click="filterStatus = 'completed'"
          >
            Selesai
          </button>

          <button
            :class="{ active: filterStatus === 'cancelled' }"
            @click="filterStatus = 'cancelled'"
          >
            Dibatalkan
          </button>

        </div>
      </section>

      <!-- LOADING -->
      <div
        v-if="loading"
        class="state-container"
      >
        <div class="spinner"></div>

        <h3>
          Memuat riwayat pesanan...
        </h3>

        <p>
          Tunggu sebentar ya.
        </p>
      </div>

      <!-- ERROR -->
      <div
        v-else-if="error"
        class="state-container error-state"
      >
        <div class="state-icon">
          ⚠️
        </div>

        <h3>
          Gagal Memuat Pesanan
        </h3>

        <p>
          {{ error }}
        </p>

        <button
          class="retry-button"
          @click="fetchOrders"
        >
          ↻ Coba Lagi
        </button>
      </div>

      <!-- EMPTY -->
      <div
        v-else-if="filteredOrders.length === 0"
        class="state-container"
      >
        <div class="state-icon">
          📦
        </div>

        <h3>
          Belum Ada Pesanan
        </h3>

        <p>
          Kamu belum memiliki pesanan pada kategori ini.
        </p>

        <button
          class="shop-button"
          @click="goToProducts"
        >
          🛍️ Mulai Belanja
        </button>
      </div>

      <!-- ORDERS -->
      <section
        v-else
        class="orders-list"
      >

        <article
          v-for="order in filteredOrders"
          :key="order.id"
          class="order-card"
        >

          <!-- ORDER HEADER -->
          <div class="order-header">

            <div class="order-info">
              <span class="order-label">
                Nomor Pesanan
              </span>

              <strong>
                {{ getOrderNumber(order) }}
              </strong>

              <small>
                {{ formatDateTime(order.created_at) }}
              </small>
            </div>

            <span
              class="status-badge"
              :class="getStatusClass(order.status)"
            >
              {{ getStatusLabel(order.status) }}
            </span>

          </div>

          <!-- ORDER BODY -->
          <div class="order-body">

            <div class="order-summary">

              <div class="summary-item">
                <span>Jumlah Produk</span>

                <strong>
                  {{ getItemsCount(order) }} item
                </strong>
              </div>

              <div class="summary-item">
                <span>Total Pembayaran</span>

                <strong class="total-price">
                  {{ formatRupiah(getTotal(order)) }}
                </strong>
              </div>

            </div>

            <!-- PREVIEW ITEMS -->
            <div
              v-if="order.items?.length"
              class="items-preview"
            >

              <div
                v-for="item in order.items.slice(0, 3)"
                :key="item.id"
                class="item-preview"
              >

                <div class="item-image">
                  <img
                    v-if="item.product?.image"
                    :src="item.product.image"
                    :alt="item.product?.name || 'Produk'"
                  >

                  <span v-else>
                    🍿
                  </span>
                </div>

                <div class="item-info">
                  <strong>
                    {{ item.product?.name || 'Produk' }}
                  </strong>

                  <span>
                    {{ item.quantity || 0 }} ×
                    {{ formatRupiah(
                      item.price ??
                      item.product?.price ??
                      0
                    ) }}
                  </span>
                </div>

              </div>

              <div
                v-if="order.items.length > 3"
                class="more-items"
              >
                +{{ order.items.length - 3 }} produk lainnya
              </div>

            </div>

          </div>

          <!-- ORDER FOOTER -->
          <div class="order-footer">

            <div class="order-date">
              📅
              {{ formatDate(order.created_at) }}
            </div>

            <div class="order-actions">

              <button
                class="detail-button"
                @click="viewDetail(order)"
              >
                👁️ Lihat Detail
              </button>

            </div>

          </div>

        </article>

      </section>

      <!-- CART SHORTCUT -->
      <section class="cart-shortcut">

        <div>
          <span class="shortcut-icon">
            🛒
          </span>

          <div>
            <strong>
              Masih ingin belanja?
            </strong>

            <p>
              Temukan camilan favoritmu di Cemilku.
            </p>
          </div>
        </div>

        <button
          class="outline-button"
          @click="goToCart"
        >
          Lihat Keranjang
        </button>

      </section>

    </main>

  </div>
</template>

<style scoped>
.orders-page {
  min-height: 100vh;
  background: linear-gradient(
    180deg,
    #f8fbff 0%,
    #eef5ff 100%
  );
  color: #0f172a;
  transition: background 0.3s ease, color 0.3s ease;
}

.dark-mode.orders-page {
  background: linear-gradient(
    180deg,
    #020817 0%,
    #0f172a 100%
  );
  color: #e2e8f0;
}

.dark-mode .page-header h1,
.dark-mode .page-header p,
.dark-mode .filter-title h2,
.dark-mode .filter-title span,
.dark-mode .stat-card strong,
.dark-mode .state-container h3,
.dark-mode .order-info strong,
.dark-mode .summary-item strong,
.dark-mode .item-info strong,
.dark-mode .cart-shortcut strong,
.dark-mode .state-container p,
.dark-mode .summary-item span,
.dark-mode .item-info span,
.dark-mode .order-date,
.dark-mode .cart-shortcut p {
  color: #f8fafc;
}

.dark-mode .stat-card,
.dark-mode .filter-card,
.dark-mode .state-container,
.dark-mode .order-card,
.dark-mode .cart-shortcut {
  background: rgba(15, 23, 42, 0.82);
  border-color: #334155;
}

.dark-mode .stat-card span,
.dark-mode .filter-title span,
.dark-mode .state-container p,
.dark-mode .summary-item span,
.dark-mode .order-info small,
.dark-mode .order-date,
.dark-mode .cart-shortcut p,
.dark-mode .item-info span,
.dark-mode .more-items,
.dark-mode .page-header p {
  color: #94a3b8;
}

.dark-mode .status-badge,
.dark-mode .detail-button,
.dark-mode .filter-buttons button,
.dark-mode .outline-button {
  border-color: #334155;
}

.dark-mode .filter-buttons button {
  background: #0f172a;
  color: #cbd5e1;
}

.dark-mode .filter-buttons button.active,
.dark-mode .shop-button,
.dark-mode .retry-button,
.dark-mode .outline-button:hover {
  background: #2563eb;
  color: #ffffff;
}

.dark-mode .order-header,
.dark-mode .order-footer,
.dark-mode .item-preview,
.dark-mode .search-button {
  border-color: #334155;
}

.dark-mode .item-preview {
  background: #111827;
}

.dark-mode .detail-button {
  background: rgba(30, 41, 59, 0.9);
  color: #bfdbfe;
}

.dark-mode .order-footer {
  background: rgba(15, 23, 42, 0.7);
}

.dark-mode .spinner {
  border-color: #1e293b;
  border-top-color: #60a5fa;
}

.dark-mode .item-image {
  background: #0f172a;
}

/* ==========================================
   CONTAINER
========================================== */

.orders-container {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
  padding: 40px 20px 70px;
}

/* ==========================================
   HEADER
========================================== */

.page-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 25px;
  margin-bottom: 28px;
}

.eyebrow {
  display: inline-block;
  margin-bottom: 8px;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  letter-spacing: 0.08em;
}

.page-header h1 {
  margin: 0;
  color: #0f172a;
  font-size: 34px;
  line-height: 1.15;
  font-weight: 800;
}

.page-header p {
  margin: 9px 0 0;
  color: #64748b;
  font-size: 14px;
}

/* ==========================================
   BUTTON
========================================== */

.shop-button {
  border: none;
  border-radius: 11px;
  padding: 12px 18px;
  background: linear-gradient(
    135deg,
    #2563eb,
    #3b82f6
  );
  color: #ffffff;
  font-size: 13px;
  font-weight: 800;
  cursor: pointer;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18);
  transition: all 0.2s ease;
}

.shop-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 25px rgba(37, 99, 235, 0.25);
}

/* ==========================================
   STATS
========================================== */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 25px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 13px;
  padding: 18px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04);
}

.stat-icon {
  width: 43px;
  height: 43px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border-radius: 12px;
  font-size: 19px;
}

.stat-icon.blue {
  background: #eff6ff;
}

.stat-icon.orange {
  background: #fff7ed;
}

.stat-icon.green {
  background: #f0fdf4;
}

.stat-card span {
  display: block;
  margin-bottom: 3px;
  color: #64748b;
  font-size: 12px;
}

.stat-card strong {
  color: #0f172a;
  font-size: 20px;
}

/* ==========================================
   FILTER
========================================== */

.filter-card {
  margin-bottom: 20px;
  padding: 18px 20px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid #e2e8f0;
  border-radius: 16px;
}

.filter-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
}

.filter-title h2 {
  margin: 0;
  font-size: 17px;
}

.filter-title span {
  color: #64748b;
  font-size: 12px;
}

.filter-buttons {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.filter-buttons button {
  border: 1px solid #dbe3ef;
  border-radius: 9px;
  padding: 8px 12px;
  background: #ffffff;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
}

.filter-buttons button:hover {
  border-color: #93c5fd;
  color: #2563eb;
  background: #eff6ff;
}

.filter-buttons button.active {
  border-color: #2563eb;
  background: #2563eb;
  color: #ffffff;
}

/* ==========================================
   STATE
========================================== */

.state-container {
  min-height: 300px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 30px;
  background: rgba(255, 255, 255, 0.9);
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  text-align: center;
}

.state-container h3 {
  margin: 10px 0 5px;
  font-size: 18px;
}

.state-container p {
  margin: 0 0 18px;
  color: #64748b;
  font-size: 13px;
}

.state-icon {
  font-size: 45px;
}

.spinner {
  width: 38px;
  height: 38px;
  border: 4px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.retry-button {
  border: none;
  border-radius: 9px;
  padding: 10px 16px;
  background: #2563eb;
  color: #ffffff;
  font-weight: 700;
  cursor: pointer;
}

/* ==========================================
   ORDER LIST
========================================== */

.orders-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.order-card {
  overflow: hidden;
  background: rgba(255, 255, 255, 0.94);
  border: 1px solid #e2e8f0;
  border-radius: 18px;
  box-shadow: 0 8px 25px rgba(15, 23, 42, 0.04);
}

/* ==========================================
   ORDER HEADER
========================================== */

.order-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  padding: 18px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.order-info {
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.order-label {
  color: #94a3b8;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
}

.order-info strong {
  color: #0f172a;
  font-size: 14px;
}

.order-info small {
  color: #64748b;
  font-size: 11px;
}

.status-badge {
  padding: 7px 11px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 800;
  white-space: nowrap;
}

.status-pending {
  background: #fff7ed;
  color: #c2410c;
}

.status-paid {
  background: #eff6ff;
  color: #2563eb;
}

.status-processing {
  background: #eff6ff;
  color: #1d4ed8;
}

.status-shipped {
  background: #f0fdf4;
  color: #15803d;
}

.status-completed {
  background: #dcfce7;
  color: #166534;
}

.status-cancelled {
  background: #fef2f2;
  color: #dc2626;
}

/* ==========================================
   ORDER BODY
========================================== */

.order-body {
  padding: 18px 20px;
}

.order-summary {
  display: flex;
  align-items: center;
  gap: 40px;
  margin-bottom: 15px;
}

.summary-item {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.summary-item span {
  color: #64748b;
  font-size: 11px;
}

.summary-item strong {
  color: #0f172a;
  font-size: 13px;
}

.summary-item .total-price {
  color: #2563eb;
  font-size: 16px;
}

/* ==========================================
   ITEMS
========================================== */

.items-preview {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.item-preview {
  min-width: 190px;
  max-width: 240px;
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px;
  background: #f8fafc;
  border: 1px solid #eef2f7;
  border-radius: 10px;
}

.item-image {
  width: 38px;
  height: 38px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  overflow: hidden;
  background: #eff6ff;
  border-radius: 8px;
  font-size: 17px;
}

.item-image img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.item-info {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-info strong {
  overflow: hidden;
  color: #334155;
  font-size: 11px;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.item-info span {
  color: #64748b;
  font-size: 10px;
}

.more-items {
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
}

/* ==========================================
   ORDER FOOTER
========================================== */

.order-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 15px;
  padding: 13px 20px;
  background: #f8fafc;
  border-top: 1px solid #e2e8f0;
}

.order-date {
  color: #64748b;
  font-size: 11px;
}

.detail-button {
  border: 1px solid #bfdbfe;
  border-radius: 9px;
  padding: 8px 13px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.detail-button:hover {
  background: #dbeafe;
}

/* ==========================================
   CART SHORTCUT
========================================== */

.cart-shortcut {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-top: 20px;
  padding: 18px 20px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  border-radius: 16px;
}

.cart-shortcut > div {
  display: flex;
  align-items: center;
  gap: 12px;
}

.shortcut-icon {
  font-size: 25px;
}

.cart-shortcut strong {
  color: #0f172a;
  font-size: 13px;
}

.cart-shortcut p {
  margin: 3px 0 0;
  color: #64748b;
  font-size: 11px;
}

.outline-button {
  border: 1px solid #2563eb;
  border-radius: 9px;
  padding: 9px 14px;
  background: #ffffff;
  color: #2563eb;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
}

.outline-button:hover {
  background: #2563eb;
  color: #ffffff;
}

/* ==========================================
   RESPONSIVE
========================================== */

@media (max-width: 800px) {
  .page-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .shop-button {
    width: 100%;
  }

  .order-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .order-summary {
    gap: 25px;
  }
}

@media (max-width: 600px) {
  .orders-container {
    padding: 25px 14px 50px;
  }

  .page-header h1 {
    font-size: 27px;
  }

  .filter-card {
    padding: 15px;
  }

  .filter-title {
    align-items: flex-start;
    flex-direction: column;
    gap: 5px;
  }

  .order-header,
  .order-body,
  .order-footer {
    padding-left: 15px;
    padding-right: 15px;
  }

  .order-summary {
    align-items: flex-start;
    flex-direction: column;
    gap: 12px;
  }

  .items-preview {
    flex-direction: column;
    align-items: stretch;
  }

  .item-preview {
    max-width: none;
  }

  .order-footer {
    align-items: flex-start;
    flex-direction: column;
  }

  .detail-button {
    width: 100%;
  }

  .cart-shortcut {
    align-items: flex-start;
    flex-direction: column;
  }

  .outline-button {
    width: 100%;
  }
}
</style>
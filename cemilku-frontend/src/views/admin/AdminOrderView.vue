<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isNavbarDark = ref(false)
const loading = ref(true)
const error = ref('')
const search = ref('')
const filterStatus = ref('')
const showDetail = ref(false)
const selectedOrder = ref(null)
const updatingStatus = ref(false)
const orders = ref([])

const statusOptions = [
  { key: 'pending', label: 'Menunggu' },
  { key: 'processing', label: 'Diproses' },
  { key: 'shipped', label: 'Dikirim' },
  { key: 'completed', label: 'Selesai' },
  { key: 'cancelled', label: 'Dibatalkan' }
]

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
  localStorage.setItem('admin-theme-mode', isNavbarDark.value ? 'dark' : 'light')

  window.dispatchEvent(
    new CustomEvent('admin-theme-change', {
      detail: { dark: isNavbarDark.value }
    })
  )
}

const logoCemilku = ref('/images/cemilku-logo.png')

const handleLogoError = (event) => {
  event.target.src = 'https://cdn-icons-png.flaticon.com/512/2553/2553691.png'
}

const filteredOrders = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  return orders.value.filter((order) => {
    const matchesSearch =
      !keyword ||
      order.order_number?.toLowerCase().includes(keyword) ||
      order.user?.name?.toLowerCase().includes(keyword) ||
      order.user?.email?.toLowerCase().includes(keyword) ||
      order.customer_name?.toLowerCase().includes(keyword)

    const matchesStatus =
      !filterStatus.value ||
      order.status === filterStatus.value

    return matchesSearch && matchesStatus
  })
})

const totalOrders = computed(() => orders.value.length)
const pendingOrders = computed(() => orders.value.filter((order) => order.status === 'pending').length)
const processingOrders = computed(() => orders.value.filter((order) => order.status === 'processing').length)
const completedOrders = computed(() => orders.value.filter((order) => order.status === 'completed').length)

const checkAdmin = () => {
  if (!auth.isLoggedIn) {
    router.replace('/login')
    return false
  }

  if (auth.user && auth.userRole !== 'admin') {
    router.replace('/')
    return false
  }

  return true
}

const handleLogout = async () => {
  const result = await Swal.fire({
    icon: 'question',
    title: 'Keluar dari Admin?',
    text: 'Kamu akan keluar dari dashboard.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Keluar',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626'
  })

  if (!result.isConfirmed) return

  await auth.logout()
  router.push('/login')
}

const goTo = (path) => {
  router.push(path)
}

// ==========================================
// FIX ALUR FETCH ORDERS ADMIN
// ==========================================
const fetchOrders = async () => {
  loading.value = true
  error.value = ''

  try {
    // Coba panggil endpoint admin terlebih dahulu, fallback ke /orders biasa
    let response
    try {
      response = await api.get('/admin/orders')
    } catch (e) {
      response = await api.get('/orders')
    }

    const payload = response.data

    // Ekstraksi data dengan aman dari berbagai bentuk response API
    let rawData = []
    if (Array.isArray(payload)) {
      rawData = payload
    } else if (Array.isArray(payload.data)) {
      rawData = payload.data
    } else if (payload.data && Array.isArray(payload.data.data)) {
      rawData = payload.data.data
    } else if (Array.isArray(payload.orders)) {
      rawData = payload.orders
    } else if (payload.orders && Array.isArray(payload.orders.data)) {
      rawData = payload.orders.data
    }

    orders.value = rawData
  } catch (err) {
    console.error('Gagal mengambil order:', err)
    error.value = err.response?.data?.message || 'Gagal mengambil data pesanan.'

    if (err.response?.status === 403) {
      await Swal.fire({
        icon: 'error',
        title: 'Akses Ditolak',
        text: 'Akun kamu tidak memiliki akses admin.',
        confirmButtonColor: '#2563eb'
      })
      router.push('/')
    }
  } finally {
    loading.value = false
  }
}

const openDetail = async (order) => {
  try {
    let response
    try {
      response = await api.get(`/admin/orders/${order.id}`)
    } catch (e) {
      response = await api.get(`/orders/${order.id}`)
    }

    const resData = response.data
    selectedOrder.value = resData.data || resData.order || resData
    showDetail.value = true
  } catch (err) {
    console.error('Gagal mengambil detail order:', err)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: err.response?.data?.message || 'Gagal mengambil detail pesanan.',
      confirmButtonColor: '#2563eb'
    })
  }
}

const closeDetail = () => {
  showDetail.value = false
  selectedOrder.value = null
}

const updateStatus = async (order, newStatus) => {
  if (!newStatus || newStatus === order.status) return

  const statusLabel = statusOptions.find((item) => item.key === newStatus)?.label || newStatus

  const result = await Swal.fire({
    icon: 'question',
    title: 'Ubah Status Pesanan?',
    text: `Status pesanan akan diubah menjadi "${statusLabel}".`,
    showCancelButton: true,
    confirmButtonText: 'Ya, ubah',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb'
  })

  if (!result.isConfirmed) return

  updatingStatus.value = true

  try {
    let response
    try {
      response = await api.put(`/admin/orders/${order.id}/status`, { status: newStatus })
    } catch (e) {
      response = await api.patch(`/admin/orders/${order.id}`, { status: newStatus })
    }

    const resData = response.data
    const updatedOrder = resData.order || resData.data || resData

    const index = orders.value.findIndex((item) => item.id === order.id)
    if (index !== -1) {
      orders.value[index] = { ...orders.value[index], ...updatedOrder, status: newStatus }
    }

    if (selectedOrder.value && selectedOrder.value.id === order.id) {
      selectedOrder.value = { ...selectedOrder.value, ...updatedOrder, status: newStatus }
    }

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Status pesanan berhasil diperbarui.',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (err) {
    console.error('Gagal mengubah status:', err)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: err.response?.data?.message || 'Status pesanan gagal diperbarui.',
      confirmButtonColor: '#2563eb'
    })
    await fetchOrders()
  } finally {
    updatingStatus.value = false
  }
}

const updatingPayment = ref(false)

const formatRupiah = (value) => {
  if (value === null || value === undefined) return '-'

  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  }).format(Number(value || 0))
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'medium',
    timeStyle: 'short'
  }).format(new Date(date))
}

const getStorageUrl = (value) => {
  if (!value) return ''
  if (/^https?:\/\//i.test(value)) return value
  const normalized = value
    .replace(/^\/+/, '')
    .replace(/^public\//i, '')
    .replace(/^storage\//i, '')
  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  const backendUrl = apiUrl.replace(/\/api\/?$/, '')
  return `${backendUrl}/storage/${normalized}`
}

const getStatusLabel = (status) => statusOptions.find((item) => item.key === status)?.label || status
const getStatusClass = (status) => `status-${status}`

const getPaymentStatusLabel = (status) => {
  const labels = {
    pending: 'Menunggu Pembayaran',
    waiting_verification: 'Menunggu Verifikasi',
    paid: 'Sudah Dibayar',
    failed: 'Gagal',
    expired: 'Kadaluarsa'
  }

  return labels[status] || status || '-'
}

const getPaymentClass = (status) => {
  if (status === 'paid') return 'payment-paid'
  if (status === 'waiting_verification') return 'payment-waiting'
  if (status === 'failed') return 'payment-failed'
  return 'payment-pending'
}

const updatePaymentStatus = async (order, newStatus) => {
  if (!order || !newStatus) return

  const result = await Swal.fire({
    icon: 'question',
    title: 'Ubah Status Pembayaran?',
    text: `Status pembayaran akan diubah menjadi "${getPaymentStatusLabel(newStatus)}".`,
    showCancelButton: true,
    confirmButtonText: 'Ya, ubah',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb'
  })

  if (!result.isConfirmed) return

  updatingPayment.value = true
  try {
    const response = await api.put(`/admin/orders/${order.id}/payment-status`, { status: newStatus })
    const resData = response.data
    const updatedOrder = resData.order || resData.data || resData

    const index = orders.value.findIndex((item) => item.id === order.id)
    if (index !== -1) {
      orders.value[index] = { ...orders.value[index], ...updatedOrder }
    }

    if (selectedOrder.value && selectedOrder.value.id === order.id) {
      selectedOrder.value = { ...selectedOrder.value, ...updatedOrder }
    }

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Status pembayaran berhasil diperbarui.',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (err) {
    console.error('Gagal mengubah status pembayaran:', err)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: err.response?.data?.message || 'Status pembayaran gagal diperbarui.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    updatingPayment.value = false
  }
}

onMounted(async () => {
  loadTheme()
  window.addEventListener('admin-theme-change', handleThemeChange)

  if (!auth.isLoggedIn) {
    router.replace('/login')
    return
  }

  if (!auth.user) {
    try {
      if (typeof auth.fetchUser === 'function') {
        await auth.fetchUser()
      } else if (typeof auth.fetchMe === 'function') {
        await auth.fetchMe()
      }
    } catch (error) {
      console.error('Gagal mengambil data user:', error)
      router.replace('/login')
      return
    }
  }

  if (!checkAdmin()) {
    return
  }

  await fetchOrders()
})

onUnmounted(() => {
  window.removeEventListener('admin-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="dashboard-layout" :class="{ 'navbar-dark': isNavbarDark }">
    <aside class="sidebar">
      <div class="sidebar-brand" @click="router.push('/')">
        <div class="brand-mark">
          <img :src="logoCemilku" alt="Logo Cemilku" class="cemilku-logo-img" draggable="false" @error="handleLogoError" />
        </div>

        <div class="brand-info">
          <span class="brand-text">Cemilku</span>
          <small>SNACK STORE</small>
        </div>
      </div>

     <nav class="sidebar-menu">
  <div class="menu-category">MAIN</div>

  <button class="menu-item" @click="goTo('/admin')">
    <span class="menu-icon">📊</span>
    <span class="menu-text">Dashboard</span>
  </button>

  <div class="menu-category">KELOLA TOKO</div>

  <button class="menu-item" @click="goTo('/admin/produk')">
    <span class="menu-icon">🍿</span>
    <span class="menu-text">Produk</span>
  </button>

  <button class="menu-item" @click="goTo('/admin/kategori')">
    <span class="menu-icon">🏷️</span>
    <span class="menu-text">Kategori</span>
  </button>

  <button class="menu-item router-link-exact-active">
    <span class="menu-icon">📑</span>
    <span class="menu-text">Order</span>
  </button>
  
  <button class="menu-item" @click="goTo('/admin/kontak')">
    <span class="menu-icon">💬</span>
    <span class="menu-text">Pesan Kontak</span>
  </button>

  <div class="menu-category">SISTEM</div>

  <button class="menu-item" @click="goTo('/admin/pengaturan')">
    <span class="menu-icon">⚙️</span>
    <span class="menu-text">Pengaturan</span>
  </button>

  <button class="menu-item" @click="goTo('/admin/profile')">
    <span class="menu-icon">👤</span>
    <span class="menu-text">Profile</span>
  </button>

  <a href="#" class="menu-item logout" @click.prevent="handleLogout">
    <span class="menu-icon">🚪</span>
    <span class="menu-text">Keluar</span>
  </a>
</nav>
    </aside>

    <div class="main-wrapper">
      <header class="topbar">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input v-model="search" type="text" placeholder="Cari order atau pelanggan..." />
        </div>

        <div class="topbar-right">
          <button class="theme-toggle" aria-label="Toggle navbar theme" @click="toggleNavbarTheme">
            {{ isNavbarDark ? '☀️' : '🌙' }}
          </button>

          <button class="icon-btn" aria-label="Notifikasi">🔔</button>

          <div class="user-info">
            <div class="user-name">{{ auth.username || auth.user?.name || 'Admin' }}</div>
            <div class="user-role">Administrator</div>
          </div>

          <div class="user-avatar">
            {{ (auth.username || auth.user?.name || 'A').charAt(0).toUpperCase() }}
          </div>
        </div>
      </header>

      <main class="content-body">
        <div class="page-header">
          <div>
            <span class="section-kicker">Manajemen Pesanan</span>
            <h1>Order</h1>
            <p>Kelola dan pantau semua pesanan pelanggan Cemilku.</p>
          </div>

          <button class="btn-primary-action" @click="fetchOrders" :disabled="loading">
            <span class="btn-icon">↻</span>
            {{ loading ? 'Memuat...' : 'Refresh' }}
          </button>
        </div>

        <div v-if="error" class="error-box">
          <span class="error-icon">⚠️</span>
          <div>
            <strong>Terjadi kesalahan</strong>
            <p>{{ error }}</p>
          </div>
          <button @click="fetchOrders">Coba Lagi</button>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Total Order</span>
              <div class="stat-icon-wrapper blue">📦</div>
            </div>
            <div class="stat-value">{{ totalOrders }}</div>
            <div class="stat-sub">Pesanan masuk</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Menunggu</span>
              <div class="stat-icon-wrapper orange">⏳</div>
            </div>
            <div class="stat-value">{{ pendingOrders }}</div>
            <div class="stat-sub">Perlu diproses</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Diproses</span>
              <div class="stat-icon-wrapper purple">🔄</div>
            </div>
            <div class="stat-value">{{ processingOrders }}</div>
            <div class="stat-sub">Sedang berjalan</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Selesai</span>
              <div class="stat-icon-wrapper green">✓</div>
            </div>
            <div class="stat-value">{{ completedOrders }}</div>
            <div class="stat-sub">Order selesai</div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-header">
            <div>
              <h3>Daftar Order</h3>
              <p>{{ filteredOrders.length }} pesanan tersedia</p>
            </div>

            <div class="toolbar-actions">
              <select v-model="filterStatus">
                <option value="">Semua Status</option>
                <option v-for="status in statusOptions" :key="status.key" :value="status.key">{{ status.label }}</option>
              </select>
            </div>
          </div>

          <div v-if="loading" class="loading-card">
            <div class="spinner"></div>
            <p>Memuat data pesanan...</p>
          </div>

          <div v-else-if="filteredOrders.length === 0" class="empty-card">
            <div class="empty-icon">📦</div>
            <h3>{{ search || filterStatus ? 'Pesanan Tidak Ditemukan' : 'Belum Ada Pesanan' }}</h3>
            <p>{{ search || filterStatus ? 'Coba ubah kata pencarian atau filter.' : 'Pesanan pelanggan akan muncul di sini.' }}</p>
          </div>

          <div v-else class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>ORDER</th>
                  <th>PELANGGAN</th>
                  <th>PRODUK</th>
                  <th>TOTAL</th>
                  <th>PEMBAYARAN</th>
                  <th>STATUS</th>
                  <th>TANGGAL</th>
                  <th>AKSI</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="order in filteredOrders" :key="order.id">
                  <td>
                    <div class="order-number">{{ order.order_number || ('#ORD-' + order.id) }}</div>
                    <small class="order-id">#{{ order.id }}</small>
                  </td>

                  <td>
                    <div class="customer-cell">
                      <div class="customer-avatar">{{ (order.user?.name || order.customer_name || '?').charAt(0).toUpperCase() }}</div>
                      <div>
                        <strong>{{ order.user?.name || order.customer_name || '-' }}</strong>
                        <small>{{ order.user?.email || order.customer_email || '-' }}</small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="product-meta">
                      <span>{{ order.items?.length || order.order_items?.length || 0 }} produk</span>
                    </div>
                    <small class="quantity">{{ (order.items || order.order_items || []).reduce((total, item) => total + Number(item.quantity || 0), 0) }} item</small>
                  </td>

                  <td>
                    <strong class="total-price">{{ formatRupiah(order.total || order.total_price || order.grand_total) }}</strong>
                  </td>

                  <td>
                    <span v-if="order.payment || order.payment_status" class="payment-badge" :class="getPaymentClass(order.payment?.status || order.payment_status)">{{ getPaymentStatusLabel(order.payment?.status || order.payment_status) }}</span>
                    <span v-else class="muted-text">Belum ada</span>
                  </td>

                  <td>
                    <select :value="order.status" class="status-select" :class="getStatusClass(order.status)" :disabled="updatingStatus" @change="updateStatus(order, $event.target.value)">
                      <option v-for="status in statusOptions" :key="status.key" :value="status.key">{{ status.label }}</option>
                    </select>
                  </td>

                  <td>
                    <span class="date-text">{{ formatDate(order.created_at) }}</span>
                  </td>

                  <td>
                    <button class="detail-button" @click="openDetail(order)">Detail</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>

    <div v-if="showDetail && selectedOrder" class="modal-overlay" @click.self="closeDetail">
      <div class="detail-modal">
        <div class="modal-header">
          <div>
            <span class="modal-label">Detail Pesanan</span>
            <h2>{{ selectedOrder.order_number || ('#ORD-' + selectedOrder.id) }}</h2>
          </div>
          <button class="close-button" @click="closeDetail">×</button>
        </div>

        <div class="modal-content">
          <div class="detail-status">
            <span>Status Pesanan</span>
            <span class="status-badge" :class="getStatusClass(selectedOrder.status)">{{ getStatusLabel(selectedOrder.status) }}</span>
          </div>

          <div class="detail-section">
            <h3>👤 Pelanggan</h3>
            <div class="info-box">
              <strong>{{ selectedOrder.user?.name || selectedOrder.customer_name || '-' }}</strong>
              <span>{{ selectedOrder.user?.email || selectedOrder.customer_email || '-' }}</span>
            </div>
          </div>

          <div class="detail-section">
            <h3>📍 Alamat Pengiriman</h3>
            <div v-if="selectedOrder.address" class="info-box">
              <strong>{{ selectedOrder.address.recipient_name || selectedOrder.address.nama_penerima || '-' }}</strong>
              <span>{{ selectedOrder.address.phone || selectedOrder.address.telepon || '-' }}</span>
              <p>{{ selectedOrder.address.full_address || selectedOrder.address.alamat_lengkap || '-' }}</p>
              <span>{{ selectedOrder.address.city || selectedOrder.address.kota || '-' }} - {{ selectedOrder.address.postal_code || selectedOrder.address.kode_pos || '-' }}</span>
            </div>
            <div v-else class="no-data">Alamat tidak tersedia.</div>
          </div>

          <div class="detail-section">
            <div class="section-title-row">
              <h3>🛒 Produk Pesanan</h3>
              <span class="items-count-badge">{{ (selectedOrder.items || selectedOrder.order_items || []).length }} Produk</span>
            </div>
            <div class="items-list">
              <div v-for="item in (selectedOrder.items || selectedOrder.order_items || [])" :key="item.id" class="order-item">
                <div class="item-product-detail">
                  <img
                    v-if="item.product?.image"
                    :src="getStorageUrl(item.product.image)"
                    :alt="item.product_name || item.product?.name"
                    class="item-thumbnail"
                  />
                  <div v-else class="item-placeholder">🍿</div>
                  <div class="item-info">
                    <strong>{{ item.product_name || item.product?.name || item.nama_produk || '-' }}</strong>
                    <span>{{ item.quantity }} pcs × {{ formatRupiah(item.price || item.harga) }}</span>
                  </div>
                </div>
                <strong class="item-subtotal">{{ formatRupiah(item.subtotal || (item.quantity * (item.price || item.harga))) }}</strong>
              </div>
            </div>
          </div>

          <div class="summary-box">
            <div>
              <span>Subtotal</span>
              <strong>{{ formatRupiah(selectedOrder.subtotal) }}</strong>
            </div>
            <div>
              <span>Ongkir</span>
              <strong>{{ formatRupiah(selectedOrder.shipping_cost || selectedOrder.ongkir) }}</strong>
            </div>
            <div class="grand-total">
              <span>Total</span>
              <strong>{{ formatRupiah(selectedOrder.total || selectedOrder.total_price || selectedOrder.grand_total) }}</strong>
            </div>
          </div>

          <div class="detail-section">
            <h3>💳 Pembayaran</h3>
            <div v-if="selectedOrder.payment" class="payment-detail">
              <div>
                <span>Metode</span>
                <strong>{{ selectedOrder.payment.method || selectedOrder.payment.payment_type || '-' }}</strong>
              </div>
              <div>
                <span>Status</span>
                <span class="payment-badge" :class="getPaymentClass(selectedOrder.payment.status)">
                  {{ getPaymentStatusLabel(selectedOrder.payment.status) }}
                </span>
              </div>
              <div>
                <span>Jumlah</span>
                <strong>{{ formatRupiah(selectedOrder.payment.amount || selectedOrder.payment.gross_amount) }}</strong>
              </div>

              <!-- Quick action verify payment for admin -->
              <div v-if="selectedOrder.payment.status !== 'paid'" class="payment-action-row">
                <button
                  class="verify-payment-btn"
                  :disabled="updatingPayment"
                  @click="updatePaymentStatus(selectedOrder, 'paid')"
                >
                  {{ updatingPayment ? 'Memproses...' : '✓ Verifikasi Pembayaran (Tandai Lunas)' }}
                </button>
              </div>

              <div v-if="selectedOrder.payment.proof_image" class="proof-wrapper">
                <div class="proof-header">
                  <span>Bukti Pembayaran</span>
                  <a
                    :href="selectedOrder.payment.proof_image_url || getStorageUrl(selectedOrder.payment.proof_image)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="proof-link"
                  >
                    🔍 Buka Ukuran Penuh
                  </a>
                </div>
                <a
                  :href="selectedOrder.payment.proof_image_url || getStorageUrl(selectedOrder.payment.proof_image)"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="proof-img-container"
                  title="Klik untuk membuka ukuran penuh"
                >
                  <img
                    :src="selectedOrder.payment.proof_image_url || getStorageUrl(selectedOrder.payment.proof_image)"
                    alt="Bukti pembayaran"
                  />
                </a>
              </div>
            </div>
            <div v-else class="no-data">Data pembayaran belum tersedia.</div>
          </div>
        </div>

        <div class="modal-footer">
          <button class="secondary-button" @click="closeDetail">Tutup</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.dashboard-layout {
  --page-bg: linear-gradient(180deg, #f8fbff 0%, #eef5ff 100%);
  --sidebar-bg: rgba(255, 255, 255, 0.8);
  --sidebar-border: rgba(226, 232, 240, 0.9);
  --topbar-bg: rgba(255, 255, 255, 0.75);
  --topbar-border: rgba(226, 232, 240, 0.9);
  --surface: rgba(255, 255, 255, 0.9);
  --text: #0f172a;
  --muted: #64748b;
  --nav-text: #475569;
  --nav-hover: #eff6ff;
  --nav-active: linear-gradient(135deg, #2563eb, #3b82f6);
  --panel-border: rgba(226, 232, 240, 0.9);

  min-height: 100vh;
  display: flex;
  background: var(--page-bg);
  color: var(--text);
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

.dashboard-layout.navbar-dark {
  --page-bg: linear-gradient(180deg, #0f172a 0%, #111827 100%);
  --sidebar-bg: rgba(15, 23, 42, 0.85);
  --sidebar-border: rgba(51, 65, 85, 0.9);
  --topbar-bg: rgba(15, 23, 42, 0.8);
  --topbar-border: rgba(51, 65, 85, 0.9);
  --surface: rgba(15, 23, 42, 0.8);
  --text: #f8fafc;
  --muted: #cbd5e1;
  --nav-text: #cbd5e1;
  --nav-hover: rgba(59, 130, 246, 0.12);
  --nav-active: linear-gradient(135deg, #1d4ed8, #3b82f6);
  --panel-border: rgba(51, 65, 85, 0.9);
}

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

.sidebar-brand:hover { opacity: 0.9; }

.brand-mark {
  width: 38px;
  height: 38px;
  border-radius: 10px;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 3px;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.18);
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

.sidebar-menu {
  padding: 16px 12px 20px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  overflow-y: auto;
}

.menu-category {
  font-size: 10px;
  font-weight: 700;
  color: var(--muted);
  padding: 12px 12px 5px;
  letter-spacing: 0.6px;
  text-transform: uppercase;
}

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
  transition: background 0.2s ease, color 0.2s ease;
  box-sizing: border-box;
}

.menu-item:hover {
  background: var(--nav-hover);
  color: var(--text);
}

.menu-item.router-link-active,
.menu-item.router-link-exact-active {
  background: var(--nav-active);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
}

.menu-item.logout {
  color: #dc2626;
  margin-top: 4px;
}

.menu-item.logout:hover {
  background: rgba(239, 68, 68, 0.08);
  color: #b91c1c;
}

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

.main-wrapper {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
  margin-left: 250px;
}

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

.search-box {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(148, 163, 184, 0.06);
  border: 1px solid var(--panel-border);
  padding: 8px 14px;
  border-radius: 10px;
  width: 320px;
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

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.theme-toggle,
.icon-btn {
  background: rgba(148, 163, 184, 0.08);
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
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #ffffff;
  display: grid;
  place-items: center;
  font-weight: 700;
}

.content-body {
  padding: 0 28px 28px;
}

.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 16px;
  margin-top: 24px;
  margin-bottom: 18px;
}

.section-kicker {
  display: block;
  font-size: 12px;
  color: #2563eb;
  font-weight: 700;
  margin-bottom: 8px;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.page-header h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 800;
  color: var(--text);
}

.page-header p {
  margin: 8px 0 0;
  color: var(--muted);
  font-size: 13px;
}

.btn-primary-action {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
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
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.22);
  transition: all 0.3s ease;
}

.btn-primary-action:hover { transform: translateY(-2px); }
.btn-primary-action:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-icon { font-size: 18px; }

.error-box {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #fef2f2;
  border: 1px solid #fecaca;
  color: #991b1b;
  border-radius: 12px;
  padding: 14px 16px;
  margin-bottom: 18px;
}

.error-box strong {
  display: block;
  margin-bottom: 3px;
}

.error-box p {
  margin: 0;
  font-size: 13px;
}

.error-box button {
  margin-left: auto;
  border: none;
  background: #dc2626;
  color: white;
  padding: 8px 12px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
}

.error-icon {
  font-size: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
  margin-bottom: 18px;
}

.stat-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 16px;
  padding: 18px 20px;
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.04);
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.stat-label {
  color: var(--muted);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.stat-icon-wrapper {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  font-size: 18px;
}

.stat-icon-wrapper.blue { background: rgba(37, 99, 235, 0.08); }
.stat-icon-wrapper.orange { background: rgba(249, 115, 22, 0.1); }
.stat-icon-wrapper.purple { background: rgba(124, 58, 237, 0.1); }
.stat-icon-wrapper.green { background: rgba(34, 197, 94, 0.12); }

.stat-value {
  font-size: 30px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.1;
}

.stat-sub {
  margin-top: 6px;
  font-size: 11px;
  color: var(--muted);
}

.table-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 14px;
  overflow: hidden;
}

.table-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 20px;
  border-bottom: 1px solid var(--panel-border);
  gap: 14px;
}

.table-header h3 {
  margin: 0;
  color: var(--text);
  font-size: 16px;
  font-weight: 800;
}

.table-header p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 11px;
}

.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.toolbar-actions select {
  min-width: 180px;
  padding: 10px 12px;
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  background: rgba(148, 163, 184, 0.04);
  color: var(--text);
  outline: none;
}

.loading-card,
.empty-card {
  min-height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  text-align: center;
}

.spinner {
  width: 35px;
  height: 35px;
  border: 3px solid #dbeafe;
  border-top-color: #2563eb;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

.empty-icon { font-size: 46px; }

.empty-card h3 {
  margin: 13px 0 5px;
  color: var(--text);
}

.empty-card p,
.loading-card p {
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}

.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

.table-card table {
  width: 100%;
  min-width: 1100px;
  border-collapse: collapse;
}

thead { background: rgba(148, 163, 184, 0.08); }

th {
  padding: 15px 18px;
  color: var(--muted);
  font-size: 10px;
  letter-spacing: 0.6px;
  font-weight: 800;
  text-align: left;
}

td {
  padding: 16px 18px;
  border-top: 1px solid var(--panel-border);
  font-size: 13px;
  color: var(--text);
}

.order-number {
  color: #2563eb;
  font-weight: 800;
}

.order-id,
.quantity,
.muted-text,
.date-text {
  color: var(--muted);
  font-size: 11px;
}

.customer-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.customer-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-weight: 800;
}

.customer-cell strong,
.customer-cell small {
  display: block;
}

.customer-cell strong {
  color: var(--text);
  font-size: 13px;
}

.customer-cell small {
  margin-top: 3px;
  color: var(--muted);
  font-size: 10px;
}

.product-meta {
  display: flex;
  align-items: center;
  gap: 6px;
  font-weight: 700;
}

.total-price {
  color: #2563eb;
  white-space: nowrap;
}

.payment-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 10px;
  font-weight: 700;
}

.payment-paid {
  background: #dcfce7;
  color: #15803d;
}

.payment-waiting {
  background: #fef3c7;
  color: #b45309;
}

.payment-pending {
  background: #fff7ed;
  color: #c2410c;
}

.payment-failed {
  background: #fee2e2;
  color: #dc2626;
}

.status-select {
  border: 1px solid;
  border-radius: 999px;
  padding: 7px 11px;
  font-size: 11px;
  font-weight: 700;
  outline: none;
  cursor: pointer;
  background: transparent;
}

.status-pending { background: #fff7ed; border-color: #fed7aa; color: #c2410c; }
.status-processing { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
.status-shipped { background: #f5f3ff; border-color: #ddd6fe; color: #6d28d9; }
.status-completed { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
.status-cancelled { background: #fef2f2; border-color: #fecaca; color: #dc2626; }

.detail-button {
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 8px;
  padding: 6px 12px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s ease;
}

.detail-button:hover {
  background: #2563eb;
  color: white;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: rgba(15, 23, 42, 0.6);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 2000;
}

.detail-modal {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 16px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 20px;
  border-bottom: 1px solid var(--panel-border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-label {
  font-size: 11px;
  color: var(--muted);
  font-weight: 700;
  text-transform: uppercase;
}

.modal-header h2 {
  margin: 4px 0 0;
  font-size: 20px;
  color: var(--text);
}

.close-button {
  background: transparent;
  border: none;
  font-size: 24px;
  color: var(--muted);
  cursor: pointer;
}

.modal-content {
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.detail-status {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 16px;
  background: rgba(148, 163, 184, 0.06);
  border-radius: 10px;
}

.detail-section h3 {
  font-size: 14px;
  margin: 0 0 10px;
  color: var(--text);
}

.info-box {
  background: rgba(148, 163, 184, 0.04);
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.info-box p {
  margin: 4px 0;
  font-size: 12px;
}

.no-data {
  font-size: 12px;
  color: var(--muted);
  font-style: italic;
}

.section-title-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.section-title-row h3 {
  margin: 0 !important;
}

.items-count-badge {
  background: rgba(37, 99, 235, 0.1);
  color: #2563eb;
  padding: 3px 8px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.items-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.order-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 12px;
  background: rgba(148, 163, 184, 0.04);
  border: 1px solid var(--panel-border);
  border-radius: 8px;
  gap: 12px;
}

.item-product-detail {
  display: flex;
  align-items: center;
  gap: 12px;
}

.item-thumbnail {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  object-fit: cover;
  border: 1px solid var(--panel-border);
  background: #f8fafc;
}

.item-placeholder {
  width: 44px;
  height: 44px;
  border-radius: 8px;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  font-size: 20px;
}

.item-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.item-info strong {
  font-size: 13px;
  color: var(--text);
}

.item-info span {
  font-size: 11px;
  color: var(--muted);
}

.item-subtotal {
  font-size: 13px;
  color: var(--text);
  white-space: nowrap;
}

.summary-box {
  background: rgba(37, 99, 235, 0.04);
  border: 1px solid rgba(37, 99, 235, 0.1);
  border-radius: 10px;
  padding: 14px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.summary-box div {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.grand-total {
  border-top: 1px solid rgba(37, 99, 235, 0.2);
  padding-top: 8px;
  color: #2563eb;
  font-weight: 800;
  font-size: 15px !important;
}

.payment-detail {
  display: flex;
  flex-direction: column;
  gap: 10px;
  background: rgba(148, 163, 184, 0.04);
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  padding: 14px;
}

.payment-detail div {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 13px;
}

.payment-action-row {
  display: flex;
  justify-content: flex-end !important;
  margin-top: 4px;
  padding-top: 8px;
  border-top: 1px dashed var(--panel-border);
}

.verify-payment-btn {
  background: #16a34a;
  color: #ffffff;
  border: none;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s ease;
}

.verify-payment-btn:hover {
  background: #15803d;
}

.verify-payment-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.proof-wrapper {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 8px;
  border-top: 1px dashed var(--panel-border);
  padding-top: 10px;
}

.proof-header {
  display: flex;
  justify-content: space-between !important;
  align-items: center;
  font-size: 12px;
}

.proof-link {
  color: #2563eb;
  text-decoration: none;
  font-weight: 600;
  font-size: 11px;
}

.proof-link:hover {
  text-decoration: underline;
}

.proof-img-container {
  display: block;
  border-radius: 8px;
  overflow: hidden;
  border: 1px solid var(--panel-border);
  background: #000;
  max-height: 240px;
  cursor: zoom-in;
}

.proof-img-container img {
  width: 100%;
  max-height: 240px;
  object-fit: contain;
  display: block;
}

.modal-footer {
  padding: 16px 20px;
  border-top: 1px solid var(--panel-border);
  display: flex;
  justify-content: flex-end;
}

.secondary-button {
  background: rgba(148, 163, 184, 0.1);
  border: 1px solid var(--panel-border);
  color: var(--text);
  padding: 8px 16px;
  border-radius: 8px;
  font-weight: 700;
  cursor: pointer;
}
</style>
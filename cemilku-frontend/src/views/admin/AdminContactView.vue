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
const search = ref('')
const selectedContact = ref(null)
const contacts = ref([])

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

const fetchContacts = async () => {
  loading.value = true

  try {
    const response = await api.get('/admin/contacts')
    const payload = response.data

    if (Array.isArray(payload)) {
      contacts.value = payload
    } else if (Array.isArray(payload.data)) {
      contacts.value = payload.data
    } else if (payload.data && Array.isArray(payload.data.data)) {
      contacts.value = payload.data.data
    } else {
      contacts.value = []
    }
  } catch (error) {
    console.error('Gagal mengambil pesan kontak:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal mengambil pesan kontak.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    loading.value = false
  }
}

const filteredContacts = computed(() => {
  const keyword = search.value.toLowerCase().trim()

  if (!keyword) return contacts.value

  return contacts.value.filter((contact) => {
    const name = contact.name?.toLowerCase() || ''
    const email = contact.email?.toLowerCase() || ''
    const message = contact.message?.toLowerCase() || ''

    return (
      name.includes(keyword) ||
      email.includes(keyword) ||
      message.includes(keyword)
    )
  })
})

const totalMessages = computed(() => contacts.value.length)

const todayMessages = computed(() => {
  const today = new Date().toDateString()

  return contacts.value.filter((contact) => {
    if (!contact.created_at) return false
    return new Date(contact.created_at).toDateString() === today
  }).length
})

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

const formatDate = (date) => {
  if (!date) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }).format(new Date(date))
}

const formatDateTime = (date) => {
  if (!date) return '-'
  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(new Date(date))
}

const showDetail = (contact) => {
  selectedContact.value = contact
}

const closeDetail = () => {
  selectedContact.value = null
}

const deleteContact = async (contact) => {
  const result = await Swal.fire({
    icon: 'warning',
    title: 'Hapus Pesan?',
    text: 'Pesan kontak ini akan dihapus secara permanen.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Hapus',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#dc2626'
  })

  if (!result.isConfirmed) return

  try {
    await api.delete(`/admin/contacts/${contact.id}`)
    contacts.value = contacts.value.filter((item) => item.id !== contact.id)
    selectedContact.value = null

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Pesan berhasil dihapus.',
      timer: 1500,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Gagal menghapus pesan:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: error.response?.data?.message || 'Gagal menghapus pesan.',
      confirmButtonColor: '#2563eb'
    })
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
      await auth.fetchUser()
    } catch (error) {
      console.error('Gagal mengambil data user:', error)
      router.replace('/login')
      return
    }
  }

  if (!checkAdmin()) {
    return
  }

  await fetchContacts()
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

  <button class="menu-item" @click="goTo('/admin/order')">
    <span class="menu-icon">📑</span>
    <span class="menu-text">Order</span>
  </button>

  
  <button class="menu-item router-link-exact-active">
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
         <input v-model="search" type="text" placeholder="Cari nama, email, atau pesan..." />
        </div>

        <div class="topbar-right">
          <button class="theme-toggle" aria-label="Toggle navbar theme" @click="toggleNavbarTheme">
            {{ isNavbarDark ? '☀️' : '🌙' }}
          </button>

          <button class="icon-btn" aria-label="Notifikasi">🔔</button>

          <div class="user-info">
            <div class="user-name">{{ auth.username || 'Admin' }}</div>
            <div class="user-role">Administrator</div>
          </div>

          <div class="user-avatar">
            {{ (auth.username || 'A').charAt(0).toUpperCase() }}
          </div>
        </div>
      </header>

      <main class="content-body">
        <div class="page-header">
          <div>
            <span class="section-kicker">Manajemen Pesan</span>
            <h1>Pesan Kontak</h1>
            <p>Kelola pesan dan pertanyaan yang dikirim pelanggan.</p>
          </div>

          <button class="btn-primary-action" @click="fetchContacts" :disabled="loading">
            <span class="btn-icon">↻</span>
            {{ loading ? 'Memuat...' : 'Refresh' }}
          </button>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Total Pesan</span>
              <div class="stat-icon-wrapper blue">💬</div>
            </div>
            <div class="stat-value">{{ totalMessages }}</div>
            <div class="stat-sub">Semua chat masuk</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Pesan Hari Ini</span>
              <div class="stat-icon-wrapper green">📅</div>
            </div>
            <div class="stat-value">{{ todayMessages }}</div>
            <div class="stat-sub">Pesan hari ini</div>
          </div>
        </div>

        <div class="table-card">
          <div class="table-header">
            <div>
              <h3>Daftar Pesan</h3>
              <p>{{ filteredContacts.length }} pesan tersedia</p>
            </div>
          </div>

          <div v-if="loading" class="loading-card">
            <div class="spinner"></div>
            <p>Memuat pesan kontak...</p>
          </div>

          <div v-else-if="filteredContacts.length === 0" class="empty-card">
            <div class="empty-icon">💬</div>
            <h3>Belum Ada Pesan</h3>
            <p>Pesan dari pelanggan akan muncul di sini.</p>
          </div>

          <div v-else class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>PENGIRIM</th>
                  <th>PESAN</th>
                  <th>TANGGAL</th>
                  <th>AKSI</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="contact in filteredContacts" :key="contact.id">
                  <td>
                    <div class="sender-cell">
                      <div class="avatar">{{ contact.name ? contact.name.charAt(0).toUpperCase() : '?' }}</div>
                      <div>
                        <strong>{{ contact.name || '-' }}</strong>
                        <small>{{ contact.email || '-' }}</small>
                      </div>
                    </div>
                  </td>

                  <td>
                    <div class="message-preview">{{ contact.message || '-' }}</div>
                  </td>

                  <td>
                    <span class="date">{{ formatDate(contact.created_at) }}</span>
                  </td>

                  <td>
                    <div class="actions">
                      <button class="view-button" title="Lihat pesan" @click="showDetail(contact)">👁️</button>
                      <button class="delete-button" title="Hapus pesan" @click="deleteContact(contact)">🗑️</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>

    <div v-if="selectedContact" class="modal-overlay" @click.self="closeDetail">
      <div class="modal">
        <div class="modal-header">
          <div>
            <span class="modal-label">DETAIL PESAN</span>
            <h2>Pesan Kontak</h2>
          </div>
          <button class="close-button" @click="closeDetail">×</button>
        </div>

        <div class="modal-body">
          <div class="sender-detail">
            <div class="large-avatar">{{ selectedContact.name ? selectedContact.name.charAt(0).toUpperCase() : '?' }}</div>
            <div>
              <h3>{{ selectedContact.name || '-' }}</h3>
              <p>{{ selectedContact.email || '-' }}</p>
            </div>
          </div>

          <div class="detail-list">
            <div class="detail-row">
              <span>Nama</span>
              <strong>{{ selectedContact.name || '-' }}</strong>
            </div>

            <div class="detail-row">
              <span>Email</span>
              <strong>{{ selectedContact.email || '-' }}</strong>
            </div>

            <div class="detail-row">
              <span>Dikirim</span>
              <strong>{{ formatDateTime(selectedContact.created_at) }}</strong>
            </div>
          </div>

          <div class="message-box">
            <span>Isi Pesan</span>
            <p>{{ selectedContact.message || '-' }}</p>
          </div>
        </div>

        <div class="modal-footer">
          <button class="cancel-button" @click="closeDetail">Tutup</button>
          <button class="danger-button" @click="deleteContact(selectedContact)">🗑️ Hapus Pesan</button>
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
  width: 360px;
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

.stats-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
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
  min-width: 1000px;
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
  vertical-align: middle;
}

.sender-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-weight: 800;
  flex-shrink: 0;
}

.sender-cell strong {
  display: block;
  color: var(--text);
}

.sender-cell small {
  display: block;
  color: var(--muted);
  margin-top: 3px;
  font-size: 10px;
}



.message-preview {
  max-width: 260px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
  color: var(--muted);
}

.date {
  white-space: nowrap;
  color: var(--muted);
  font-size: 11px;
}

.actions {
  display: flex;
  gap: 7px;
}

.view-button,
.delete-button {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 9px;
  cursor: pointer;
}

.view-button { background: #eff6ff; }
.delete-button { background: #fef2f2; }
.view-button:hover,
.delete-button:hover { transform: translateY(-1px); }

.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  background: rgba(15, 23, 42, 0.55);
  backdrop-filter: blur(5px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal {
  width: min(680px, 100%);
  max-height: 90vh;
  overflow-y: auto;
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 20px;
  box-shadow: 0 30px 80px rgba(15, 23, 42, 0.25);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 22px 24px;
  border-bottom: 1px solid var(--panel-border);
}

.modal-label {
  color: var(--muted);
  font-size: 12px;
  display: block;
}

.modal-header h2 {
  margin: 5px 0 0;
  color: var(--text);
}

.close-button {
  width: 34px;
  height: 34px;
  border: none;
  border-radius: 9px;
  background: rgba(148, 163, 184, 0.08);
  color: var(--text);
  font-size: 22px;
  cursor: pointer;
}

.modal-body {
  padding: 24px;
}

.sender-detail {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 16px;
  background: rgba(148, 163, 184, 0.04);
  border-radius: 12px;
  margin-bottom: 20px;
}

.large-avatar {
  width: 55px;
  height: 55px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: rgba(37, 99, 235, 0.08);
  color: #2563eb;
  font-weight: 800;
}

.sender-detail h3 {
  margin: 0;
  color: var(--text);
}

.sender-detail p {
  margin: 4px 0 0;
  color: var(--muted);
  font-size: 13px;
}

.detail-list {
  display: grid;
  gap: 10px;
  margin-bottom: 20px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  padding: 13px 15px;
  background: rgba(148, 163, 184, 0.04);
  border-radius: 10px;
}

.detail-row span {
  color: var(--muted);
}

.detail-row strong {
  color: var(--text);
  text-align: right;
}

.message-box {
  padding: 17px;
  background: rgba(37, 99, 235, 0.05);
  border: 1px solid rgba(37, 99, 235, 0.15);
  border-radius: 12px;
}

.message-box span {
  display: block;
  margin-bottom: 9px;
  color: #2563eb;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
}

.message-box p {
  margin: 0;
  color: var(--text);
  line-height: 1.7;
  white-space: pre-wrap;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 18px 24px;
  border-top: 1px solid var(--panel-border);
}

.cancel-button,
.danger-button {
  border: none;
  padding: 10px 16px;
  border-radius: 99px;
  font-weight: 700;
  cursor: pointer;
}

.cancel-button {
  background: rgba(148, 163, 184, 0.08);
  color: var(--text);
}

.danger-button {
  background: #dc2626;
  color: white;
}

@media (max-width: 900px) {
  .sidebar { width: 220px; }
  .main-wrapper { margin-left: 220px; }
  .content-body { padding: 0 20px 24px; }
  .stats-grid { grid-template-columns: 1fr; }
}

@media (max-width: 700px) {
  .sidebar {
    position: relative;
    width: 100%;
    min-height: auto;
    height: auto;
  }

  .main-wrapper {
    width: 100%;
    margin-left: 0;
  }

  .dashboard-layout { display: block; }

  .topbar {
    height: auto;
    padding: 18px 20px;
    gap: 15px;
    flex-wrap: wrap;
  }

  .content-body { padding: 0 16px 20px; }

  .page-header {
    align-items: flex-start;
    flex-direction: column;
    gap: 16px;
  }

  .btn-primary-action {
    width: 100%;
    justify-content: center;
  }

  .user-info { display: none; }

  .detail-row {
    flex-direction: column;
    gap: 5px;
  }

  .detail-row strong {
    text-align: left;
  }

  .modal-footer {
    flex-direction: column;
  }

  .cancel-button,
  .danger-button {
    width: 100%;
  }
}
</style>
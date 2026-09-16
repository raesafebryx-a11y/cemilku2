<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isNavbarDark = ref(false)
const isSaving = ref(false)

const defaultSettings = {
  storeName: 'Cemilku',
  slogan: 'Cemilan favorit untuk setiap momen.',
  phone: '+62 812-3456-7890',
  email: 'hello@cemilku.id',
  whatsapp: '+62 812-3456-7890',
  instagram: '@cemilku_store',
  address: 'Jl. Merdeka No. 12, Bandung, Indonesia',
  bannerText: 'Nikmati cemilan berkualitas untuk setiap hari Anda.'
}

const settings = ref({ ...defaultSettings })

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

const goTo = (path) => {
  router.push(path)
}

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

const loadSettings = () => {
  const saved = localStorage.getItem('cemilku-admin-settings')

  if (saved) {
    try {
      const parsed = JSON.parse(saved)
      settings.value = { ...defaultSettings, ...parsed }
    } catch (error) {
      console.warn('Gagal membaca setting tersimpan:', error)
      settings.value = { ...defaultSettings }
    }
  }
}

const saveSettings = async () => {
  isSaving.value = true

  try {
    localStorage.setItem('cemilku-admin-settings', JSON.stringify(settings.value))

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil',
      text: 'Pengaturan toko berhasil disimpan.',
      timer: 1600,
      showConfirmButton: false
    })
  } catch (error) {
    console.error('Gagal menyimpan pengaturan:', error)
    await Swal.fire({
      icon: 'error',
      title: 'Gagal',
      text: 'Pengaturan tidak dapat disimpan.',
      confirmButtonColor: '#2563eb'
    })
  } finally {
    isSaving.value = false
  }
}

const resetSettings = async () => {
  const result = await Swal.fire({
    icon: 'question',
    title: 'Reset Pengaturan?',
    text: 'Semua nilai pengaturan toko akan dikembalikan ke default.',
    showCancelButton: true,
    confirmButtonText: 'Ya, Reset',
    cancelButtonText: 'Batal',
    confirmButtonColor: '#2563eb'
  })

  if (!result.isConfirmed) return

  settings.value = { ...defaultSettings }
  localStorage.setItem('cemilku-admin-settings', JSON.stringify(settings.value))

  await Swal.fire({
    icon: 'success',
    title: 'Berhasil',
    text: 'Pengaturan berhasil direset ke default.',
    timer: 1500,
    showConfirmButton: false
  })
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

  loadSettings()
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

        <button class="menu-item" @click="goTo('/admin/order-item')">
          <span class="menu-icon">📋</span>
          <span class="menu-text">Order Item</span>
        </button>

        <button class="menu-item" @click="goTo('/admin/kontak')">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Pesan Kontak</span>
        </button>

        <div class="menu-category">SISTEM</div>

        <button class="menu-item router-link-exact-active">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Pengaturan</span>
        </button>

        <a href="#" class="menu-item logout" @click.prevent="handleLogout">
          <span class="menu-icon">🚪</span>
          <span class="menu-text">Keluar</span>
        </a>
      </nav>
    </aside>

    <div class="main-wrapper">
      <header class="topbar">
        <div class="title-box">
          <span class="section-kicker">Pengaturan Toko</span>
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
            <span class="section-kicker">Setelan</span>
            <h1>Pengaturan</h1>
            <p>Atur informasi toko, kontak, dan preferensi utama Cemilku.</p>
          </div>

          <div class="header-actions">
            <button class="secondary-btn" @click="resetSettings">Reset</button>
            <button class="btn-primary-action" @click="saveSettings" :disabled="isSaving">
              <span class="btn-icon">💾</span>
              {{ isSaving ? 'Menyimpan...' : 'Simpan' }}
            </button>
          </div>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Nama Toko</span>
              <div class="stat-icon-wrapper blue">🏪</div>
            </div>
            <div class="stat-value">{{ settings.storeName }}</div>
            <div class="stat-sub">Nama depan toko</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Kontak</span>
              <div class="stat-icon-wrapper green">📞</div>
            </div>
            <div class="stat-value">{{ settings.phone }}</div>
            <div class="stat-sub">Nomor WhatsApp</div>
          </div>

          <div class="stat-card">
            <div class="stat-header">
              <span class="stat-label">Instagram</span>
              <div class="stat-icon-wrapper purple">📱</div>
            </div>
            <div class="stat-value">{{ settings.instagram }}</div>
            <div class="stat-sub">Akun sosial</div>
          </div>
        </div>

        <div class="settings-grid">
          <div class="settings-panel">
            <div class="panel-header">
              <h3>Informasi Toko</h3>
            </div>

            <div class="form-grid">
              <label>
                <span>Nama Toko</span>
                <input v-model="settings.storeName" type="text" placeholder="Cemilku" />
              </label>

              <label>
                <span>Slogan</span>
                <input v-model="settings.slogan" type="text" placeholder="Cemilan favorit untuk setiap momen." />
              </label>

              <label class="full-width">
                <span>Alamat</span>
                <textarea v-model="settings.address" rows="3" placeholder="Jl. Merdeka No. 12, Bandung, Indonesia"></textarea>
              </label>

              <label>
                <span>Nomor Telepon</span>
                <input v-model="settings.phone" type="text" placeholder="+62 812-3456-7890" />
              </label>

              <label>
                <span>Email</span>
                <input v-model="settings.email" type="email" placeholder="hello@cemilku.id" />
              </label>

              <label>
                <span>WhatsApp</span>
                <input v-model="settings.whatsapp" type="text" placeholder="+62 812-3456-7890" />
              </label>

              <label>
                <span>Instagram</span>
                <input v-model="settings.instagram" type="text" placeholder="@cemilku_store" />
              </label>

              <label class="full-width">
                <span>Banner / teks promo</span>
                <textarea v-model="settings.bannerText" rows="3" placeholder="Nikmati cemilan berkualitas untuk setiap hari Anda."></textarea>
              </label>
            </div>
          </div>

          <div class="settings-panel preview-panel">
            <div class="panel-header">
              <h3>Preview</h3>
            </div>

            <div class="preview-card">
              <div class="preview-badge">Store Profile</div>
              <h4>{{ settings.storeName }}</h4>
              <p>{{ settings.slogan }}</p>

              <div class="preview-list">
                <span>📍 {{ settings.address }}</span>
                <span>📞 {{ settings.phone }}</span>
                <span>✉️ {{ settings.email }}</span>
                <span>💬 {{ settings.whatsapp }}</span>
                <span>📱 {{ settings.instagram }}</span>
              </div>

              <div class="preview-banner">{{ settings.bannerText }}</div>
            </div>
          </div>
        </div>
      </main>
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

.title-box {
  display: flex;
  align-items: center;
  gap: 8px;
}

.section-kicker {
  display: block;
  font-size: 12px;
  color: #2563eb;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
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

.header-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}

.btn-primary-action,
.secondary-btn {
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary-action {
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
  border: none;
  padding: 10px 18px;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.22);
}

.secondary-btn {
  background: rgba(148, 163, 184, 0.08);
  border: 1px solid var(--panel-border);
  color: var(--text);
  padding: 9px 16px;
}

.btn-primary-action:hover,
.secondary-btn:hover { transform: translateY(-1px); }
.btn-primary-action:disabled {
  opacity: 0.7;
  cursor: not-allowed;
}

.btn-icon { font-size: 18px; }

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
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
.stat-icon-wrapper.purple { background: rgba(124, 58, 237, 0.1); }

.stat-value {
  font-size: 24px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.1;
  word-break: break-word;
}

.stat-sub {
  margin-top: 6px;
  font-size: 11px;
  color: var(--muted);
}

.settings-grid {
  display: grid;
  grid-template-columns: 1.6fr 0.9fr;
  gap: 20px;
}

.settings-panel {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 16px;
  overflow: hidden;
}

.panel-header {
  padding: 18px 20px;
  border-bottom: 1px solid var(--panel-border);
}

.panel-header h3 {
  margin: 0;
  color: var(--text);
  font-size: 16px;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
  padding: 20px;
}

label {
  display: flex;
  flex-direction: column;
  gap: 8px;
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
}

label span {
  color: var(--muted);
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.02em;
}

input,
textarea {
  width: 100%;
  border: 1px solid var(--panel-border);
  background: rgba(148, 163, 184, 0.04);
  color: var(--text);
  border-radius: 10px;
  padding: 10px 12px;
  font: inherit;
  resize: vertical;
  outline: none;
  box-sizing: border-box;
}

input:focus,
textarea:focus {
  border-color: rgba(37, 99, 235, 0.7);
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.full-width {
  grid-column: 1 / -1;
}

.preview-panel {
  padding-bottom: 20px;
}

.preview-card {
  margin: 20px;
  padding: 20px;
  border-radius: 14px;
  background: linear-gradient(135deg, rgba(37, 99, 235, 0.08), rgba(124, 58, 237, 0.08));
  border: 1px solid rgba(37, 99, 235, 0.15);
}

.preview-badge {
  display: inline-block;
  background: rgba(37, 99, 235, 0.12);
  color: #2563eb;
  font-size: 10px;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.preview-card h4 {
  margin: 14px 0 4px;
  color: var(--text);
  font-size: 24px;
}

.preview-card p {
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}

.preview-list {
  display: grid;
  gap: 8px;
  margin-top: 16px;
  color: var(--text);
  font-size: 13px;
}

.preview-banner {
  margin-top: 18px;
  padding: 12px 14px;
  background: rgba(15, 23, 42, 0.04);
  border-radius: 10px;
  color: var(--text);
  border: 1px solid var(--panel-border);
  font-weight: 600;
}

@media (max-width: 980px) {
  .sidebar { width: 220px; }
  .main-wrapper { margin-left: 220px; }
  .content-body { padding: 0 20px 24px; }
  .stats-grid, .settings-grid { grid-template-columns: 1fr; }
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

  .header-actions {
    width: 100%;
    justify-content: space-between;
  }

  .btn-primary-action,
  .secondary-btn {
    flex: 1;
  }

  .form-grid {
    grid-template-columns: 1fr;
  }

  .user-info { display: none; }
}
</style>

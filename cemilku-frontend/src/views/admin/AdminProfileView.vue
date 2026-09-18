<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import Swal from 'sweetalert2'

import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isNavbarDark = ref(false)
const loading = ref(false)
const saving = ref(false)
const changingPassword = ref(false)

// ==========================================
// FORM DATA PROFIL
// ==========================================
const profileForm = ref({
  name: '',
  email: '',
  username: '',
  phone: ''
})

// ==========================================
// FORM DATA PASSWORD
// ==========================================
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

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
      detail: { dark: isNavbarDark.value }
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
// FETCH PROFIL
// ==========================================
const fetchUserProfile = async () => {
  loading.value = true
  try {
    if (auth.fetchMe) {
      await auth.fetchMe()
    }
    const user = auth.user || {}
    profileForm.value = {
      name: user.name || user.nama || auth.username || '',
      email: user.email || '',
      username: user.username || auth.username || '',
      phone: user.phone || user.no_hp || user.telepon || ''
    }
  } catch (err) {
    console.error('Gagal memuat profil:', err)
  } finally {
    loading.value = false
  }
}

// ==========================================
// UPDATE PROFIL
// ==========================================
const handleUpdateProfile = async () => {
  saving.value = true
  try {
    await api.put('/profile', profileForm.value)
    
    if (auth.fetchMe) {
      await auth.fetchMe()
    }

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Data profil berhasil diperbarui.',
      confirmButtonColor: '#2563eb'
    })
  } catch (err) {
    console.error('Gagal memperbarui profil:', err)
    const msg = err.response?.data?.message || 'Gagal menyimpan perubahan profil.'
    await Swal.fire({
      icon: 'error',
      title: 'Gagal Update',
      text: msg,
      confirmButtonColor: '#2563eb'
    })
  } finally {
    saving.value = false
  }
}

// ==========================================
// UPDATE PASSWORD
// ==========================================
const handleUpdatePassword = async () => {
  if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
    await Swal.fire({
      icon: 'warning',
      title: 'Password Tidak Sama',
      text: 'Konfirmasi password baru tidak cocok.',
      confirmButtonColor: '#2563eb'
    })
    return
  }

  changingPassword.value = true
  try {
    await api.put('/profile/password', {
      current_password: passwordForm.value.current_password,
      password: passwordForm.value.new_password,
      password_confirmation: passwordForm.value.new_password_confirmation
    })

    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    }

    await Swal.fire({
      icon: 'success',
      title: 'Berhasil!',
      text: 'Password berhasil diubah.',
      confirmButtonColor: '#2563eb'
    })
  } catch (err) {
    console.error('Gagal merubah password:', err)
    const msg = err.response?.data?.message || 'Gagal mengubah password. Cek password lama Anda.'
    await Swal.fire({
      icon: 'error',
      title: 'Gagal Ganti Password',
      text: msg,
      confirmButtonColor: '#2563eb'
    })
  } finally {
    changingPassword.value = false
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

  if (!result.isConfirmed) return

  try {
    await auth.logout()
  } catch (err) {
    console.error('Logout error:', err)
  }
  router.push('/login')
}

// ==========================================
// MOUNT & UNMOUNT
// ==========================================
onMounted(async () => {
  loadTheme()
  window.addEventListener('admin-theme-change', handleThemeChange)

  if (!checkAdmin()) return
  await fetchUserProfile()
})

onUnmounted(() => {
  window.removeEventListener('admin-theme-change', handleThemeChange)
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
      <div class="sidebar-brand" @click="router.push('/')">
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
          <span class="brand-text">Cemilku</span>
          <small>SNACK STORE</small>
        </div>
      </div>

      <!-- MENU -->
      <nav class="sidebar-menu">
        <div class="menu-category">MAIN</div>
        <router-link to="/admin" class="menu-item" exact>
          <span class="menu-icon">📊</span>
          <span class="menu-text">Dashboard</span>
        </router-link>

        <div class="menu-category">KELOLA TOKO</div>
        <router-link to="/admin/produk" class="menu-item">
          <span class="menu-icon">🍿</span>
          <span class="menu-text">Produk</span>
        </router-link>

        <router-link to="/admin/kategori" class="menu-item">
          <span class="menu-icon">🏷️</span>
          <span class="menu-text">Kategori</span>
        </router-link>

        <router-link to="/admin/order" class="menu-item">
          <span class="menu-icon">📑</span>
          <span class="menu-text">Order</span>
        </router-link>

        <router-link to="/admin/kontak" class="menu-item">
          <span class="menu-icon">💬</span>
          <span class="menu-text">Pesan Kontak</span>
        </router-link>

        <div class="menu-category">SISTEM</div>
        <router-link to="/admin/pengaturan" class="menu-item">
          <span class="menu-icon">⚙️</span>
          <span class="menu-text">Pengaturan</span>
        </router-link>

        <router-link to="/admin/profile" class="menu-item">
          <span class="menu-icon">👤</span>
          <span class="menu-text">Profile</span>
        </router-link>

        <a href="#" class="menu-item logout" @click.prevent="handleLogout">
          <span class="menu-icon">🚪</span>
          <span class="menu-text">Keluar</span>
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
          <span class="search-icon">🔍</span>
          <input type="text" placeholder="Cari sesuatu..." />
        </div>

        <div class="topbar-right">
          <button
            class="theme-toggle"
            aria-label="Toggle navbar theme"
            @click="toggleNavbarTheme"
          >
            {{ isNavbarDark ? '☀️' : '🌙' }}
          </button>

          <button class="icon-btn" aria-label="Notifikasi">
            🔔
          </button>

          <div class="user-profile-wrapper" @click="router.push('/admin/profile')">
            <div class="user-info">
              <div class="user-name">
                {{ auth.username || auth.user?.name || 'Admin' }}
              </div>
              <div class="user-role">Administrator</div>
            </div>
            <div class="user-avatar">
              {{ (auth.username || auth.user?.name || 'A').charAt(0).toUpperCase() }}
            </div>
          </div>
        </div>
      </header>

      <!-- ==================================================
           CONTENT BODY
      =================================================== -->
      <main class="content-body">
        <!-- BANNER -->
        <div class="dashboard-banner">
          <div class="banner-content">
            <div>
              <span class="welcome-text">Pengaturan Akun</span>
              <h1>Profil Administrator</h1>
              <p>Kelola informasi akun dan kata sandi Anda di sini.</p>
            </div>
          </div>
        </div>

        <!-- LOADING STATE -->
        <div v-if="loading" class="loading-state">
          <div class="loading-spinner"></div>
          <p>Memuat profil...</p>
        </div>

        <!-- PROFILE CONTENT GRID -->
        <div v-else class="profile-grid">
          <!-- CARD AKUN OVERVIEW -->
          <div class="profile-card overview-card">
            <div class="avatar-large">
              {{ (auth.username || auth.user?.name || 'A').charAt(0).toUpperCase() }}
            </div>
            <h3>{{ profileForm.name || 'Administrator' }}</h3>
            <span class="badge-role">Administrator</span>
            <p class="user-email-text">{{ profileForm.email || '-' }}</p>
          </div>

          <!-- FORM EDIT PROFIL -->
          <div class="profile-card">
            <div class="card-header">
              <h3>Detail Informasi Profil</h3>
              <p>Perbarui informasi akun Anda</p>
            </div>

            <form @submit.prevent="handleUpdateProfile" class="profile-form">
              <div class="form-group">
                <label>Nama Lengkap</label>
                <input
                  v-model="profileForm.name"
                  type="text"
                  placeholder="Masukkan nama lengkap"
                  required
                />
              </div>

              <div class="form-group">
                <label>Username</label>
                <input
                  v-model="profileForm.username"
                  type="text"
                  placeholder="Masukkan username"
                  required
                />
              </div>

              <div class="form-group">
                <label>Email</label>
                <input
                  v-model="profileForm.email"
                  type="email"
                  placeholder="nama@email.com"
                  required
                />
              </div>

              <div class="form-group">
                <label>Nomor Telepon / WA</label>
                <input
                  v-model="profileForm.phone"
                  type="text"
                  placeholder="08123456789"
                />
              </div>

              <button
                type="submit"
                class="btn-primary-action"
                :disabled="saving"
              >
                {{ saving ? 'Menyimpan...' : 'Simpan Perubahan' }}
              </button>
            </form>
          </div>

          <!-- FORM GANTI PASSWORD -->
          <div class="profile-card full-width">
            <div class="card-header">
              <h3>Ubah Kata Sandi</h3>
              <p>Pastikan menggunakan kombinasi kata sandi yang aman</p>
            </div>

            <form @submit.prevent="handleUpdatePassword" class="password-form-grid">
              <div class="form-group">
                <label>Password Saat Ini</label>
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  placeholder="••••••••"
                  required
                />
              </div>

              <div class="form-group">
                <label>Password Baru</label>
                <input
                  v-model="passwordForm.new_password"
                  type="password"
                  placeholder="••••••••"
                  required
                />
              </div>

              <div class="form-group">
                <label>Konfirmasi Password Baru</label>
                <input
                  v-model="passwordForm.new_password_confirmation"
                  type="password"
                  placeholder="••••••••"
                  required
                />
              </div>

              <div class="form-action-full">
                <button
                  type="submit"
                  class="btn-primary-action"
                  :disabled="changingPassword"
                >
                  {{ changingPassword ? 'Memproses...' : 'Ubah Password' }}
                </button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<style scoped>
/* ==================================================
   DASHBOARD LAYOUT (SAME AS DASHBOARDVIEW)
================================================== */
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
  --input-bg: rgba(255, 255, 255, 0.8);

  min-height: 100vh;
  display: flex;
  background: var(--page-bg);
  color: var(--text);
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
}

/* DARK MODE */
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
  --input-bg: rgba(30, 41, 59, 0.8);
}

/* SIDEBAR */
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

.sidebar-brand:hover {
  opacity: 0.9;
}

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
  flex-shrink: 0;
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

.menu-item.router-link-active {
  background: var(--nav-active);
  color: #ffffff;
  box-shadow: 0 8px 16px rgba(37, 99, 235, 0.18);
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

.menu-item.logout {
  color: #dc2626;
  margin-top: 4px;
}

.menu-item.logout:hover {
  background: rgba(239, 68, 68, 0.08);
  color: #b91c1c;
}

/* MAIN WRAPPER */
.main-wrapper {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  overflow-x: hidden;
  margin-left: 250px;
}

/* TOPBAR */
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

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.user-profile-wrapper {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 4px 8px;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s ease;
}

.user-profile-wrapper:hover {
  background: rgba(148, 163, 184, 0.12);
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
  flex-shrink: 0;
}

/* CONTENT BODY */
.content-body {
  padding: 0 28px 28px;
}

.dashboard-banner {
  background: linear-gradient(135deg, rgba(191, 219, 254, 0.9), rgba(239, 246, 255, 0.9));
  border: 1px solid rgba(191, 219, 254, 0.9);
  border-radius: 16px;
  padding: 28px;
  margin-top: 24px;
  margin-bottom: 24px;
  box-shadow: 0 12px 24px rgba(37, 99, 235, 0.08);
}

.dashboard-layout.navbar-dark .dashboard-banner {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.9));
  border-color: rgba(51, 65, 85, 0.9);
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

.welcome-text {
  display: block;
  font-size: 12px;
  color: #2563eb;
  font-weight: 700;
  margin-bottom: 6px;
}

/* ==================================================
   PROFILE SPECIFIC STYLES
================================================== */
.profile-grid {
  display: grid;
  grid-template-columns: 280px 1fr;
  gap: 24px;
}

.profile-card {
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 16px;
  padding: 24px;
  backdrop-filter: blur(10px);
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.03);
}

.profile-card.full-width {
  grid-column: 1 / -1;
}

.overview-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.avatar-large {
  width: 90px;
  height: 90px;
  border-radius: 50%;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #ffffff;
  display: grid;
  place-items: center;
  font-size: 36px;
  font-weight: 800;
  margin-bottom: 16px;
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.25);
}

.overview-card h3 {
  font-size: 18px;
  font-weight: 800;
  margin: 0 0 6px 0;
}

.badge-role {
  background: rgba(37, 99, 235, 0.12);
  color: #2563eb;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 12px;
  border-radius: 20px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.user-email-text {
  font-size: 13px;
  color: var(--muted);
  margin-top: 12px;
}

.card-header {
  margin-bottom: 20px;
}

.card-header h3 {
  font-size: 16px;
  font-weight: 800;
  margin: 0;
}

.card-header p {
  font-size: 12px;
  color: var(--muted);
  margin: 4px 0 0;
}

.profile-form {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

.password-form-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.form-group label {
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
}

.form-group input {
  background: var(--input-bg);
  border: 1px solid var(--panel-border);
  border-radius: 10px;
  padding: 10px 14px;
  font-size: 13px;
  color: var(--text);
  outline: none;
  transition: border-color 0.2s ease;
}

.form-group input:focus {
  border-color: #2563eb;
}

.form-action-full {
  grid-column: 1 / -1;
  display: flex;
  justify-content: flex-end;
  margin-top: 8px;
}

.btn-primary-action {
  grid-column: 1 / -1;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: #ffffff;
  border: none;
  padding: 10px 24px;
  border-radius: 10px;
  font-weight: 700;
  font-size: 14px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.22);
  transition: all 0.3s ease;
  align-self: flex-start;
  justify-self: flex-start;
  margin-top: 8px;
}

.btn-primary-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary-action:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
}

/* LOADING STATE */
.loading-state {
  text-align: center;
  padding: 48px 20px;
  background: var(--surface);
  border: 1px solid var(--panel-border);
  border-radius: 16px;
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

@media (max-width: 992px) {
  .profile-grid {
    grid-template-columns: 1fr;
  }
  
  .profile-form,
  .password-form-grid {
    grid-template-columns: 1fr;
  }
}
</style>
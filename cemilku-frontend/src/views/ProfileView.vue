<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const isDarkMode = ref(false)

const currentUser = computed(() => {
  if (auth.user) return auth.user

  try {
    return JSON.parse(localStorage.getItem('user') || 'null') || null
  } catch {
    return null
  }
})

const userName = computed(() => currentUser.value?.name || 'Pengguna')
const userEmail = computed(() => currentUser.value?.email || 'belum-tersedia@email.com')
const userPhone = computed(() => currentUser.value?.phone || 'Belum diisi')
const userRole = computed(() => currentUser.value?.role || 'customer')

const profileStats = computed(() => [
  {
    label: 'Total Pesanan',
    value: '12',
    note: 'Pesanan aktif'
  },
  {
    label: 'Wishlist',
    value: '08',
    note: 'Favorit tersimpan'
  },
  {
    label: 'Member',
    value: 'Gold',
    note: 'Status keanggotaan'
  }
])

const recentActivity = ref([
  {
    title: 'Order #ORD-2026-004',
    detail: 'Basreng Pedas • 2 item',
    meta: 'Selesai',
    tone: 'success'
  },
  {
    title: 'Alamat utama diperbarui',
    detail: 'Jl. Melati No. 15, Bandung',
    meta: 'Baru',
    tone: 'info'
  },
  {
    title: 'Review produk dikirim',
    detail: 'Kue Kering Matcha',
    meta: 'Tersimpan',
    tone: 'warning'
  }
])

const loadTheme = () => {
  const savedTheme = localStorage.getItem('theme')
  isDarkMode.value = savedTheme === 'dark'
}

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

onMounted(async () => {
  loadTheme()
  window.addEventListener('cemilku-theme-change', handleThemeChange)

  if (!auth.isLoggedIn && !localStorage.getItem('token')) {
    router.replace('/login')
    return
  }

  try {
    await auth.fetchUser()
  } catch (error) {
    console.error('Gagal memuat profil user:', error)
  }
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <div class="page-shell" :class="{ dark: isDarkMode }">
    <main class="profile-page">
      <section class="profile-hero">
        <div class="profile-card">
          <div class="avatar-wrap">
            <div class="avatar">
              {{ userName.charAt(0).toUpperCase() }}
            </div>
          </div>

          <div class="profile-meta">
            <span class="role-badge">{{ userRole === 'admin' ? 'Administrator' : 'Member' }}</span>
            <h1>{{ userName }}</h1>
            <p>{{ userEmail }}</p>
          </div>

          <button class="edit-button" type="button">Edit Profil</button>
        </div>
      </section>

      <section class="stats-grid">
        <div v-for="stat in profileStats" :key="stat.label" class="stat-card">
          <span class="stat-label">{{ stat.label }}</span>
          <strong class="stat-value">{{ stat.value }}</strong>
          <small>{{ stat.note }}</small>
        </div>
      </section>

      <section class="content-grid">
        <div class="panel info-panel">
          <div class="panel-header">
            <h2>Informasi akun</h2>
          </div>

          <div class="info-list">
            <div class="info-item">
              <span>Nama</span>
              <strong>{{ userName }}</strong>
            </div>

            <div class="info-item">
              <span>Email</span>
              <strong>{{ userEmail }}</strong>
            </div>

            <div class="info-item">
              <span>No. HP</span>
              <strong>{{ userPhone }}</strong>
            </div>

            <div class="info-item">
              <span>Role</span>
              <strong>{{ userRole }}</strong>
            </div>
          </div>
        </div>

        <div class="panel activity-panel">
          <div class="panel-header">
            <h2>Aktivitas terbaru</h2>
          </div>

          <ul class="activity-list">
            <li v-for="item in recentActivity" :key="item.title" class="activity-item">
              <div class="dot" :class="item.tone"></div>
              <div>
                <strong>{{ item.title }}</strong>
                <p>{{ item.detail }}</p>
              </div>
              <span class="badge" :class="item.tone">{{ item.meta }}</span>
            </li>
          </ul>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
.page-shell {
  --bg: #f5f7fb;
  --panel: rgba(255, 255, 255, 0.88);
  --panel-strong: #ffffff;
  --text: #1f2937;
  --muted: #64748b;
  --line: rgba(148, 163, 184, 0.18);
  --primary: #2563eb;
  --primary-soft: rgba(37, 99, 235, 0.1);
  --shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
  min-height: 100vh;
  background: var(--bg);
  color: var(--text);
}

.page-shell.dark {
  --bg: #0f172a;
  --panel: rgba(15, 23, 42, 0.8);
  --panel-strong: #111827;
  --text: #e2e8f0;
  --muted: #94a3b8;
  --line: rgba(148, 163, 184, 0.18);
  --primary: #60a5fa;
  --primary-soft: rgba(96, 165, 250, 0.12);
  --shadow: 0 20px 45px rgba(2, 6, 23, 0.4);
}

.profile-page {
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 20px 56px;
}

.profile-hero {
  margin-top: 24px;
}

.profile-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  background: linear-gradient(135deg, var(--panel-strong), var(--panel));
  border: 1px solid var(--line);
  border-radius: 28px;
  box-shadow: var(--shadow);
  padding: 28px;
}

.avatar-wrap {
  display: flex;
  align-items: center;
  gap: 18px;
}

.avatar {
  width: 86px;
  height: 86px;
  display: grid;
  place-items: center;
  border-radius: 24px;
  background: linear-gradient(135deg, #2563eb, #60a5fa);
  color: white;
  font-size: 2rem;
  font-weight: 800;
  box-shadow: 0 14px 35px rgba(37, 99, 235, 0.35);
}

.profile-meta h1 {
  margin: 8px 0 6px;
  font-size: clamp(2rem, 3vw, 2.8rem);
  letter-spacing: -0.05em;
}

.profile-meta p {
  margin: 0;
  font-size: 0.97rem;
  color: var(--muted);
}

.role-badge {
  display: inline-flex;
  align-items: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: var(--primary-soft);
  color: var(--primary);
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.edit-button {
  border: none;
  background: linear-gradient(135deg, #2563eb, #3b82f6);
  color: white;
  font-weight: 700;
  padding: 12px 18px;
  border-radius: 12px;
  cursor: pointer;
  box-shadow: 0 12px 28px rgba(37, 99, 235, 0.22);
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
  margin-top: 28px;
}

.stat-card {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 22px;
  padding: 22px 20px;
  box-shadow: var(--shadow);
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-label {
  color: var(--muted);
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}

.stat-value {
  font-size: clamp(1.7rem, 3vw, 2.4rem);
  letter-spacing: -0.05em;
}

.stat-card small {
  color: var(--muted);
  font-size: 0.82rem;
}

.content-grid {
  display: grid;
  grid-template-columns: 1.05fr 1.25fr;
  gap: 22px;
  margin-top: 28px;
}

.panel {
  background: var(--panel);
  border: 1px solid var(--line);
  border-radius: 24px;
  box-shadow: var(--shadow);
  padding: 22px 20px;
}

.panel-header {
  margin-bottom: 18px;
}

.panel-header h2 {
  margin: 0;
  font-size: 1.2rem;
  letter-spacing: -0.04em;
}

.info-list {
  display: grid;
  gap: 16px;
}

.info-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);
}

.info-item:last-child {
  border-bottom: none;
}

.info-item span {
  color: var(--muted);
}

.info-item strong {
  font-size: 0.95rem;
}

.activity-list {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 16px;
}

.activity-item {
  display: grid;
  grid-template-columns: 12px 1fr auto;
  align-items: flex-start;
  gap: 12px;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);
}

.activity-item:last-child {
  border-bottom: none;
}

.dot {
  width: 10px;
  height: 10px;
  margin-top: 8px;
  border-radius: 50%;
}

.dot.success {
  background: #22c55e;
}

.dot.info {
  background: #3b82f6;
}

.dot.warning {
  background: #f59e0b;
}

.activity-item strong {
  display: block;
  margin-bottom: 6px;
  font-size: 0.96rem;
}

.activity-item p {
  margin: 0;
  color: var(--muted);
  font-size: 0.88rem;
}

.badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 10px;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
}

.badge.success {
  background: rgba(34, 197, 94, 0.12);
  color: #22c55e;
}

.badge.info {
  background: rgba(59, 130, 246, 0.12);
  color: #3b82f6;
}

.badge.warning {
  background: rgba(245, 158, 11, 0.12);
  color: #d97706;
}

@media (max-width: 900px) {
  .profile-card {
    flex-direction: column;
    align-items: flex-start;
  }

  .content-grid,
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 600px) {
  .profile-page {
    padding-inline: 16px;
  }

  .profile-card {
    padding: 20px 18px;
  }

  .avatar {
    width: 72px;
    height: 72px;
    font-size: 1.6rem;
  }

  .info-item {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>

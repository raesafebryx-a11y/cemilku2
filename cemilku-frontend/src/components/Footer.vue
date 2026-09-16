<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const isDarkMode = ref(localStorage.getItem('theme') === 'dark')

const goTo = (name) => {
  router.push({ name })
}

const handleThemeChange = (event) => {
  if (typeof event.detail?.dark === 'boolean') {
    isDarkMode.value = event.detail.dark
  }
}

onMounted(() => {
  window.addEventListener('cemilku-theme-change', handleThemeChange)
})

onUnmounted(() => {
  window.removeEventListener('cemilku-theme-change', handleThemeChange)
})
</script>

<template>
  <footer
    class="footer"
    :class="{ 'theme-dark': isDarkMode, 'theme-light': !isDarkMode }"
  >
    <div class="footer-container">

      <!-- BRAND -->
      <div class="footer-brand">
        <div class="footer-logo">
          <div class="footer-logo-icon">
            🍿
          </div>

          <div>
            <h2>CEMILKU</h2>
            <span>Teman ngemil setiap saat</span>
          </div>
        </div>

        <p>
          Cemilan favorit dengan rasa yang bikin ketagihan.
          Temukan berbagai pilihan cemilan terbaik hanya di Cemilku.
        </p>
      </div>

      <!-- NAVIGASI -->
      <div class="footer-column">
        <h3>Navigasi</h3>

        <button @click="goTo('home')">
          Beranda
        </button>

        <button @click="goTo('products')">
          Produk
        </button>

        <button @click="goTo('tentang-kami')">
          Tentang Kami
        </button>

        <button @click="goTo('kontak')">
          Kontak
        </button>
      </div>

      <!-- INFORMASI -->
      <div class="footer-column">
        <h3>Informasi</h3>

        <button @click="goTo('products')">
          Semua Produk
        </button>

        <button @click="goTo('kontak')">
          Hubungi Kami
        </button>
      </div>

      <!-- KONTAK -->
      <div class="footer-column">
        <h3>Hubungi Kami</h3>

        <p>📍 Indonesia</p>
        <p>📞 +62 812-3456-7890</p>
        <p>✉️ cemilku@gmail.com</p>
      </div>

    </div>

    <!-- BOTTOM -->
    <div class="footer-bottom">
      <div>
        © {{ new Date().getFullYear() }} <strong>CEMILKU</strong>.
        Semua hak dilindungi.
      </div>

      <div class="footer-social">
        <a href="#" aria-label="Instagram">📷</a>
        <a href="#" aria-label="Facebook">📘</a>
        <a href="#" aria-label="TikTok">🎵</a>
      </div>
    </div>
  </footer>
</template>

<style scoped>
.footer {
  margin-top: 0;
  background: #f8fafc;
  color: #0f172a;
  transition: background 0.25s ease, color 0.25s ease;
}

.footer.theme-dark {
  background: #0f172a;
  color: #cbd5e1;
}

.footer.theme-light {
  background: #f8fafc;
  color: #0f172a;
}

.footer-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 60px 24px 45px;

  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1.3fr;
  gap: 50px;
}

.footer-brand {
  max-width: 350px;
}

.footer-logo {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 20px;
}

.footer-logo-icon {
  width: 48px;
  height: 48px;

  display: flex;
  align-items: center;
  justify-content: center;

  background: #2563eb;
  border-radius: 14px;

  font-size: 24px;
}

.footer-logo h2 {
  margin: 0;
  color: inherit;
  font-size: 22px;
  font-weight: 800;
}

.footer-logo span {
  display: block;
  margin-top: 3px;

  color: inherit;
  opacity: 0.75;
  font-size: 12px;
}

.footer-brand p {
  margin: 0;

  color: inherit;
  opacity: 0.8;
  font-size: 14px;
  line-height: 1.8;
}

.footer-column {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}

.footer-column h3 {
  margin: 0 0 20px;

  color: inherit;
  font-size: 16px;
  font-weight: 700;
}

.footer-column button {
  padding: 0;
  margin-bottom: 12px;

  border: none;
  background: none;

  color: inherit;
  opacity: 0.8;
  font-size: 14px;

  cursor: pointer;
  transition: 0.2s ease;
}

.footer-column button:hover {
  color: #60a5fa;
  transform: translateX(3px);
}

.footer-column p {
  margin: 0 0 12px;

  color: inherit;
  opacity: 0.8;
  font-size: 14px;
  line-height: 1.5;
}

.footer-bottom {
  max-width: 1200px;
  margin: 0 auto;

  padding: 22px 24px;

  border-top: none;

  display: flex;
  align-items: center;
  justify-content: space-between;

  color: inherit;
  opacity: 0.8;
  font-size: 13px;
}

.footer-bottom strong {
  color: #60a5fa;
}

.footer-social {
  display: flex;
  gap: 10px;
}

.footer-social a {
  width: 36px;
  height: 36px;

  display: flex;
  align-items: center;
  justify-content: center;

  border-radius: 10px;
  background: rgba(148, 163, 184, 0.15);

  text-decoration: none;
  font-size: 16px;

  transition: 0.2s ease;
}

.footer-social a:hover {
  background: #2563eb;
  transform: translateY(-2px);
}

@media (max-width: 900px) {
  .footer-container {
    grid-template-columns: 1fr 1fr;
    gap: 40px;
  }

  .footer-brand {
    max-width: none;
  }
}

@media (max-width: 600px) {
  .footer-container {
    grid-template-columns: 1fr;
    gap: 32px;
    padding: 45px 20px 35px;
  }

  .footer-bottom {
    flex-direction: column;
    gap: 18px;
    text-align: center;
  }
}
</style>
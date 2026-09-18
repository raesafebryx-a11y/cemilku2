// =====================================================
// ORDER STATUS
// Mapping status pesanan + payment dipakai bersama
// oleh halaman detail order, daftar order, dan admin.
// =====================================================

// Status pesanan yang berarti "sudah diproses penjual"
export const PROCESSING_STATUSES = ["processing", "shipped", "completed"];

// Status pesanan yang berarti "sudah dikirim"
export const SHIPPED_STATUSES = ["shipped", "completed"];

export const ORDER_STATUSES = {
  pending: {
    label: "Menunggu Pembayaran",
    icon: "⏳",
    class: "status-pending",
  },

  processing: {
    label: "Sedang Diproses",
    icon: "📦",
    class: "status-processing",
  },

  shipped: {
    label: "Sedang Dikirim",
    icon: "🚚",
    class: "status-shipped",
  },

  completed: {
    label: "Selesai",
    icon: "✅",
    class: "status-completed",
  },

  cancelled: {
    label: "Dibatalkan",
    icon: "❌",
    class: "status-cancelled",
  },
};

export const PAYMENT_STATUSES = {
  pending: {
    label: "Menunggu Pembayaran",
    class: "payment-pending",
  },

  waiting_verification: {
    label: "Menunggu Verifikasi",
    class: "payment-waiting",
  },

  paid: {
    label: "✅ Pembayaran Berhasil",
    class: "payment-paid",
  },

  settlement: {
    label: "✅ Pembayaran Berhasil",
    class: "payment-paid",
  },

  verified: {
    label: "✅ Pembayaran Terverifikasi",
    class: "payment-paid",
  },

  failed: {
    label: "Pembayaran Gagal",
    class: "payment-rejected",
  },

  rejected: {
    label: "Pembayaran Ditolak",
    class: "payment-rejected",
  },
};

const ORDER_STATUS_FALLBACK = (status) => ({
  label: status || "Menunggu",
  icon: "📋",
  class: "status-pending",
});

const PAYMENT_STATUS_FALLBACK = (status) => ({
  label: status || "Menunggu",
  class: "payment-pending",
});

// =====================================================
// INFO LOOKUPS
// =====================================================

export const getOrderStatusInfo = (status) => {
  return ORDER_STATUSES[status] || ORDER_STATUS_FALLBACK(status);
};

export const getPaymentStatusInfo = (status) => {
  return PAYMENT_STATUSES[status] || PAYMENT_STATUS_FALLBACK(status);
};

// =====================================================
// PREDICATES
// =====================================================

export const isPending = (status) => status === "pending";

export const isProcessing = (status) => {
  return PROCESSING_STATUSES.includes(status);
};

export const isShipped = (status) => {
  return SHIPPED_STATUSES.includes(status);
};

export const isCompleted = (status) => status === "completed";

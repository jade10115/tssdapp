<template>
  <div class="dash-wrap">
    <!-- Header -->
    <div class="dash-header">
      <div class="brand">
        <div class="brand-logos">
          <img src="/img/dole.png" alt="DOLE" />
          <img src="/img/bagongphlogo.png" alt="Bagong Pilipinas" />
        </div>
        <div class="brand-text">
          <h1>Dashboard</h1>
          <p class="muted">TUPAD + Supply Monitoring (OO1 / OO2 / OO3)</p>
        </div>
      </div>

      <div class="header-actions">
        <button class="btn btn-light" @click="refresh">
          <span class="dot" :class="{ live: !loading }"></span>
          Refresh
        </button>
      </div>
    </div>

    <!-- Loading / Error -->
    <div v-if="loading" class="notice info">
      Loading dashboard data…
    </div>
    <div v-else-if="error" class="notice danger">
      {{ error }}
    </div>

    <template v-else>
      <!-- SECTION: OVERALL -->
      <section class="section">
        <div class="section-title">
          <h2>Overall (TUPAD)</h2>
          <span class="pill">All Offices</span>
        </div>

        <div class="grid cards-4">
          <div class="card">
            <div class="card-label">Total Records</div>
            <div class="card-value">{{ fmtInt(dash.overall.total_records) }}</div>
          </div>

          <div class="card">
            <div class="card-label">Total Amount</div>
            <div class="card-value">{{ fmtMoney(dash.overall.total_amount) }}</div>
          </div>

          <div class="card">
            <div class="card-label">Total Balance</div>
            <div class="card-value">{{ fmtMoney(dash.overall.total_balance) }}</div>
          </div>

          <div class="card accent">
            <div class="card-label">Utilization</div>
            <div class="card-value">{{ fmtPct(dash.overall.utilization) }}</div>
            <div class="progress">
              <div class="bar" :style="{ width: safePct(dash.overall.utilization) + '%' }"></div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION: OO1 OO2 OO3 -->
      <section class="section">
        <div class="section-title">
          <h2>By Office (TUPAD)</h2>
          <span class="pill">OO1 • OO2 • OO3</span>
        </div>

        <div class="grid cards-3">
          <div class="panel">
            <div class="panel-head">
              <h3>OO1</h3>
              <span class="mini-pill">TUPAD</span>
            </div>
            <div class="panel-body">
              <div class="row">
                <span class="k">Records</span>
                <span class="v">{{ fmtInt(dash.oo1.total_records) }}</span>
              </div>
              <div class="row">
                <span class="k">Amount</span>
                <span class="v">{{ fmtMoney(dash.oo1.total_amount) }}</span>
              </div>
              <div class="row">
                <span class="k">Balance</span>
                <span class="v">{{ fmtMoney(dash.oo1.total_balance) }}</span>
              </div>
              <div class="row">
                <span class="k">Utilization</span>
                <span class="v strong">{{ fmtPct(dash.oo1.utilization) }}</span>
              </div>

              <div class="progress sm">
                <div class="bar" :style="{ width: safePct(dash.oo1.utilization) + '%' }"></div>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head">
              <h3>OO2</h3>
              <span class="mini-pill">TUPAD</span>
            </div>
            <div class="panel-body">
              <div class="row">
                <span class="k">Records</span>
                <span class="v">{{ fmtInt(dash.oo2.total_records) }}</span>
              </div>
              <div class="row">
                <span class="k">Amount</span>
                <span class="v">{{ fmtMoney(dash.oo2.total_amount) }}</span>
              </div>
              <div class="row">
                <span class="k">Balance</span>
                <span class="v">{{ fmtMoney(dash.oo2.total_balance) }}</span>
              </div>
              <div class="row">
                <span class="k">Utilization</span>
                <span class="v strong">{{ fmtPct(dash.oo2.utilization) }}</span>
              </div>

              <div class="progress sm">
                <div class="bar" :style="{ width: safePct(dash.oo2.utilization) + '%' }"></div>
              </div>
            </div>
          </div>

          <div class="panel">
            <div class="panel-head">
              <h3>OO3</h3>
              <span class="mini-pill">TUPAD</span>
            </div>
            <div class="panel-body">
              <div class="row">
                <span class="k">Records</span>
                <span class="v">{{ fmtInt(dash.oo3.total_records) }}</span>
              </div>
              <div class="row">
                <span class="k">Amount</span>
                <span class="v">{{ fmtMoney(dash.oo3.total_amount) }}</span>
              </div>
              <div class="row">
                <span class="k">Balance</span>
                <span class="v">{{ fmtMoney(dash.oo3.total_balance) }}</span>
              </div>
              <div class="row">
                <span class="k">Utilization</span>
                <span class="v strong">{{ fmtPct(dash.oo3.utilization) }}</span>
              </div>

              <div class="progress sm">
                <div class="bar" :style="{ width: safePct(dash.oo3.utilization) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- SECTION: SUPPLY -->
      <section class="section">
        <div class="section-title">
          <h2>Supply (Inventory)</h2>
          <span class="pill">tbl_products</span>
        </div>

        <div class="grid cards-4">
          <div class="card">
            <div class="card-label">Total Products</div>
            <div class="card-value">{{ fmtInt(dash.supply.total_products) }}</div>
          </div>

          <div class="card warn">
            <div class="card-label">Low Stock (1–10)</div>
            <div class="card-value">{{ fmtInt(dash.supply.low_stock) }}</div>
          </div>

          <div class="card danger">
            <div class="card-label">Out of Stock (≤ 0)</div>
            <div class="card-value">{{ fmtInt(dash.supply.out_of_stock) }}</div>
          </div>

          <div class="card">
            <div class="card-label">Total Stock</div>
            <div class="card-value">{{ fmtInt(dash.supply.total_stock) }}</div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import axios from "axios";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");


const loading = ref(false);
const error = ref("");
const dash = ref({
  overall: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  oo1: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  oo2: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  oo3: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  supply: { total_products: 0, low_stock: 0, out_of_stock: 0, total_stock: 0 },
});

const safePct = (v) => {
  const n = Number(v || 0);
  if (!Number.isFinite(n)) return 0;
  return Math.max(0, Math.min(100, n));
};

const fmtInt = (v) => Number(v || 0).toLocaleString();

const fmtMoney = (v) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(v || 0));

const fmtPct = (v) => `${safePct(v).toFixed(2)}%`;

const fetchDashboard = async () => {
  loading.value = true;
  error.value = "";

  try {
    const res = await axios.get(`${API_BASE}/dashboard`);
    dash.value = res.data || dash.value;
  } catch (e) {
    console.error(e);
    error.value = e?.response?.data?.message || "Failed to load dashboard. Please check API and controller.";
  } finally {
    loading.value = false;
  }
};

const refresh = async () => {
  Swal.fire({
    title: "Refreshing…",
    text: "Please wait while the dashboard updates.",
    allowOutsideClick: false,
    showConfirmButton: false,
    didOpen: () => Swal.showLoading(),
  });

  await fetchDashboard();
  Swal.close();
};

onMounted(fetchDashboard);
</script>

<style scoped>
.dash-wrap {
  max-width: 1400px;
  margin: 18px auto;
  padding: 0 18px 26px;
  font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
  color: #0f172a;
}

/* Header */
.dash-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  padding: 16px 18px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.06);
}

.brand {
  display: flex;
  align-items: center;
  gap: 14px;
}

.brand-logos {
  display: flex;
  gap: 10px;
  align-items: center;
}

.brand-logos img {
  width: 52px;
  height: 52px;
  object-fit: contain;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  padding: 6px;
}

.brand-text h1 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.muted {
  margin: 4px 0 0;
  color: #64748b;
  font-weight: 600;
  font-size: 12px;
}

.header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Notices */
.notice {
  margin-top: 14px;
  border-radius: 12px;
  padding: 12px 14px;
  border: 1px solid;
  font-weight: 700;
}
.notice.info {
  background: #eff6ff;
  border-color: #bfdbfe;
  color: #1e3a8a;
}
.notice.danger {
  background: #fef2f2;
  border-color: #fecaca;
  color: #991b1b;
}

/* Sections */
.section {
  margin-top: 18px;
  padding: 16px 18px;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  background: #ffffff;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}

.section-title {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 10px;
  margin-bottom: 12px;
}

.section-title h2 {
  margin: 0;
  font-size: 16px;
  font-weight: 900;
  letter-spacing: -0.01em;
}

.pill {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  color: #334155;
  font-weight: 800;
  font-size: 12px;
  padding: 6px 10px;
  border-radius: 999px;
}

/* Grid */
.grid {
  display: grid;
  gap: 14px;
}
.cards-4 {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}
.cards-3 {
  grid-template-columns: repeat(3, minmax(0, 1fr));
}

@media (max-width: 1020px) {
  .cards-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .cards-3 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
}

/* Cards */
.card {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 14px 14px;
  background: #ffffff;
}
.card.accent {
  border-color: #c7d2fe;
  background: linear-gradient(180deg, #ffffff, #eef2ff);
}
.card.warn {
  border-color: #fde68a;
  background: linear-gradient(180deg, #ffffff, #fffbeb);
}
.card.danger {
  border-color: #fecaca;
  background: linear-gradient(180deg, #ffffff, #fef2f2);
}

.card-label {
  color: #64748b;
  font-weight: 800;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.card-value {
  margin-top: 8px;
  font-size: 22px;
  font-weight: 900;
  color: #0f172a;
}

/* Panels (OO1/OO2/OO3) */
.panel {
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  overflow: hidden;
  background: #fff;
}

.panel-head {
  padding: 12px 14px;
  border-bottom: 1px solid #e5e7eb;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8fafc;
}

.panel-head h3 {
  margin: 0;
  font-size: 14px;
  font-weight: 900;
  color: #0f172a;
}

.mini-pill {
  font-size: 11px;
  font-weight: 900;
  padding: 5px 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
}

.panel-body {
  padding: 12px 14px;
}

.row {
  display: flex;
  justify-content: space-between;
  padding: 8px 0;
  border-bottom: 1px dashed #e5e7eb;
}
.row:last-child {
  border-bottom: none;
}

.k {
  color: #64748b;
  font-weight: 800;
}
.v {
  font-weight: 900;
  color: #0f172a;
}
.v.strong {
  color: #1d4ed8;
}

/* Progress */
.progress {
  margin-top: 10px;
  height: 10px;
  background: #e5e7eb;
  border-radius: 999px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
}
.progress.sm {
  height: 9px;
}
.bar {
  height: 100%;
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  width: 0%;
}

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  cursor: pointer;
  font-weight: 800;
  background: #fff;
  color: #0f172a;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 18px rgba(2, 6, 23, 0.08);
}

.btn-light {
  background: #f8fafc;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #94a3b8;
}
.dot.live {
  background: #22c55e;
}
</style>

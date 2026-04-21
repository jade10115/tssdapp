<template>
  <div class="dashboard-container">
    <!-- Header with Year Selector & Refresh -->
    <div class="dashboard-header">
      <div class="header-left">
        <div class="brand">
          <div class="brand-logos">
            <img src="/img/dole.png" alt="DOLE" />
            <img src="/img/bagongphlogo.png" alt="Bagong Pilipinas" />
          </div>
          <div class="brand-text">
            <h1>Dashboard</h1>
            <p>TUPAD + Supply Monitoring (OO1 / OO2 / OO3)</p>
          </div>
        </div>
      </div>

      <div class="header-right">
        <div class="year-selector">
          <label for="yearFilter">Year</label>
          <select id="yearFilter" v-model="selectedYear" @change="fetchDashboard">
            <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>
        <button class="btn-refresh" @click="refresh" :disabled="loading">
          <svg class="refresh-icon" :class="{ spinning: loading }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          {{ loading ? 'Loading...' : 'Refresh' }}
        </button>
      </div>
    </div>

    <!-- Loading Skeleton -->
    <div v-if="loading" class="skeleton-grid">
      <div class="skeleton-card" v-for="i in 10" :key="i"></div>
      <div class="skeleton-panel" v-for="i in 3" :key="'panel'+i"></div>
      <div class="skeleton-card" v-for="i in 4" :key="'supply'+i"></div>
    </div>

    <!-- Error Message -->
    <div v-else-if="error" class="error-message">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" />
        <line x1="12" y1="8" x2="12" y2="12" />
        <line x1="12" y1="16" x2="12.01" y2="16" />
      </svg>
      <span>{{ error }}</span>
    </div>

    <template v-else>
      <!-- Last updated info -->
      <div class="last-updated">Last updated: {{ lastUpdated }}</div>

      <!-- Overall TUPAD Section -->
      <section class="section">
        <div class="section-header">
          <h2>Overall TUPAD</h2>
          <span class="badge">All Offices · {{ selectedYear }}</span>
        </div>
        <div class="stats-grid">
          <div class="stat-card animate-fadein">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
              <div class="stat-label">Total Records</div>
              <div class="stat-value">{{ fmtInt(dash.overall.total_records) }}</div>
            </div>
          </div>

          <div class="stat-card animate-fadein" style="animation-delay: 0.1s">
            <div class="stat-icon peso">₱</div>
            <div class="stat-content">
              <div class="stat-label">Total Allocation</div>
              <div class="stat-value">{{ fmtMoney(dash.overall.total_allocation) }}</div>
            </div>
          </div>

          <div class="stat-card animate-fadein spent" style="animation-delay: 0.2s">
            <div class="stat-icon">💸</div>
            <div class="stat-content">
              <div class="stat-label">Total Spent</div>
              <div class="stat-value">{{ fmtMoney(dash.overall.total_spent) }}</div>
            </div>
          </div>

          <div class="stat-card animate-fadein" style="animation-delay: 0.3s">
            <div class="stat-icon">🏦</div>
            <div class="stat-content">
              <div class="stat-label">Remaining Balance ({{ selectedYear }})</div>
              <div class="stat-value">{{ fmtMoney(dash.overall.total_balance) }}</div>
            </div>
          </div>

          <div class="stat-card animate-fadein" style="animation-delay: 0.4s">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
              <div class="stat-label">Total Beneficiaries</div>
              <div class="stat-value">{{ fmtInt(dash.overall.total_beneficiaries) }}</div>
            </div>
          </div>

          <div class="stat-card highlight animate-fadein" style="animation-delay: 0.5s">
            <div class="stat-icon">📈</div>
            <div class="stat-content">
              <div class="stat-label">Utilization Rate</div>
              <div class="stat-value">{{ fmtPct(dash.overall.utilization) }}</div>
              <div class="progress-bar">
                <div class="progress-fill" :style="{ width: safePct(dash.overall.utilization) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Beneficiary Demographics Section -->
      <section class="section">
        <div class="section-header">
          <h2>Beneficiary Demographics</h2>
          <span class="badge">{{ selectedYear }}</span>
        </div>
        <div class="demographics-grid">
          <!-- Status Cards -->
          <div class="stat-card animate-fadein" style="animation-delay: 0.6s">
            <div class="stat-icon">📋</div>
            <div class="stat-content">
              <div class="stat-label">4Ps Beneficiaries</div>
              <div class="stat-value">{{ fmtInt(dash.overall.four_ps_beneficiaries) }}</div>
            </div>
          </div>
          <div class="stat-card animate-fadein" style="animation-delay: 0.7s">
            <div class="stat-icon">🩼</div>
            <div class="stat-content">
              <div class="stat-label">PWD Beneficiaries</div>
              <div class="stat-value">{{ fmtInt(dash.overall.pwd_beneficiaries) }}</div>
            </div>
          </div>
          <div class="stat-card animate-fadein" style="animation-delay: 0.8s">
            <div class="stat-icon">👴</div>
            <div class="stat-content">
              <div class="stat-label">Senior Citizens</div>
              <div class="stat-value">{{ fmtInt(dash.overall.senior_beneficiaries) }}</div>
            </div>
          </div>
          <!-- Pie Chart (CSS conic-gradient) -->
          <div class="chart-card animate-fadein" style="animation-delay: 0.9s">
            <div class="chart-title">Gender Distribution</div>
            <div class="pie-chart" :style="pieStyle"></div>
            <div class="chart-legend">
              <div><span class="legend-dot female"></span> Female: {{ fmtInt(dash.overall.female_beneficiaries) }}</div>
              <div><span class="legend-dot male"></span> Male: {{ fmtInt(dash.overall.male_beneficiaries) }}</div>
            </div>
          </div>
        </div>
      </section>

      <!-- By Office (OO1, OO2, OO3) -->
      <section class="section">
        <div class="section-header">
          <h2>By Office (TUPAD)</h2>
          <span class="badge">OO1 · OO2 · OO3</span>
        </div>
        <div class="offices-grid">
          <div v-for="office in ['oo1', 'oo2', 'oo3']" :key="office" class="office-card animate-slideup">
            <div class="office-header">
              <h3>{{ office.toUpperCase() }}</h3>
              <span class="office-badge">TUPAD</span>
            </div>
            <div class="office-stats">
              <div class="stat-row">
                <span>Records</span>
                <strong>{{ fmtInt(dash[office].total_records) }}</strong>
              </div>
              <div class="stat-row">
                <span>Amount</span>
                <strong>{{ fmtMoney(dash[office].total_amount) }}</strong>
              </div>
              <div class="stat-row">
                <span>Balance</span>
                <strong>{{ fmtMoney(dash[office].total_balance) }}</strong>
              </div>
              <div class="stat-row">
                <span>Utilization</span>
                <strong class="util-value">{{ fmtPct(dash[office].utilization) }}</strong>
              </div>
              <div class="progress-bar mini">
                <div class="progress-fill" :style="{ width: safePct(dash[office].utilization) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Supply Section -->
      <section class="section">
        <div class="section-header">
          <h2>Supply Inventory</h2>
          <span class="badge">tbl_products</span>
        </div>
        <div class="stats-grid">
          <div class="stat-card animate-fadein">
            <div class="stat-icon">📦</div>
            <div class="stat-content">
              <div class="stat-label">Total Products</div>
              <div class="stat-value">{{ fmtInt(dash.supply.total_products) }}</div>
            </div>
          </div>
          <div class="stat-card warning animate-fadein" style="animation-delay: 0.1s">
            <div class="stat-icon">⚠️</div>
            <div class="stat-content">
              <div class="stat-label">Low Stock (1-10)</div>
              <div class="stat-value">{{ fmtInt(dash.supply.low_stock) }}</div>
            </div>
          </div>
          <div class="stat-card danger animate-fadein" style="animation-delay: 0.2s">
            <div class="stat-icon">🚫</div>
            <div class="stat-content">
              <div class="stat-label">Out of Stock</div>
              <div class="stat-value">{{ fmtInt(dash.supply.out_of_stock) }}</div>
            </div>
          </div>
          <div class="stat-card animate-fadein" style="animation-delay: 0.3s">
            <div class="stat-icon">🔢</div>
            <div class="stat-content">
              <div class="stat-label">Total Stock (units)</div>
              <div class="stat-value">{{ fmtInt(dash.supply.total_stock) }}</div>
            </div>
          </div>
        </div>
      </section>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, watch, computed } from "vue";
import axios from "axios";
import { inject } from "vue";

const API_BASE = inject("API_BASE");


const loading = ref(false);
const error = ref("");
const dash = ref({
  overall: {
    total_records: 0,
    total_allocation: 0,
    total_remaining_budget: 0,
    total_balance: 0,
    total_spent: 0,
    total_beneficiaries: 0,
    utilization: 0,
    total_beneficiaries_real: 0,
    female_beneficiaries: 0,
    male_beneficiaries: 0,
    four_ps_beneficiaries: 0,
    pwd_beneficiaries: 0,
    senior_beneficiaries: 0,
  },
  oo1: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  oo2: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  oo3: { total_records: 0, total_amount: 0, total_balance: 0, utilization: 0 },
  supply: { total_products: 0, low_stock: 0, out_of_stock: 0, total_stock: 0 },
});

const selectedYear = ref(new Date().getFullYear());
const availableYears = ref([2022, 2023, 2024, 2025, 2026]);
const lastUpdated = ref("");

// Helpers
const safePct = (v) => {
  const n = Number(v || 0);
  return Math.min(100, Math.max(0, isNaN(n) ? 0 : n));
};
const fmtInt = (v) => Number(v || 0).toLocaleString();
const fmtMoney = (v) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(v || 0));
const fmtPct = (v) => `${safePct(v).toFixed(2)}%`;

// Pie chart style using CSS conic-gradient
const pieStyle = computed(() => {
  const female = dash.value.overall.female_beneficiaries || 0;
  const male = dash.value.overall.male_beneficiaries || 0;
  const total = female + male;
  if (total === 0) {
    return { background: "#e2e8f0" }; // gray for no data
  }
  const femalePercent = (female / total) * 100;
  const malePercent = (male / total) * 100;
  return {
    background: `conic-gradient(#f59e0b 0% ${femalePercent}%, #3b82f6 ${femalePercent}% 100%)`,
  };
});

// Fetch data
const fetchDashboard = async () => {
  loading.value = true;
  error.value = "";
  try {
    const res = await axios.get(`${API_BASE}/dashboard`, {
      params: { year: selectedYear.value },
    });
    dash.value = res.data;
    lastUpdated.value = new Date().toLocaleString();
  } catch (err) {
    console.error(err);
    error.value = err.response?.data?.message || "Failed to load dashboard.";
  } finally {
    loading.value = false;
  }
};

// Refresh
const refresh = async () => {
  await fetchDashboard();
};

watch(selectedYear, () => fetchDashboard());
onMounted(() => {
  fetchDashboard();
});
</script>

<style scoped>
/* ========== Global ========== */
.dashboard-container {
  padding: 1.5rem;
  font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  background: #f8fafc;
  min-height: 100vh;
}

/* Header */
.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
  margin-bottom: 1.5rem;
  padding: 1rem 1.5rem;
  background: #fff;
  border-radius: 1.5rem;
  box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.brand {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.brand-logos {
  display: flex;
  gap: 0.75rem;
}

.brand-logos img {
  width: 48px;
  height: 48px;
  object-fit: contain;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 6px;
  transition: transform 0.2s ease;
}
.brand-logos img:hover {
  transform: scale(1.05);
}

.brand-text h1 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
}

.brand-text p {
  margin: 0.25rem 0 0;
  font-size: 0.75rem;
  color: #64748b;
  font-weight: 500;
}

.header-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.year-selector {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: #f1f5f9;
  padding: 0.25rem 0.75rem;
  border-radius: 2rem;
  border: 1px solid #e2e8f0;
  transition: all 0.2s;
}
.year-selector:hover {
  background: #eef2ff;
  border-color: #cbd5e1;
}

.year-selector label {
  font-weight: 600;
  font-size: 0.75rem;
  color: #475569;
}

.year-selector select {
  background: transparent;
  border: none;
  font-weight: 600;
  font-size: 0.875rem;
  padding: 0.25rem;
  cursor: pointer;
  outline: none;
  color: #0f172a;
}

.btn-refresh {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 2rem;
  padding: 0.5rem 1rem;
  font-weight: 600;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s;
  color: #334155;
}

.btn-refresh:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
}

.btn-refresh:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.refresh-icon {
  width: 1rem;
  height: 1rem;
}

.spinning {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Last updated */
.last-updated {
  text-align: right;
  font-size: 0.75rem;
  color: #94a3b8;
  margin-bottom: 1rem;
}

/* Sections */
.section {
  background: #fff;
  border-radius: 1.5rem;
  padding: 1.5rem;
  margin-bottom: 2rem;
  box-shadow: 0 4px 6px -2px rgba(0, 0, 0, 0.05), 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  transition: box-shadow 0.2s;
}
.section:hover {
  box-shadow: 0 12px 20px -12px rgba(0, 0, 0, 0.15);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.25rem;
}

.section-header h2 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 800;
  color: #0f172a;
}

.badge {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: 0.25rem 0.75rem;
  font-size: 0.75rem;
  font-weight: 600;
  color: #475569;
}

/* Grids */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.25rem;
}

.demographics-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.25rem;
}

.offices-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.25rem;
}

/* Cards */
.stat-card, .chart-card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 1rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
  box-shadow: 0 1px 2px rgba(0,0,0,0.02);
}
.stat-card:hover, .chart-card:hover {
  transform: translateY(-4px) scale(1.01);
  box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1);
  border-color: #cbd5e1;
}
.chart-card {
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 0.75rem;
}
.chart-title {
  font-size: 0.875rem;
  font-weight: 700;
  color: #334155;
}
.chart-legend {
  display: flex;
  gap: 1rem;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
}
.legend-dot {
  display: inline-block;
  width: 10px;
  height: 10px;
  border-radius: 50%;
  margin-right: 4px;
}
.legend-dot.female { background: #f59e0b; }
.legend-dot.male { background: #3b82f6; }

/* Pie chart */
.pie-chart {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  margin: 0 auto;
  background: #e2e8f0;
  transition: background 0.3s ease;
}

.stat-card.highlight {
  background: linear-gradient(135deg, #ffffff 0%, #fef9e6 100%);
  border-color: #fde68a;
}

.stat-card.spent {
  background: linear-gradient(135deg, #ffffff 0%, #eff6ff 100%);
  border-color: #bfdbfe;
}

.stat-card.warning {
  background: #fffbeb;
  border-color: #fde68a;
}

.stat-card.danger {
  background: #fef2f2;
  border-color: #fecaca;
}

.stat-icon {
  font-size: 2rem;
  width: 3rem;
  height: 3rem;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #f8fafc;
  border-radius: 1rem;
  border: 1px solid #eef2f7;
  transition: all 0.2s;
}
.stat-icon.peso {
  font-size: 2.2rem;
  font-weight: 800;
  color: #0f172a;
}
.stat-card:hover .stat-icon {
  transform: scale(1.05);
  background: #fff;
}

.stat-content {
  flex: 1;
}

.stat-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #64748b;
  margin-bottom: 0.25rem;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.2;
}

.progress-bar {
  margin-top: 0.5rem;
  height: 6px;
  background: #e2e8f0;
  border-radius: 3px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  width: 0;
  border-radius: 3px;
  transition: width 0.4s ease;
}

/* Office Cards */
.office-card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 1rem;
  overflow: hidden;
  transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
}

.office-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 25px -12px rgba(0,0,0,0.1);
}

.office-header {
  background: #f8fafc;
  padding: 1rem;
  border-bottom: 1px solid #eef2f7;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.office-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 800;
}

.office-badge {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  padding: 0.25rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 600;
}

.office-stats {
  padding: 1rem;
}

.stat-row {
  display: flex;
  justify-content: space-between;
  align-items: baseline;
  margin-bottom: 0.75rem;
  font-size: 0.875rem;
  color: #475569;
}

.stat-row strong {
  font-weight: 800;
  color: #0f172a;
}

.util-value {
  color: #2563eb;
}

.progress-bar.mini {
  height: 4px;
  margin-top: 0.75rem;
}

/* Animations */
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(15px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.animate-fadein {
  animation: fadeInUp 0.5s ease forwards;
  opacity: 0;
}

.animate-slideup {
  animation: slideUp 0.5s ease forwards;
  opacity: 0;
}

/* Skeleton */
.skeleton-grid {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.skeleton-card, .skeleton-panel {
  background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
  background-size: 200% 100%;
  animation: shimmer 1.2s infinite linear;
  border-radius: 1rem;
  height: 100px;
}
.skeleton-panel {
  height: 180px;
}
@keyframes shimmer {
  0% { background-position: -200% 0; }
  100% { background-position: 200% 0; }
}

/* Error */
.error-message {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 1rem;
  padding: 1rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #991b1b;
  margin: 1rem 0;
}
.error-message svg {
  width: 1.5rem;
  height: 1.5rem;
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-container {
    padding: 1rem;
  }
  .dashboard-header {
    flex-direction: column;
    align-items: stretch;
  }
  .header-right {
    justify-content: space-between;
  }
  .stats-grid, .demographics-grid, .offices-grid {
    grid-template-columns: 1fr;
  }
  .stat-value {
    font-size: 1.25rem;
  }
  .stat-icon {
    font-size: 1.5rem;
    width: 2.5rem;
    height: 2.5rem;
  }
  .pie-chart {
    width: 120px;
    height: 120px;
  }
}
</style>
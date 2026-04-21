<template>
  <div class="main-content">
    <!-- HEADER -->
    <div class="page-header">
      <div class="head-left">
        <div class="header-content">
          <div class="header-icon">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"/>
            </svg>
          </div>
          <div>
            <h1>TUPAD</h1>
            <p class="sub">Division overview and budget allocation</p>
          </div>
        </div>
      </div>

      <div class="controls">
        <div class="control-group">
          <label class="control">
            <span class="control-label">Year</span>
            <select v-model="selectedYear" @change="refreshAll" class="control-input">
              <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
            </select>
          </label>

          <label class="control search">
            <span class="control-label">Search</span>
            <div class="search-wrapper">
              <svg class="search-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/>
                <path d="m21 21-4.35-4.35"/>
              </svg>
              <input 
                v-model="search" 
                placeholder="Search division..." 
                class="control-input search-input"
              />
            </div>
          </label>
        </div>

        <button class="btn btn-primary" @click="refreshAll" :disabled="loading">
          <svg v-if="loading" class="spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 12a9 9 0 11-6.219-8.56"/>
          </svg>
          <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <polyline points="23 4 23 10 17 10"/>
            <path d="M20.49 15a9 9 0 11-2.12-9.36L23 10"/>
          </svg>
          {{ loading ? 'Refreshing...' : 'Refresh' }}
        </button>
      </div>
    </div>

    <!-- TOP 4 CARDS (OVERALL TOTALS) -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon adl">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
              <circle cx="8.5" cy="7" r="4"/>
              <line x1="20" y1="8" x2="20" y2="14"/>
              <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
          </div>
          <span class="stat-label">Total ADLs</span>
        </div>
        <div class="stat-value">{{ formatNumber(totals.total_adls) }}</div>
        <div class="stat-sub">Year: {{ selectedYear }}</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon allocated">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <line x1="12" y1="1" x2="12" y2="23"/>
              <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </div>
          <span class="stat-label">Total Allocated</span>
        </div>
        <div class="stat-value">{{ formatMoney(totals.total_allocated) }}</div>
        <div class="stat-sub">Sum of all divisions</div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon spent">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M21 12a9 9 0 11-9 9c2.52 0 4.93-1 6.74-2.74L21 8"/>
            </svg>
          </div>
          <span class="stat-label">Total Spent</span>
        </div>
        <div class="stat-value">{{ formatMoney(totals.total_spent) }}</div>
        <div class="stat-progress">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: `${totals.percent_used}%` }"></div>
          </div>
          <span class="progress-text">{{ totals.percent_used.toFixed(1) }}%</span>
        </div>
      </div>

      <div class="stat-card">
        <div class="stat-header">
          <div class="stat-icon balance">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
          </div>
          <span class="stat-label">Total Balance</span>
        </div>
        <div class="stat-value">{{ formatMoney(totals.total_balance) }}</div>
        <div class="stat-sub">Remaining funds</div>
      </div>
    </div>

    <!-- TABLE -->
    <div class="table-card">
      <div class="table-top">
        <div class="table-title-group">
          <h2 class="table-title">Divisions</h2>
          <span class="table-meta">
            Showing <strong>{{ filteredDivisions.length }}</strong> of 
            <strong>{{ divisions.length }}</strong> divisions
          </span>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="tupad-table">
          <thead>
            <tr>
              <th class="col-n">#</th>
              <th class="col-division">Division</th>
              <th class="col-num right">ADLs</th>
              <th class="col-num right">Allocated</th>
              <th class="col-num right">Spent</th>
              <th class="col-num right">Balance</th>
              <th class="col-small right">% Used</th>
              <th class="col-status">Status</th>
            </tr>
          </thead>

          <tbody>
            <tr v-if="loading" class="loading-row">
              <td colspan="8">
                <div class="loading-content">
                  <div class="loading-spinner"></div>
                  <span>Loading divisions...</span>
                </div>
              </td>
            </tr>

            <tr v-else-if="filteredDivisions.length === 0" class="empty-row">
              <td colspan="8">
                <div class="empty-content">
                  <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.35-4.35"/>
                  </svg>
                  <span>No divisions found</span>
                  <small>Try adjusting your search criteria</small>
                </div>
              </td>
            </tr>

            <tr
              v-else
              v-for="(d, idx) in filteredDivisions"
              :key="d.id"
              class="data-row"
              @click="openDivision(d)"
            >
              <td class="col-n">{{ idx + 1 }}</td>

              <td class="col-division">
                <div class="division-cell">
                  <div class="division-avatar" :style="{ backgroundColor: getAvatarColor(d.division || d.name) }">
                    {{ (d.division || d.name || 'D').slice(0, 1).toUpperCase() }}
                  </div>
                  <div class="division-info">
                    <div class="division-name">{{ d.division || d.name }}</div>
                    <div class="division-meta">ID: {{ d.id }}</div>
                  </div>
                </div>
              </td>

              <td class="col-num right">
                <span class="number-value">{{ formatNumber(d.adl_count ?? 0) }}</span>
              </td>
              <td class="col-num right">
                <span class="number-value">{{ formatMoney(d.total_amount ?? 0) }}</span>
              </td>
              <td class="col-num right">
                <span class="number-value">{{ formatMoney(d.total_spent ?? 0) }}</span>
              </td>
              <td class="col-num right">
                <span class="number-value balance" :class="{ negative: (d.balance ?? d.total_balance ?? 0) < 0 }">
                  {{ formatMoney(d.balance ?? d.total_balance ?? 0) }}
                </span>
              </td>
              <td class="col-small right">
                <div class="percentage-cell">
                  <div class="percentage-bar">
                    <div 
                      class="percentage-fill" 
                      :class="getPercentageClass(Number(d.percentage ?? 0))"
                      :style="{ width: `${Math.min(100, Number(d.percentage ?? 0))}%` }"
                    ></div>
                  </div>
                  <span class="percentage-text">{{ Number(d.percentage ?? 0).toFixed(1) }}%</span>
                </div>
              </td>

              <td class="col-status">
                <span class="status-badge" :class="statusClass(d.status)">
                  <span class="status-dot"></span>
                  {{ d.status || '—' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="table-foot">
        <div class="foot-content">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"/>
            <line x1="12" y1="16" x2="12" y2="12"/>
            <line x1="12" y1="8" x2="12.01" y2="8"/>
          </svg>
          <span>Click any row to view detailed information</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import { useRouter } from "vue-router";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");
const router = useRouter();

const baseDivisions = ref([]);
const yearMetricMap = ref({});
const loading = ref(false);

const selectedYear = ref(new Date().getFullYear());
const search = ref("");

const yearOptions = computed(() => {
  const now = new Date().getFullYear();
  const yrs = [];
  for (let y = now + 1; y >= now - 6; y--) yrs.push(y);
  return yrs;
});

const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", { 
    style: "currency", 
    currency: "PHP",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value || 0));

const formatNumber = (value) =>
  new Intl.NumberFormat("en-US").format(Number(value || 0));

const getAvatarColor = (name) => {
  const colors = [
    '#3b82f6', '#8b5cf6', '#ec4899', '#f59e0b', 
    '#10b981', '#06b6d4', '#6366f1', '#f43f5e'
  ];
  const hash = name ? name.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0) : 0;
  return colors[hash % colors.length];
};

const getPercentageClass = (pct) => {
  if (pct >= 90) return 'critical';
  if (pct >= 70) return 'warning';
  if (pct >= 40) return 'normal';
  return 'low';
};

const divisions = computed(() => {
  return baseDivisions.value.map((d) => {
    const m = yearMetricMap.value[d.id] || {};
    const total_amount = Number(m.total_amount ?? m.allocated ?? m.total_allocated ?? 0);
    const balance = Number(m.balance ?? m.total_balance ?? 0);
    const total_spent = Number(m.total_spent ?? Math.max(0, total_amount - balance));
    const percentage = Number(
      m.percentage ??
        (total_amount > 0 ? ((total_spent / total_amount) * 100) : 0)
    );

    return {
      ...d,
      adl_count: Number(m.adl_count ?? m.total_adls ?? 0),
      total_amount,
      balance,
      total_spent,
      percentage: Math.max(0, Math.min(100, percentage)),
      status: m.status ?? statusFromPct(percentage),
    };
  });
});

const filteredDivisions = computed(() => {
  const s = search.value.trim().toLowerCase();
  if (!s) return divisions.value;
  return divisions.value.filter((d) =>
    (d.division || d.name || "").toLowerCase().includes(s)
  );
});

const totals = computed(() => {
  const rows = divisions.value;
  let total_adls = 0;
  let total_allocated = 0;
  let total_spent = 0;
  let total_balance = 0;

  for (const r of rows) {
    total_adls += Number(r.adl_count || 0);
    total_allocated += Number(r.total_amount || 0);
    total_spent += Number(r.total_spent || 0);
    total_balance += Number(r.balance || 0);
  }

  const percent_used =
    total_allocated > 0 ? (total_spent / total_allocated) * 100 : 0;

  return {
    total_adls,
    total_allocated,
    total_spent,
    total_balance,
    percent_used: Math.max(0, Math.min(100, percent_used)),
  };
});

const fetchAllDivisions = async () => {
  const resp = await axios.get(`${API_BASE}/divisions`);
  baseDivisions.value = Array.isArray(resp.data) ? resp.data : [];
};

const fetchYearMetrics = async () => {
  try {
    const resp = await axios.get(`${API_BASE}/divisions`, {
      params: { year: selectedYear.value },
    });

    const rows = Array.isArray(resp.data) ? resp.data : [];
    const map = {};
    for (const r of rows) {
      const id = r.id ?? r.division_id;
      if (id != null) map[id] = r;
    }
    yearMetricMap.value = map;
  } catch (err) {
    console.error(err);
    yearMetricMap.value = {};
    Swal.fire("Error", "Failed to load year report metrics.", "error");
  }
};

const refreshAll = async () => {
  loading.value = true;
  try {
    await fetchAllDivisions();
    await fetchYearMetrics();
  } catch (err) {
    console.error(err);
    Swal.fire("Error", "Failed to load divisions.", "error");
  } finally {
    loading.value = false;
  }
};

const openDivision = (division) => {
  router.push({
    name: "TUPADadl",
    query: { division_id: division.id, year: selectedYear.value },
  });
};

const statusFromPct = (pct) => {
  const p = Number(pct || 0);
  if (p >= 90) return "Completed";
  if (p >= 50) return "Active";
  return "Pending";
};

const statusClass = (status) => {
  const s = String(status || "").toLowerCase();
  if (s.includes("complete")) return "completed";
  if (s.includes("active")) return "active";
  if (s.includes("pending")) return "pending";
  return "";
};

onMounted(refreshAll);
</script>

<style scoped>
/* Reset and base styles */
* {
  box-sizing: border-box;
}

.main-content {
  padding: 24px;
  max-width: 1400px;
  margin: 0 auto;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  color: #1e293b;
  background: #f8fafc;
  min-height: 100vh;
}

/* HEADER */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 24px;
  margin-bottom: 32px;
  background: white;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.header-content {
  display: flex;
  align-items: center;
  gap: 16px;
}

.header-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 48px;
  height: 48px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border-radius: 12px;
}

h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  letter-spacing: -0.025em;
}

.sub {
  margin: 4px 0 0;
  color: #64748b;
  font-size: 14px;
  font-weight: 500;
}

.controls {
  display: flex;
  gap: 16px;
  align-items: center;
  flex-wrap: wrap;
}

.control-group {
  display: flex;
  gap: 16px;
  align-items: center;
}

.control {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.control-label {
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.control-input {
  padding: 10px 14px;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
  outline: none;
}

.control-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.search-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 14px;
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  padding-left: 40px !important;
  min-width: 280px;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 20px;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  outline: none;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-primary {
  background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
  color: white;
  box-shadow: 0 4px 6px rgba(59, 130, 246, 0.25);
}

.btn-primary:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 12px rgba(59, 130, 246, 0.35);
}

.spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* TOP STATS */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}

.stat-card {
  background: white;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  transition: all 0.3s ease;
  border: 1px solid #f1f5f9;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.stat-header {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  color: white;
}

.stat-icon.adl { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-icon.allocated { background: linear-gradient(135deg, #f093fb, #f5576c); }
.stat-icon.spent { background: linear-gradient(135deg, #4facfe, #00f2fe); }
.stat-icon.balance { background: linear-gradient(135deg, #43e97b, #38f9d7); }

.stat-label {
  font-size: 14px;
  font-weight: 600;
  color: #64748b;
}

.stat-value {
  font-size: 28px;
  font-weight: 700;
  color: #0f172a;
  margin-bottom: 8px;
}

.stat-sub {
  font-size: 13px;
  color: #94a3b8;
}

.stat-progress {
  display: flex;
  align-items: center;
  gap: 12px;
}

.progress-bar {
  flex: 1;
  height: 8px;
  background: #f1f5f9;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #3b82f6, #2563eb);
  border-radius: 4px;
  transition: width 0.3s ease;
}

.progress-text {
  font-size: 13px;
  font-weight: 600;
  color: #64748b;
  min-width: 45px;
}

/* TABLE */
.table-card {
  background: white;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.table-top {
  padding: 20px 24px;
  border-bottom: 1px solid #f1f5f9;
}

.table-title-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.table-title {
  margin: 0;
  font-size: 20px;
  font-weight: 700;
  color: #0f172a;
}

.table-meta {
  font-size: 13px;
  color: #64748b;
}

.table-wrapper {
  overflow-x: auto;
}

.tupad-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

thead th {
  position: sticky;
  top: 0;
  background: #f8fafc;
  z-index: 10;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  padding: 16px;
  border-bottom: 2px solid #f1f5f9;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

tbody td {
  padding: 16px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 14px;
  vertical-align: middle;
}

.loading-row td {
  padding: 48px 16px;
  text-align: center;
}

.loading-content {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  color: #64748b;
}

.loading-spinner {
  width: 24px;
  height: 24px;
  border: 2px solid #e2e8f0;
  border-top: 2px solid #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

.empty-row td {
  padding: 48px 16px;
}

.empty-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: #94a3b8;
}

.empty-content span {
  font-size: 16px;
  font-weight: 600;
}

.empty-content small {
  font-size: 14px;
  color: #cbd5e1;
}

.data-row {
  cursor: pointer;
  transition: background-color 0.2s ease;
}

.data-row:hover {
  background: #f8fafc;
}

.right {
  text-align: right;
}

/* Column sizing */
.col-n { width: 60px; }
.col-division { min-width: 300px; }
.col-num { min-width: 140px; }
.col-small { min-width: 120px; }
.col-status { min-width: 120px; }

/* Division cell */
.division-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.division-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  font-size: 16px;
  flex-shrink: 0;
}

.division-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.division-name {
  font-weight: 600;
  color: #0f172a;
}

.division-meta {
  font-size: 12px;
  color: #94a3b8;
}

/* Number values */
.number-value {
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.number-value.balance.negative {
  color: #ef4444;
}

/* Percentage cell */
.percentage-cell {
  display: flex;
  align-items: center;
  gap: 8px;
}

.percentage-bar {
  width: 60px;
  height: 6px;
  background: #f1f5f9;
  border-radius: 3px;
  overflow: hidden;
}

.percentage-fill {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.percentage-fill.low { background: #10b981; }
.percentage-fill.normal { background: #3b82f6; }
.percentage-fill.warning { background: #f59e0b; }
.percentage-fill.critical { background: #ef4444; }

.percentage-text {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
  min-width: 40px;
}

/* Status badge */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  border: 1px solid transparent;
}

.status-badge.completed {
  background: #dcfce7;
  color: #166534;
  border-color: #bbf7d0;
}

.status-badge.active {
  background: #dbeafe;
  color: #1d4ed8;
  border-color: #bfdbfe;
}

.status-badge.pending {
  background: #fef3c7;
  color: #92400e;
  border-color: #fde68a;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}

/* Table footer */
.table-foot {
  padding: 16px 24px;
  background: #f8fafc;
  border-top: 1px solid #f1f5f9;
}

.foot-content {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #64748b;
  font-size: 13px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .main-content {
    padding: 16px;
  }
  
  .page-header {
    flex-direction: column;
    align-items: stretch;
    gap: 20px;
  }
  
  .controls {
    flex-direction: column;
  }
  
  .control-group {
    flex-direction: column;
    width: 100%;
  }
  
  .control-input {
    width: 100%;
  }
  
  .search-input {
    min-width: 100%;
  }
  
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 1024px) {
  .table-wrapper {
    overflow-x: scroll;
  }
  
  .tupad-table {
    min-width: 900px;
  }
}
</style>
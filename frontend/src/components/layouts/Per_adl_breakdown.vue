<!-- resources/js/pages/Per_adl_breakdown.vue -->
<template>
  <div class="main-content">
    <!-- Header -->
    <div class="page-header">
      <div class="header-left">
        <h2>ADL Breakdown</h2>

        <div class="subline">
          <span class="chip">ADL: <b>{{ meta.adl || "—" }}</b></span>
          <span class="chip soft">Sponsor: <b>{{ meta.sponsor || "—" }}</b></span>
          <span class="chip">Mode: <b>{{ meta.mode || "—" }}</b></span>
          <span v-if="meta.adl_id" class="chip soft">ADL ID: <b>{{ meta.adl_id }}</b></span>
          <span v-if="meta.adl_detail_id" class="chip soft">ADL Detail ID: <b>{{ meta.adl_detail_id }}</b></span>
        </div>
      </div>

      <div class="actions">
        <button class="btn" @click="goBack">Back</button>
      </div>
    </div>

    <!-- REPORT BAR -->
    <div class="report-bar">
      <div class="report-left">
        <div class="field-inline">
          <label class="mini-label">Year</label>
          <select v-model="reportYear" class="input mini">
            <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
          </select>
        </div>

        <div class="hint-inline">
          Downloads Excel using PhpSpreadsheet (server). If you only made 1-tab reports, use the separate buttons.
        </div>
      </div>

      <div class="report-actions">
        <button class="btn primary" :disabled="downloading" @click="downloadWorkbook">
          {{ downloading ? "Preparing..." : "Download 3 Tabs (Workbook)" }}
        </button>

        <button class="btn" :disabled="downloading" @click="downloadTab('per-adl')">
          TAB 1 - Per ADL
        </button>

        <button class="btn" :disabled="downloading" @click="downloadTab('lgu-per-adl')">
          TAB 2 - LGU per ADL
        </button>

        <button class="btn" :disabled="downloading" @click="downloadTab('all-adl')">
          TAB 3 - All ADL Summary
        </button>
      </div>

      <div v-if="downloadError" class="errorBox">{{ downloadError }}</div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <div class="filter">
        <label>Division</label>

        <select v-model="selectedDivisionKey" class="input">
          <option value="">All Divisions</option>
          <option v-for="d in divisionOptions" :key="d.division_key" :value="d.division_key">
            {{ d.division_name }}
          </option>
        </select>

        <div class="hint">
          Pick a division to filter LGU list + recompute totals.
        </div>
      </div>

      <div class="filter">
        <label>Search LGU</label>
        <input v-model="search" class="input" placeholder="Type LGU..." />
        <div class="hint">Search applies to the currently shown list.</div>
      </div>
    </div>

    <!-- Computation Cards -->
    <div class="cards">
      <div class="card">
        <div class="card-title">Rows</div>
        <div class="card-value">{{ shownRows.length }}</div>
        <div class="card-sub">LGU rows shown</div>
      </div>

      <div class="card">
        <div class="card-title">Total Beneficiaries</div>
        <div class="card-value">{{ formatNumber(shownTotals.totalBeneficiaries) }}</div>
        <div class="card-sub">Sum of beneficiaries shown</div>
      </div>

      <div class="card">
        <div class="card-title">Total Amount</div>
        <div class="card-value">{{ formatMoney(shownTotals.totalAmount) }}</div>
        <div class="card-sub">Sum of amount shown</div>
      </div>

      <div class="card">
        <div class="card-title">Allocated (Backend)</div>
        <div class="card-value">{{ formatMoney(meta.allocated) }}</div>
        <div class="card-sub">From ADL/Detail</div>
      </div>

      <div class="card">
        <div class="card-title">Balance (Backend)</div>
        <div class="card-value">{{ formatMoney(meta.balance) }}</div>
        <div class="card-sub">From ADL/Detail</div>
      </div>
    </div>

    <!-- LGU Table -->
    <div class="table-section">
      <div class="table-head">
        <div class="table-title">
          <h3>LGU List</h3>
          <p>
            Displays LGU under selected ADL.
            <span v-if="loading">Loading…</span>
          </p>
        </div>
      </div>

      <div class="table-wrapper">
        <!-- Desktop -->
        <table class="desktop-table">
          <thead>
            <tr>
              <th>LGU</th>
              <th class="numeric">Beneficiaries</th>
              <th class="numeric">Amount</th>
              <th>Division</th>
              <th>OSH Date</th>
              <th>Payout Date</th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="row in shownRows" :key="row.id">
              <td>
                <div class="lgu-cell">
                  <div class="lgu-badge">{{ (row.lgu || "L").slice(0, 1).toUpperCase() }}</div>
                  <div class="lgu-meta">
                    <div class="lgu-name">{{ row.lgu || "—" }}</div>
                  </div>
                </div>
              </td>

              <td class="numeric"><span class="num-pill">{{ formatNumber(row.beneficiaries || 0) }}</span></td>
              <td class="numeric"><span class="money-pill">{{ formatMoney(row.amount || 0) }}</span></td>
              <td><span class="tag-pill">{{ row.division_name || "—" }}</span></td>
              <td><span class="date-pill">{{ formatDate(row.osh_date) }}</span></td>
              <td><span class="date-pill">{{ formatDate(row.payout_date) }}</span></td>
            </tr>

            <tr v-if="shownRows.length === 0 && !loading">
              <td colspan="6" class="empty">No LGU rows found.</td>
            </tr>

            <tr v-if="loading">
              <td colspan="6" class="empty">Loading...</td>
            </tr>
          </tbody>
        </table>

        <!-- Mobile -->
        <div class="mobile-cards">
          <div v-for="row in shownRows" :key="'m-' + row.id" class="mcard">
            <div class="mcard-top">
              <div class="m-lgu">
                <div class="lgu-badge">{{ (row.lgu || "L").slice(0, 1).toUpperCase() }}</div>
                <div>
                  <div class="m-title">{{ row.lgu || "—" }}</div>
                  <div class="m-sub">{{ row.division_name || "—" }}</div>
                </div>
              </div>
            </div>

            <div class="mgrid">
              <div class="mitem">
                <div class="mlabel">Beneficiaries</div>
                <div class="mvalue">{{ formatNumber(row.beneficiaries || 0) }}</div>
              </div>
              <div class="mitem">
                <div class="mlabel">Amount</div>
                <div class="mvalue">{{ formatMoney(row.amount || 0) }}</div>
              </div>
              <div class="mitem">
                <div class="mlabel">OSH Date</div>
                <div class="mvalue">{{ formatDate(row.osh_date) }}</div>
              </div>
              <div class="mitem">
                <div class="mlabel">Payout Date</div>
                <div class="mvalue">{{ formatDate(row.payout_date) }}</div>
              </div>
            </div>

            <div class="mfoot">
              <div class="mfoot-pill">Breakdown ID: {{ row.id }}</div>
              <div class="mfoot-pill">ADL Detail: {{ row.adl_detail_id }}</div>
            </div>
          </div>

          <div v-if="shownRows.length === 0 && !loading" class="mobile-empty">
            <div class="empty">No LGU rows found.</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, watch, inject } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";

// ✅ Global API base (injected from main.js)
const API_BASE = inject("API_BASE");
const route = useRoute();
const router = useRouter();

const adlId = computed(() => Number(route.query.adl_id || 0) || null);
const adlDetailId = computed(() => Number(route.query.adl_detail_id || 0) || null);

const meta = ref({
  mode: null,
  adl_id: null,
  adl_detail_id: null,
  adl: null,
  sponsor: null,
  allocated: 0,
  balance: 0,
});

const rows = ref([]);
const totalsByDivision = ref([]);

const selectedDivisionKey = ref("");
const search = ref("");
const loading = ref(false);

/** Year for report */
const currentYear = new Date().getFullYear();
const reportYear = ref(currentYear);
const years = computed(() => {
  const list = [];
  for (let y = currentYear; y >= currentYear - 6; y--) list.push(y);
  return list;
});

const downloading = ref(false);
const downloadError = ref("");

const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));

const formatNumber = (n) => (Number(n || 0)).toLocaleString();

const formatDate = (v) => {
  if (!v) return "—";
  const d = new Date(String(v).slice(0, 10));
  if (isNaN(d.getTime())) return "—";
  return d.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "2-digit" });
};

const divisionOptions = computed(() => totalsByDivision.value || []);

const shownRows = computed(() => {
  let list = [...rows.value];

  if (selectedDivisionKey.value !== "") {
    list = list.filter((r) => String(r.division_key || "") === selectedDivisionKey.value);
  }

  const q = String(search.value || "").trim().toLowerCase();
  if (q) list = list.filter((r) => String(r.lgu || "").toLowerCase().includes(q));

  list.sort((a, b) => Number(b.id || 0) - Number(a.id || 0));
  return list;
});

const shownTotals = computed(() => {
  return shownRows.value.reduce(
    (acc, r) => {
      acc.totalAmount += Number(r.amount || 0);
      acc.totalBeneficiaries += Number(r.beneficiaries || 0);
      return acc;
    },
    { totalAmount: 0, totalBeneficiaries: 0 }
  );
});

const fetchBreakdown = async () => {
  if (!adlId.value && !adlDetailId.value) {
    Swal.fire("Missing", "Provide adl_id or adl_detail_id in the URL.", "warning");
    return;
  }

  loading.value = true;
  try {
    const resp = await axios.get(`${API_BASE}/per-adl-breakdown`, {
      params: {
        adl_id: adlId.value || undefined,
        adl_detail_id: adlDetailId.value || undefined,
      },
    });

    meta.value = resp.data?.meta || meta.value;
    rows.value = Array.isArray(resp.data?.rows) ? resp.data.rows : [];
    totalsByDivision.value = Array.isArray(resp.data?.totals_by_division) ? resp.data.totals_by_division : [];
    selectedDivisionKey.value = "";
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || "Failed to load breakdown.", "error");
    rows.value = [];
    totalsByDivision.value = [];
  } finally {
    loading.value = false;
  }
};

const goBack = () => router.back();

/**
 * Download helpers – use the same injected API_BASE (already includes /api)
 */
async function downloadFile(url, filename) {
  downloadError.value = "";
  downloading.value = true;

  try {
    const res = await fetch(url, { method: "GET" });
    if (!res.ok) {
      const txt = await res.text();
      throw new Error(txt || "Download failed.");
    }
    const blob = await res.blob();
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
  } catch (e) {
    downloadError.value = e?.message || "Download failed.";
  } finally {
    downloading.value = false;
  }
}

async function downloadWorkbook() {
  const y = reportYear.value;
  const url = `${API_BASE}/tupad/reports/workbook?year=${encodeURIComponent(y)}`;
  await downloadFile(url, `TUPAD_Report_${y}_Workbook.xlsx`);
}

async function downloadTab(type) {
  const y = reportYear.value;

  const map = {
    "per-adl": {
      url: `${API_BASE}/tupad/reports/per-adl?year=${encodeURIComponent(y)}`,
      file: `TUPAD_Report_${y}_TAB1_Per_ADL.xlsx`,
    },
    "lgu-per-adl": {
      url: `${API_BASE}/tupad/reports/lgu-per-adl?year=${encodeURIComponent(y)}`,
      file: `TUPAD_Report_${y}_TAB2_LGU_Per_ADL.xlsx`,
    },
    "all-adl": {
      url: `${API_BASE}/tupad/reports/all-adl?year=${encodeURIComponent(y)}`,
      file: `TUPAD_Report_${y}_TAB3_All_ADL_Summary.xlsx`,
    },
  };

  const cfg = map[type];
  if (!cfg) return;
  await downloadFile(cfg.url, cfg.file);
}

onMounted(fetchBreakdown);

watch(
  () => [adlId.value, adlDetailId.value],
  async () => {
    rows.value = [];
    totalsByDivision.value = [];
    selectedDivisionKey.value = "";
    search.value = "";
    await fetchBreakdown();
  }
);
</script>

<style scoped>
/* Your existing styles remain unchanged – they are identical to the original */
.main-content {
  padding: 18px;
  max-width: 1300px;
  margin: 0 auto;
  font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial;
  color: #0f172a;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.header-left { min-width: 260px; }
h2 { margin: 0; font-size: 22px; font-weight: 900; letter-spacing: -0.02em; }
.subline { margin-top: 8px; display: flex; gap: 8px; flex-wrap: wrap; }
.chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: #334155;
  font-weight: 800;
  font-size: 12px;
}
.chip.soft { background: #f8fafc; }

.actions { display: flex; gap: 10px; align-items: center; }
.btn {
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 900;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
  white-space: nowrap;
}
.btn:hover { transform: translateY(-1px); box-shadow: 0 8px 18px rgba(2, 6, 23, 0.06); }
.btn:disabled { opacity: 0.65; cursor: not-allowed; transform: none; box-shadow: none; }
.btn.primary {
  border-color: #1e3a8a;
  background: #1e3a8a;
  color: #fff;
}

/* Report Bar */
.report-bar {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 12px;
  margin-bottom: 14px;
}

.report-left {
  display: flex;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
}

.field-inline {
  display: flex;
  align-items: center;
  gap: 10px;
}
.mini-label {
  font-size: 12px;
  font-weight: 900;
  color: #334155;
}
.hint-inline {
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
}

.report-actions {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.input {
  width: 100%;
  height: 44px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  padding: 10px 12px;
  font-weight: 900;
  outline: none;
  background: #fff;
  box-sizing: border-box;
}
.input:focus {
  border-color: rgba(37, 99, 235, 0.55);
  box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
}
.input.mini {
  width: 140px;
  height: 42px;
}

.errorBox {
  grid-column: 1 / -1;
  margin-top: 8px;
  border: 1px solid #fda29b;
  background: #fffbfa;
  color: #b42318;
  padding: 10px;
  border-radius: 12px;
  font-size: 13px;
  font-weight: 900;
}

/* Filters */
.filters {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin: 14px 0 14px;
}
.filter {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.04);
}
.filter label {
  display: block;
  font-size: 12px;
  font-weight: 900;
  color: #334155;
  margin-bottom: 8px;
}
.hint { margin-top: 8px; font-size: 12px; font-weight: 800; color: #64748b; }

/* Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
  gap: 12px;
  margin: 12px 0 18px;
}
.card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 14px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}
.card-title {
  color: #64748b;
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.card-value { margin-top: 8px; font-size: 20px; font-weight: 1000; color: #0f172a; }
.card-sub { margin-top: 8px; font-size: 12px; font-weight: 800; color: #64748b; }

/* LGU table */
.table-section {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #eef2f7;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
}
.table-head { padding: 14px 16px; border-bottom: 1px solid #eef2f7; }
.table-title h3 { margin: 0; font-size: 16px; font-weight: 1000; }
.table-title p { margin: 6px 0 0; font-size: 12px; font-weight: 800; color: #64748b; }

.table-wrapper { overflow-x: auto; }
.desktop-table { width: 100%; border-collapse: collapse; min-width: 980px; }
.desktop-table thead th {
  background: linear-gradient(180deg, #fcfdff, #f2f6ff);
  padding: 14px 16px;
  text-align: left;
  font-weight: 900;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  border-bottom: 1px solid #eef2f7;
  color: #334155;
}
.desktop-table tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  vertical-align: middle;
}
.desktop-table tbody tr:hover td { background: #fbfdff; }

.numeric { text-align: right; font-weight: 900; }
.empty { text-align: center; padding: 22px; font-weight: 900; color: #64748b; }

/* LGU cell */
.lgu-cell { display: flex; align-items: center; gap: 12px; }
.lgu-badge {
  width: 36px; height: 36px;
  border-radius: 10px;
  display: grid; place-items: center;
  font-weight: 900;
  color: #1d4ed8;
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.14);
}
.lgu-meta { display: flex; flex-direction: column; gap: 2px; }
.lgu-name { font-weight: 1000; color: #0b3a66; line-height: 1.1; }

.num-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  min-width: 90px;
}
.money-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(16, 185, 129, 0.08);
  border: 1px solid rgba(16, 185, 129, 0.18);
  min-width: 140px;
  color: #047857;
}
.tag-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: rgba(37, 99, 235, 0.08);
  border: 1px solid rgba(37, 99, 235, 0.18);
  font-weight: 900;
  font-size: 12px;
  color: #1d4ed8;
}
.date-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-weight: 900;
  font-size: 12px;
  color: #334155;
}

/* Mobile */
.mobile-cards { display: none; }
@media (max-width: 980px) {
  .filters { grid-template-columns: 1fr; }
  .report-bar { grid-template-columns: 1fr; }
  .report-actions { justify-content: flex-start; }
  .desktop-table { display: none; }
  .mobile-cards { display: grid; gap: 12px; padding: 14px; }
  .table-wrapper { overflow: visible; }
  .input.mini { width: 100%; }
}

.mcard {
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
  background: #fff;
}
.mcard-top { display: flex; justify-content: space-between; gap: 10px; align-items: center; margin-bottom: 10px; }
.m-lgu { display: flex; gap: 10px; align-items: center; }
.m-title { font-weight: 1000; color: #0b3a66; }
.m-sub { font-size: 12px; font-weight: 900; color: #64748b; margin-top: 2px; }
.mgrid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.mitem { border: 1px solid #eef2f7; background: #f8fafc; border-radius: 12px; padding: 10px; }
.mlabel { font-size: 11px; font-weight: 1000; color: #64748b; text-transform: uppercase; letter-spacing: 0.06em; }
.mvalue { margin-top: 6px; font-weight: 1000; color: #0f172a; }
.mfoot { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
.mfoot-pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  background: #fff;
  border: 1px solid #e2e8f0;
  font-weight: 900;
  font-size: 12px;
  color: #334155;
}
.mobile-empty { padding: 0 14px 14px; }
@media (max-width: 420px) { .mgrid { grid-template-columns: 1fr; } }
</style>
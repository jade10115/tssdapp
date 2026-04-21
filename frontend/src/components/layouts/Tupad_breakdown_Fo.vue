<template>
  <div class="main-content">
    <!-- Header -->
    <div class="page-header">
      <div>
        <h2>ADL Breakdown</h2>
        <p class="sub">ADL Detail ID: <b>{{ adlDetailId || "—" }}</b></p>
      </div>

      
    </div>

    <!-- Summary Cards -->
    <div class="cards">
      <div class="card">
        <div class="card-title">Rows</div>
        <div class="card-value">{{ rows.length }}</div>
      </div>

      <div class="card">
        <div class="card-title">Total Beneficiaries</div>
        <div class="card-value">{{ formatNumber(totalBeneficiaries) }}</div>
      </div>

      <div class="card">
        <div class="card-title">Allocated Amount</div>
        <div class="card-value">{{ formatMoney(allocatedAmount) }}</div>
      </div>

      <div class="card">
        <div class="card-title">Balance</div>
        <div class="card-value">{{ formatMoney(adlBalance) }}</div>
      </div>

      <div class="card">
        <div class="card-title">Utilization Rate</div>
        <div class="card-value">{{ utilizationRate }}%</div>
        <div class="card-sub">Spent: {{ formatMoney(spentAmount) }}</div>
      </div>
    </div>

    <!-- Hidden file input (shared; per-row trigger) -->
    <input
      ref="excelInputRef"
      type="file"
      class="hidden-file"
      accept=".xlsx,.xls,.csv"
      @change="onExcelChosen"
    />

    <!-- Table -->
    <div class="table-section">
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>LGU / Barangay</th>
              <th class="numeric">Beneficiaries</th>
              <th class="numeric">Amount</th>
              <th>Status</th>
              <th>Osh Date</th>
              <th>Payout Date</th>

              <!-- ✅ KEEP THESE -->
              <th class="numeric">4Ps</th>
              <th class="numeric">Senior Citizens</th>
              <th class="numeric">PWD</th>
              <th class="numeric">Females</th>

             
            </tr>
          </thead>

          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td>
                <div class="lgu-cell">
                  <div class="lgu-badge">{{ (row.lgu || "L").slice(0, 1).toUpperCase() }}</div>
                  <div class="lgu-meta">
                    <div class="lgu-name">{{ row.lgu || "—" }}</div>
                    <div class="lgu-sub">Breakdown ID: {{ row.id }}</div>
                  </div>
                </div>
              </td>

              <td class="numeric">
                <span class="num-pill">{{ formatNumber(row.beneficiaries || 0) }}</span>
              </td>

              <td class="numeric">
                <span class="money-pill">{{ formatMoney(row.amount || 0) }}</span>
              </td>

              <td>
                <span :class="['pill', normalizeStatus(row.status) === 'received' ? 'ok' : 'pending']">
                  {{ normalizeStatus(row.status) === "received" ? "Received" : "Pending" }}
                </span>
              </td>

              <td><span class="date-pill">{{ formatDate(row.osh_date) }}</span></td>
              <td><span class="date-pill">{{ formatDate(row.payout_date) }}</span></td>

              <!-- ✅ counts (from DB) -->
              <td class="numeric"><span class="mini-pill">{{ formatNumber(row.four_ps || 0) }}</span></td>
              <td class="numeric"><span class="mini-pill">{{ formatNumber(row.seniors || 0) }}</span></td>
              <td class="numeric"><span class="mini-pill">{{ formatNumber(row.pwd || 0) }}</span></td>
              <td class="numeric"><span class="mini-pill">{{ formatNumber(row.female || 0) }}</span></td>

         
            </tr>

            <tr v-if="rows.length === 0 && !loadingRows && !loadingDetail">
              <td colspan="11" class="empty">
                No breakdown rows found.
                <div v-if="allocatedAmount <= 0" class="empty-sub">
                  This ADL Detail has <b>₱0.00 allocated</b>. Add allocation first.
                </div>
                <div v-else-if="adlBalance <= 0" class="empty-sub">
                  This ADL Detail is already <b>fully utilized</b> (Balance is ₱0.00).
                </div>
              </td>
            </tr>

            <tr v-if="loadingRows || loadingDetail">
              <td colspan="11" class="empty">Loading...</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- ✅ GAME MODAL (shows while reading/uploading) -->
    <div v-if="gameModalOpen" class="game-overlay" role="dialog" aria-modal="true">
      <div class="game-modal">
        <div class="game-top">
          <div>
            <div class="game-title">Processing Excel…</div>
            <div class="game-sub">
              Please wait. You can play while it loads.
              <span v-if="currentExcelRowId" class="game-badge">Row #{{ currentExcelRowId }}</span>
            </div>
          </div>

          <div class="game-meta">
            <div class="game-stat">
              <div class="game-stat-label">Score</div>
              <div class="game-stat-value">{{ game.score }}</div>
            </div>
            <div class="game-stat">
              <div class="game-stat-label">Best</div>
              <div class="game-stat-value">{{ game.best }}</div>
            </div>
          </div>
        </div>

        <div class="game-wrap">
          <canvas ref="gameCanvasRef" class="game-canvas" width="520" height="240"></canvas>

          <div class="game-help">
            <div class="help-pill">← → Move</div>
            <div class="help-pill">Avoid blocks</div>
            <button class="help-btn" @click="restartGame" type="button">Restart</button>
          </div>

          <div v-if="game.over" class="game-over">
            <div class="go-title">Game Over</div>
            <div class="go-sub">Press Restart (or keep waiting).</div>
          </div>
        </div>

        <div class="game-bottom">
          <div class="progress-row">
            <div class="progress-label">
              Uploading & counting…
              <span class="dotty" aria-hidden="true"><span>.</span><span>.</span><span>.</span></span>
            </div>
            <div class="progress-bar">
              <div class="progress-fill" :style="{ width: progressPercent + '%' }"></div>
            </div>
          </div>

          <div class="game-note">
            Tip: This modal closes automatically when Excel processing finishes.
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onBeforeUnmount, watch } from "vue";
import axios from "axios";
import { useRoute, useRouter } from "vue-router";
import Swal from "sweetalert2";

import { inject } from "vue";

const API_BASE = inject("API_BASE");


const route = useRoute();
const router = useRouter();

const adlDetailId = computed(() => Number(route.query.adl_detail_id || route.query.adl_id || 0) || null);

const rows = ref([]);
const adlDetail = ref(null);

const loadingRows = ref(false);
const loadingDetail = ref(false);

// Excel upload state
const excelInputRef = ref(null);
const selectedRowForExcel = ref(null);
const excelBusyRowId = ref(null);

// ✅ Game modal state
const gameModalOpen = ref(false);
const currentExcelRowId = ref(null);
const progressPercent = ref(18); // visual only (since server does the work)
let progressTimer = null;

// Canvas game refs/state
const gameCanvasRef = ref(null);
let rafId = null;
let lastTs = 0;

const game = ref({
  over: false,
  score: 0,
  best: Number(localStorage.getItem("adl_excel_game_best") || 0),
  // world
  w: 520,
  h: 240,
  // player
  px: 260,
  py: 210,
  pw: 28,
  ph: 12,
  vx: 0,
  // blocks
  blocks: [],
  spawnCd: 0,
});

const formatMoney = (value) =>
  new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));
const formatNumber = (n) => (Number(n || 0)).toLocaleString();
const normalizeStatus = (s) => String(s || "").trim().toLowerCase() || "pending";

const formatDate = (v) => {
  if (!v) return "—";
  const d = new Date(String(v).slice(0, 10));
  if (isNaN(d.getTime())) return "—";
  return d.toLocaleDateString("en-PH", { year: "numeric", month: "short", day: "2-digit" });
};

const allocatedAmount = computed(() => Number(adlDetail.value?.amount || 0));
const adlBalance = computed(() => Number(adlDetail.value?.balance || 0));
const spentAmount = computed(() => Math.max(0, allocatedAmount.value - adlBalance.value));

const utilizationRate = computed(() => {
  const a = allocatedAmount.value;
  const b = adlBalance.value;
  if (a <= 0) return 0;
  if (b <= 0) return 100;
  const pct = Math.round(((a - b) / a) * 100);
  return Math.max(0, Math.min(100, pct));
});

const totalBeneficiaries = computed(() => rows.value.reduce((sum, r) => sum + Number(r.beneficiaries || 0), 0));

const fetchDetail = async () => {
  if (!adlDetailId.value) return;
  loadingDetail.value = true;
  try {
    const resp = await axios.get(`${API_BASE}/tupad_adl_details/${adlDetailId.value}`);
    adlDetail.value = resp.data || null;
  } catch (err) {
    console.error(err);
    adlDetail.value = null;
    Swal.fire("Error", err.response?.data?.message || "Failed to load ADL Detail.", "error");
  } finally {
    loadingDetail.value = false;
  }
};

const fetchRows = async () => {
  if (!adlDetailId.value) return;
  loadingRows.value = true;
  try {
    const resp = await axios.get(`${API_BASE}/tupad_adl_breakdown`, {
      params: { adl_detail_id: adlDetailId.value },
    });
    rows.value = Array.isArray(resp.data) ? resp.data : [];
  } catch (err) {
    console.error(err);
    Swal.fire("Error", err.response?.data?.message || "Failed to load breakdown.", "error");
  } finally {
    loadingRows.value = false;
  }
};

const refreshAll = async () => {
  await fetchDetail();
  await fetchRows();
};

const goBack = () => router.back();

/** ✅ Upload button click (per row) */
const triggerExcel = (row) => {
  selectedRowForExcel.value = row;
  if (!excelInputRef.value) return;
  excelInputRef.value.value = "";
  excelInputRef.value.click();
};

/* ---------------- GAME MODAL HELPERS ---------------- */
const openGameModal = (rowId) => {
  currentExcelRowId.value = rowId || null;
  progressPercent.value = 18;
  gameModalOpen.value = true;
  startProgressAnim();
  startGameLoop();
};

const closeGameModal = () => {
  gameModalOpen.value = false;
  currentExcelRowId.value = null;
  stopProgressAnim();
  stopGameLoop();
};

const startProgressAnim = () => {
  stopProgressAnim();
  // Fake progress: climbs to ~92% while waiting; final close happens on real completion
  progressTimer = setInterval(() => {
    const p = progressPercent.value;
    const cap = 92;
    if (p < cap) {
      const step = Math.max(0.6, (cap - p) * 0.06);
      progressPercent.value = Math.min(cap, p + step);
    }
  }, 120);
};

const stopProgressAnim = () => {
  if (progressTimer) clearInterval(progressTimer);
  progressTimer = null;
};

const finalizeProgress = async () => {
  progressPercent.value = 100;
  await new Promise((r) => setTimeout(r, 220));
};

/* ---------------- CANVAS GAME ---------------- */
const clamp = (n, a, b) => Math.max(a, Math.min(b, n));
const aabb = (ax, ay, aw, ah, bx, by, bw, bh) =>
  ax < bx + bw && ax + aw > bx && ay < by + bh && ay + ah > by;

const resetGame = () => {
  game.value.over = false;
  game.value.score = 0;
  game.value.px = 260;
  game.value.vx = 0;
  game.value.blocks = [];
  game.value.spawnCd = 0;
};

const restartGame = () => {
  resetGame();
};

const drawGame = (ctx) => {
  const g = game.value;
  ctx.clearRect(0, 0, g.w, g.h);

  // background
  ctx.fillStyle = "rgba(15, 23, 42, 0.06)";
  ctx.fillRect(0, 0, g.w, g.h);

  // safe floor line
  ctx.strokeStyle = "rgba(15, 23, 42, 0.18)";
  ctx.beginPath();
  ctx.moveTo(0, g.py + g.ph + 8);
  ctx.lineTo(g.w, g.py + g.ph + 8);
  ctx.stroke();

  // blocks
  ctx.fillStyle = "rgba(37, 99, 235, 0.85)";
  for (const b of g.blocks) {
    ctx.fillRect(b.x, b.y, b.w, b.h);
  }

  // player
  ctx.fillStyle = "rgba(16, 185, 129, 0.95)";
  ctx.fillRect(g.px, g.py, g.pw, g.ph);

  // text overlay inside canvas
  ctx.fillStyle = "rgba(2, 6, 23, 0.8)";
  ctx.font = "900 12px system-ui, -apple-system, Segoe UI, Roboto, Arial";
  ctx.fillText("Dodge while Excel processes", 12, 20);

  if (g.over) {
    ctx.fillStyle = "rgba(2, 6, 23, 0.55)";
    ctx.fillRect(0, 0, g.w, g.h);
    ctx.fillStyle = "#fff";
    ctx.font = "900 22px system-ui, -apple-system, Segoe UI, Roboto, Arial";
    ctx.fillText("GAME OVER", 180, 120);
  }
};

const tickGame = (dt) => {
  const g = game.value;
  if (g.over) return;

  // score
  g.score += Math.floor(dt * 40);

  // spawn blocks
  g.spawnCd -= dt;
  const difficulty = 0.85 + Math.min(1.4, g.score / 2500); // gradually faster
  if (g.spawnCd <= 0) {
    const w = 18 + Math.random() * 28;
    const h = 10 + Math.random() * 18;
    const x = Math.random() * (g.w - w);
    const vy = 80 * difficulty + Math.random() * 60 * difficulty;
    g.blocks.push({ x, y: -h, w, h, vy });
    g.spawnCd = 0.28 + Math.random() * 0.32; // spawn cadence
  }

  // player movement
  g.px = clamp(g.px + g.vx * dt, 0, g.w - g.pw);

  // update blocks + collision
  for (const b of g.blocks) {
    b.y += b.vy * dt;
    if (aabb(g.px, g.py, g.pw, g.ph, b.x, b.y, b.w, b.h)) {
      g.over = true;
      g.best = Math.max(g.best, g.score);
      localStorage.setItem("adl_excel_game_best", String(g.best));
    }
  }

  // remove offscreen
  g.blocks = g.blocks.filter((b) => b.y < g.h + 60);
};

const gameKeyDown = (e) => {
  if (!gameModalOpen.value) return;
  if (e.key === "ArrowLeft") game.value.vx = -240;
  if (e.key === "ArrowRight") game.value.vx = 240;
};
const gameKeyUp = (e) => {
  if (!gameModalOpen.value) return;
  if (e.key === "ArrowLeft" && game.value.vx < 0) game.value.vx = 0;
  if (e.key === "ArrowRight" && game.value.vx > 0) game.value.vx = 0;
};

const startGameLoop = () => {
  stopGameLoop();
  resetGame();
  lastTs = performance.now();

  // attach keys
  window.addEventListener("keydown", gameKeyDown);
  window.addEventListener("keyup", gameKeyUp);

  const loop = (ts) => {
    rafId = requestAnimationFrame(loop);
    const dt = Math.min(0.05, (ts - lastTs) / 1000);
    lastTs = ts;

    const canvas = gameCanvasRef.value;
    if (!canvas) return;
    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    tickGame(dt);
    drawGame(ctx);
  };

  rafId = requestAnimationFrame(loop);
};

const stopGameLoop = () => {
  if (rafId) cancelAnimationFrame(rafId);
  rafId = null;
  window.removeEventListener("keydown", gameKeyDown);
  window.removeEventListener("keyup", gameKeyUp);
};

/** ✅ Excel file picked -> send to backend import-demographics -> update row in UI */
const onExcelChosen = async (e) => {
  const file = e.target.files?.[0];
  const row = selectedRowForExcel.value;

  if (!file || !row?.id) return;

  excelBusyRowId.value = row.id;

  // ✅ open modal game while waiting
  openGameModal(row.id);

  try {
    const form = new FormData();
    form.append("file", file);

    const resp = await axios.post(`${API_BASE}/tupad_adl_breakdown/${row.id}/import-demographics`, form, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    const counts = resp.data?.counts || {};
    const mapped = resp.data?.mapped_columns || {};
    const headerRow = resp.data?.header_row;

    // ✅ update only this row in table
    const idx = rows.value.findIndex((r) => r.id === row.id);
    if (idx >= 0) {
      rows.value[idx] = {
        ...rows.value[idx],
        four_ps: Number(counts.four_ps || 0),
        seniors: Number(counts.seniors || 0),
        pwd: Number(counts.pwd || 0),
        female: Number(counts.female || 0),
      };
    }

    await finalizeProgress();
    closeGameModal();

    await Swal.fire({
      icon: "success",
      title: "Counted!",
      html: `
        <div style="margin-top:6px;font-weight:800;color:#475569;">
          4Ps: <b>${counts.four_ps ?? 0}</b>,
          Seniors: <b>${counts.seniors ?? 0}</b>,
          PWD: <b>${counts.pwd ?? 0}</b>,
          Females: <b>${counts.female ?? 0}</b>
        </div>
        <div style="margin-top:10px;font-size:12px;color:#94a3b8;">
          Header row: <b>${headerRow ?? "—"}</b> |
          Mapped: PWD=<b>${mapped.pwd ?? "—"}</b>,
          4Ps=<b>${mapped.four_ps ?? "—"}</b>,
          Sex=<b>${mapped.sex ?? "—"}</b>,
          Age=<b>${mapped.age ?? "—"}</b>
        </div>
      `,
      confirmButtonText: "OK",
    });
  } catch (err) {
    console.error(err);
    await finalizeProgress();
    closeGameModal();
    Swal.fire("Error", err.response?.data?.message || err.message || "Failed to import demographics.", "error");
  } finally {
    excelBusyRowId.value = null;
    selectedRowForExcel.value = null;
    if (excelInputRef.value) excelInputRef.value.value = "";
  }
};

/* ---------------- EXISTING CRUD (kept placeholders) ---------------- */
/* Replace these with your real functions (your existing implementations). */
const openAddModal = async () => Swal.fire("Info", "Paste your existing openAddModal() here (unchanged).", "info");
const openEditModal = async () => Swal.fire("Info", "Paste your existing openEditModal() here (unchanged).", "info");
const removeRow = async () => Swal.fire("Info", "Paste your existing removeRow() here (unchanged).", "info");
const markAsReceived = async () => Swal.fire("Info", "Paste your existing markAsReceived() here (unchanged).", "info");

onMounted(refreshAll);

watch(
  () => adlDetailId.value,
  async () => {
    rows.value = [];
    adlDetail.value = null;
    await refreshAll();
  }
);

onBeforeUnmount(() => {
  stopGameLoop();
  stopProgressAnim();
});
</script>

<style scoped>
.main-content {
  padding: 20px;
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
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
h2 {
  margin: 0;
  font-size: 22px;
  font-weight: 900;
  letter-spacing: -0.02em;
}
.sub {
  margin: 6px 0 0;
  color: #64748b;
  font-weight: 700;
  font-size: 13px;
}
.actions {
  display: flex;
  gap: 10px;
  align-items: center;
}

.btn {
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 900;
  transition: transform 0.12s ease, box-shadow 0.12s ease, opacity 0.12s ease;
}
.btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 18px rgba(2, 6, 23, 0.06);
}
.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.btn-primary {
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  border: none;
  color: #fff;
  box-shadow: 0 10px 22px rgba(37, 99, 235, 0.18);
}

/* Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 14px;
  margin: 14px 0 18px;
}
.card {
  background: #fff;
  border: 1px solid #eef2f7;
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 10px 26px rgba(2, 6, 23, 0.05);
}
.card-title {
  color: #64748b;
  font-weight: 800;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.card-value {
  margin-top: 8px;
  font-size: 20px;
  font-weight: 900;
  color: #0f172a;
}
.card-sub {
  margin-top: 8px;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
}

/* Table */
.table-section {
  background: #fff;
  border-radius: 14px;
  border: 1px solid #eef2f7;
  box-shadow: 0 10px 30px rgba(2, 6, 23, 0.05);
  overflow: hidden;
}
.table-wrapper {
  overflow-x: auto;
}
table {
  width: 100%;
  border-collapse: collapse;
  min-width: 1250px;
}
thead th {
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
tbody td {
  padding: 14px 16px;
  border-bottom: 1px solid #f1f5f9;
  background: #fff;
  vertical-align: middle;
}
tbody tr:hover td {
  background: #fbfdff;
}

.numeric {
  text-align: right;
  font-weight: 900;
}
.actions-head {
  text-align: center;
  width: 260px;
}
.actions-cell {
  display: flex;
  justify-content: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* LGU cell */
.lgu-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}
.lgu-badge {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  display: grid;
  place-items: center;
  font-weight: 900;
  color: #1d4ed8;
  background: rgba(37, 99, 235, 0.1);
  border: 1px solid rgba(37, 99, 235, 0.14);
}
.lgu-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.lgu-name {
  font-weight: 900;
  color: #0b3a66;
  line-height: 1.1;
}
.lgu-sub {
  font-size: 12px;
  color: #64748b;
  font-weight: 700;
}

/* Pills */
.num-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  min-width: 80px;
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
.mini-pill {
  display: inline-flex;
  justify-content: flex-end;
  padding: 6px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  min-width: 70px;
  font-weight: 900;
  color: #0f172a;
}
.pill {
  display: inline-flex;
  padding: 6px 10px;
  border-radius: 999px;
  font-weight: 900;
  font-size: 12px;
  border: 1px solid;
}
.pill.ok {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.25);
  color: #047857;
}
.pill.pending {
  background: rgba(245, 158, 11, 0.12);
  border-color: rgba(245, 158, 11, 0.28);
  color: #b45309;
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

/* Action buttons */
.icon-btn {
  width: 38px;
  height: 38px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  cursor: pointer;
  font-weight: 900;
  transition: transform 0.12s ease, box-shadow 0.12s ease, background 0.12s ease;
  box-shadow: 0 6px 14px rgba(2, 6, 23, 0.04);
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.icon-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 18px rgba(2, 6, 23, 0.06);
  background: #fbfdff;
}
.icon-btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}
.icon-btn.danger:hover {
  background: rgba(239, 68, 68, 0.1);
  border-color: rgba(239, 68, 68, 0.25);
}
.icon-btn.okbtn:hover {
  background: rgba(16, 185, 129, 0.1);
  border-color: rgba(16, 185, 129, 0.25);
}
.icon-btn.uploadbtn:hover {
  background: rgba(37, 99, 235, 0.1);
  border-color: rgba(37, 99, 235, 0.25);
}

/* mini spinner */
.spinner-mini {
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 2px solid #e2e8f0;
  border-top: 2px solid #2563eb;
  animation: spin 0.9s linear infinite;
}
@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.hidden-file {
  display: none;
}

/* Empty */
.empty {
  text-align: center;
  padding: 30px;
  font-weight: 800;
  color: #64748b;
}
.empty-sub {
  margin-top: 8px;
  font-size: 12px;
}

/* ✅ GAME MODAL STYLES */
.game-overlay {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.55);
  display: grid;
  place-items: center;
  z-index: 9999;
  padding: 18px;
}
.game-modal {
  width: min(840px, 98vw);
  background: #fff;
  border-radius: 18px;
  border: 1px solid rgba(226, 232, 240, 0.9);
  box-shadow: 0 30px 90px rgba(2, 6, 23, 0.25);
  overflow: hidden;
}
.game-top {
  display: flex;
  justify-content: space-between;
  gap: 14px;
  padding: 16px 18px;
  background: linear-gradient(180deg, #fcfdff, #f2f6ff);
  border-bottom: 1px solid #eef2f7;
}
.game-title {
  font-weight: 1000;
  letter-spacing: -0.02em;
  font-size: 18px;
  color: #0f172a;
}
.game-sub {
  margin-top: 6px;
  font-weight: 800;
  color: #64748b;
  font-size: 12px;
}
.game-badge {
  display: inline-flex;
  margin-left: 8px;
  padding: 3px 8px;
  border-radius: 999px;
  border: 1px solid rgba(37, 99, 235, 0.25);
  background: rgba(37, 99, 235, 0.08);
  color: #1d4ed8;
  font-weight: 900;
}
.game-meta {
  display: flex;
  gap: 10px;
  align-items: center;
}
.game-stat {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 8px 10px;
  min-width: 84px;
  text-align: right;
}
.game-stat-label {
  font-size: 11px;
  font-weight: 900;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.game-stat-value {
  margin-top: 3px;
  font-size: 16px;
  font-weight: 1000;
  color: #0f172a;
}
.game-wrap {
  padding: 16px 18px 14px;
  position: relative;
}
.game-canvas {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 14px;
  border: 1px solid #e2e8f0;
  background: #fff;
}
.game-help {
  margin-top: 12px;
  display: flex;
  gap: 10px;
  align-items: center;
  flex-wrap: wrap;
}
.help-pill {
  padding: 7px 10px;
  border-radius: 999px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  font-weight: 900;
  font-size: 12px;
  color: #334155;
}
.help-btn {
  margin-left: auto;
  padding: 9px 12px;
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  background: #fff;
  font-weight: 1000;
  cursor: pointer;
  transition: transform 0.12s ease, box-shadow 0.12s ease;
}
.help-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(2, 6, 23, 0.08);
}

.game-over {
  position: absolute;
  inset: 16px 18px auto 18px;
  pointer-events: none;
  display: grid;
  place-items: center;
  height: 240px;
}
.go-title {
  font-weight: 1100;
  font-size: 22px;
  color: #0f172a;
  text-shadow: 0 8px 24px rgba(2, 6, 23, 0.12);
}
.go-sub {
  margin-top: 6px;
  font-weight: 900;
  color: #64748b;
  font-size: 12px;
}

.game-bottom {
  padding: 14px 18px 16px;
  border-top: 1px solid #eef2f7;
}
.progress-row {
  display: grid;
  gap: 8px;
}
.progress-label {
  font-weight: 900;
  color: #334155;
  font-size: 12px;
  display: flex;
  align-items: center;
  gap: 10px;
}
.progress-bar {
  height: 10px;
  border-radius: 999px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  width: 20%;
  border-radius: 999px;
  background: linear-gradient(90deg, #2563eb, #1d4ed8);
  transition: width 0.18s ease;
}
.game-note {
  margin-top: 10px;
  font-size: 12px;
  font-weight: 800;
  color: #64748b;
}

/* animated dots */
.dotty span {
  animation: dot 1.2s infinite;
  display: inline-block;
}
.dotty span:nth-child(2) {
  animation-delay: 0.2s;
}
.dotty span:nth-child(3) {
  animation-delay: 0.4s;
}
@keyframes dot {
  0%,
  20% {
    opacity: 0.2;
    transform: translateY(0);
  }
  50% {
    opacity: 1;
    transform: translateY(-1px);
  }
  100% {
    opacity: 0.2;
    transform: translateY(0);
  }
}
</style>

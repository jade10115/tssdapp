<template>
  <div class="page">
    <header class="topbar">
      <div class="brand">
        <div class="seal"></div>
        <div class="titleWrap">
          <h1 class="title">Calendar</h1>
          <p class="subtitle">TUPAD (OSH + PAYOUT) and Events with division filter.</p>
        </div>
      </div>

      <!-- ✅ Only show Create Event if userlevel_id == 1 -->
      <button
        v-if="canCreateEvent"
        class="btn primary"
        type="button"
        @click="openCreateModal"
      >
        + Create Event
      </button>
    </header>

    <section class="content">
      <main class="main">
        <div class="card calendarCard">
          <div v-if="!fullcalendarCssReady" class="muted pad10">Loading calendar styles...</div>
          <FullCalendar v-else :options="calendarOptions" />
        </div>
      </main>

      <aside class="sidebar">
        <div class="card">
          <h2 class="cardTitle">Filters</h2>

          <div class="toggleRow">
            <label class="toggle">
              <input type="checkbox" v-model="filters.showEvents" @change="refetchAll" />
              <span>Events</span>
            </label>

            <label class="toggle">
              <input type="checkbox" v-model="filters.showTupad" @change="refetchAll" />
              <span>TUPAD</span>
            </label>
          </div>

          <div class="field">
            <label class="label">Division (TUPAD)</label>
            <select class="select" v-model="filters.division_id" @change="refetchAll" :disabled="!filters.showTupad">
              <option value="ALL">All</option>
              <option v-for="d in divisions" :key="d.id" :value="String(d.id)">{{ d.division }}</option>
            </select>
          </div>
        </div>

        <div class="card">
          <div class="cardHeaderRow">
            <h2 class="cardTitle">Upcoming Events</h2>
            <button class="btn" type="button" @click="loadUpcoming">Refresh</button>
          </div>

          <div v-if="upcomingLoading" class="muted">Loading...</div>
          <div v-else-if="upcoming.length === 0" class="muted">No upcoming events.</div>

          <ul v-else class="upcomingList">
            <li v-for="u in upcoming" :key="u.id" class="upcomingItem">
              <div class="uTitle">{{ u.title }}</div>
              <div class="uMeta">
                {{ formatDateTime(u.start_at) }}
                <span v-if="u.end_at"> → {{ formatDateTime(u.end_at) }}</span>
              </div>
            </li>
          </ul>
        </div>
      </aside>
    </section>

    <!-- ✅ CREATE MODAL (only for userlevel_id == 1) -->
    <div v-if="modal.open && canCreateEvent" class="calOverlay" @click.self="closeModal">
      <div class="calWrap" role="dialog" aria-modal="true" aria-label="Create Event">
        <div class="calCard">
          <div class="calHeader">
            <div>
              <h2 class="calTitle">Create Event</h2>
              <div class="calSub">Fill in details and select attendees.</div>
            </div>
            <button class="calClose" type="button" @click="closeModal" aria-label="Close">✕</button>
          </div>

          <div class="calBody">
            <div class="grid2">
              <div class="field">
                <label class="label">Title</label>
                <input class="input" v-model="modal.form.title" placeholder="Event title" />
              </div>

              <div class="field">
                <label class="label">All Day</label>
                <select class="select" v-model="modal.form.is_all_day">
                  <option :value="false">No</option>
                  <option :value="true">Yes</option>
                </select>
              </div>
            </div>

            <div class="field">
              <label class="label">Description</label>
              <textarea class="textarea" v-model="modal.form.description" placeholder="Optional"></textarea>
            </div>

            <div class="grid2">
              <div class="field">
                <label class="label">Start</label>
                <input class="input" type="datetime-local" v-model="modal.form.start_at" />
              </div>

              <div class="field">
                <label class="label">End (Deadline)</label>
                <input class="input" type="datetime-local" v-model="modal.form.end_at" />
              </div>
            </div>

            <div class="card subtle">
              <div class="attendeesHeader">
                <h3 class="subTitle">Attendees</h3>
                <button class="btn" type="button" @click="toggleAttendeeDropdown">+ Add Attendees</button>
              </div>

              <div v-if="attendeeDropdownOpen" class="dropdown">
                <div class="dropdownSearch">
                  <input
                    class="input"
                    v-model="employeeSearch"
                    placeholder="Search employee, position, division, phone..."
                  />
                </div>

                <div class="dropdownList">
                  <button
                    v-for="emp in filteredEmployees"
                    :key="emp.user_id"
                    class="dropdownItem"
                    type="button"
                    @click="addAttendee(emp)"
                  >
                    <div class="empName">{{ emp.full_name }}</div>
                    <div class="empMeta">
                      {{ emp.position }}
                      <span v-if="emp.division">• {{ emp.division }}</span>
                      <span v-if="emp.phone">• {{ emp.phone }}</span>
                    </div>
                  </button>
                </div>
              </div>

              <div v-if="modal.form.attendees.length === 0" class="muted">No attendees selected.</div>

              <div v-else class="chips">
                <div class="chip" v-for="a in modal.form.attendees" :key="a.user_id">
                  <span class="chipText">
                    {{ a.full_name }} <span v-if="a.position">({{ a.position }})</span>
                  </span>
                  <button class="chipX" type="button" @click="removeAttendee(a.user_id)">✕</button>
                </div>
              </div>
            </div>

            <div v-if="modal.error" class="errorBox">{{ modal.error }}</div>
          </div>

          <div class="calFooter">
            <button class="btn" type="button" @click="closeModal" :disabled="modal.saving">Cancel</button>
            <button class="btn primary" type="button" @click="saveEvent" :disabled="modal.saving">
              {{ modal.saving ? "Saving..." : "Save Event" }}
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import Swal from "sweetalert2";

import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid";
import interactionPlugin from "@fullcalendar/interaction";

import { inject } from "vue";

const API_BASE = inject("API_BASE");


/** ✅ Role check (Admin = userlevel_id 1) */
const userlevelId = ref(Number(localStorage.getItem("userlevel_id") || 0));
const canCreateEvent = computed(() => userlevelId.value === 1);

const fullcalendarCssReady = ref(false);
function ensureFullCalendarCss() {
  const links = [
    "https://cdn.jsdelivr.net/npm/@fullcalendar/core@6.1.15/main.min.css",
    "https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid@6.1.15/main.min.css",
    "https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid@6.1.15/main.min.css",
  ];
  let loaded = 0;

  links.forEach((href) => {
    const id = "fc-css-" + btoa(href).replace(/=+/g, "");
    if (document.getElementById(id)) {
      loaded++;
      if (loaded === links.length) fullcalendarCssReady.value = true;
      return;
    }
    const link = document.createElement("link");
    link.id = id;
    link.rel = "stylesheet";
    link.href = href;
    link.onload = () => {
      loaded++;
      if (loaded === links.length) fullcalendarCssReady.value = true;
    };
    link.onerror = () => {
      loaded++;
      if (loaded === links.length) fullcalendarCssReady.value = true;
    };
    document.head.appendChild(link);
  });
}

async function safeJson(res) {
  const txt = await res.text();
  if (!txt) return {};
  try { return JSON.parse(txt); } catch { return {}; }
}

/* State */
const filters = ref({ showEvents: true, showTupad: true, division_id: "ALL" });
const divisions = ref([]);
const upcoming = ref([]);
const upcomingLoading = ref(false);

const employees = ref([]);
const employeeSearch = ref("");
const attendeeDropdownOpen = ref(false);

const modal = ref({
  open: false,
  saving: false,
  error: "",
  form: { title: "", description: "", start_at: "", end_at: "", is_all_day: false, attendees: [] },
});

const calendarEvents = ref([]);

/* Calendar */
const calendarOptions = computed(() => ({
  plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
  initialView: "dayGridMonth",
  headerToolbar: { left: "prev,next today", center: "title", right: "dayGridMonth,timeGridWeek,timeGridDay" },
  selectable: true,
  height: "auto",
  events: calendarEvents.value,

  datesSet: async (info) => {
    await fetchFeed(info.startStr, info.endStr);
  },

  // ✅ only admin can click date to create event
  dateClick: (info) => {
    if (!canCreateEvent.value) return;

    openCreateModal();
    const dt = new Date(info.date);
    dt.setHours(9, 0, 0, 0);
    modal.value.form.start_at = toDatetimeLocal(dt);
    const end = new Date(dt);
    end.setHours(10, 0, 0, 0);
    modal.value.form.end_at = toDatetimeLocal(end);
    modal.value.form.is_all_day = false;
  },
}));

onMounted(async () => {
  // refresh user level on mount (in case it changed)
  userlevelId.value = Number(localStorage.getItem("userlevel_id") || 0);

  ensureFullCalendarCss();
  await loadDivisions();
  await loadEmployees();
  await loadUpcoming();
  await refetchAll();
});

async function loadDivisions() {
  const res = await fetch(`${API_BASE}/api/calendar/divisions`, { headers: { Accept: "application/json" } });
  const json = await safeJson(res);
  divisions.value = json.data || [];
}

async function loadEmployees() {
  const res = await fetch(`${API_BASE}/api/calendar/employees`, { headers: { Accept: "application/json" } });
  const json = await safeJson(res);
  employees.value = json.data || [];
}

async function loadUpcoming() {
  try {
    upcomingLoading.value = true;
    const res = await fetch(`${API_BASE}/api/calendar/upcoming?days=7`, { headers: { Accept: "application/json" } });
    const json = await safeJson(res);
    upcoming.value = json.data || [];
  } finally {
    upcomingLoading.value = false;
  }
}

async function fetchFeed(startISO, endISO) {
  const params = new URLSearchParams({
    start: startISO,
    end: endISO,
    showEvents: filters.value.showEvents ? "1" : "0",
    showTupad: filters.value.showTupad ? "1" : "0",
    division_id: filters.value.division_id || "ALL",
  });

  const res = await fetch(`${API_BASE}/api/calendar/feed?${params.toString()}`, {
    headers: { Accept: "application/json" },
  });

  const json = await safeJson(res);

  calendarEvents.value = (json.data || []).map((x) => ({
    id: x.id,
    title: x.title,
    start: x.start,
    end: x.end,
    allDay: x.allDay,
    backgroundColor: x.color,
    borderColor: x.color,
    extendedProps: { type: x.type, description: x.description, meta: x.meta },
  }));
}

async function refetchAll() {
  await loadUpcoming();
  const now = new Date();
  const start = new Date(now.getFullYear(), now.getMonth(), 1);
  const end = new Date(now.getFullYear(), now.getMonth() + 1, 1);
  await fetchFeed(start.toISOString(), end.toISOString());
}

/* Employees filter */
const filteredEmployees = computed(() => {
  const q = employeeSearch.value.trim().toLowerCase();
  if (!q) return employees.value;
  return employees.value.filter((e) => {
    return (
      String(e.full_name || "").toLowerCase().includes(q) ||
      String(e.position || "").toLowerCase().includes(q) ||
      String(e.division || "").toLowerCase().includes(q) ||
      String(e.phone || "").toLowerCase().includes(q)
    );
  });
});

/* Modal */
function openCreateModal() {
  if (!canCreateEvent.value) return; // ✅ guard

  modal.value.open = true;
  modal.value.error = "";
  attendeeDropdownOpen.value = false;
  employeeSearch.value = "";

  const now = new Date();
  now.setMinutes(0, 0, 0);

  if (!modal.value.form.start_at) modal.value.form.start_at = toDatetimeLocal(now);
  if (!modal.value.form.end_at) {
    const end = new Date(now);
    end.setHours(end.getHours() + 1);
    modal.value.form.end_at = toDatetimeLocal(end);
  }

  document.body.classList.add("modal-open-lock");
}

function closeModal() {
  modal.value.open = false;
  modal.value.saving = false;
  modal.value.error = "";
  modal.value.form = { title: "", description: "", start_at: "", end_at: "", is_all_day: false, attendees: [] };
  document.body.classList.remove("modal-open-lock");
}

function toggleAttendeeDropdown() {
  attendeeDropdownOpen.value = !attendeeDropdownOpen.value;
}

function addAttendee(emp) {
  if (modal.value.form.attendees.some((a) => a.user_id === emp.user_id)) return;
  modal.value.form.attendees.push(emp);
}

function removeAttendee(userId) {
  modal.value.form.attendees = modal.value.form.attendees.filter((a) => a.user_id !== userId);
}

/* Save */
async function saveEvent() {
  if (!canCreateEvent.value) {
    await Swal.fire({ icon: "info", title: "Not allowed", text: "Only Admin (userlevel_id = 1) can create events." });
    return;
  }

  modal.value.error = "";

  if (!modal.value.form.title.trim()) {
    modal.value.error = "Title is required.";
    return;
  }
  if (!modal.value.form.start_at) {
    modal.value.error = "Start is required.";
    return;
  }

  modal.value.saving = true;

  try {
    const payload = {
      title: modal.value.form.title,
      description: modal.value.form.description,
      start_at: modal.value.form.start_at,
      end_at: modal.value.form.end_at || null,
      is_all_day: !!modal.value.form.is_all_day,
      attendee_user_ids: modal.value.form.attendees.map((a) => Number(a.user_id)),
    };

    const res = await fetch(`${API_BASE}/api/calendar/events`, {
      method: "POST",
      headers: { Accept: "application/json", "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    if (!res.ok) {
      const err = await safeJson(res);
      const msg = err.message || "Failed to save event.";
      modal.value.error = msg;
      await Swal.fire({ icon: "error", title: "Save Failed", text: msg });
      return;
    }

    await Swal.fire({
      icon: "success",
      title: "Saved!",
      text: "Event created successfully.",
      confirmButtonText: "OK",
    });

    closeModal();
    await refetchAll();
  } finally {
    modal.value.saving = false;
  }
}

/* Helpers */
function toDatetimeLocal(dateObj) {
  const pad = (n) => String(n).padStart(2, "0");
  const y = dateObj.getFullYear();
  const m = pad(dateObj.getMonth() + 1);
  const d = pad(dateObj.getDate());
  const hh = pad(dateObj.getHours());
  const mm = pad(dateObj.getMinutes());
  return `${y}-${m}-${d}T${hh}:${mm}`;
}
function formatDateTime(value) {
  if (!value) return "";
  const d = new Date(value);
  return d.toLocaleString();
}
</script>

<style scoped>
/* ✅ Make SweetAlert always ABOVE your modal */
:global(.swal2-container) {
  z-index: 2147483647 !important;
}

.page {
  padding: 16px;
  max-width: 1400px;
  margin: 0 auto;
  box-sizing: border-box;
  font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  color: #0f172a;
}

.topbar {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  margin-bottom: 14px;
  padding: 12px;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.brand { display: flex; gap: 12px; align-items: center; min-width: 260px; }
.seal {
  width: 40px; height: 40px; border-radius: 999px; border: 2px solid #1e3a8a;
  background: radial-gradient(circle at 30% 30%, rgba(30,58,138,0.20), rgba(30,58,138,0.02)),
              linear-gradient(180deg, #ffffff, #f1f5f9);
}
.title { margin: 0; font-size: 20px; font-weight: 900; }
.subtitle { margin: 4px 0 0; font-size: 12.5px; color: #475569; }

.content { display: flex; gap: 14px; align-items: flex-start; }
.main { flex: 1 1 auto; min-width: 0; }

.sidebar {
  flex: 0 0 340px;
  width: 340px;
  max-width: 340px;
  display: flex;
  flex-direction: column;
  gap: 14px;
  position: sticky;
  top: 12px;
}

.card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 12px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}

.calendarCard { padding: 10px; width: 100%; overflow: hidden; }
.cardTitle { margin: 0 0 10px; font-size: 14px; font-weight: 900; }
.cardHeaderRow { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }

.toggleRow { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px; }
.toggle { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #334155; }
.field { margin-bottom: 12px; }
.label { display: block; font-size: 12px; color: #475569; margin-bottom: 6px; }

.input, .select, .textarea {
  width: 100%; box-sizing: border-box;
  border: 1px solid #cbd5e1;
  border-radius: 12px;
  padding: 10px;
  font-size: 14px;
  outline: none;
  background: #fff;
}
.textarea { min-height: 90px; resize: vertical; }

.btn {
  border: 1px solid #cbd5e1;
  background: #ffffff;
  padding: 10px 12px;
  border-radius: 12px;
  font-size: 14px;
  cursor: pointer;
}
.btn.primary { border-color: #1e3a8a; background: #1e3a8a; color: #ffffff; }

.upcomingList { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
.upcomingItem { border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px; }
.uTitle { font-weight: 900; font-size: 13px; }
.uMeta { margin-top: 4px; font-size: 12px; color: #64748b; }

.muted { font-size: 13px; color: #64748b; }
.pad10 { padding: 10px; }

.grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.card.subtle { background: #f8fafc; }

.attendeesHeader { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
.subTitle { margin: 0; font-size: 13px; font-weight: 900; }

.dropdown { border: 1px solid #cbd5e1; border-radius: 14px; background: #fff; overflow: hidden; margin-bottom: 10px; }
.dropdownSearch { padding: 10px; border-bottom: 1px solid #e2e8f0; }
.dropdownList { max-height: 260px; overflow: auto; }
.dropdownItem { width: 100%; text-align: left; padding: 10px; border: none; border-bottom: 1px solid #f1f5f9; background: #fff; cursor: pointer; }
.dropdownItem:hover { background: #f8fafc; }
.empName { font-size: 13px; font-weight: 900; }
.empMeta { font-size: 12px; color: #64748b; margin-top: 2px; }

.chips { display: flex; flex-wrap: wrap; gap: 8px; }
.chip { display: inline-flex; align-items: center; gap: 8px; border: 1px solid #cbd5e1; border-radius: 999px; padding: 6px 10px; }
.chipX { border: none; background: transparent; cursor: pointer; font-size: 14px; }

.errorBox { margin-top: 10px; border: 1px solid #fda29b; background: #fffbfa; color: #b42318; padding: 10px; border-radius: 12px; font-size: 13px; }

/* Modal */
.calOverlay {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.60);
  z-index: 10000;
}
.calWrap {
  position: fixed;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: calc(100% - 32px);
  max-width: 760px;
  z-index: 10001;
}
.calCard {
  background: #ffffff;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 18px 60px rgba(15, 23, 42, 0.35);
  overflow: hidden;
  max-height: calc(100vh - 32px);
  display: flex;
  flex-direction: column;
}
.calHeader {
  display: flex;
  justify-content: space-between;
  padding: 14px;
  border-bottom: 1px solid #e2e8f0;
}
.calTitle { margin: 0; font-size: 16px; font-weight: 900; }
.calSub { margin-top: 4px; font-size: 12px; color: #64748b; }
.calClose { border: 1px solid #cbd5e1; background: #fff; cursor: pointer; padding: 6px 10px; border-radius: 12px; }
.calBody { padding: 14px; overflow: auto; }
.calFooter {
  padding: 14px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

/* Responsive */
@media (max-width: 980px) {
  .content { flex-direction: column; }
  .sidebar { width: 100%; max-width: none; flex: 1 1 auto; position: static; }
  .grid2 { grid-template-columns: 1fr; }
}
:global(body.modal-open-lock) { overflow: hidden; }
</style>

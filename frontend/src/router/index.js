import { createRouter, createWebHistory } from "vue-router";

import Layout from "../components/layouts/Layout.vue";
import LayoutSupply from "../components/layouts/LayoutSupply.vue";
import LayoutFo from "../components/layouts/LayoutUser.vue";

import Login from "../components/layouts/Login.vue";

// ADMIN
import Dashboard from "../components/layouts/Dashboard.vue";
import Tupad from "../components/layouts/Tupad.vue";
import TUPADadl from "../components/layouts/Tupad_adl.vue";
import Breakdown from "../components/layouts/Tupad_breakdown.vue";
import Adlstatus from "../components/layouts/Adlstatus.vue";
import Employees from "../components/layouts/Employees.vue";
import Positions from "../components/layouts/Positions.vue";
import Adlbreakdown from "../components/layouts/Per_adl_breakdown.vue";
import Calendar from "../components/layouts/Eventcalendar.vue";
import Supply from "../components/layouts/Supply.vue";
import Supplyrequest from "../components/layouts/Supplyrequest.vue";
import Supplyreport from "../components/layouts/Supplyreport.vue";
import Cashadvance from "../components/layouts/tupadcashadvance.vue";

// SUPPLY ADMIN
import DashboardSupply from "../components/layouts/DashboardSupply.vue";
import Supply_Supply from "../components/layouts/Supply_Supply.vue";
import Supplyrequest_Supply from "../components/layouts/Supplyrequest_Supply.vue";
import Supplyreport_Supply from "../components/layouts/Supplyreport_Supply.vue";

// USER/FO
import DashboardFo from "../components/layouts/DashboardFo.vue";
import Tupad_Fo from "../components/layouts/Tupad_Fo.vue";
import Tupad_adl_Fo from "../components/layouts/Tupad_adl_Fo.vue";
import Tupad_breakdown_Fo from "../components/layouts/Tupad_breakdown_Fo.vue";
import AdlstatusFo from "../components/layouts/AdlstatusFo.vue";
import Per_adl_breakdown_Fo from "../components/layouts/Per_adl_breakdown_Fo.vue";
import Supply_Fo from "../components/layouts/Supply_Fo.vue";
import SupplyrequestUser from "../components/layouts/SupplyrequestUser.vue";

const routes = [
  { path: "/login", name: "Login", component: Login, meta: { requiresAuth: false } },

  // ✅ ADMIN
  {
    path: "/",
    component: Layout,
    meta: { requiresAuth: true },
    children: [
      { path: "", redirect: "/dashboard" },
      { path: "dashboard", name: "Dashboard", component: Dashboard },
      { path: "tupad", name: "Tupad", component: Tupad },
      { path: "TUPADadl", name: "TUPADadl", component: TUPADadl },
      { path: "Calendar", name: "Calendar", component: Calendar },
      { path: "Breakdown", name: "Breakdown", component: Breakdown },
      { path: "Adlstatus", name: "Adlstatus", component: Adlstatus },
      { path: "Employees", name: "Employees", component: Employees },
      { path: "Positions", name: "Positions", component: Positions },
      { path: "Adlbreakdown", name: "Adlbreakdown", component: Adlbreakdown },
      { path: "Supply", name: "Supply", component: Supply },
      { path: "Supplyrequest", name: "Supplyrequest", component: Supplyrequest },
      { path: "Supplyreport", name: "Supplyreport", component: Supplyreport },
      { path: "Cashadvance", name: "Cashadvance", component: Cashadvance },
    ],
  },

  // ✅ SUPPLY ADMIN
  {
    path: "/supply",
    component: LayoutSupply,
    meta: { requiresAuth: true },
    children: [
      { path: "", redirect: "/supply/DashboardSupply" },
      { path: "DashboardSupply", name: "DashboardSupply", component: DashboardSupply },
      { path: "items", name: "SupplyItems", component: Supply_Supply },
      { path: "requests", name: "SupplyRequests", component: Supplyrequest_Supply },
      { path: "reports", name: "SupplyReports", component: Supplyreport_Supply },

      // ✅ Calendar for Supply
      { path: "calendar", name: "SupplyCalendar", component: Calendar },
    ],
  },

  // ✅ FO / USER
  {
    path: "/fo",
    component: LayoutFo,
    meta: { requiresAuth: true },
    children: [
      { path: "", redirect: "/fo/dashboard" },
      { path: "dashboard", name: "DashboardFo", component: DashboardFo },
      { path: "tupad", name: "TupadFo", component: Tupad_Fo },
      { path: "tupad-adl", name: "TupadAdlFo", component: Tupad_adl_Fo },
      { path: "tupad-breakdown", name: "TupadBreakdownFo", component: Tupad_breakdown_Fo },
      { path: "adlstatus", name: "AdlstatusFo", component: AdlstatusFo },
      { path: "adlbreakdown", name: "PerAdlBreakdownFo", component: Per_adl_breakdown_Fo },
      { path: "supply", name: "SupplyFo", component: Supply_Fo },
      { path: "supplyrequest", name: "SupplyrequestUser", component: SupplyrequestUser },

      // ✅ Calendar for FO
      { path: "calendar", name: "FoCalendar", component: Calendar },
    ],
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("auth_token");
  const ul = Number(localStorage.getItem("userlevel_id") || 0);

  if (to.meta.requiresAuth && !token) return next("/login");

  if (to.path === "/login" && token) {
    if (ul === 1) return next("/dashboard");
    if (ul === 2) return next("/supply/DashboardSupply");
    if (ul === 3) return next("/fo/dashboard");
    return next("/dashboard");
  }

  next();
});

export default router;

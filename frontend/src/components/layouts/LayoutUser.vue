<template>
  <div class="layout">
    <!-- SIDEBAR -->
    <aside
      :class="[
        'sidebar bg-light border-end d-flex flex-column',
        isOpen ? 'sidebar-open' : 'sidebar-closed',
      ]"
      aria-label="Sidebar"
    >
      <!-- PROFILE -->
      <div class="p-3 d-flex align-items-center sidebar-top">
        <img
          v-if="user?.profile_image"
          :src="user.profile_image"
          class="rounded-circle me-2 profile-img"
          width="40"
          height="40"
          alt="User profile"
        />
        <div v-if="isOpen" class="fw-bold text-truncate">
          {{ user.first_name }} {{ user.last_name }}
        </div>
      </div>

      <!-- NAV -->
      <nav class="flex-grow-1 px-2 sidebar-nav">
        <!-- DASHBOARD (FO) -->
        <router-link
          to="/fo/dashboard"
          class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-2 sidebar-link"
          active-class="active-link"
        >
          <font-awesome-icon :icon="['fas', 'tachometer-alt']" class="me-2" />
          <span v-if="isOpen">Dashboard</span>
        </router-link>

        <!-- OO3 (FO - TUPAD ONLY) -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openOO3 = !openOO3"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'hand-holding-usd']" class="me-2" />
              <span v-if="isOpen">OO3 DILEEP</span>
            </span>
            <i v-if="isOpen" :class="['bi', openOO3 ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openOO3 && isOpen" class="ps-4">
              <router-link
                to="/fo/tupad"
                class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
                active-class="active-link"
              >
                DOLE-TUPAD
              </router-link>

              <router-link
                to="/fo/tupad-adl"
                class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
                active-class="active-link"
              >
                TUPAD ADL
              </router-link>

              <router-link
                to="/fo/tupad-breakdown"
                class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
                active-class="active-link"
              >
                TUPAD Breakdown
              </router-link>

              <router-link
                to="/fo/adlstatus"
                class="d-block p-2 text-decoration-none text-dark rounded sidebar-link"
                active-class="active-link"
              >
                ADL Status
              </router-link>
            </div>
          </transition>
        </div>

        <!-- PER ADL BREAKDOWN (FO) -->
        <div class="mt-2">
          <router-link
            to="/fo/adlbreakdown"
            class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
            active-class="active-link"
          >
            <font-awesome-icon :icon="['fas', 'chart-pie']" class="me-2" />
            <span v-if="isOpen">Per ADL Breakdown</span>
          </router-link>
        </div>

        <!-- SUPPLY (FO) -->
        <div class="mt-2">
          <button
            type="button"
            class="btn w-100 d-flex align-items-center justify-content-between p-2 text-start text-dark bg-transparent border-0 sidebar-link"
            @click="openSupply = !openSupply"
          >
            <span class="d-flex align-items-center">
              <font-awesome-icon :icon="['fas', 'shopping-cart']" class="me-2" />
              <span v-if="isOpen">Supply</span>
            </span>
            <i v-if="isOpen" :class="['bi', openSupply ? 'bi-chevron-up' : 'bi-chevron-down']"></i>
          </button>

          <transition name="slide-fade">
            <div v-show="openSupply && isOpen" class="ps-4">
              <router-link
                to="/fo/supply"
                class="d-block p-2 text-decoration-none text-dark rounded mb-1 sidebar-link"
                active-class="active-link"
              >
                Items
              </router-link>

              <router-link
                to="/fo/supplyrequest"
                class="d-block p-2 text-decoration-none text-dark rounded sidebar-link"
                active-class="active-link"
              >
                Request
              </router-link>
            </div>
          </transition>

          <!-- ✅ CALENDAR (FO) -->
<div class="mt-2">
  <router-link
    to="/fo/calendar"
    class="d-flex align-items-center p-2 text-decoration-none text-dark rounded mb-2 sidebar-link"
    active-class="active-link"
  >
    <font-awesome-icon :icon="['fas', 'calendar-alt']" class="me-2" />
    <span v-if="isOpen">Calendar</span>
  </router-link>
</div>

        </div>
      </nav>

      <!-- LOGOUT -->
      <div class="p-3 border-top sidebar-bottom">
        <button class="btn btn-outline-danger w-100" @click="handleLogout">
          <font-awesome-icon :icon="['fas', 'sign-out-alt']" class="me-2" />
          <span v-if="isOpen">Logout</span>
        </button>
      </div>
    </aside>

    <!-- MAIN -->
    <div :class="['content', isOpen ? 'content-shift' : 'content-normal']">
      <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm px-3 d-flex justify-content-between">
        <div class="d-flex align-items-center">
          <button class="btn btn-outline-primary me-3" @click="toggleSidebar">
            <i class="bi bi-list"></i>
          </button>
          <router-link to="/fo/dashboard" class="navbar-brand">TSSD (User)</router-link>
        </div>

        <div class="d-flex align-items-center">
          <img :src="bagongLogo" alt="Bagong Pilipinas" height="40" class="me-3" />
          <img :src="doleLogo" alt="DOLE" height="40" />
        </div>
      </nav>

      <main class="p-4">
        <router-view />
      </main>
    </div>

    <!-- MOBILE BACKDROP -->
    <div v-if="mobileBackdrop" class="mobile-backdrop" @click="isOpen = false"></div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch } from "vue";
import Swal from "sweetalert2";
import axios from "axios";
import bagongLogo from "../../assets/logo/bagongphlogo.png";
import doleLogo from "../../assets/logo/dole.png";
import { useRouter } from "vue-router";

export default {
  setup() {
    const router = useRouter();

    const isOpen = ref(true);
    const openOO3 = ref(false);
    const openSupply = ref(false);

    const user = ref({
      first_name: "",
      last_name: "",
      profile_image: "",
    });

    onMounted(async () => {
      try {
        await axios.get("http://localhost:8000/sanctum/csrf-cookie", {
          withCredentials: true,
        });

        const res = await axios.get("http://localhost:8000/api/session", {
          withCredentials: true,
        });

        if (res.data.logged_in) {
          user.value = {
            first_name: res.data.user.first_name || "",
            last_name: res.data.user.last_name || "",
            profile_image: res.data.user.profile_image || "",
          };
        } else {
          router.push("/Login");
        }
      } catch (error) {
        router.push("/Login");
      }
    });

    const toggleSidebar = () => (isOpen.value = !isOpen.value);

    const handleLogout = async () => {
      try {
        await axios.post("api/logout", {});
        localStorage.removeItem("auth_token");
        delete axios.defaults.headers.common["Authorization"];

        Swal.fire({
          title: "Logged out!",
          icon: "success",
          timer: 1500,
          showConfirmButton: false,
        });
      } catch (error) {
        localStorage.removeItem("auth_token");
        delete axios.defaults.headers.common["Authorization"];
      } finally {
        setTimeout(() => router.push("/Login"), 800);
      }
    };

    // ✅ mobile overlay behavior
    const mobileBackdrop = computed(() => window.innerWidth <= 768 && isOpen.value);

    const onResize = () => {
      if (window.innerWidth <= 768) isOpen.value = false;
      else isOpen.value = true;
    };

    onMounted(() => {
      window.addEventListener("resize", onResize);
      onResize();
    });

    watch(isOpen, (v) => {
      // close dropdown sections when sidebar collapsed
      if (!v) {
        openOO3.value = false;
        openSupply.value = false;
      }
    });

    return {
      isOpen,
      openOO3,
      openSupply,
      user,
      toggleSidebar,
      handleLogout,
      bagongLogo,
      doleLogo,
      mobileBackdrop,
    };
  },
};
</script>

<style>
/* Layout wrapper */
.layout {
  min-height: 100vh;
}

/* Sidebar positioning */
.sidebar {
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  overflow: hidden;
  z-index: 1030;
}

/* Keep nav scrollable if too tall */
.sidebar-nav {
  overflow-y: auto;
  padding-bottom: 16px;
}

/* Sidebar widths */
.sidebar-open {
  width: 240px;
  transition: width 0.25s ease;
}
.sidebar-closed {
  width: 72px;
  transition: width 0.25s ease;
}

/* Profile */
.profile-img {
  object-fit: cover;
}
.sidebar-top {
  min-height: 64px;
}
.sidebar-bottom {
  background: #f8f9fa;
}

/* Content shifts with sidebar */
.content {
  min-height: 100vh;
  transition: margin-left 0.25s ease;
}
.content-shift {
  margin-left: 240px;
}
.content-normal {
  margin-left: 72px;
}

/* Active router link */
.active-link {
  background: #0d6efd !important;
  color: #fff !important;
}

/* Collapsible transition */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.25s ease;
}
.slide-fade-enter-from,
.slide-fade-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateY(-4px);
}
.slide-fade-enter-to,
.slide-fade-leave-from {
  max-height: 240px;
  opacity: 1;
  transform: translateY(0);
}

/* Sidebar link hover */
.sidebar-link {
  cursor: pointer;
  transition: all 0.2s ease;
}
.sidebar-link:hover {
  background-color: #e9ecef;
  color: #0d6efd !important;
  transform: translateX(2px);
}

/* ✅ MOBILE: sidebar becomes overlay */
@media (max-width: 768px) {
  .sidebar-open {
    width: 80vw;
    max-width: 320px;
  }
  .sidebar-closed {
    width: 0;
    border: none !important;
  }

  .content-shift,
  .content-normal {
    margin-left: 0 !important;
  }

  .mobile-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.25);
    z-index: 1020;
  }
}
</style>

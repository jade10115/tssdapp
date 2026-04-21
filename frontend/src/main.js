import { createApp } from "vue";
import App from "./App.vue";
import router from "./router";
import "./style.css";

// Bootstrap
import "bootstrap/dist/css/bootstrap.min.css";
import "bootstrap/dist/js/bootstrap.bundle.min.js";

// FontAwesome
import { library } from "@fortawesome/fontawesome-svg-core";
import { FontAwesomeIcon } from "@fortawesome/vue-fontawesome";
import {
  faTachometerAlt,
  faBriefcase,
  faUserCog,
  faHandHoldingUsd,
  faBook,
  faShoppingCart,
  faSignOutAlt,
  faBox,
  faClock,
  faUsers,
  faDollarSign,
  faCalendarAlt,
  faUserPlus,
} from "@fortawesome/free-solid-svg-icons";

library.add(
  faUsers,
  faTachometerAlt,
  faBriefcase,
  faUserCog,
  faHandHoldingUsd,
  faBook,
  faShoppingCart,
  faSignOutAlt,
  faBox,
  faClock,
  faDollarSign,
  faCalendarAlt,
  faUserPlus
);

// ✅ AXIOS CONFIG
import axios from "axios";

const API_BASE_URL = import.meta.env.VITE_API_URL;

axios.defaults.withCredentials = true;
axios.defaults.baseURL = API_BASE_URL;

// =====================

const app = createApp(App);
app.use(router);
app.component("font-awesome-icon", FontAwesomeIcon);

app.provide("API_BASE", API_BASE_URL);

app.mount("#app");
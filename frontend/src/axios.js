import axios from "axios";

// ✅ Automatically use the Render URL in production, or localhost in development
axios.defaults.baseURL = import.meta.env.VITE_API_URL || "http://127.0.0.1:8000";

// ✅ Set to false because you are using Bearer tokens, not cookies! (Prevents CORS errors)
axios.defaults.withCredentials = false;

// ✅ Automatically attach token to EVERY request globally
axios.interceptors.request.use((config) => {
  const token = localStorage.getItem("auth_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// ✅ Auto logout on 401
axios.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem("auth_token");
      localStorage.removeItem("userlevel_id");
      window.location.href = "/login";
    }
    return Promise.reject(error);
  }
);

export default axios;
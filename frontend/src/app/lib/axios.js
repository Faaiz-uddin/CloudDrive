import axios from "axios";
import { logoutUser } from "../context/AuthContext";

const api = axios.create({
  baseURL: "http://localhost:8000/api",
  withCredentials: true, // required for httpOnly cookies
  headers: { "Content-Type": "application/json" },
});

// Request interceptor: fetch CSRF cookie before login/register
api.interceptors.request.use(async (config) => {
  if (!config._csrfFetched) {
    await axios.get("http://localhost:8000/sanctum/csrf-cookie", {
      withCredentials: true,
    });
    config._csrfFetched = true;
  }
  return config;
});

// Response interceptor: auto logout on 401
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      if (typeof window !== "undefined") {
        localStorage.removeItem("user");
        window.location.href = "/auth/login";
      }
    }
    return Promise.reject(error);
  }
);

export default api;
import axios from "axios";

// URL base de tu backend Laravel (verifica que el puerto sea el correcto) //
const apiClient = axios.create({
  baseURL: "http://localhost:8000/api",
  headers: {
    Accept: "application/json",
    "Content-Type": "application/json",
  },
});

// Interceptor de Peticiones: Inyecta el token automáticamente //
apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem("token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

// Interceptor de Respuestas: Manejo de errores 401 (No autorizado) //
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Si el token expiró o es inválido, limpiamos la sesión //
      localStorage.removeItem("token");
      localStorage.removeItem("user");

      // Solo redirigimos si no estamos ya en el login //
      if (window.location.pathname !== "/login") {
        window.location.href = "/login?expired=1";
      }
    }
    return Promise.reject(error);
  },
);

export default apiClient;

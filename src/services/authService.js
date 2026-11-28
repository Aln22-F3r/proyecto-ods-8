import apiClient from "../api/client";

export const authService = {
  // Obtiene los roles disponibles para registrarse (Candidato y Empresa) //
  getRolesRegistro: async () => {
    const response = await apiClient.get("/roles/registro");
    return response.data.data;
  },

  // Envía el formulario de registro al backend //
  register: async (data) => {
    const response = await apiClient.post("/auth/register", data);
    return response.data;
  },
};

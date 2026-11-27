import apiClient from "../api/client";

export const ofertaService = {
  // Obtiene la lista de ofertas para la página principal //
  getOfertas: async (categoriaId = null) => {
    const params = categoriaId ? { categoria_id: categoriaId } : {};
    const response = await apiClient.get("/ofertas-publicas", { params });
    return response.data; // Devuelve el objeto con data, links, meta, etc.
  },

  // Obtiene el detalle de una oferta específica //
  getOfertaById: async (id) => {
    const response = await apiClient.get(`/ofertas-publicas/${id}`);
    return response.data.data;
  },
};

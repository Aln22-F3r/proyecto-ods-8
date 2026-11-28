import apiClient from "../api/client";

export const postulacionService = {
  crear: async (ofertaId, comentario = null) => {
    const response = await apiClient.post("/candidato/postulaciones", {
      oferta_empleo_id: ofertaId,
      comentario: comentario,
    });
    return response.data;
  },

  // Para el Candidato: ver sus propias postulaciones //
  getMisPostulaciones: async () => {
    const response = await apiClient.get("/candidato/postulaciones");
    return response.data;
  },

  // Para la Empresa: ver quiénes se postularon a sus ofertas //
  getPostulacionesRecibidas: async () => {
    const response = await apiClient.get("/empresa/postulaciones");
    return response.data;
  },
};

import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { postulacionService } from "../services/postulacionService";
import Spinner from "../components/Spinner";

const MisPostulaciones = () => {
  const [postulaciones, setPostulaciones] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchPostulaciones = async () => {
      try {
        const response = await postulacionService.getMisPostulaciones();
        setPostulaciones(response.data || response);
      } catch (error) {
        console.error("Error al cargar postulaciones", error);
      } finally {
        setLoading(false);
      }
    };
    fetchPostulaciones();
  }, []);

  const getEstadoColor = (estado) => {
    switch (estado) {
      case "Aceptada":
        return "bg-green-100 text-green-700 border-green-200";
      case "Rechazada":
        return "bg-red-100 text-red-700 border-red-200";
      case "En revisión":
        return "bg-yellow-100 text-yellow-700 border-yellow-200";
      case "Retirada":
        return "bg-gray-100 text-gray-700 border-gray-200";
      default:
        return "bg-blue-100 text-blue-700 border-blue-200";
    }
  };

  if (loading)
    return (
      <div className="p-12 flex justify-center">
        <Spinner />
      </div>
    );

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-4xl mx-auto">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold text-gray-800">
            Mis Postulaciones
          </h1>
          <Link to="/dashboard" className="text-blue-600 hover:underline">
            ← Volver al panel
          </Link>
        </div>

        {postulaciones.length === 0 ? (
          <div className="bg-white p-8 text-center rounded-lg shadow text-gray-500">
            Aún no te has postulado a ninguna oferta. <br />
            <Link
              to="/"
              className="text-blue-600 hover:underline mt-2 inline-block"
            >
              Explorar ofertas
            </Link>
          </div>
        ) : (
          <div className="space-y-4">
            {postulaciones.map((postulacion) => (
              <div
                key={postulacion.id}
                className="bg-white p-6 rounded-lg shadow flex flex-col md:flex-row justify-between md:items-center gap-4"
              >
                <div>
                  <h3 className="font-bold text-lg text-gray-800">
                    {postulacion.oferta}
                  </h3>
                  <p className="text-sm text-gray-600 font-medium">
                    {postulacion.empresa}
                  </p>
                  <p className="text-xs text-gray-400 mt-2 flex items-center gap-1">
                    <svg
                      className="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                      ></path>
                    </svg>
                    Aplicado el: {postulacion.fecha_postulacion}
                  </p>
                </div>
                <div>
                  <span
                    className={`px-4 py-1.5 rounded-full text-sm font-bold border ${getEstadoColor(postulacion.estado)}`}
                  >
                    {postulacion.estado}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default MisPostulaciones;

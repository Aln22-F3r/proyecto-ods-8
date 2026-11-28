import { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { postulacionService } from "../services/postulacionService";
import Spinner from "../components/Spinner";

const PostulacionesRecibidas = () => {
  const [postulaciones, setPostulaciones] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchPostulaciones = async () => {
      try {
        const response = await postulacionService.getPostulacionesRecibidas();
        setPostulaciones(response.data || response);
      } catch (error) {
        console.error("Error al cargar postulaciones", error);
      } finally {
        setLoading(false);
      }
    };
    fetchPostulaciones();
  }, []);

  if (loading)
    return (
      <div className="p-12 flex justify-center">
        <Spinner />
      </div>
    );

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-5xl mx-auto">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold text-gray-800">
            Postulaciones Recibidas
          </h1>
          <Link to="/dashboard" className="text-blue-600 hover:underline">
            ← Volver al panel
          </Link>
        </div>

        {postulaciones.length === 0 ? (
          <div className="bg-white p-8 text-center rounded-lg shadow text-gray-500">
            Nadie se ha postulado a tus ofertas todavía.
          </div>
        ) : (
          <div className="bg-white rounded-lg shadow overflow-hidden">
            <table className="w-full text-left border-collapse">
              <thead className="bg-gray-100">
                <tr>
                  <th className="p-4 font-semibold text-gray-600">Candidato</th>
                  <th className="p-4 font-semibold text-gray-600">Oferta</th>
                  <th className="p-4 font-semibold text-gray-600">
                    Fecha de Aplicación
                  </th>
                  <th className="p-4 font-semibold text-gray-600">Estado</th>
                  <th className="p-4 font-semibold text-gray-600">Acciones</th>
                </tr>
              </thead>
              <tbody>
                {postulaciones.map((post) => (
                  <tr
                    key={post.id}
                    className="border-t border-gray-100 hover:bg-gray-50"
                  >
                    <td className="p-4">
                      <div className="font-bold text-gray-800">
                        {post.usuario || "Candidato"}
                      </div>
                      <div className="text-sm text-gray-500">{post.correo}</div>
                    </td>
                    <td className="p-4 text-gray-700 font-medium">
                      {post.oferta}
                    </td>
                    <td className="p-4 text-sm text-gray-500">
                      {post.fecha_postulacion}
                    </td>
                    <td className="p-4">
                      <span className="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-bold border border-blue-200">
                        {post.estado}
                      </span>
                    </td>
                    <td className="p-4">
                      {post.cv ? (
                        <a
                          href={post.cv}
                          target="_blank"
                          rel="noreferrer"
                          className="text-blue-600 hover:underline text-sm font-bold bg-blue-50 px-3 py-1 rounded"
                        >
                          Ver CV
                        </a>
                      ) : (
                        <span className="text-gray-400 text-sm italic">
                          Sin CV
                        </span>
                      )}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </div>
    </div>
  );
};

export default PostulacionesRecibidas;

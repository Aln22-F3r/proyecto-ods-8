import { useState, useEffect } from "react";
import { useParams, Link, useNavigate } from "react-router-dom";
import { ofertaService } from "../services/ofertaService";
import { postulacionService } from "../services/postulacionService";
import { useAuth } from "../context/AuthContext";
import Spinner from "../components/Spinner";

const OfertaDetail = () => {
  const { id } = useParams();
  const { user, isAuthenticated } = useAuth();
  const navigate = useNavigate();

  const [oferta, setOferta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [postulando, setPostulando] = useState(false);

  const fetchOferta = async () => {
    try {
      setLoading(true);
      const data = await ofertaService.getOfertaById(id);
      setOferta(data);
    } catch (err) {
      setError("Oferta no encontrada o eliminada.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOferta();
  }, [id]);

  const handlePostularme = async () => {
    if (!isAuthenticated) {
      navigate("/login");
      return;
    }

    try {
      setPostulando(true);
      await postulacionService.crear(oferta.id);
      alert("¡Postulación enviada exitosamente!");
      fetchOferta(); // Recargamos para que el botón cambie de estado
    } catch (err) {
      // Muestra el mensaje exacto del backend (ej. "El candidato no tiene un CV...")
      alert(err.response?.data?.message || "Error al postularse.");
    } finally {
      setPostulando(false);
    }
  };

  if (loading)
    return (
      <div className="min-h-screen flex items-center justify-center">
        <Spinner />
      </div>
    );
  if (error)
    return (
      <div className="text-center p-12 text-red-600 text-xl">
        {error} <br />
        <Link to="/" className="text-blue-500 underline mt-4 block">
          Volver al inicio
        </Link>
      </div>
    );

  // Lógica para decidir qué mostrar en el botón de acción //
  const renderAccion = () => {
    if (oferta.es_mi_oferta) {
      return (
        <div className="bg-gray-100 text-gray-600 px-6 py-3 rounded-lg text-lg font-bold">
          Esta es tu oferta
        </div>
      );
    }
    if (user?.rol === "Empresa") {
      return null; // Una empresa no se postula a ofertas
    }
    if (oferta.ya_postulado) {
      return (
        <div className="bg-green-100 text-green-800 px-6 py-3 rounded-lg text-lg font-bold">
          Ya te postulaste (Estado: {oferta.mi_postulacion_estado})
        </div>
      );
    }
    return (
      <button
        onClick={handlePostularme}
        disabled={postulando}
        className="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-bold hover:bg-blue-700 transition disabled:bg-gray-400"
      >
        {postulando ? "Enviando..." : "Postularme a esta oferta"}
      </button>
    );
  };

  return (
    <div className="min-h-screen bg-gray-50 pb-12">
      <nav className="bg-white shadow-sm py-4 mb-8">
        <div className="max-w-4xl mx-auto px-4">
          <Link to="/" className="text-blue-600 hover:underline">
            ← Volver al catálogo
          </Link>
        </div>
      </nav>

      <main className="max-w-4xl mx-auto px-4">
        <div className="bg-white rounded-lg shadow-md overflow-hidden p-8">
          <div className="flex justify-between items-start mb-6">
            <div>
              <h1 className="text-3xl font-bold text-gray-800">
                {oferta.titulo}
              </h1>
              <p className="text-lg text-gray-600 mt-2">{oferta.empresa}</p>
            </div>
            <span
              className={`px-4 py-1 rounded-full text-sm font-bold ${oferta.estado === "Abierta" ? "bg-green-100 text-green-700" : "bg-red-100 text-red-700"}`}
            >
              {oferta.estado}
            </span>
          </div>

          <div className="grid grid-cols-2 gap-4 mb-8 bg-blue-50 p-4 rounded-lg">
            <div>
              <span className="text-gray-500 text-sm">Salario</span>
              <br />
              <strong className="text-lg text-blue-700">
                ${Number(oferta.salario).toLocaleString("es-MX")}
              </strong>
            </div>
            <div>
              <span className="text-gray-500 text-sm">Ubicación</span>
              <br />
              <strong>{oferta.ubicacion}</strong>
            </div>
            <div>
              <span className="text-gray-500 text-sm">Tipo de empleo</span>
              <br />
              <strong>{oferta.tipo_empleo}</strong>
            </div>
            <div>
              <span className="text-gray-500 text-sm">Categoría</span>
              <br />
              <strong>{oferta.categoria}</strong>
            </div>
          </div>

          <h2 className="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
            Descripción del puesto
          </h2>
          <p className="text-gray-700 whitespace-pre-line mb-8">
            {oferta.descripcion}
          </p>

          <div className="text-center mt-8 border-t pt-8">{renderAccion()}</div>
        </div>
      </main>
    </div>
  );
};

export default OfertaDetail;

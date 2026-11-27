import { useState, useEffect } from "react";
import { useParams, Link } from "react-router-dom";
import { ofertaService } from "../services/ofertaService";
import Spinner from "../components/Spinner";

const OfertaDetail = () => {
  const { id } = useParams();
  const [oferta, setOferta] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  useEffect(() => {
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
    fetchOferta();
  }, [id]);

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
              <p className="text-lg text-gray-600 mt-2">
                {oferta.usuario?.perfil?.nombre}
              </p>
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
              <strong>{oferta.categoria?.nombre}</strong>
            </div>
          </div>

          <h2 className="text-xl font-bold text-gray-800 mb-4 border-b pb-2">
            Descripción del puesto
          </h2>
          <p className="text-gray-700 whitespace-pre-line mb-8">
            {oferta.descripcion}
          </p>

          <div className="text-center mt-8">
            <button className="bg-blue-600 text-white px-8 py-3 rounded-lg text-lg font-bold hover:bg-blue-700 transition">
              Postularme a esta oferta
            </button>
          </div>
        </div>
      </main>
    </div>
  );
};

export default OfertaDetail;

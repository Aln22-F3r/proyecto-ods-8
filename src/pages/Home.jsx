import { useState, useEffect } from "react";
import { ofertaService } from "../services/ofertaService";
import OfertaCard from "../components/OfertaCard";
import Spinner from "../components/Spinner";
import { Link } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

const Home = () => {
  const [ofertas, setOfertas] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const { isAuthenticated } = useAuth();

  useEffect(() => {
    const fetchOfertas = async () => {
      try {
        setLoading(true);
        const response = await ofertaService.getOfertas();
        setOfertas(response.data);
      } catch (err) {
        setError("No se pudo conectar con el servidor. Intenta más tarde.");
      } finally {
        setLoading(false);
      }
    };

    fetchOfertas();
  }, []);

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Barra de navegación pública */}
      <nav className="bg-white shadow-sm py-4">
        <div className="max-w-6xl mx-auto px-4 flex justify-between items-center">
          <h1 className="text-2xl font-bold text-blue-600">Bolsa de Trabajo</h1>
          <div>
            {isAuthenticated ? (
              <Link
                to="/dashboard"
                className="bg-blue-600 text-white px-4 py-2 rounded"
              >
                Ir al Panel
              </Link>
            ) : (
              <Link
                to="/login"
                className="bg-blue-600 text-white px-4 py-2 rounded"
              >
                Iniciar Sesión
              </Link>
            )}
          </div>
        </div>
      </nav>

      {/* Contenido principal */}
      <main className="max-w-6xl mx-auto px-4 py-8">
        <div className="mb-8 text-center">
          <h2 className="text-3xl font-bold text-gray-800 mb-2">
            Encuentra tu próximo empleo
          </h2>
          <p className="text-gray-600">
            Explora las mejores ofertas laborales del mercado.
          </p>
        </div>

        {loading && <Spinner />}

        {error && (
          <div className="bg-red-100 text-red-700 p-4 rounded text-center">
            {error}
          </div>
        )}

        {!loading && !error && ofertas.length === 0 && (
          <div className="text-center text-gray-500 py-12">
            No hay ofertas disponibles en este momento.
          </div>
        )}

        {!loading && !error && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {ofertas.map((oferta) => (
              <OfertaCard key={oferta.id} oferta={oferta} />
            ))}
          </div>
        )}
      </main>
    </div>
  );
};

export default Home;

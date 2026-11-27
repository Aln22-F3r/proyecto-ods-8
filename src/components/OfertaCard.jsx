import { Link } from "react-router-dom";

const OfertaCard = ({ oferta }) => {
  // Si la oferta tiene logo, lo usamos; si no, un placeholder //
  const logoUrl = oferta.logo
    ? `http://localhost:8000${oferta.logo}`
    : "https://via.placeholder.com/100?text=Sin+Logo";

  return (
    <div
      className={`bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition ${oferta.destacada ? "border-2 border-yellow-400" : "border border-gray-200"}`}
    >
      {/* Etiqueta de destacada */}
      {oferta.destacada && (
        <div className="bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 text-center">
          ⭐ OFERTA DESTACADA
        </div>
      )}

      <div className="p-5">
        <div className="flex items-center gap-4 mb-4">
          <img
            src={logoUrl}
            alt={oferta.titulo}
            className="w-16 h-16 object-contain bg-gray-50 rounded border"
          />
          <div>
            <h3 className="font-bold text-lg text-gray-800 line-clamp-1">
              {oferta.titulo}
            </h3>
            <p className="text-sm text-gray-500">
              {oferta.usuario?.perfil?.nombre || "Empresa confidencial"}
            </p>
          </div>
        </div>

        <div className="space-y-2 mb-4">
          <p className="text-sm text-gray-600 flex items-center gap-2">
            <span>{oferta.ubicacion}</span>
          </p>
          <p className="text-sm text-gray-600 flex items-center gap-2">
            <span>{oferta.tipo_empleo}</span>
          </p>
          <p className="text-sm text-gray-600 flex items-center gap-2">
            <span>{oferta.categoria?.nombre}</span>
          </p>
        </div>

        <div className="flex justify-between items-center mt-4 pt-4 border-t border-gray-100">
          <span className="font-bold text-blue-600">
            ${Number(oferta.salario).toLocaleString("es-MX")} MXN
          </span>
          <Link
            to={`/oferta/${oferta.id}`}
            className="text-sm bg-blue-50 text-blue-600 px-3 py-1.5 rounded hover:bg-blue-100 transition"
          >
            Ver Detalle
          </Link>
        </div>
      </div>
    </div>
  );
};

export default OfertaCard;

import { useAuth } from "../context/AuthContext";
import { useNavigate, Link } from "react-router-dom";

const Dashboard = () => {
  const { user, logout } = useAuth();
  const navigate = useNavigate();

  const handleLogout = async () => {
    await logout();
    navigate("/login");
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-4xl mx-auto">
        {/* Barra superior */}
        <div className="flex justify-between items-center mb-8 bg-white p-4 rounded-lg shadow">
          <div>
            <h1 className="text-xl font-bold text-gray-800">
              Panel de {user?.rol}
            </h1>
            <p className="text-gray-500 text-sm">{user?.correo}</p>
          </div>
          <div className="flex gap-4 items-center">
            <Link to="/" className="text-blue-600 hover:underline font-medium">
              Ver Catálogo
            </Link>
            <button
              onClick={handleLogout}
              className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600 transition"
            >
              Cerrar Sesión
            </button>
          </div>
        </div>

        {/* Contenido principal */}
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-2xl font-semibold mb-6 text-gray-800">
            ¡Bienvenido, {user?.perfil?.nombre || user?.correo}!
          </h2>

          {/* VISTA PARA CANDIDATO */}
          {user?.rol === "Candidato" && (
            <div className="space-y-4">
              <p className="text-gray-600">
                Aquí puedes gestionar tus postulaciones y tu perfil profesional.
              </p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <Link
                  to="/mis-postulaciones"
                  className="bg-blue-50 text-blue-700 p-4 rounded border border-blue-200 text-left hover:bg-blue-100 transition block"
                >
                  <span className="block font-bold text-lg mb-1">
                    Mis Postulaciones
                  </span>
                  <span className="text-sm">
                    Revisa el estado de tus aplicaciones.
                  </span>
                </Link>
                <button className="bg-gray-50 text-gray-700 p-4 rounded border border-gray-200 text-left hover:bg-gray-100 transition">
                  <span className="block font-bold text-lg mb-1">
                    Mi Perfil
                  </span>
                  <span className="text-sm">
                    Actualiza tu CV y datos de contacto.
                  </span>
                </button>
              </div>
            </div>
          )}

          {/* VISTA PARA EMPRESA */}
          {user?.rol === "Empresa" && (
            <div className="space-y-4">
              <p className="text-gray-600">
                Administra tus ofertas publicadas y revisa a los candidatos.
              </p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <button className="bg-green-50 text-green-700 p-4 rounded border border-green-200 text-left hover:bg-green-100 transition">
                  <span className="block font-bold text-lg mb-1">
                    Mis Ofertas
                  </span>
                  <span className="text-sm">
                    Crea, edita o destaca tus ofertas de empleo.
                  </span>
                </button>
                <Link
                  to="/postulaciones-recibidas"
                  className="bg-blue-50 text-blue-700 p-4 rounded border border-blue-200 text-left hover:bg-blue-100 transition block"
                >
                  <span className="block font-bold text-lg mb-1">
                    Postulaciones Recibidas
                  </span>
                  <span className="text-sm">
                    Revisa los CVs y contacta candidatos.
                  </span>
                </Link>
              </div>
            </div>
          )}

          {/* VISTA PARA ADMINISTRADOR */}
          {user?.rol === "Administrador" && (
            <div className="space-y-4">
              <p className="text-gray-600">Tienes acceso total al sistema.</p>
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                <div className="bg-purple-50 text-purple-700 p-4 rounded border border-purple-200 text-center font-bold cursor-pointer hover:bg-purple-100 transition">
                  Usuarios
                </div>
                <div className="bg-purple-50 text-purple-700 p-4 rounded border border-purple-200 text-center font-bold cursor-pointer hover:bg-purple-100 transition">
                  Ofertas
                </div>
                <div className="bg-purple-50 text-purple-700 p-4 rounded border border-purple-200 text-center font-bold cursor-pointer hover:bg-purple-100 transition">
                  Pagos
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

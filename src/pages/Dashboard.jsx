import { useAuth } from "../context/AuthContext";
import { useNavigate } from "react-router-dom";

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
        <div className="flex justify-between items-center mb-8 bg-white p-4 rounded-lg shadow">
          <div>
            <h1 className="text-xl font-bold">Panel de {user?.rol}</h1>
            <p className="text-gray-500 text-sm">{user?.correo}</p>
          </div>
          <button
            onClick={handleLogout}
            className="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600"
          >
            Cerrar Sesión
          </button>
        </div>
        <div className="bg-white p-6 rounded-lg shadow">
          <h2 className="text-lg font-semibold mb-4">
            ¡Bienvenido al sistema!
          </h2>
          <p>Aquí irá el catálogo y los demás módulos más adelante.</p>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;

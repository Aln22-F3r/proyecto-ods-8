import { BrowserRouter, Routes, Route, Navigate } from "react-router-dom";
import Login from "./pages/Login";
import Dashboard from "./pages/Dashboard";
import Home from "./pages/Home";
import Register from "./pages/Register";
import OfertaDetail from "./pages/OfertaDetail";
import MisPostulaciones from "./pages/MisPostulaciones";
import PostulacionesRecibidas from "./pages/PostulacionesRecibidas";
import { useAuth } from "./context/AuthContext";

const ProtectedRoute = ({ children }) => {
  const { isAuthenticated, loading } = useAuth();
  if (loading) return <div className="p-8 text-center">Cargando...</div>;
  if (!isAuthenticated) return <Navigate to="/login" replace />;
  return children;
};

function App() {
  return (
    <BrowserRouter>
      <Routes>
        {/* Rutas Públicas */}
        <Route path="/" element={<Home />} />
        <Route path="/oferta/:id" element={<OfertaDetail />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        {/* Ruta Protegida */}
        <Route
          path="/dashboard"
          element={
            <ProtectedRoute>
              <Dashboard />
            </ProtectedRoute>
          }
        />
        <Route
          path="/mis-postulaciones"
          element={
            <ProtectedRoute>
              <MisPostulaciones />
            </ProtectedRoute>
          }
        />
        <Route
          path="/postulaciones-recibidas"
          element={
            <ProtectedRoute>
              <PostulacionesRecibidas />
            </ProtectedRoute>
          }
        />
      </Routes>
    </BrowserRouter>
  );
}

export default App;

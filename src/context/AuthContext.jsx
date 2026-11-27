import { createContext, useState, useContext, useEffect } from "react";
import apiClient from "../api/client";

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);

  // Al cargar la app, recuperamos la sesión del localStorage //
  useEffect(() => {
    const storedToken = localStorage.getItem("token");
    const storedUser = localStorage.getItem("user");

    if (storedToken && storedUser) {
      setToken(storedToken);
      setUser(JSON.parse(storedUser));
    }
    setLoading(false);
  }, []);

  // Función para iniciar sesión //
  const login = async (correo, password) => {
    try {
      const response = await apiClient.post("/auth/login", {
        correo,
        password,
      });
      const { token, usuario } = response.data;

      // Guardar en el estado de React //
      setToken(token);
      setUser(usuario);

      // Guardar en el navegador para persistencia //
      localStorage.setItem("token", token);
      localStorage.setItem("user", JSON.stringify(usuario));

      return { success: true };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || "Error al iniciar sesión",
      };
    }
  };

  // Función para cerrar sesión //
  const logout = async () => {
    try {
      // Pedir al backend que revoque el token actual //
      await apiClient.post("/auth/logout");
    } catch (error) {
      console.error("Error al cerrar sesión en el servidor", error);
    } finally {
      // Limpiar estado y almacenamiento local siempre //
      setToken(null);
      setUser(null);
      localStorage.removeItem("token");
      localStorage.removeItem("user");
    }
  };

  return (
    <AuthContext.Provider
      value={{ user, token, loading, login, logout, isAuthenticated: !!token }}
    >
      {children}
    </AuthContext.Provider>
  );
};

// Hook personalizado para usar el contexto fácilmente //
export const useAuth = () => useContext(AuthContext);

import { Link } from "@inertiajs/react";
import AdminLayout from "../Layouts/AdminLayout";

const modulos = [
    { nombre: "Roles", ruta: "/roles" },
    { nombre: "Usuarios", ruta: "/usuarios" },
    { nombre: "Perfiles", ruta: "/perfiles" },
    { nombre: "Categorías", ruta: "/categorias" },
    { nombre: "Ofertas de empleo", ruta: "/ofertas-empleo" },
    { nombre: "Postulaciones", ruta: "/postulaciones" },
    { nombre: "Transacciones", ruta: "/transacciones" },
    { nombre: "Login sociales", ruta: "/login-sociales" },
    { nombre: "Logs", ruta: "/logs" },
];

export default function Dashboard() {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">Dashboard</h1>
            <p className="mt-1 text-white">
                Bienvenido al panel administrativo. Selecciona un módulo para
                comenzar.
            </p>

            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {modulos.map((m) => (
                    <Link
                        key={m.ruta}
                        href={m.ruta}
                        className="rounded border border-[#4a4141] bg-[#272020] p-4 font-medium text-white transition-colors hover:bg-[#9a5f64]"
                    >
                        {m.nombre}
                    </Link>
                ))}
            </div>
        </AdminLayout>
    );
}

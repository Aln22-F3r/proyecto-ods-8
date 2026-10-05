import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const usuarios = [
    {
        id: 1,
        rol: "Administrador",
        nombre: "Ana",
        apellido: "López",
        correo: "ana@correo.com",
        telefono: "3312345678",
        estado: "Activo",
    },
    {
        id: 2,
        rol: "Empresa",
        nombre: "Carlos",
        apellido: "Ramírez",
        correo: "carlos@correo.com",
        telefono: "3398765432",
        estado: "Activo",
    },
    {
        id: 3,
        rol: "Candidato",
        nombre: "María",
        apellido: "Torres",
        correo: "maria@correo.com",
        telefono: "3355511122",
        estado: "Inactivo",
    },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">Usuarios</h1>
                <Link
                    href="/usuarios/crear"
                    className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80"
                >
                    Nuevo
                </Link>
            </div>

            <div className="mt-6 overflow-x-auto rounded border border-[#4a4141]">
                <table className="w-full text-left text-sm text-white">
                    <thead className="bg-[#272020] text-xs uppercase">
                        <tr>
                            <th className="px-4 py-3">ID</th>
                            <th className="px-4 py-3">Rol</th>
                            <th className="px-4 py-3">Nombre</th>
                            <th className="px-4 py-3">Apellido</th>
                            <th className="px-4 py-3">Correo</th>
                            <th className="px-4 py-3">Teléfono</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {usuarios.map((u) => (
                            <tr
                                key={u.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{u.id}</td>
                                <td className="px-4 py-3">{u.rol}</td>
                                <td className="px-4 py-3">{u.nombre}</td>
                                <td className="px-4 py-3">{u.apellido}</td>
                                <td className="px-4 py-3">{u.correo}</td>
                                <td className="px-4 py-3">{u.telefono}</td>
                                <td className="px-4 py-3">{u.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/usuarios/${u.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/usuarios/${u.id}/editar`}
                                            className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                        >
                                            Editar
                                        </Link>
                                        <button
                                            type="button"
                                            className="rounded bg-red-800 px-3 py-1 text-white hover:bg-red-700"
                                        >
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </AdminLayout>
    );
}

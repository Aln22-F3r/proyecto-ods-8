import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";

export default function Index({ usuarios }) {
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
                        {usuarios.data.map((u) => (
                            <tr
                                key={u.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{u.id}</td>
                                <td className="px-4 py-3">{u.rol?.nombre}</td>
                                <td className="px-4 py-3">{u.nombre}</td>
                                <td className="px-4 py-3">{u.apellido}</td>
                                <td className="px-4 py-3">{u.correo}</td>
                                <td className="px-4 py-3">{u.telefono}</td>
                                <td className="px-4 py-3">
                                    {u.estado ? "Activo" : "Inactivo"}
                                </td>
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

            <Paginacion links={usuarios.links} />
        </AdminLayout>
    );
}

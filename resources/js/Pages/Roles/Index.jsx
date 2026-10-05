import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const roles = [
    { id: 1, nombre: "Administrador", descripcion: "Acceso total al sistema" },
    {
        id: 2,
        nombre: "Empresa",
        descripcion: "Publica y gestiona ofertas de empleo",
    },
    { id: 3, nombre: "Candidato", descripcion: "Busca y se postula a ofertas" },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">Roles</h1>
                <Link
                    href="/roles/crear"
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
                            <th className="px-4 py-3">Nombre</th>
                            <th className="px-4 py-3">Descripción</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {roles.map((r) => (
                            <tr
                                key={r.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{r.id}</td>
                                <td className="px-4 py-3">{r.nombre}</td>
                                <td className="px-4 py-3">{r.descripcion}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/roles/${r.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/roles/${r.id}/editar`}
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

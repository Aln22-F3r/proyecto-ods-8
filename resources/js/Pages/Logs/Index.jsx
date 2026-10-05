import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const logs = [
    {
        id: 1,
        usuario: "Ana López",
        accion: "Inició sesión",
        fecha: "2026-10-01",
        ip: "192.168.1.10",
    },
    {
        id: 2,
        usuario: "Carlos Ramírez",
        accion: "Publicó una oferta de empleo",
        fecha: "2026-10-02",
        ip: "192.168.1.25",
    },
    {
        id: 3,
        usuario: "María Torres",
        accion: "Se postuló a una oferta",
        fecha: "2026-10-03",
        ip: "192.168.1.31",
    },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">Logs</h1>
                <Link
                    href="/logs/crear"
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
                            <th className="px-4 py-3">Usuario</th>
                            <th className="px-4 py-3">Acción</th>
                            <th className="px-4 py-3">Fecha</th>
                            <th className="px-4 py-3">IP</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {logs.map((l) => (
                            <tr
                                key={l.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{l.id}</td>
                                <td className="px-4 py-3">{l.usuario}</td>
                                <td className="px-4 py-3">{l.accion}</td>
                                <td className="px-4 py-3">{l.fecha}</td>
                                <td className="px-4 py-3">{l.ip}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/logs/${l.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/logs/${l.id}/editar`}
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

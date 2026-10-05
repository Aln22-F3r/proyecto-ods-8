import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const postulaciones = [
    {
        id: 1,
        usuario: "Ana López",
        oferta: "Desarrollador web",
        fecha_postulacion: "2026-09-28",
        estado: "En revisión",
    },
    {
        id: 2,
        usuario: "Carlos Ramírez",
        oferta: "Albañil",
        fecha_postulacion: "2026-09-30",
        estado: "Aceptada",
    },
    {
        id: 3,
        usuario: "María Torres",
        oferta: "Diseñador gráfico",
        fecha_postulacion: "2026-10-01",
        estado: "Rechazada",
    },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    Postulaciones
                </h1>
                <Link
                    href="/postulaciones/crear"
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
                            <th className="px-4 py-3">Oferta de empleo</th>
                            <th className="px-4 py-3">Fecha de postulación</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {postulaciones.map((p) => (
                            <tr
                                key={p.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{p.id}</td>
                                <td className="px-4 py-3">{p.usuario}</td>
                                <td className="px-4 py-3">{p.oferta}</td>
                                <td className="px-4 py-3">
                                    {p.fecha_postulacion}
                                </td>
                                <td className="px-4 py-3">{p.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/postulaciones/${p.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/postulaciones/${p.id}/editar`}
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

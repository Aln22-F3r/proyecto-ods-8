import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const transacciones = [
    {
        id: 1,
        usuario: "Ana López",
        tipo_registro: "Registro",
        descripcion: "Alta de usuario en el sistema",
        fecha: "2026-09-28",
        estado: "Completada",
    },
    {
        id: 2,
        usuario: "Carlos Ramírez",
        tipo_registro: "Publicación",
        descripcion: "Publicación de una oferta de empleo",
        fecha: "2026-09-30",
        estado: "Completada",
    },
    {
        id: 3,
        usuario: "María Torres",
        tipo_registro: "Postulación",
        descripcion: "Postulación a una oferta de empleo",
        fecha: "2026-10-01",
        estado: "Pendiente",
    },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    Transacciones
                </h1>
                <Link
                    href="/transacciones/crear"
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
                            <th className="px-4 py-3">Tipo de registro</th>
                            <th className="px-4 py-3">Descripción</th>
                            <th className="px-4 py-3">Fecha</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {transacciones.map((t) => (
                            <tr
                                key={t.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{t.id}</td>
                                <td className="px-4 py-3">{t.usuario}</td>
                                <td className="px-4 py-3">{t.tipo_registro}</td>
                                <td className="px-4 py-3">{t.descripcion}</td>
                                <td className="px-4 py-3">{t.fecha}</td>
                                <td className="px-4 py-3">{t.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/transacciones/${t.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/transacciones/${t.id}/editar`}
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

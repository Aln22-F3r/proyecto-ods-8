import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const perfiles = [
    {
        id: 1,
        usuario: "Ana López",
        profesion_oficio: "Desarrolladora web",
        experiencia: 4,
        ciudad: "Guadalajara",
    },
    {
        id: 2,
        usuario: "Carlos Ramírez",
        profesion_oficio: "Electricista",
        experiencia: 10,
        ciudad: "Zapopan",
    },
    {
        id: 3,
        usuario: "María Torres",
        profesion_oficio: "Diseñadora gráfica",
        experiencia: 2,
        ciudad: "Tlaquepaque",
    },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">Perfiles</h1>
                <Link
                    href="/perfiles/crear"
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
                            <th className="px-4 py-3">Profesión u oficio</th>
                            <th className="px-4 py-3">Experiencia (años)</th>
                            <th className="px-4 py-3">Ciudad</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {perfiles.map((p) => (
                            <tr
                                key={p.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{p.id}</td>
                                <td className="px-4 py-3">{p.usuario}</td>
                                <td className="px-4 py-3">
                                    {p.profesion_oficio}
                                </td>
                                <td className="px-4 py-3">{p.experiencia}</td>
                                <td className="px-4 py-3">{p.ciudad}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/perfiles/${p.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/perfiles/${p.id}/editar`}
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

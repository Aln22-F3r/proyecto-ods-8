import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";

export default function Index({ ofertas }) {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    Ofertas de empleo
                </h1>
                <Link
                    href="/ofertas-empleo/crear"
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
                            <th className="px-4 py-3">Título</th>
                            <th className="px-4 py-3">Empresa</th>
                            <th className="px-4 py-3">Categoría</th>
                            <th className="px-4 py-3">Ubicación</th>
                            <th className="px-4 py-3">Salario</th>
                            <th className="px-4 py-3">Tipo de empleo</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {ofertas.data.map((o) => (
                            <tr
                                key={o.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{o.id}</td>
                                <td className="px-4 py-3">{o.titulo}</td>
                                <td className="px-4 py-3">{o.empresa}</td>
                                <td className="px-4 py-3">
                                    {o.categoria?.nombre}
                                </td>
                                <td className="px-4 py-3">{o.ubicacion}</td>
                                <td className="px-4 py-3">${o.salario}</td>
                                <td className="px-4 py-3">{o.tipo_empleo}</td>
                                <td className="px-4 py-3">{o.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/ofertas-empleo/${o.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/ofertas-empleo/${o.id}/editar`}
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

            <Paginacion links={ofertas.links} />
        </AdminLayout>
    );
}

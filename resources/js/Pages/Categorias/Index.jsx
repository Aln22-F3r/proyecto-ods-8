import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const categorias = [
    {
        id: 1,
        nombre: "Tecnología",
        descripcion: "Desarrollo de software, soporte y sistemas",
    },
    {
        id: 2,
        nombre: "Construcción",
        descripcion: "Oficios y proyectos de construcción",
    },
    {
        id: 3,
        nombre: "Diseño",
        descripcion: "Diseño gráfico, web y audiovisual",
    },
];

export default function Index() {
    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    Categorías
                </h1>
                <Link
                    href="/categorias/crear"
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
                        {categorias.map((c) => (
                            <tr
                                key={c.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{c.id}</td>
                                <td className="px-4 py-3">{c.nombre}</td>
                                <td className="px-4 py-3">{c.descripcion}</td>
                                <td className="px-4 py-3">
                                    <div className="flex gap-2">
                                        <Link
                                            href={`/categorias/${c.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        <Link
                                            href={`/categorias/${c.id}/editar`}
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

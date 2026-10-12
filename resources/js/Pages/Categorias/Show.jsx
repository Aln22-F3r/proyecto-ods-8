import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

function Dato({ etiqueta, valor }) {
    const vacio = valor === null || valor === undefined || valor === "";

    return (
        <div>
            <dt className="text-xs font-bold uppercase text-white/70">
                {etiqueta}
            </dt>
            <dd className="mt-1 text-sm text-white">{vacio ? "—" : valor}</dd>
        </div>
    );
}

export default function Show({ categoria }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar categoría
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {categoria.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Esta categoría está eliminada
                        {categoria.fecha_eliminacion
                            ? ` desde el ${categoria.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={categoria.id} />
                    <Dato etiqueta="Nombre" valor={categoria.nombre} />
                    <Dato
                        etiqueta="Descripción"
                        valor={categoria.descripcion}
                    />
                    <Dato
                        etiqueta="Ofertas de empleo activas en esta categoría"
                        valor={categoria.ofertas_count}
                    />
                    <Dato etiqueta="Creado" valor={categoria.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={categoria.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            categoria.eliminado
                                ? "/categorias/eliminados"
                                : "/categorias"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!categoria.eliminado && (
                        <Link
                            href={`/categorias/${categoria.id}/editar`}
                            className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80"
                        >
                            Editar
                        </Link>
                    )}
                </div>
            </div>
        </AdminLayout>
    );
}

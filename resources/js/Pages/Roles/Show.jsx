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

export default function Show({ rol }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">Consultar rol</h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {rol.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Este rol está eliminado
                        {rol.fecha_eliminacion
                            ? ` desde el ${rol.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={rol.id} />
                    <Dato etiqueta="Nombre" valor={rol.nombre} />
                    <Dato etiqueta="Descripción" valor={rol.descripcion} />
                    <Dato
                        etiqueta="Usuarios activos con este rol"
                        valor={rol.usuarios_count}
                    />
                    <Dato etiqueta="Creado" valor={rol.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={rol.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={rol.eliminado ? "/roles/eliminados" : "/roles"}
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!rol.eliminado && (
                        <Link
                            href={`/roles/${rol.id}/editar`}
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

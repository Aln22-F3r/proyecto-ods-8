import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

function Dato({ etiqueta, valor }) {
    const vacio = valor === null || valor === undefined || valor === "";

    return (
        <div>
            <dt className="text-xs font-bold uppercase text-white/70">
                {etiqueta}
            </dt>
            <dd className="mt-1 break-all text-sm text-white">
                {vacio ? "—" : valor}
            </dd>
        </div>
    );
}

export default function Show({ log }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">Consultar log</h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {log.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Este log está eliminado
                        {log.fecha_eliminacion
                            ? ` desde el ${log.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={log.id} />
                    <Dato etiqueta="Usuario" valor={log.usuario} />
                    <Dato etiqueta="Correo del usuario" valor={log.correo} />
                    <Dato etiqueta="Acción" valor={log.accion} />
                    <Dato etiqueta="Fecha" valor={log.fecha} />
                    <Dato etiqueta="IP" valor={log.ip} />
                    <Dato etiqueta="Creado" valor={log.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={log.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={log.eliminado ? "/logs/eliminados" : "/logs"}
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!log.eliminado && (
                        <Link
                            href={`/logs/${log.id}/editar`}
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

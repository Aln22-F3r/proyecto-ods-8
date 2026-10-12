import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

function Dato({ etiqueta, valor }) {
    const vacio = valor === null || valor === undefined || valor === "";

    return (
        <div>
            <dt className="text-xs font-bold uppercase text-white/70">
                {etiqueta}
            </dt>
            <dd className="mt-1 whitespace-pre-line text-sm text-white">
                {vacio ? "—" : valor}
            </dd>
        </div>
    );
}

export default function Show({ transaccion }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar transacción
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {transaccion.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Esta transacción está eliminada
                        {transaccion.fecha_eliminacion
                            ? ` desde el ${transaccion.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={transaccion.id} />
                    <Dato etiqueta="Usuario" valor={transaccion.usuario} />
                    <Dato
                        etiqueta="Correo del usuario"
                        valor={transaccion.correo}
                    />
                    <Dato
                        etiqueta="Tipo de registro"
                        valor={transaccion.tipo_registro}
                    />
                    <Dato
                        etiqueta="Descripción"
                        valor={transaccion.descripcion}
                    />
                    <Dato etiqueta="Fecha" valor={transaccion.fecha} />
                    <Dato etiqueta="Estado" valor={transaccion.estado} />
                    <Dato etiqueta="Creado" valor={transaccion.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={transaccion.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            transaccion.eliminado
                                ? "/transacciones/eliminados"
                                : "/transacciones"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!transaccion.eliminado && (
                        <Link
                            href={`/transacciones/${transaccion.id}/editar`}
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

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

export default function Show({ postulacion }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar postulación
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {postulacion.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Esta postulación está eliminada
                        {postulacion.fecha_eliminacion
                            ? ` desde el ${postulacion.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={postulacion.id} />
                    <Dato etiqueta="Usuario" valor={postulacion.usuario} />
                    <Dato
                        etiqueta="Correo del usuario"
                        valor={postulacion.correo}
                    />
                    <Dato
                        etiqueta="Oferta de empleo"
                        valor={postulacion.oferta}
                    />
                    <Dato etiqueta="Empresa" valor={postulacion.empresa} />
                    <Dato
                        etiqueta="Fecha de postulación"
                        valor={postulacion.fecha_postulacion}
                    />
                    <Dato etiqueta="Estado" valor={postulacion.estado} />
                    <Dato
                        etiqueta="Comentario"
                        valor={postulacion.comentario}
                    />
                    <Dato etiqueta="Creado" valor={postulacion.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={postulacion.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            postulacion.eliminado
                                ? "/postulaciones/eliminados"
                                : "/postulaciones"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!postulacion.eliminado && (
                        <Link
                            href={`/postulaciones/${postulacion.id}/editar`}
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

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

export default function Show({ oferta }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar oferta de empleo
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {oferta.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Esta oferta de empleo está eliminada
                        {oferta.fecha_eliminacion
                            ? ` desde el ${oferta.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <div>
                    <p className="text-xs font-bold uppercase text-white/70">
                        Logo de la empresa
                    </p>
                    {oferta.logo ? (
                        <img
                            src={oferta.logo}
                            alt="Logo de la empresa"
                            className="mt-2 h-32 w-32 rounded object-contain"
                        />
                    ) : (
                        <p className="mt-1 text-sm text-white">Sin logo</p>
                    )}
                </div>

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={oferta.id} />
                    <Dato etiqueta="Categoría" valor={oferta.categoria} />
                    <Dato etiqueta="Título" valor={oferta.titulo} />
                    <Dato etiqueta="Empresa" valor={oferta.empresa} />
                    <Dato etiqueta="Descripción" valor={oferta.descripcion} />
                    <Dato etiqueta="Ubicación" valor={oferta.ubicacion} />
                    <Dato etiqueta="Salario" valor={oferta.salario} />
                    <Dato
                        etiqueta="Tipo de empleo"
                        valor={oferta.tipo_empleo}
                    />
                    <Dato
                        etiqueta="Fecha de publicación"
                        valor={oferta.fecha_publicacion}
                    />
                    <Dato etiqueta="Estado" valor={oferta.estado} />
                    <Dato
                        etiqueta="Postulaciones activas"
                        valor={oferta.postulaciones_count}
                    />
                    <Dato
                        etiqueta="Veces en una lista de deseos"
                        valor={oferta.lista_deseos_count}
                    />
                    <Dato etiqueta="Creado" valor={oferta.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={oferta.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            oferta.eliminado
                                ? "/ofertas-empleo/eliminados"
                                : "/ofertas-empleo"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!oferta.eliminado && (
                        <Link
                            href={`/ofertas-empleo/${oferta.id}/editar`}
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

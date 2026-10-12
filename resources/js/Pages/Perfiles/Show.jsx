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

export default function Show({ perfil }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar perfil
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {perfil.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Este perfil está eliminado
                        {perfil.fecha_eliminacion
                            ? ` desde el ${perfil.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <div>
                    <p className="text-xs font-bold uppercase text-white/70">
                        Foto de perfil
                    </p>
                    {perfil.foto ? (
                        <img
                            src={perfil.foto}
                            alt="Foto de perfil"
                            className="mt-2 h-32 w-32 rounded object-cover"
                        />
                    ) : (
                        <p className="mt-1 text-sm text-white">
                            Sin foto de perfil
                        </p>
                    )}
                </div>

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={perfil.id} />
                    <Dato etiqueta="Usuario" valor={perfil.usuario} />
                    <Dato etiqueta="Correo del usuario" valor={perfil.correo} />
                    <Dato
                        etiqueta="Profesión u oficio"
                        valor={perfil.profesion_oficio}
                    />
                    <Dato etiqueta="Descripción" valor={perfil.descripcion} />
                    <Dato
                        etiqueta="Experiencia (años)"
                        valor={perfil.experiencia}
                    />
                    <Dato etiqueta="Habilidades" valor={perfil.habilidades} />
                    <Dato etiqueta="Ciudad" valor={perfil.ciudad} />

                    <div>
                        <dt className="text-xs font-bold uppercase text-white/70">
                            CV
                        </dt>
                        <dd className="mt-1 text-sm text-white">
                            {perfil.cv ? (
                                <a
                                    href={perfil.cv}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="underline"
                                >
                                    Ver archivo
                                </a>
                            ) : (
                                "—"
                            )}
                        </dd>
                    </div>

                    <Dato etiqueta="Creado" valor={perfil.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={perfil.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            perfil.eliminado
                                ? "/perfiles/eliminados"
                                : "/perfiles"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!perfil.eliminado && (
                        <Link
                            href={`/perfiles/${perfil.id}/editar`}
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

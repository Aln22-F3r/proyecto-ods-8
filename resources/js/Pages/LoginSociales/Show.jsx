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

export default function Show({ loginSocial }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar login social
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {loginSocial.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Este login social está eliminado
                        {loginSocial.fecha_eliminacion
                            ? ` desde el ${loginSocial.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={loginSocial.id} />
                    <Dato etiqueta="Usuario" valor={loginSocial.usuario} />
                    <Dato
                        etiqueta="Correo del usuario"
                        valor={loginSocial.correo_usuario}
                    />
                    <Dato etiqueta="Proveedor" valor={loginSocial.proveedor} />
                    <Dato
                        etiqueta="ID del proveedor"
                        valor={loginSocial.proveedor_id}
                    />
                    <Dato
                        etiqueta="Correo del login social"
                        valor={loginSocial.correo}
                    />
                    <Dato etiqueta="Creado" valor={loginSocial.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={loginSocial.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            loginSocial.eliminado
                                ? "/login-sociales/eliminados"
                                : "/login-sociales"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!loginSocial.eliminado && (
                        <Link
                            href={`/login-sociales/${loginSocial.id}/editar`}
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

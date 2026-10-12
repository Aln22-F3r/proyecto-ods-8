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

export default function Show({ usuario }) {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Consultar usuario
            </h1>

            <div className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                {usuario.eliminado && (
                    <div className="rounded border border-yellow-700 bg-yellow-900/40 p-3 text-sm text-white">
                        Este usuario está eliminado
                        {usuario.fecha_eliminacion
                            ? ` desde el ${usuario.fecha_eliminacion}`
                            : ""}
                        .
                    </div>
                )}

                <dl className="space-y-4">
                    <Dato etiqueta="ID" valor={usuario.id} />
                    <Dato etiqueta="Rol" valor={usuario.rol} />
                    <Dato etiqueta="Nombre" valor={usuario.nombre} />
                    <Dato etiqueta="Apellido" valor={usuario.apellido} />
                    <Dato etiqueta="Correo" valor={usuario.correo} />
                    <Dato etiqueta="Teléfono" valor={usuario.telefono} />
                    <Dato
                        etiqueta="Fecha de registro"
                        valor={usuario.fecha_registro}
                    />
                    <Dato
                        etiqueta="Estado"
                        valor={usuario.estado ? "Activo" : "Inactivo"}
                    />
                    <Dato
                        etiqueta="Perfil (profesión u oficio)"
                        valor={usuario.perfil}
                    />
                    <Dato
                        etiqueta="Postulaciones"
                        valor={usuario.postulaciones_count}
                    />
                    <Dato
                        etiqueta="Ofertas en su lista de deseos"
                        valor={usuario.lista_deseos_count}
                    />
                    <Dato
                        etiqueta="Transacciones"
                        valor={usuario.transacciones_count}
                    />
                    <Dato
                        etiqueta="Login sociales"
                        valor={usuario.login_sociales_count}
                    />
                    <Dato etiqueta="Logs" valor={usuario.logs_count} />
                    <Dato etiqueta="Creado" valor={usuario.creado} />
                    <Dato
                        etiqueta="Última actualización"
                        valor={usuario.actualizado}
                    />
                </dl>

                <div className="flex gap-2 pt-2">
                    <Link
                        href={
                            usuario.eliminado
                                ? "/usuarios/eliminados"
                                : "/usuarios"
                        }
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Volver
                    </Link>
                    {!usuario.eliminado && (
                        <Link
                            href={`/usuarios/${usuario.id}/editar`}
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

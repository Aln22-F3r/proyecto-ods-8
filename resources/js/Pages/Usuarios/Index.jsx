import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const nombreCompleto = (u) => `${u.nombre} ${u.apellido}`;

const configuraciones = {
    eliminar: (u) => ({
        titulo: "Eliminar usuario",
        mensaje: `¿Seguro que quieres eliminar al usuario «${nombreCompleto(u)}»? Se moverá a la lista de eliminados y podrás restaurarlo después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (u) => ({
        titulo: "Restaurar usuario",
        mensaje: `¿Quieres restaurar al usuario «${nombreCompleto(u)}»? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (u) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente al usuario «${nombreCompleto(u)}»? Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ usuarios, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion ? configuraciones[accion.tipo](accion.usuario) : null;

    const confirmar = () => {
        const { tipo, usuario } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/usuarios/${usuario.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(`/usuarios/${usuario.id}/restaurar`, {}, opciones);
        } else {
            router.delete(`/usuarios/${usuario.id}/definitivo`, opciones);
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados ? "Usuarios eliminados" : "Usuarios"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/usuarios"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a usuarios
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/usuarios/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminados
                            </Link>
                            <Link
                                href="/usuarios/crear"
                                className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80"
                            >
                                Nuevo
                            </Link>
                        </>
                    )}
                </div>
            </div>

            <div className="mt-6 overflow-x-auto rounded border border-[#4a4141]">
                <table className="w-full text-left text-sm text-white">
                    <thead className="bg-[#272020] text-xs uppercase">
                        <tr>
                            <th className="px-4 py-3">ID</th>
                            <th className="px-4 py-3">Rol</th>
                            <th className="px-4 py-3">Nombre</th>
                            <th className="px-4 py-3">Apellido</th>
                            <th className="px-4 py-3">Correo</th>
                            <th className="px-4 py-3">Teléfono</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {usuarios.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={8}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay usuarios eliminados."
                                        : "No hay usuarios registrados."}
                                </td>
                            </tr>
                        )}
                        {usuarios.data.map((u) => (
                            <tr
                                key={u.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{u.id}</td>
                                <td className="px-4 py-3">{u.rol?.nombre}</td>
                                <td className="px-4 py-3">{u.nombre}</td>
                                <td className="px-4 py-3">{u.apellido}</td>
                                <td className="px-4 py-3">{u.correo}</td>
                                <td className="px-4 py-3">{u.telefono}</td>
                                <td className="px-4 py-3">
                                    {u.estado ? "Activo" : "Inactivo"}
                                </td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/usuarios/${u.id}`}
                                            className="rounded bg-gray-600 px-3 py-1 text-white hover:bg-gray-500"
                                        >
                                            Consultar
                                        </Link>
                                        {eliminados ? (
                                            <>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "restaurar",
                                                            usuario: u,
                                                        })
                                                    }
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Restaurar
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "definitivo",
                                                            usuario: u,
                                                        })
                                                    }
                                                    className={`rounded px-3 py-1 text-white ${rojo}`}
                                                >
                                                    Eliminar definitivamente
                                                </button>
                                            </>
                                        ) : (
                                            <>
                                                <Link
                                                    href={`/usuarios/${u.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            usuario: u,
                                                        })
                                                    }
                                                    className={`rounded px-3 py-1 text-white ${rojo}`}
                                                >
                                                    Eliminar
                                                </button>
                                            </>
                                        )}
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Paginacion links={usuarios.links} />

            <ConfirmarModal
                abierto={accion !== null}
                titulo={config?.titulo}
                mensaje={config?.mensaje}
                textoConfirmar={config?.texto}
                colorConfirmar={config?.color}
                procesando={procesando}
                onConfirmar={confirmar}
                onCancelar={() => setAccion(null)}
            />
        </AdminLayout>
    );
}

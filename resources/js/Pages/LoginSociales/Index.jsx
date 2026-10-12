import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const descripcion = (l) =>
    `el login social de «${l.proveedor}» de «${l.usuario?.nombre ?? ""} ${l.usuario?.apellido ?? ""}»`;

const configuraciones = {
    eliminar: (l) => ({
        titulo: "Eliminar login social",
        mensaje: `¿Seguro que quieres eliminar ${descripcion(l)}? Se moverá a la lista de eliminados y podrás restaurarlo después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (l) => ({
        titulo: "Restaurar login social",
        mensaje: `¿Quieres restaurar ${descripcion(l)}? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (l) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente ${descripcion(l)}? Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ loginSociales, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion
        ? configuraciones[accion.tipo](accion.loginSocial)
        : null;

    const confirmar = () => {
        const { tipo, loginSocial } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/login-sociales/${loginSocial.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(
                `/login-sociales/${loginSocial.id}/restaurar`,
                {},
                opciones,
            );
        } else {
            router.delete(
                `/login-sociales/${loginSocial.id}/definitivo`,
                opciones,
            );
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados
                        ? "Login sociales eliminados"
                        : "Login sociales"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/login-sociales"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a login sociales
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/login-sociales/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminados
                            </Link>
                            <Link
                                href="/login-sociales/crear"
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
                            <th className="px-4 py-3">Usuario</th>
                            <th className="px-4 py-3">Proveedor</th>
                            <th className="px-4 py-3">ID del proveedor</th>
                            <th className="px-4 py-3">Correo</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loginSociales.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={6}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay login sociales eliminados."
                                        : "No hay login sociales registrados."}
                                </td>
                            </tr>
                        )}
                        {loginSociales.data.map((l) => (
                            <tr
                                key={l.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{l.id}</td>
                                <td className="px-4 py-3">
                                    {l.usuario?.nombre} {l.usuario?.apellido}
                                </td>
                                <td className="px-4 py-3">{l.proveedor}</td>
                                <td className="px-4 py-3">{l.proveedor_id}</td>
                                <td className="px-4 py-3">{l.correo}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/login-sociales/${l.id}`}
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
                                                            loginSocial: l,
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
                                                            loginSocial: l,
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
                                                    href={`/login-sociales/${l.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            loginSocial: l,
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

            <Paginacion links={loginSociales.links} />

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

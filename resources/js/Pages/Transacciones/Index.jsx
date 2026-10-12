import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const descripcion = (t) =>
    `la transacción «${t.tipo_registro}» de «${t.usuario?.nombre ?? ""} ${t.usuario?.apellido ?? ""}»`;

const configuraciones = {
    eliminar: (t) => ({
        titulo: "Eliminar transacción",
        mensaje: `¿Seguro que quieres eliminar ${descripcion(t)}? Se moverá a la lista de eliminadas y podrás restaurarla después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (t) => ({
        titulo: "Restaurar transacción",
        mensaje: `¿Quieres restaurar ${descripcion(t)}? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (t) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente ${descripcion(t)}? Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ transacciones, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion
        ? configuraciones[accion.tipo](accion.transaccion)
        : null;

    const confirmar = () => {
        const { tipo, transaccion } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/transacciones/${transaccion.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(
                `/transacciones/${transaccion.id}/restaurar`,
                {},
                opciones,
            );
        } else {
            router.delete(
                `/transacciones/${transaccion.id}/definitivo`,
                opciones,
            );
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados ? "Transacciones eliminadas" : "Transacciones"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/transacciones"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a transacciones
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/transacciones/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminadas
                            </Link>
                            <Link
                                href="/transacciones/crear"
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
                            <th className="px-4 py-3">Tipo de registro</th>
                            <th className="px-4 py-3">Descripción</th>
                            <th className="px-4 py-3">Fecha</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {transacciones.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={7}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay transacciones eliminadas."
                                        : "No hay transacciones registradas."}
                                </td>
                            </tr>
                        )}
                        {transacciones.data.map((t) => (
                            <tr
                                key={t.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{t.id}</td>
                                <td className="px-4 py-3">
                                    {t.usuario?.nombre} {t.usuario?.apellido}
                                </td>
                                <td className="px-4 py-3">{t.tipo_registro}</td>
                                <td className="px-4 py-3">{t.descripcion}</td>
                                <td className="px-4 py-3">{t.fecha}</td>
                                <td className="px-4 py-3">{t.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/transacciones/${t.id}`}
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
                                                            transaccion: t,
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
                                                            transaccion: t,
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
                                                    href={`/transacciones/${t.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            transaccion: t,
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

            <Paginacion links={transacciones.links} />

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

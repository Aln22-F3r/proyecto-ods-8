import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const descripcion = (p) =>
    `la postulación de «${p.usuario?.nombre ?? ""} ${p.usuario?.apellido ?? ""}» a «${p.oferta_empleo?.titulo ?? ""}»`;

const configuraciones = {
    eliminar: (p) => ({
        titulo: "Eliminar postulación",
        mensaje: `¿Seguro que quieres eliminar ${descripcion(p)}? Se moverá a la lista de eliminadas y podrás restaurarla después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (p) => ({
        titulo: "Restaurar postulación",
        mensaje: `¿Quieres restaurar ${descripcion(p)}? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (p) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente ${descripcion(p)}? Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ postulaciones, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion
        ? configuraciones[accion.tipo](accion.postulacion)
        : null;

    const confirmar = () => {
        const { tipo, postulacion } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/postulaciones/${postulacion.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(
                `/postulaciones/${postulacion.id}/restaurar`,
                {},
                opciones,
            );
        } else {
            router.delete(
                `/postulaciones/${postulacion.id}/definitivo`,
                opciones,
            );
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados ? "Postulaciones eliminadas" : "Postulaciones"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/postulaciones"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a postulaciones
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/postulaciones/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminadas
                            </Link>
                            <Link
                                href="/postulaciones/crear"
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
                            <th className="px-4 py-3">Oferta de empleo</th>
                            <th className="px-4 py-3">Fecha de postulación</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {postulaciones.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={6}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay postulaciones eliminadas."
                                        : "No hay postulaciones registradas."}
                                </td>
                            </tr>
                        )}
                        {postulaciones.data.map((p) => (
                            <tr
                                key={p.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{p.id}</td>
                                <td className="px-4 py-3">
                                    {p.usuario?.nombre} {p.usuario?.apellido}
                                </td>
                                <td className="px-4 py-3">
                                    {p.oferta_empleo?.titulo}
                                </td>
                                <td className="px-4 py-3">
                                    {p.fecha_postulacion}
                                </td>
                                <td className="px-4 py-3">{p.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/postulaciones/${p.id}`}
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
                                                            postulacion: p,
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
                                                            postulacion: p,
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
                                                    href={`/postulaciones/${p.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            postulacion: p,
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

            <Paginacion links={postulaciones.links} />

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

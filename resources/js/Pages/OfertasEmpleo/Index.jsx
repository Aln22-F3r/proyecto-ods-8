import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const descripcion = (o) => `la oferta «${o.titulo}» de «${o.empresa}»`;

const configuraciones = {
    eliminar: (o) => ({
        titulo: "Eliminar oferta de empleo",
        mensaje: `¿Seguro que quieres eliminar ${descripcion(o)}? Se moverá a la lista de eliminadas y podrás restaurarla después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (o) => ({
        titulo: "Restaurar oferta de empleo",
        mensaje: `¿Quieres restaurar ${descripcion(o)}? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (o) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente ${descripcion(o)}? También se borrará su logo. Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ ofertas, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion ? configuraciones[accion.tipo](accion.oferta) : null;

    const confirmar = () => {
        const { tipo, oferta } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/ofertas-empleo/${oferta.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(`/ofertas-empleo/${oferta.id}/restaurar`, {}, opciones);
        } else {
            router.delete(`/ofertas-empleo/${oferta.id}/definitivo`, opciones);
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados
                        ? "Ofertas de empleo eliminadas"
                        : "Ofertas de empleo"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/ofertas-empleo"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a ofertas
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/ofertas-empleo/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminadas
                            </Link>
                            <Link
                                href="/ofertas-empleo/crear"
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
                            <th className="px-4 py-3">Título</th>
                            <th className="px-4 py-3">Empresa</th>
                            <th className="px-4 py-3">Categoría</th>
                            <th className="px-4 py-3">Ubicación</th>
                            <th className="px-4 py-3">Salario</th>
                            <th className="px-4 py-3">Tipo de empleo</th>
                            <th className="px-4 py-3">Estado</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {ofertas.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={9}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay ofertas de empleo eliminadas."
                                        : "No hay ofertas de empleo registradas."}
                                </td>
                            </tr>
                        )}
                        {ofertas.data.map((o) => (
                            <tr
                                key={o.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{o.id}</td>
                                <td className="px-4 py-3">{o.titulo}</td>
                                <td className="px-4 py-3">{o.empresa}</td>
                                <td className="px-4 py-3">
                                    {o.categoria?.nombre}
                                </td>
                                <td className="px-4 py-3">{o.ubicacion}</td>
                                <td className="px-4 py-3">${o.salario}</td>
                                <td className="px-4 py-3">{o.tipo_empleo}</td>
                                <td className="px-4 py-3">{o.estado}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/ofertas-empleo/${o.id}`}
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
                                                            oferta: o,
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
                                                            oferta: o,
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
                                                    href={`/ofertas-empleo/${o.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            oferta: o,
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

            <Paginacion links={ofertas.links} />

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

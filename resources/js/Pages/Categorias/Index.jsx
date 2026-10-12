import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const configuraciones = {
    eliminar: (c) => ({
        titulo: "Eliminar categoría",
        mensaje: `¿Seguro que quieres eliminar la categoría «${c.nombre}»? Se moverá a la lista de eliminadas y podrás restaurarla después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (c) => ({
        titulo: "Restaurar categoría",
        mensaje: `¿Quieres restaurar la categoría «${c.nombre}»? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (c) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente la categoría «${c.nombre}»? Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ categorias, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion
        ? configuraciones[accion.tipo](accion.categoria)
        : null;

    const confirmar = () => {
        const { tipo, categoria } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/categorias/${categoria.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(`/categorias/${categoria.id}/restaurar`, {}, opciones);
        } else {
            router.delete(`/categorias/${categoria.id}/definitivo`, opciones);
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados ? "Categorías eliminadas" : "Categorías"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/categorias"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a categorías
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/categorias/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminadas
                            </Link>
                            <Link
                                href="/categorias/crear"
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
                            <th className="px-4 py-3">Nombre</th>
                            <th className="px-4 py-3">Descripción</th>
                            <th className="px-4 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {categorias.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={4}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay categorías eliminadas."
                                        : "No hay categorías registradas."}
                                </td>
                            </tr>
                        )}
                        {categorias.data.map((c) => (
                            <tr
                                key={c.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{c.id}</td>
                                <td className="px-4 py-3">{c.nombre}</td>
                                <td className="px-4 py-3">{c.descripcion}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/categorias/${c.id}`}
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
                                                            categoria: c,
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
                                                            categoria: c,
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
                                                    href={`/categorias/${c.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            categoria: c,
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

            <Paginacion links={categorias.links} />

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

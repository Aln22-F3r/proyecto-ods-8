import { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";
import Paginacion from "../../Components/Paginacion";
import ConfirmarModal from "../../Components/ConfirmarModal";

const rojo = "bg-red-800 hover:bg-red-700";

const configuraciones = {
    eliminar: (r) => ({
        titulo: "Eliminar rol",
        mensaje: `¿Seguro que quieres eliminar el rol «${r.nombre}»? Se moverá a la lista de eliminados y podrás restaurarlo después.`,
        texto: "Eliminar",
        color: rojo,
    }),
    restaurar: (r) => ({
        titulo: "Restaurar rol",
        mensaje: `¿Quieres restaurar el rol «${r.nombre}»? Volverá a aparecer en la lista principal.`,
        texto: "Restaurar",
        color: "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    }),
    definitivo: (r) => ({
        titulo: "Eliminar definitivamente",
        mensaje: `¿Seguro que quieres eliminar definitivamente el rol «${r.nombre}»? Esta acción no se puede deshacer.`,
        texto: "Eliminar definitivamente",
        color: rojo,
    }),
};

export default function Index({ roles, eliminados = false }) {
    const [accion, setAccion] = useState(null);
    const [procesando, setProcesando] = useState(false);

    const config = accion ? configuraciones[accion.tipo](accion.rol) : null;

    const confirmar = () => {
        const { tipo, rol } = accion;

        const opciones = {
            onStart: () => setProcesando(true),
            onFinish: () => {
                setProcesando(false);
                setAccion(null);
            },
        };

        if (tipo === "eliminar") {
            router.delete(`/roles/${rol.id}`, opciones);
        } else if (tipo === "restaurar") {
            router.put(`/roles/${rol.id}/restaurar`, {}, opciones);
        } else {
            router.delete(`/roles/${rol.id}/definitivo`, opciones);
        }
    };

    return (
        <AdminLayout>
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-semibold text-white">
                    {eliminados ? "Roles eliminados" : "Roles"}
                </h1>
                <div className="flex gap-2">
                    {eliminados ? (
                        <Link
                            href="/roles"
                            className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                        >
                            Volver a roles
                        </Link>
                    ) : (
                        <>
                            <Link
                                href="/roles/eliminados"
                                className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                            >
                                Ver eliminados
                            </Link>
                            <Link
                                href="/roles/crear"
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
                        {roles.data.length === 0 && (
                            <tr className="border-t border-[#4a4141]">
                                <td
                                    colSpan={4}
                                    className="px-4 py-6 text-center text-white/70"
                                >
                                    {eliminados
                                        ? "No hay roles eliminados."
                                        : "No hay roles registrados."}
                                </td>
                            </tr>
                        )}
                        {roles.data.map((r) => (
                            <tr
                                key={r.id}
                                className="border-t border-[#4a4141]"
                            >
                                <td className="px-4 py-3">{r.id}</td>
                                <td className="px-4 py-3">{r.nombre}</td>
                                <td className="px-4 py-3">{r.descripcion}</td>
                                <td className="px-4 py-3">
                                    <div className="flex flex-wrap gap-2">
                                        <Link
                                            href={`/roles/${r.id}`}
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
                                                            rol: r,
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
                                                            rol: r,
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
                                                    href={`/roles/${r.id}/editar`}
                                                    className="rounded bg-[#9a5f64] px-3 py-1 text-white hover:bg-[#9a5f64]/80"
                                                >
                                                    Editar
                                                </Link>
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setAccion({
                                                            tipo: "eliminar",
                                                            rol: r,
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

            <Paginacion links={roles.links} />

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

import { useEffect } from "react";
import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

export default function Form({ categoria = null }) {
    const editando = categoria !== null;

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        setError,
        clearErrors,
    } = useForm({
        nombre: categoria?.nombre ?? "",
        descripcion: categoria?.descripcion ?? "",
    });

    // Al pasar de crear a editar (o entre registros) se recargan los campos //
    useEffect(() => {
        setData({
            nombre: categoria?.nombre ?? "",
            descripcion: categoria?.descripcion ?? "",
        });
        clearErrors();
    }, [categoria?.id]);

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.nombre.trim()) {
            nuevos.nombre = "El nombre es obligatorio.";
        } else if (data.nombre.trim().length < 3) {
            nuevos.nombre = "El nombre debe tener al menos 3 caracteres.";
        }

        if (!data.descripcion.trim()) {
            nuevos.descripcion = "La descripción es obligatoria.";
        } else if (data.descripcion.trim().length < 5) {
            nuevos.descripcion =
                "La descripción debe tener al menos 5 caracteres.";
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        if (editando) {
            put(`/categorias/${categoria.id}`);
        } else {
            post("/categorias");
        }
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                {editando ? "Editar categoría" : "Formulario de categoría"}
            </h1>

            <form
                onSubmit={enviar}
                noValidate
                className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6"
            >
                {Object.keys(errors).length > 0 && (
                    <div className="rounded border border-red-700 bg-red-900/40 p-3 text-sm text-white">
                        Revisa los campos marcados antes de guardar.
                    </div>
                )}

                <div>
                    <label htmlFor="nombre" className={labelClase}>
                        Nombre
                    </label>
                    <input
                        id="nombre"
                        type="text"
                        maxLength={100}
                        value={data.nombre}
                        onChange={(e) => setData("nombre", e.target.value)}
                        className={inputClase}
                    />
                    {errors.nombre && (
                        <p className={errorClase}>{errors.nombre}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="descripcion" className={labelClase}>
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        rows={3}
                        maxLength={255}
                        value={data.descripcion}
                        onChange={(e) => setData("descripcion", e.target.value)}
                        className={inputClase}
                    />
                    {errors.descripcion && (
                        <p className={errorClase}>{errors.descripcion}</p>
                    )}
                </div>

                <div className="flex gap-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80 disabled:opacity-50"
                    >
                        {editando ? "Actualizar" : "Guardar"}
                    </button>
                    <Link
                        href="/categorias"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

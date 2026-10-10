import { useEffect } from "react";
import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

const valoresIniciales = (transaccion) => ({
    usuario_id: transaccion?.usuario_id ?? "",
    tipo_registro: transaccion?.tipo_registro ?? "",
    descripcion: transaccion?.descripcion ?? "",
    fecha: transaccion?.fecha ?? "",
    estado: transaccion?.estado ?? "",
});

export default function Form({ usuarios = [], transaccion = null }) {
    const editando = transaccion !== null;

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        setError,
        clearErrors,
    } = useForm(valoresIniciales(transaccion));

    // Al pasar de crear a editar (o entre registros) se recargan los campos //
    useEffect(() => {
        setData(valoresIniciales(transaccion));
        clearErrors();
    }, [transaccion?.id]);

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.usuario_id) {
            nuevos.usuario_id = "Selecciona un usuario.";
        }

        if (!data.tipo_registro) {
            nuevos.tipo_registro = "Selecciona un tipo de registro.";
        }

        if (!data.descripcion.trim()) {
            nuevos.descripcion = "La descripción es obligatoria.";
        } else if (data.descripcion.trim().length < 5) {
            nuevos.descripcion =
                "La descripción debe tener al menos 5 caracteres.";
        }

        if (!data.fecha) {
            nuevos.fecha = "La fecha es obligatoria.";
        } else if (isNaN(new Date(data.fecha).getTime())) {
            nuevos.fecha = "La fecha no es válida.";
        }

        if (!data.estado) {
            nuevos.estado = "Selecciona un estado.";
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        if (editando) {
            put(`/transacciones/${transaccion.id}`);
        } else {
            post("/transacciones");
        }
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                {editando ? "Editar transacción" : "Formulario de transacción"}
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
                    <label htmlFor="usuario_id" className={labelClase}>
                        Usuario
                    </label>
                    <select
                        id="usuario_id"
                        value={data.usuario_id}
                        onChange={(e) => setData("usuario_id", e.target.value)}
                        className={inputClase}
                    >
                        <option value="">Selecciona un usuario</option>
                        {usuarios.map((u) => (
                            <option key={u.id} value={u.id}>
                                {u.nombre} {u.apellido}
                            </option>
                        ))}
                    </select>
                    {errors.usuario_id && (
                        <p className={errorClase}>{errors.usuario_id}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="tipo_registro" className={labelClase}>
                        Tipo de registro
                    </label>
                    <select
                        id="tipo_registro"
                        value={data.tipo_registro}
                        onChange={(e) =>
                            setData("tipo_registro", e.target.value)
                        }
                        className={inputClase}
                    >
                        <option value="">Selecciona un tipo</option>
                        <option value="Registro">Registro</option>
                        <option value="Publicación">Publicación</option>
                        <option value="Postulación">Postulación</option>
                    </select>
                    {errors.tipo_registro && (
                        <p className={errorClase}>{errors.tipo_registro}</p>
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

                <div>
                    <label htmlFor="fecha" className={labelClase}>
                        Fecha
                    </label>
                    <input
                        id="fecha"
                        type="datetime-local"
                        step="1"
                        value={data.fecha}
                        onChange={(e) => setData("fecha", e.target.value)}
                        className={inputClase}
                    />
                    {errors.fecha && (
                        <p className={errorClase}>{errors.fecha}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="estado" className={labelClase}>
                        Estado
                    </label>
                    <select
                        id="estado"
                        value={data.estado}
                        onChange={(e) => setData("estado", e.target.value)}
                        className={inputClase}
                    >
                        <option value="">Selecciona un estado</option>
                        <option value="Pendiente">Pendiente</option>
                        <option value="Completada">Completada</option>
                    </select>
                    {errors.estado && (
                        <p className={errorClase}>{errors.estado}</p>
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
                        href="/transacciones"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

export default function Form({ usuarios = [], ofertas = [] }) {
    const { data, setData, post, processing, errors, setError, clearErrors } =
        useForm({
            usuario_id: "",
            oferta_empleo_id: "",
            fecha_postulacion: "",
            estado: "",
            comentario: "",
        });

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.usuario_id) {
            nuevos.usuario_id = "Selecciona un usuario.";
        }

        if (!data.oferta_empleo_id) {
            nuevos.oferta_empleo_id = "Selecciona una oferta de empleo.";
        }

        if (!data.fecha_postulacion) {
            nuevos.fecha_postulacion =
                "La fecha de postulación es obligatoria.";
        } else if (isNaN(new Date(data.fecha_postulacion).getTime())) {
            nuevos.fecha_postulacion = "La fecha de postulación no es válida.";
        }

        if (!data.estado) {
            nuevos.estado = "Selecciona un estado.";
        }

        if (!data.comentario.trim()) {
            nuevos.comentario = "El comentario es obligatorio.";
        } else if (data.comentario.trim().length < 5) {
            nuevos.comentario =
                "El comentario debe tener al menos 5 caracteres.";
        } else if (data.comentario.trim().length > 1000) {
            nuevos.comentario =
                "El comentario no puede tener más de 1000 caracteres.";
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        post("/postulaciones");
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de postulación
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
                    <label htmlFor="oferta_empleo_id" className={labelClase}>
                        Oferta de empleo
                    </label>
                    <select
                        id="oferta_empleo_id"
                        value={data.oferta_empleo_id}
                        onChange={(e) =>
                            setData("oferta_empleo_id", e.target.value)
                        }
                        className={inputClase}
                    >
                        <option value="">Selecciona una oferta</option>
                        {ofertas.map((o) => (
                            <option key={o.id} value={o.id}>
                                {o.titulo} - {o.empresa}
                            </option>
                        ))}
                    </select>
                    {errors.oferta_empleo_id && (
                        <p className={errorClase}>{errors.oferta_empleo_id}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="fecha_postulacion" className={labelClase}>
                        Fecha de postulación
                    </label>
                    <input
                        id="fecha_postulacion"
                        type="datetime-local"
                        value={data.fecha_postulacion}
                        onChange={(e) =>
                            setData("fecha_postulacion", e.target.value)
                        }
                        className={inputClase}
                    />
                    {errors.fecha_postulacion && (
                        <p className={errorClase}>{errors.fecha_postulacion}</p>
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
                        <option value="En revisión">En revisión</option>
                        <option value="Aceptada">Aceptada</option>
                        <option value="Rechazada">Rechazada</option>
                    </select>
                    {errors.estado && (
                        <p className={errorClase}>{errors.estado}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="comentario" className={labelClase}>
                        Comentario
                    </label>
                    <textarea
                        id="comentario"
                        rows={3}
                        maxLength={1000}
                        value={data.comentario}
                        onChange={(e) => setData("comentario", e.target.value)}
                        className={inputClase}
                    />
                    {errors.comentario && (
                        <p className={errorClase}>{errors.comentario}</p>
                    )}
                </div>

                <div className="flex gap-2">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80 disabled:opacity-50"
                    >
                        Guardar
                    </button>
                    <Link
                        href="/postulaciones"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const archivoClase =
    "w-full text-sm text-white file:mr-3 file:rounded file:border-0 file:bg-gray-600 file:px-3 file:py-2 file:text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

export default function Form({ usuarios = [] }) {
    const { data, setData, post, processing, errors, setError, clearErrors } =
        useForm({
            usuario_id: "",
            profesion_oficio: "",
            descripcion: "",
            experiencia: "",
            habilidades: "",
            ciudad: "",
            cv: null,
            foto: null,
        });

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.usuario_id) {
            nuevos.usuario_id = "Selecciona un usuario.";
        }

        if (!data.profesion_oficio.trim()) {
            nuevos.profesion_oficio = "La profesión u oficio es obligatoria.";
        } else if (data.profesion_oficio.trim().length < 3) {
            nuevos.profesion_oficio =
                "La profesión u oficio debe tener al menos 3 caracteres.";
        }

        if (!data.descripcion.trim()) {
            nuevos.descripcion = "La descripción es obligatoria.";
        } else if (data.descripcion.trim().length < 10) {
            nuevos.descripcion =
                "La descripción debe tener al menos 10 caracteres.";
        }

        if (data.experiencia === "") {
            nuevos.experiencia = "La experiencia es obligatoria.";
        } else if (!/^[0-9]+$/.test(String(data.experiencia))) {
            nuevos.experiencia = "La experiencia debe ser un número entero.";
        } else if (Number(data.experiencia) > 60) {
            nuevos.experiencia = "La experiencia no puede ser mayor a 60 años.";
        }

        if (!data.habilidades.trim()) {
            nuevos.habilidades = "Las habilidades son obligatorias.";
        } else if (data.habilidades.trim().length < 3) {
            nuevos.habilidades =
                "Las habilidades deben tener al menos 3 caracteres.";
        }

        if (!data.ciudad.trim()) {
            nuevos.ciudad = "La ciudad es obligatoria.";
        } else if (data.ciudad.trim().length < 2) {
            nuevos.ciudad = "La ciudad debe tener al menos 2 caracteres.";
        }

        if (!data.cv) {
            nuevos.cv = "El CV es obligatorio.";
        } else if (!/\.(pdf|doc|docx)$/i.test(data.cv.name)) {
            nuevos.cv =
                "El CV debe ser un archivo PDF o Word (pdf, doc, docx).";
        } else if (data.cv.size > 5 * 1024 * 1024) {
            nuevos.cv = "El CV no puede pesar más de 5 MB.";
        }

        if (data.foto) {
            if (!/\.(jpg|jpeg|png|webp)$/i.test(data.foto.name)) {
                nuevos.foto = "La foto debe ser de tipo jpg, jpeg, png o webp.";
            } else if (data.foto.size > 2 * 1024 * 1024) {
                nuevos.foto = "La foto no puede pesar más de 2 MB.";
            }
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        post("/perfiles");
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de perfil
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
                    <label htmlFor="profesion_oficio" className={labelClase}>
                        Profesión u oficio
                    </label>
                    <input
                        id="profesion_oficio"
                        type="text"
                        maxLength={100}
                        value={data.profesion_oficio}
                        onChange={(e) =>
                            setData("profesion_oficio", e.target.value)
                        }
                        className={inputClase}
                    />
                    {errors.profesion_oficio && (
                        <p className={errorClase}>{errors.profesion_oficio}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="descripcion" className={labelClase}>
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        rows={3}
                        value={data.descripcion}
                        onChange={(e) => setData("descripcion", e.target.value)}
                        className={inputClase}
                    />
                    {errors.descripcion && (
                        <p className={errorClase}>{errors.descripcion}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="experiencia" className={labelClase}>
                        Experiencia (años)
                    </label>
                    <input
                        id="experiencia"
                        type="number"
                        min={0}
                        max={60}
                        value={data.experiencia}
                        onChange={(e) => setData("experiencia", e.target.value)}
                        className={inputClase}
                    />
                    {errors.experiencia && (
                        <p className={errorClase}>{errors.experiencia}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="habilidades" className={labelClase}>
                        Habilidades
                    </label>
                    <textarea
                        id="habilidades"
                        rows={3}
                        value={data.habilidades}
                        onChange={(e) => setData("habilidades", e.target.value)}
                        className={inputClase}
                    />
                    {errors.habilidades && (
                        <p className={errorClase}>{errors.habilidades}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="ciudad" className={labelClase}>
                        Ciudad
                    </label>
                    <input
                        id="ciudad"
                        type="text"
                        maxLength={100}
                        value={data.ciudad}
                        onChange={(e) => setData("ciudad", e.target.value)}
                        className={inputClase}
                    />
                    {errors.ciudad && (
                        <p className={errorClase}>{errors.ciudad}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="cv" className={labelClase}>
                        CV (PDF o Word, máximo 5 MB)
                    </label>
                    <input
                        id="cv"
                        type="file"
                        accept=".pdf,.doc,.docx"
                        onChange={(e) =>
                            setData("cv", e.target.files[0] ?? null)
                        }
                        className={archivoClase}
                    />
                    {errors.cv && <p className={errorClase}>{errors.cv}</p>}
                </div>

                <div>
                    <label htmlFor="foto" className={labelClase}>
                        Foto de perfil (jpg, png o webp, máximo 2 MB)
                    </label>
                    <input
                        id="foto"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp"
                        onChange={(e) =>
                            setData("foto", e.target.files[0] ?? null)
                        }
                        className={archivoClase}
                    />
                    {errors.foto && <p className={errorClase}>{errors.foto}</p>}
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
                        href="/perfiles"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

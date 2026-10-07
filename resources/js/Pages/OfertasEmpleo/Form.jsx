import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const archivoClase =
    "w-full text-sm text-white file:mr-3 file:rounded file:border-0 file:bg-gray-600 file:px-3 file:py-2 file:text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

const tiposEmpleo = ["Tiempo completo", "Medio tiempo", "Por obra"];

export default function Form({ categorias = [] }) {
    const { data, setData, post, processing, errors, setError, clearErrors } =
        useForm({
            categoria_id: "",
            titulo: "",
            empresa: "",
            descripcion: "",
            ubicacion: "",
            salario: "",
            tipo_empleo: "",
            fecha_publicacion: "",
            estado: "",
            logo: null,
        });

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.categoria_id) {
            nuevos.categoria_id = "Selecciona una categoría.";
        }

        if (!data.titulo.trim()) {
            nuevos.titulo = "El título es obligatorio.";
        } else if (data.titulo.trim().length < 3) {
            nuevos.titulo = "El título debe tener al menos 3 caracteres.";
        }

        if (!data.empresa.trim()) {
            nuevos.empresa = "La empresa es obligatoria.";
        } else if (data.empresa.trim().length < 2) {
            nuevos.empresa = "La empresa debe tener al menos 2 caracteres.";
        }

        if (!data.descripcion.trim()) {
            nuevos.descripcion = "La descripción es obligatoria.";
        } else if (data.descripcion.trim().length < 10) {
            nuevos.descripcion =
                "La descripción debe tener al menos 10 caracteres.";
        }

        if (!data.ubicacion.trim()) {
            nuevos.ubicacion = "La ubicación es obligatoria.";
        } else if (data.ubicacion.trim().length < 3) {
            nuevos.ubicacion = "La ubicación debe tener al menos 3 caracteres.";
        }

        if (data.salario === "") {
            nuevos.salario = "El salario es obligatorio.";
        } else if (isNaN(Number(data.salario))) {
            nuevos.salario = "El salario debe ser un número.";
        } else if (Number(data.salario) < 0) {
            nuevos.salario = "El salario no puede ser negativo.";
        } else if (Number(data.salario) > 99999999.99) {
            nuevos.salario = "El salario no puede ser mayor a 99999999.99.";
        }

        if (!data.tipo_empleo) {
            nuevos.tipo_empleo = "Selecciona el tipo de empleo.";
        }

        if (!data.fecha_publicacion) {
            nuevos.fecha_publicacion =
                "La fecha de publicación es obligatoria.";
        } else if (isNaN(new Date(data.fecha_publicacion).getTime())) {
            nuevos.fecha_publicacion = "La fecha de publicación no es válida.";
        }

        if (!data.estado) {
            nuevos.estado = "Selecciona un estado.";
        }

        if (data.logo) {
            if (!/\.(jpg|jpeg|png|webp)$/i.test(data.logo.name)) {
                nuevos.logo = "El logo debe ser de tipo jpg, jpeg, png o webp.";
            } else if (data.logo.size > 2 * 1024 * 1024) {
                nuevos.logo = "El logo no puede pesar más de 2 MB.";
            }
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        post("/ofertas-empleo");
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de oferta de empleo
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
                    <label htmlFor="categoria_id" className={labelClase}>
                        Categoría
                    </label>
                    <select
                        id="categoria_id"
                        value={data.categoria_id}
                        onChange={(e) =>
                            setData("categoria_id", e.target.value)
                        }
                        className={inputClase}
                    >
                        <option value="">Selecciona una categoría</option>
                        {categorias.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.nombre}
                            </option>
                        ))}
                    </select>
                    {errors.categoria_id && (
                        <p className={errorClase}>{errors.categoria_id}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="titulo" className={labelClase}>
                        Título
                    </label>
                    <input
                        id="titulo"
                        type="text"
                        maxLength={150}
                        value={data.titulo}
                        onChange={(e) => setData("titulo", e.target.value)}
                        className={inputClase}
                    />
                    {errors.titulo && (
                        <p className={errorClase}>{errors.titulo}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="empresa" className={labelClase}>
                        Empresa
                    </label>
                    <input
                        id="empresa"
                        type="text"
                        maxLength={150}
                        value={data.empresa}
                        onChange={(e) => setData("empresa", e.target.value)}
                        className={inputClase}
                    />
                    {errors.empresa && (
                        <p className={errorClase}>{errors.empresa}</p>
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
                    <label htmlFor="ubicacion" className={labelClase}>
                        Ubicación
                    </label>
                    <input
                        id="ubicacion"
                        type="text"
                        maxLength={150}
                        value={data.ubicacion}
                        onChange={(e) => setData("ubicacion", e.target.value)}
                        className={inputClase}
                    />
                    {errors.ubicacion && (
                        <p className={errorClase}>{errors.ubicacion}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="salario" className={labelClase}>
                        Salario
                    </label>
                    <input
                        id="salario"
                        type="number"
                        min={0}
                        step="0.01"
                        value={data.salario}
                        onChange={(e) => setData("salario", e.target.value)}
                        className={inputClase}
                    />
                    {errors.salario && (
                        <p className={errorClase}>{errors.salario}</p>
                    )}
                </div>

                <div>
                    <p className={labelClase}>Tipo de empleo</p>
                    <div className="flex flex-wrap gap-4 text-sm text-white">
                        {tiposEmpleo.map((tipo) => (
                            <label
                                key={tipo}
                                className="flex items-center gap-2"
                            >
                                <input
                                    type="radio"
                                    name="tipo_empleo"
                                    value={tipo}
                                    checked={data.tipo_empleo === tipo}
                                    onChange={(e) =>
                                        setData("tipo_empleo", e.target.value)
                                    }
                                />
                                {tipo}
                            </label>
                        ))}
                    </div>
                    {errors.tipo_empleo && (
                        <p className={errorClase}>{errors.tipo_empleo}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="fecha_publicacion" className={labelClase}>
                        Fecha de publicación
                    </label>
                    <input
                        id="fecha_publicacion"
                        type="datetime-local"
                        value={data.fecha_publicacion}
                        onChange={(e) =>
                            setData("fecha_publicacion", e.target.value)
                        }
                        className={inputClase}
                    />
                    {errors.fecha_publicacion && (
                        <p className={errorClase}>{errors.fecha_publicacion}</p>
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
                        <option value="Abierta">Abierta</option>
                        <option value="Cerrada">Cerrada</option>
                    </select>
                    {errors.estado && (
                        <p className={errorClase}>{errors.estado}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="logo" className={labelClase}>
                        Logo de la empresa (jpg, png o webp, máximo 2 MB)
                    </label>
                    <input
                        id="logo"
                        type="file"
                        accept=".jpg,.jpeg,.png,.webp"
                        onChange={(e) =>
                            setData("logo", e.target.files[0] ?? null)
                        }
                        className={archivoClase}
                    />
                    {errors.logo && <p className={errorClase}>{errors.logo}</p>}
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
                        href="/ofertas-empleo"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";

export default function Form() {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de oferta de empleo
            </h1>

            <form className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                <div>
                    <label htmlFor="categoria_id" className={labelClase}>
                        Categoría
                    </label>
                    <select id="categoria_id" className={inputClase}>
                        <option value="">Selecciona una categoría</option>
                        <option value="1">Tecnología</option>
                        <option value="2">Construcción</option>
                        <option value="3">Diseño</option>
                    </select>
                </div>

                <div>
                    <label htmlFor="titulo" className={labelClase}>
                        Título
                    </label>
                    <input
                        id="titulo"
                        type="text"
                        maxLength={150}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="empresa" className={labelClase}>
                        Empresa
                    </label>
                    <input
                        id="empresa"
                        type="text"
                        maxLength={150}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="descripcion" className={labelClase}>
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        rows={3}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="ubicacion" className={labelClase}>
                        Ubicación
                    </label>
                    <input
                        id="ubicacion"
                        type="text"
                        maxLength={150}
                        className={inputClase}
                    />
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
                        className={inputClase}
                    />
                </div>

                <div>
                    <p className={labelClase}>Tipo de empleo</p>
                    <div className="flex flex-wrap gap-4 text-sm text-white">
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="tipo_empleo"
                                value="Tiempo completo"
                            />
                            Tiempo completo
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="tipo_empleo"
                                value="Medio tiempo"
                            />
                            Medio tiempo
                        </label>
                        <label className="flex items-center gap-2">
                            <input
                                type="radio"
                                name="tipo_empleo"
                                value="Por obra"
                            />
                            Por obra
                        </label>
                    </div>
                </div>

                <div>
                    <label htmlFor="fecha_publicacion" className={labelClase}>
                        Fecha de publicación
                    </label>
                    <input
                        id="fecha_publicacion"
                        type="datetime-local"
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="estado" className={labelClase}>
                        Estado
                    </label>
                    <select id="estado" className={inputClase}>
                        <option value="">Selecciona un estado</option>
                        <option value="Abierta">Abierta</option>
                        <option value="Cerrada">Cerrada</option>
                    </select>
                </div>

                <div className="flex gap-2">
                    <button
                        type="button"
                        className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80"
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

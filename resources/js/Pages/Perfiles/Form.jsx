import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";

export default function Form() {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de perfil
            </h1>

            <form className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                <div>
                    <label htmlFor="usuario_id" className={labelClase}>
                        Usuario
                    </label>
                    <select id="usuario_id" className={inputClase}>
                        <option value="">Selecciona un usuario</option>
                        <option value="1">Ana López</option>
                        <option value="2">Carlos Ramírez</option>
                        <option value="3">María Torres</option>
                    </select>
                </div>

                <div>
                    <label htmlFor="profesion_oficio" className={labelClase}>
                        Profesión u oficio
                    </label>
                    <input
                        id="profesion_oficio"
                        type="text"
                        maxLength={100}
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
                    <label htmlFor="experiencia" className={labelClase}>
                        Experiencia (años)
                    </label>
                    <input
                        id="experiencia"
                        type="number"
                        min={0}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="habilidades" className={labelClase}>
                        Habilidades
                    </label>
                    <textarea
                        id="habilidades"
                        rows={3}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="ciudad" className={labelClase}>
                        Ciudad
                    </label>
                    <input
                        id="ciudad"
                        type="text"
                        maxLength={100}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="cv" className={labelClase}>
                        CV
                    </label>
                    <input
                        id="cv"
                        type="file"
                        accept=".pdf,.doc,.docx"
                        className="w-full text-sm text-white file:mr-3 file:rounded file:border-0 file:bg-gray-600 file:px-3 file:py-2 file:text-white"
                    />
                </div>

                <div className="flex gap-2">
                    <button
                        type="button"
                        className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80"
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

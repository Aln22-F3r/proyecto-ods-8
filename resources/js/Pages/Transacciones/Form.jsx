import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";

export default function Form() {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de transacción
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
                    <label htmlFor="tipo_registro" className={labelClase}>
                        Tipo de registro
                    </label>
                    <select id="tipo_registro" className={inputClase}>
                        <option value="">Selecciona un tipo</option>
                        <option value="Registro">Registro</option>
                        <option value="Publicación">Publicación</option>
                        <option value="Postulación">Postulación</option>
                    </select>
                </div>

                <div>
                    <label htmlFor="descripcion" className={labelClase}>
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        rows={3}
                        maxLength={255}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="fecha" className={labelClase}>
                        Fecha
                    </label>
                    <input
                        id="fecha"
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
                        <option value="Pendiente">Pendiente</option>
                        <option value="Completada">Completada</option>
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

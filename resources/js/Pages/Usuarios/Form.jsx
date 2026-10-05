import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";

export default function Form() {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de usuario
            </h1>

            <form className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                <div>
                    <label htmlFor="rol_id" className={labelClase}>
                        Rol
                    </label>
                    <select id="rol_id" className={inputClase}>
                        <option value="">Selecciona un rol</option>
                        <option value="1">Administrador</option>
                        <option value="2">Empresa</option>
                        <option value="3">Candidato</option>
                    </select>
                </div>

                <div>
                    <label htmlFor="nombre" className={labelClase}>
                        Nombre
                    </label>
                    <input
                        id="nombre"
                        type="text"
                        maxLength={100}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="apellido" className={labelClase}>
                        Apellido
                    </label>
                    <input
                        id="apellido"
                        type="text"
                        maxLength={100}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="correo" className={labelClase}>
                        Correo
                    </label>
                    <input
                        id="correo"
                        type="email"
                        maxLength={150}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="password" className={labelClase}>
                        Contraseña
                    </label>
                    <input
                        id="password"
                        type="password"
                        maxLength={255}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="telefono" className={labelClase}>
                        Teléfono
                    </label>
                    <input
                        id="telefono"
                        type="tel"
                        maxLength={20}
                        className={inputClase}
                    />
                </div>

                <div>
                    <label htmlFor="fecha_registro" className={labelClase}>
                        Fecha de registro
                    </label>
                    <input
                        id="fecha_registro"
                        type="datetime-local"
                        className={inputClase}
                    />
                </div>

                <div className="flex items-center gap-2">
                    <input id="estado" type="checkbox" className="h-4 w-4" />
                    <label
                        htmlFor="estado"
                        className="text-sm font-medium text-white"
                    >
                        Activo
                    </label>
                </div>

                <div className="flex gap-2">
                    <button
                        type="button"
                        className="rounded bg-[#9a5f64] px-4 py-2 text-sm font-medium text-white hover:bg-[#9a5f64]/80"
                    >
                        Guardar
                    </button>
                    <Link
                        href="/usuarios"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

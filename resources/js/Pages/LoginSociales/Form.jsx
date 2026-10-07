import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

export default function Form({ usuarios = [] }) {
    const { data, setData, post, processing, errors, setError, clearErrors } =
        useForm({
            usuario_id: "",
            proveedor: "",
            proveedor_id: "",
            correo: "",
        });

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.usuario_id) {
            nuevos.usuario_id = "Selecciona un usuario.";
        }

        if (!data.proveedor) {
            nuevos.proveedor = "Selecciona un proveedor.";
        }

        if (!data.proveedor_id.trim()) {
            nuevos.proveedor_id = "El ID del proveedor es obligatorio.";
        } else if (data.proveedor_id.trim().length < 3) {
            nuevos.proveedor_id =
                "El ID del proveedor debe tener al menos 3 caracteres.";
        }

        if (!data.correo.trim()) {
            nuevos.correo = "El correo es obligatorio.";
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.correo.trim())) {
            nuevos.correo = "El correo no tiene un formato válido.";
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        post("/login-sociales");
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de login social
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
                    <label htmlFor="proveedor" className={labelClase}>
                        Proveedor
                    </label>
                    <select
                        id="proveedor"
                        value={data.proveedor}
                        onChange={(e) => setData("proveedor", e.target.value)}
                        className={inputClase}
                    >
                        <option value="">Selecciona un proveedor</option>
                        <option value="Google">Google</option>
                        <option value="Facebook">Facebook</option>
                        <option value="GitHub">GitHub</option>
                    </select>
                    {errors.proveedor && (
                        <p className={errorClase}>{errors.proveedor}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="proveedor_id" className={labelClase}>
                        ID del proveedor
                    </label>
                    <input
                        id="proveedor_id"
                        type="text"
                        maxLength={255}
                        value={data.proveedor_id}
                        onChange={(e) =>
                            setData("proveedor_id", e.target.value)
                        }
                        className={inputClase}
                    />
                    {errors.proveedor_id && (
                        <p className={errorClase}>{errors.proveedor_id}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="correo" className={labelClase}>
                        Correo
                    </label>
                    <input
                        id="correo"
                        type="email"
                        maxLength={150}
                        value={data.correo}
                        onChange={(e) => setData("correo", e.target.value)}
                        className={inputClase}
                    />
                    {errors.correo && (
                        <p className={errorClase}>{errors.correo}</p>
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
                        href="/login-sociales"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

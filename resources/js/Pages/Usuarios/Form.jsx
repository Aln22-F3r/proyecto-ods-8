import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

export default function Form({ roles = [] }) {
    const { data, setData, post, processing, errors, setError, clearErrors } =
        useForm({
            rol_id: "",
            nombre: "",
            apellido: "",
            correo: "",
            password: "",
            telefono: "",
            fecha_registro: "",
            estado: true,
        });

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.rol_id) {
            nuevos.rol_id = "Selecciona un rol.";
        }

        if (!data.nombre.trim()) {
            nuevos.nombre = "El nombre es obligatorio.";
        } else if (data.nombre.trim().length < 2) {
            nuevos.nombre = "El nombre debe tener al menos 2 caracteres.";
        }

        if (!data.apellido.trim()) {
            nuevos.apellido = "El apellido es obligatorio.";
        } else if (data.apellido.trim().length < 2) {
            nuevos.apellido = "El apellido debe tener al menos 2 caracteres.";
        }

        if (!data.correo.trim()) {
            nuevos.correo = "El correo es obligatorio.";
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.correo.trim())) {
            nuevos.correo = "El correo no tiene un formato válido.";
        }

        if (!data.password) {
            nuevos.password = "La contraseña es obligatoria.";
        } else if (data.password.length < 8) {
            nuevos.password = "La contraseña debe tener al menos 8 caracteres.";
        }

        if (!data.telefono.trim()) {
            nuevos.telefono = "El teléfono es obligatorio.";
        } else if (!/^[0-9]{10,20}$/.test(data.telefono.trim())) {
            nuevos.telefono =
                "El teléfono debe tener solo números, entre 10 y 20 dígitos.";
        }

        if (!data.fecha_registro) {
            nuevos.fecha_registro = "La fecha de registro es obligatoria.";
        } else if (isNaN(new Date(data.fecha_registro).getTime())) {
            nuevos.fecha_registro = "La fecha de registro no es válida.";
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        post("/usuarios");
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de usuario
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
                    <label htmlFor="rol_id" className={labelClase}>
                        Rol
                    </label>
                    <select
                        id="rol_id"
                        value={data.rol_id}
                        onChange={(e) => setData("rol_id", e.target.value)}
                        className={inputClase}
                    >
                        <option value="">Selecciona un rol</option>
                        {roles.map((r) => (
                            <option key={r.id} value={r.id}>
                                {r.nombre}
                            </option>
                        ))}
                    </select>
                    {errors.rol_id && (
                        <p className={errorClase}>{errors.rol_id}</p>
                    )}
                </div>

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
                    <label htmlFor="apellido" className={labelClase}>
                        Apellido
                    </label>
                    <input
                        id="apellido"
                        type="text"
                        maxLength={100}
                        value={data.apellido}
                        onChange={(e) => setData("apellido", e.target.value)}
                        className={inputClase}
                    />
                    {errors.apellido && (
                        <p className={errorClase}>{errors.apellido}</p>
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

                <div>
                    <label htmlFor="password" className={labelClase}>
                        Contraseña
                    </label>
                    <input
                        id="password"
                        type="password"
                        maxLength={255}
                        value={data.password}
                        onChange={(e) => setData("password", e.target.value)}
                        className={inputClase}
                    />
                    {errors.password && (
                        <p className={errorClase}>{errors.password}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="telefono" className={labelClase}>
                        Teléfono
                    </label>
                    <input
                        id="telefono"
                        type="tel"
                        maxLength={20}
                        value={data.telefono}
                        onChange={(e) => setData("telefono", e.target.value)}
                        className={inputClase}
                    />
                    {errors.telefono && (
                        <p className={errorClase}>{errors.telefono}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="fecha_registro" className={labelClase}>
                        Fecha de registro
                    </label>
                    <input
                        id="fecha_registro"
                        type="datetime-local"
                        value={data.fecha_registro}
                        onChange={(e) =>
                            setData("fecha_registro", e.target.value)
                        }
                        className={inputClase}
                    />
                    {errors.fecha_registro && (
                        <p className={errorClase}>{errors.fecha_registro}</p>
                    )}
                </div>

                <div>
                    <div className="flex items-center gap-2">
                        <input
                            id="estado"
                            type="checkbox"
                            checked={data.estado}
                            onChange={(e) =>
                                setData("estado", e.target.checked)
                            }
                            className="h-4 w-4"
                        />
                        <label
                            htmlFor="estado"
                            className="text-sm font-medium text-white"
                        >
                            Activo
                        </label>
                    </div>
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

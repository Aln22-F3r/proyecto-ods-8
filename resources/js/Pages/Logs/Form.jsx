import { useEffect } from "react";
import { Link, useForm } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

const inputClase =
    "w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white";
const labelClase = "mb-1 block text-sm font-medium text-white";
const errorClase = "mt-1 text-sm text-red-400";

const esIpv4 = (valor) =>
    /^(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)(\.(25[0-5]|2[0-4]\d|1\d\d|[1-9]?\d)){3}$/.test(
        valor,
    );
const esIpv6 = (valor) =>
    /^[0-9a-fA-F:]+$/.test(valor) && (valor.match(/:/g) || []).length >= 2;

const valoresIniciales = (log) => ({
    usuario_id: log?.usuario_id ?? "",
    accion: log?.accion ?? "",
    fecha: log?.fecha ?? "",
    ip: log?.ip ?? "",
});

export default function Form({ usuarios = [], log = null }) {
    const editando = log !== null;

    const {
        data,
        setData,
        post,
        put,
        processing,
        errors,
        setError,
        clearErrors,
    } = useForm(valoresIniciales(log));

    // Al pasar de crear a editar (o entre registros) se recargan los campos //
    useEffect(() => {
        setData(valoresIniciales(log));
        clearErrors();
    }, [log?.id]);

    const enviar = (e) => {
        e.preventDefault();
        clearErrors();

        const nuevos = {};

        if (!data.usuario_id) {
            nuevos.usuario_id = "Selecciona un usuario.";
        }

        if (!data.accion.trim()) {
            nuevos.accion = "La acción es obligatoria.";
        } else if (data.accion.trim().length < 3) {
            nuevos.accion = "La acción debe tener al menos 3 caracteres.";
        }

        if (!data.fecha) {
            nuevos.fecha = "La fecha es obligatoria.";
        } else if (isNaN(new Date(data.fecha).getTime())) {
            nuevos.fecha = "La fecha no es válida.";
        }

        const ip = data.ip.trim();
        if (!ip) {
            nuevos.ip = "La IP es obligatoria.";
        } else if (!esIpv4(ip) && !esIpv6(ip)) {
            nuevos.ip = "La IP no tiene un formato válido (IPv4 o IPv6).";
        }

        if (Object.keys(nuevos).length > 0) {
            setError(nuevos);
            return;
        }

        if (editando) {
            put(`/logs/${log.id}`);
        } else {
            post("/logs");
        }
    };

    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                {editando ? "Editar log" : "Formulario de log"}
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
                    <label htmlFor="accion" className={labelClase}>
                        Acción
                    </label>
                    <input
                        id="accion"
                        type="text"
                        maxLength={255}
                        value={data.accion}
                        onChange={(e) => setData("accion", e.target.value)}
                        className={inputClase}
                    />
                    {errors.accion && (
                        <p className={errorClase}>{errors.accion}</p>
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
                    <label htmlFor="ip" className={labelClase}>
                        IP
                    </label>
                    <input
                        id="ip"
                        type="text"
                        maxLength={45}
                        value={data.ip}
                        onChange={(e) => setData("ip", e.target.value)}
                        className={inputClase}
                    />
                    {errors.ip && <p className={errorClase}>{errors.ip}</p>}
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
                        href="/logs"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

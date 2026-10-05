import { Link } from "@inertiajs/react";
import AdminLayout from "../../Layouts/AdminLayout";

export default function Form() {
    return (
        <AdminLayout>
            <h1 className="text-2xl font-semibold text-white">
                Formulario de rol
            </h1>

            <form className="mt-6 max-w-xl space-y-4 rounded border border-[#4a4141] bg-[#272020] p-6">
                <div>
                    <label
                        htmlFor="nombre"
                        className="mb-1 block text-sm font-medium text-white"
                    >
                        Nombre
                    </label>
                    <input
                        id="nombre"
                        type="text"
                        maxLength={50}
                        className="w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white"
                    />
                </div>

                <div>
                    <label
                        htmlFor="descripcion"
                        className="mb-1 block text-sm font-medium text-white"
                    >
                        Descripción
                    </label>
                    <textarea
                        id="descripcion"
                        rows={3}
                        maxLength={150}
                        className="w-full rounded border border-[#5a5050] bg-[#363131] px-3 py-2 text-white"
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
                        href="/roles"
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AdminLayout>
    );
}

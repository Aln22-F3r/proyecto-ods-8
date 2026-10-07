import { useState } from "react";
import { Link, usePage } from "@inertiajs/react";

const modulos = [
    { nombre: "Roles", ruta: "/roles" },
    { nombre: "Usuarios", ruta: "/usuarios" },
    { nombre: "Perfiles", ruta: "/perfiles" },
    { nombre: "Categorías", ruta: "/categorias" },
    { nombre: "Ofertas de empleo", ruta: "/ofertas-empleo" },
    { nombre: "Postulaciones", ruta: "/postulaciones" },
    { nombre: "Transacciones", ruta: "/transacciones" },
    { nombre: "Login sociales", ruta: "/login-sociales" },
    { nombre: "Logs", ruta: "/logs" },
];

export default function AdminLayout({ children }) {
    const { url, props } = usePage();
    const flash = props.flash;
    const [abierto, setAbierto] = useState(false);

    const claseLink = (ruta) =>
        `block rounded px-3 py-1.5 text-sm text-white transition-colors ${
            url === ruta
                ? "bg-[#9a5f64] font-semibold"
                : "hover:bg-[#9a5f64]/50"
        }`;

    return (
        <div className="min-h-screen flex flex-col bg-[#363131]">
            {/* ENCABEZADO */}
            <header className="fixed top-0 left-0 right-0 z-20 h-14 bg-[#2b2727] text-white flex items-center justify-between px-4">
                <Link
                    href="/dashboard"
                    className="text-xl font-semibold text-white"
                >
                    Panel Administrativo
                </Link>
                <button
                    type="button"
                    className="md:hidden p-2 text-white"
                    onClick={() => setAbierto(!abierto)}
                >
                    <span className="sr-only">Abrir menú</span>
                    <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24">
                        <path
                            stroke="currentColor"
                            strokeLinecap="round"
                            strokeWidth="2"
                            d="M5 7h14M5 12h14M5 17h14"
                        />
                    </svg>
                </button>
            </header>

            {/* MENÚ PRINCIPAL */}
            <aside
                className={`fixed top-14 left-0 z-10 h-[calc(100vh-3.5rem)] w-64 overflow-y-auto bg-[#272020] p-4 text-white transition-transform md:translate-x-0 ${
                    abierto ? "translate-x-0" : "-translate-x-full"
                }`}
            >
                <Link href="/dashboard" className={claseLink("/dashboard")}>
                    Dashboard
                </Link>

                {modulos.map((m) => (
                    <div key={m.ruta} className="mt-4">
                        <p className="px-3 text-xs font-bold uppercase text-white/70">
                            {m.nombre}
                        </p>
                        <Link href={m.ruta} className={claseLink(m.ruta)}>
                            Lista
                        </Link>
                        <Link
                            href={`${m.ruta}/crear`}
                            className={claseLink(`${m.ruta}/crear`)}
                        >
                            Formulario
                        </Link>
                    </div>
                ))}
            </aside>

            {/* ÁREA DE CONTENIDO + PIE DE PÁGINA */}
            <div className="flex flex-1 flex-col pt-14 md:ml-64">
                <main className="flex-1 p-6">
                    {flash?.exito && (
                        <div className="mb-4 rounded border border-green-700 bg-green-900/40 p-3 text-sm text-white">
                            {flash.exito}
                        </div>
                    )}
                    {flash?.error && (
                        <div className="mb-4 rounded border border-red-700 bg-red-900/40 p-3 text-sm text-white">
                            {flash.error}
                        </div>
                    )}
                    {children}
                </main>

                <footer className="bg-[#272020] p-3 text-center text-sm text-white">
                    © 2026 Panel Administrativo
                </footer>
            </div>
        </div>
    );
}

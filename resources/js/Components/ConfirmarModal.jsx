import { useEffect } from "react";

export default function ConfirmarModal({
    abierto,
    titulo,
    mensaje,
    textoConfirmar = "Confirmar",
    colorConfirmar = "bg-[#9a5f64] hover:bg-[#9a5f64]/80",
    procesando = false,
    onConfirmar,
    onCancelar,
}) {
    // Cierra el modal con la tecla Escape //
    useEffect(() => {
        if (!abierto) return;

        const alPresionar = (e) => {
            if (e.key === "Escape" && !procesando) onCancelar();
        };

        window.addEventListener("keydown", alPresionar);
        return () => window.removeEventListener("keydown", alPresionar);
    }, [abierto, procesando]);

    if (!abierto) return null;

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
            onClick={() => !procesando && onCancelar()}
        >
            <div
                role="dialog"
                aria-modal="true"
                className="w-full max-w-md rounded border border-[#4a4141] bg-[#272020] p-6 text-white"
                onClick={(e) => e.stopPropagation()}
            >
                <h2 className="text-lg font-semibold">{titulo}</h2>
                <p className="mt-2 text-sm text-white/80">{mensaje}</p>

                <div className="mt-6 flex justify-end gap-2">
                    <button
                        type="button"
                        onClick={onCancelar}
                        disabled={procesando}
                        className="rounded bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-500 disabled:opacity-50"
                    >
                        Cancelar
                    </button>
                    <button
                        type="button"
                        onClick={onConfirmar}
                        disabled={procesando}
                        className={`rounded px-4 py-2 text-sm font-medium text-white disabled:opacity-50 ${colorConfirmar}`}
                    >
                        {textoConfirmar}
                    </button>
                </div>
            </div>
        </div>
    );
}

import { Link } from "@inertiajs/react";

const traducir = (label) =>
    label
        .replace("&laquo; Previous", "Anterior")
        .replace("Next &raquo;", "Siguiente");

export default function Paginacion({ links }) {
    return (
        <div className="mt-4 flex flex-wrap gap-1">
            {links.map((l, i) =>
                l.url ? (
                    <Link
                        key={i}
                        href={l.url}
                        className={`rounded px-3 py-1 text-sm text-white ${
                            l.active
                                ? "bg-[#9a5f64]"
                                : "bg-gray-600 hover:bg-gray-500"
                        }`}
                    >
                        {traducir(l.label)}
                    </Link>
                ) : (
                    <span
                        key={i}
                        className="rounded bg-[#272020] px-3 py-1 text-sm text-white/40"
                    >
                        {traducir(l.label)}
                    </span>
                ),
            )}
        </div>
    );
}

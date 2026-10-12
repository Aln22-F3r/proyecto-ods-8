<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PerfilController extends Controller
{
    public function index()
    {
        return Inertia::render('Perfiles/Index', [
            'perfiles' => Perfil::with('usuario:id,nombre,apellido')->paginate(5),
            'eliminados' => false,
        ]);
    }

    public function eliminados()
    {
        return Inertia::render('Perfiles/Index', [
            'perfiles' => Perfil::onlyTrashed()->with('usuario:id,nombre,apellido')->paginate(5),
            'eliminados' => true,
        ]);
    }

    public function show($id)
    {
        $perfil = Perfil::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$perfil) {
            return redirect('/perfiles')->with('error', 'El perfil solicitado no existe.');
        }

        $zona = 'America/Mexico_City';

        return Inertia::render('Perfiles/Show', [
            'perfil' => [
                'id' => $perfil->id,
                'usuario' => $perfil->usuario
                    ? "{$perfil->usuario->nombre} {$perfil->usuario->apellido}"
                    : null,
                'correo' => $perfil->usuario?->correo,
                'profesion_oficio' => $perfil->profesion_oficio,
                'descripcion' => $perfil->descripcion,
                'experiencia' => $perfil->experiencia,
                'habilidades' => $perfil->habilidades,
                'ciudad' => $perfil->ciudad,
                // Solo se envía la URL si el archivo realmente existe en el disco //
                'cv' => $this->urlSiExiste($perfil->cv),
                'foto' => $this->urlSiExiste($perfil->foto),
                'creado' => $perfil->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $perfil->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $perfil->trashed(),
                'fecha_eliminacion' => $perfil->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('Perfiles/Form', [
            // Usuarios sin perfil; se cuentan también los perfiles eliminados, porque siguen ocupando al usuario //
            'usuarios' => Usuario::whereDoesntHave('perfil', fn($q) => $q->withTrashed())
                ->select('id', 'nombre', 'apellido')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        try {
            DB::transaction(function () use ($request, $datos) {
                // Se crea el registro para obtener su id (cv es obligatorio en la tabla, queda vacío un instante) //
                $perfil = Perfil::create([
                    'usuario_id' => $datos['usuario_id'],
                    'profesion_oficio' => $datos['profesion_oficio'],
                    'descripcion' => $datos['descripcion'],
                    'experiencia' => $datos['experiencia'],
                    'habilidades' => $datos['habilidades'],
                    'ciudad' => $datos['ciudad'],
                    'cv' => '',
                ]);
                // Se guardan los archivos con el id y se actualiza la ruta en la base de datos //
                $cv = $request->file('cv');
                $rutaCv = $cv->storeAs('cv', "Perfil_{$perfil->id}_cv." . $cv->extension(), 'public');
                $perfil->cv = Storage::url($rutaCv);

                if ($request->hasFile('foto')) {
                    $foto = $request->file('foto');
                    $rutaFoto = $foto->storeAs('imagenes', "Perfil_{$perfil->id}_1." . $foto->extension(), 'public');
                    $perfil->foto = Storage::url($rutaFoto);
                }

                $perfil->save();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo guardar el perfil. Intenta de nuevo.');
        }

        return redirect('/perfiles')->with('exito', 'Perfil creado correctamente.');
    }

    public function edit($id)
    {
        $perfil = Perfil::find($id);

        if (!$perfil) {
            return redirect('/perfiles')->with('error', 'El perfil solicitado no existe. No se puede editar.');
        }

        return Inertia::render('Perfiles/Form', [
            'perfil' => [
                'id' => $perfil->id,
                'usuario_id' => $perfil->usuario_id,
                'profesion_oficio' => $perfil->profesion_oficio,
                'descripcion' => $perfil->descripcion,
                'experiencia' => $perfil->experiencia,
                'habilidades' => $perfil->habilidades,
                'ciudad' => $perfil->ciudad,
                'cv' => $perfil->cv,
                'foto' => $perfil->foto,
            ],
            // Usuarios sin perfil (activo o eliminado), más el dueño de este perfil aunque esté eliminado //
            'usuarios' => Usuario::withTrashed()
                ->where(function ($q) use ($perfil) {
                    $q->where('id', $perfil->usuario_id)
                        ->orWhere(function ($q2) {
                            $q2->whereNull('deleted_at')
                                ->whereDoesntHave('perfil', fn($p) => $p->withTrashed());
                        });
                })
                ->select('id', 'nombre', 'apellido')
                ->orderBy('nombre')
                ->get(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $perfil = Perfil::find($id);

        if (!$perfil) {
            return redirect('/perfiles')->with('error', 'El perfil solicitado no existe. No se puede actualizar.');
        }

        $datos = $this->validar($request, $perfil->id);

        // Los archivos se manejan aparte; aquí solo los datos de texto //
        $perfil->fill(collect($datos)->except(['cv', 'foto'])->all());

        $archivoNuevo = $request->hasFile('cv') || $request->hasFile('foto');

        if (!$perfil->isDirty() && !$archivoNuevo) {
            return redirect('/perfiles')->with('advertencia', 'No se realizaron cambios en el perfil.');
        }

        $anteriores = [];

        try {
            if ($request->hasFile('cv')) {
                $cv = $request->file('cv');
                $ruta = $cv->storeAs('cv', "Perfil_{$perfil->id}_cv." . $cv->extension(), 'public');
                $url = Storage::url($ruta);
                if ($perfil->cv !== $url) {
                    $anteriores[] = $perfil->cv;
                }
                $perfil->cv = $url;
            }

            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $ruta = $foto->storeAs('imagenes', "Perfil_{$perfil->id}_1." . $foto->extension(), 'public');
                $url = Storage::url($ruta);
                if ($perfil->foto !== $url) {
                    $anteriores[] = $perfil->foto;
                }
                $perfil->foto = $url;
            }

            $perfil->save();
        } catch (\Throwable $e) {
            return back()->with('error', 'No se pudo actualizar el perfil. Intenta de nuevo.');
        }

        // Solo se borran los archivos viejos cuando todo se guardó bien y su nombre cambió //
        foreach ($anteriores as $anterior) {
            $this->borrarArchivo($anterior);
        }

        return redirect('/perfiles')->with('exito', 'Perfil actualizado correctamente.');
    }

    // Borrado lógico: solo llena deleted_at, el registro y sus archivos se conservan //
    public function destroy($id)
    {
        $perfil = Perfil::with('usuario:id,nombre,apellido')->find($id);

        if (!$perfil) {
            return redirect('/perfiles')->with('error', 'El perfil solicitado no existe o ya fue eliminado.');
        }

        try {
            $perfil->delete();
        } catch (\Throwable $e) {
            return redirect('/perfiles')->with('error', 'No se pudo eliminar el perfil. Intenta de nuevo.');
        }

        return redirect('/perfiles')->with('exito', "{$this->etiqueta($perfil)} se eliminó. Puedes restaurarlo desde la lista de eliminados.");
    }

    public function restore($id)
    {
        $perfil = Perfil::onlyTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$perfil) {
            return redirect('/perfiles/eliminados')->with('error', 'El perfil solicitado no existe o no está eliminado.');
        }

        try {
            $perfil->restore();
        } catch (\Throwable $e) {
            return redirect('/perfiles/eliminados')->with('error', 'No se pudo restaurar el perfil. Intenta de nuevo.');
        }

        return redirect('/perfiles/eliminados')->with('exito', "{$this->etiqueta($perfil)} se restauró y ya aparece en la lista principal.");
    }

    // Borrado físico: solo para registros ya eliminados. Ninguna otra tabla depende de los perfiles. //
    public function forceDelete($id)
    {
        $perfil = Perfil::withTrashed()->with('usuario:id,nombre,apellido')->find($id);

        if (!$perfil) {
            return redirect('/perfiles/eliminados')->with('error', 'El perfil solicitado no existe.');
        }

        if (!$perfil->trashed()) {
            return redirect('/perfiles')->with('advertencia', 'El perfil debe eliminarse primero (borrado lógico) antes de poder borrarlo definitivamente.');
        }

        $etiqueta = $this->etiqueta($perfil);
        $archivos = [$perfil->cv, $perfil->foto];

        try {
            $perfil->forceDelete();
        } catch (\Throwable $e) {
            return redirect('/perfiles/eliminados')->with('error', 'No se pudo eliminar definitivamente el perfil. Intenta de nuevo.');
        }

        // El registro ya no existe: se eliminan también sus archivos para no dejarlos huérfanos //
        foreach ($archivos as $archivo) {
            $this->borrarArchivo($archivo);
        }

        return redirect('/perfiles/eliminados')->with('exito', "{$etiqueta} se eliminó definitivamente, junto con su CV y su foto.");
    }

    private function etiqueta(Perfil $perfil): string
    {
        $usuario = $perfil->usuario
            ? "{$perfil->usuario->nombre} {$perfil->usuario->apellido}"
            : 'un usuario';

        return "El perfil de «{$usuario}»";
    }

    private function relativa(?string $ruta): ?string
    {
        return $ruta ? Str::after($ruta, '/storage/') : null;
    }

    private function urlSiExiste(?string $ruta): ?string
    {
        $relativa = $this->relativa($ruta);

        return ($relativa && Storage::disk('public')->exists($relativa))
            ? Storage::url($relativa)
            : null;
    }

    private function borrarArchivo(?string $ruta): void
    {
        $relativa = $this->relativa($ruta);

        if ($relativa) {
            Storage::disk('public')->delete($relativa);
        }
    }

    private function validar(Request $request, $id = null): array
    {
        return $request->validate([
            'usuario_id' => [
                'required',
                'exists:usuarios,id',
                Rule::unique('perfiles', 'usuario_id')->ignore($id),
            ],
            'profesion_oficio' => ['required', 'string', 'min:3', 'max:100'],
            'descripcion' => ['required', 'string', 'min:10'],
            'experiencia' => ['required', 'integer', 'min:0', 'max:60'],
            'habilidades' => ['required', 'string', 'min:3'],
            'ciudad' => ['required', 'string', 'min:2', 'max:100'],
            'cv' => [$id ? 'nullable' : 'required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
            'min' => 'El campo :attribute debe tener al menos :min caracteres.',
            'max' => 'El campo :attribute no puede tener más de :max caracteres.',
            'exists' => 'El usuario seleccionado no es válido.',
            'unique' => 'Ese usuario ya tiene un perfil.',
            'integer' => 'La experiencia debe ser un número entero.',
            'experiencia.min' => 'La experiencia no puede ser negativa.',
            'experiencia.max' => 'La experiencia no puede ser mayor a :max años.',
            'file' => 'El campo :attribute debe ser un archivo.',
            'image' => 'La foto debe ser una imagen.',
            'cv.mimes' => 'El CV debe ser un archivo PDF o Word (pdf, doc, docx).',
            'cv.max' => 'El CV no puede pesar más de 5 MB.',
            'foto.mimes' => 'La foto debe ser de tipo jpg, jpeg, png o webp.',
            'foto.max' => 'La foto no puede pesar más de 2 MB.',
        ], [
            'usuario_id' => 'usuario',
            'profesion_oficio' => 'profesión u oficio',
            'descripcion' => 'descripción',
            'experiencia' => 'experiencia',
            'cv' => 'CV',
        ]);
    }
}

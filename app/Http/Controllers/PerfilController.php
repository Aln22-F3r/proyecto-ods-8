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
        ]);
    }

    public function create()
    {
        return Inertia::render('Perfiles/Form', [
            'usuarios' => Usuario::doesntHave('perfil')
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
            // Usuarios sin perfil, más el dueño de este perfil para que no desaparezca de la lista //
            'usuarios' => Usuario::where(function ($q) use ($perfil) {
                $q->doesntHave('perfil')->orWhere('id', $perfil->usuario_id);
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

    private function borrarArchivo(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk('public')->delete(Str::after($ruta, '/storage/'));
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

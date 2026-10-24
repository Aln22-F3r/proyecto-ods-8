<?php

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class PerfilController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Perfil::with('usuario:id,nombre,apellido')
                ->paginate(15),
        ]);
    }

    public function eliminados()
    {
        return response()->json([
            'data' => Perfil::onlyTrashed()
                ->with('usuario:id,nombre,apellido')
                ->paginate(15),
        ]);
    }

    public function show($id)
    {
        $perfil = Perfil::withTrashed()
            ->with('usuario:id,nombre,apellido,correo')
            ->find($id);

        if (!$perfil) {
            return response()->json([
                'message' => 'El perfil solicitado no existe.',
            ], 404);
        }

        $zona = 'America/Mexico_City';

        return response()->json([
            'data' => [
                'id' => $perfil->id,
                'usuario_id' => $perfil->usuario_id,
                'usuario' => $perfil->usuario
                    ? "{$perfil->usuario->nombre} {$perfil->usuario->apellido}"
                    : null,
                'correo' => $perfil->usuario?->correo,
                'profesion_oficio' => $perfil->profesion_oficio,
                'descripcion' => $perfil->descripcion,
                'experiencia' => $perfil->experiencia,
                'habilidades' => $perfil->habilidades,
                'ciudad' => $perfil->ciudad,
                'cv' => $this->urlSiExiste($perfil->cv),
                'foto' => $this->urlSiExiste($perfil->foto),
                'creado' => $perfil->created_at?->timezone($zona)->format('d/m/Y H:i'),
                'actualizado' => $perfil->updated_at?->timezone($zona)->format('d/m/Y H:i'),
                'eliminado' => $perfil->trashed(),
                'fecha_eliminacion' => $perfil->deleted_at?->timezone($zona)->format('d/m/Y H:i'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $archivosNuevos = [];

        try {
            $perfil = DB::transaction(function () use (
                $request,
                $datos,
                &$archivosNuevos
            ) {
                // Crear el registro para obtener su ID. //
                $perfil = Perfil::create([
                    'usuario_id' => $datos['usuario_id'],
                    'profesion_oficio' => $datos['profesion_oficio'],
                    'descripcion' => $datos['descripcion'],
                    'experiencia' => $datos['experiencia'],
                    'habilidades' => $datos['habilidades'],
                    'ciudad' => $datos['ciudad'],
                    'cv' => '',
                ]);

                // Guardar el CV. //
                $cv = $request->file('cv');
                $nombreCv = 'Perfil_' . $perfil->id . '_cv_'
                    . Str::uuid() . '.' . $cv->extension();

                $rutaCv = $cv->storeAs('cv', $nombreCv, 'public');
                $archivosNuevos[] = $rutaCv;
                $perfil->cv = Storage::url($rutaCv);

                // Guardar la foto si fue enviada. //
                if ($request->hasFile('foto')) {
                    $foto = $request->file('foto');
                    $nombreFoto = 'Perfil_' . $perfil->id . '_'
                        . Str::uuid() . '.' . $foto->extension();

                    $rutaFoto = $foto->storeAs(
                        'imagenes',
                        $nombreFoto,
                        'public'
                    );

                    $archivosNuevos[] = $rutaFoto;
                    $perfil->foto = Storage::url($rutaFoto);
                }

                $perfil->save();

                return $perfil;
            });
        } catch (Throwable $e) {
            report($e);

            foreach ($archivosNuevos as $archivo) {
                Storage::disk('public')->delete($archivo);
            }

            return response()->json([
                'message' => 'No se pudo guardar el perfil. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => 'Perfil creado correctamente.',
            'data' => $perfil->fresh()->load('usuario:id,nombre,apellido'),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $perfil = Perfil::find($id);

        if (!$perfil) {
            return response()->json([
                'message' => 'El perfil solicitado no existe o está eliminado.',
            ], 404);
        }

        $datos = $this->validar($request, $perfil->id);

        // Los datos de texto se actualizan por separado de los archivos. //
        $perfil->fill(
            collect($datos)->except(['cv', 'foto'])->all()
        );

        $archivoNuevo = $request->hasFile('cv')
            || $request->hasFile('foto');

        if (!$perfil->isDirty() && !$archivoNuevo) {
            return response()->json([
                'message' => 'No se realizaron cambios en el perfil.',
                'data' => $perfil->load('usuario:id,nombre,apellido'),
            ]);
        }

        $archivosNuevos = [];
        $archivosAnteriores = [];

        try {
            if ($request->hasFile('cv')) {
                $cv = $request->file('cv');

                $nombreCv = 'Perfil_' . $perfil->id . '_cv_'
                    . Str::uuid() . '.' . $cv->extension();

                $rutaCv = $cv->storeAs('cv', $nombreCv, 'public');
                $archivosNuevos[] = $rutaCv;

                $archivosAnteriores[] = $perfil->cv;
                $perfil->cv = Storage::url($rutaCv);
            }

            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');

                $nombreFoto = 'Perfil_' . $perfil->id . '_'
                    . Str::uuid() . '.' . $foto->extension();

                $rutaFoto = $foto->storeAs(
                    'imagenes',
                    $nombreFoto,
                    'public'
                );

                $archivosNuevos[] = $rutaFoto;
                $archivosAnteriores[] = $perfil->foto;
                $perfil->foto = Storage::url($rutaFoto);
            }

            $perfil->save();
        } catch (Throwable $e) {
            report($e);

            foreach ($archivosNuevos as $archivo) {
                Storage::disk('public')->delete($archivo);
            }

            return response()->json([
                'message' => 'No se pudo actualizar el perfil. Intenta de nuevo.',
            ], 500);
        }

        // Eliminar los archivos anteriores después de guardar correctamente. //
        foreach ($archivosAnteriores as $archivo) {
            $this->borrarArchivo($archivo);
        }

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
            'data' => $perfil->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    // Borrado lógico: conserva el registro y sus archivos. //
    public function destroy($id)
    {
        $perfil = Perfil::with('usuario:id,nombre,apellido')->find($id);

        if (!$perfil) {
            return response()->json([
                'message' => 'El perfil solicitado no existe o ya fue eliminado.',
            ], 404);
        }

        $etiqueta = $this->etiqueta($perfil);

        try {
            $perfil->delete();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar el perfil. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó. Puedes restaurarlo desde la lista de eliminados.",
        ]);
    }

    public function restore($id)
    {
        $perfil = Perfil::onlyTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$perfil) {
            return response()->json([
                'message' => 'El perfil solicitado no existe o no está eliminado.',
            ], 404);
        }

        try {
            $perfil->restore();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo restaurar el perfil. Intenta de nuevo.',
            ], 500);
        }

        return response()->json([
            'message' => "{$this->etiqueta($perfil)} se restauró correctamente.",
            'data' => $perfil->fresh()->load('usuario:id,nombre,apellido'),
        ]);
    }

    // Borrado físico: exige que el perfil ya esté eliminado lógicamente. //
    public function forceDelete($id)
    {
        $perfil = Perfil::withTrashed()
            ->with('usuario:id,nombre,apellido')
            ->find($id);

        if (!$perfil) {
            return response()->json([
                'message' => 'El perfil solicitado no existe.',
            ], 404);
        }

        if (!$perfil->trashed()) {
            return response()->json([
                'message' => 'El perfil debe eliminarse primero antes de borrarlo definitivamente.',
            ], 409);
        }

        $etiqueta = $this->etiqueta($perfil);
        $archivos = [$perfil->cv, $perfil->foto];

        try {
            $perfil->forceDelete();
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo eliminar definitivamente el perfil. Intenta de nuevo.',
            ], 500);
        }

        // Eliminar los archivos después de borrar el registro. //
        foreach ($archivos as $archivo) {
            $this->borrarArchivo($archivo);
        }

        return response()->json([
            'message' => "{$etiqueta} se eliminó definitivamente, junto con su CV y su foto.",
        ]);
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
            'profesion_oficio' => [
                'required',
                'string',
                'min:3',
                'max:100',
            ],
            'descripcion' => ['required', 'string', 'min:10'],
            'experiencia' => ['required', 'integer', 'min:0', 'max:60'],
            'habilidades' => ['required', 'string', 'min:3'],
            'ciudad' => ['required', 'string', 'min:2', 'max:100'],
            'cv' => [
                $id ? 'nullable' : 'required',
                'file',
                'mimes:pdf,doc,docx',
                'max:5120',
            ],
            'foto' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
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

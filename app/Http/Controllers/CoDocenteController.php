<?php

namespace App\Http\Controllers;

use App\Models\Aula;
use App\Models\InvitacionAula;
use App\Models\User;
use App\Notifications\InvitacionCoDocente;
use App\Services\Notificador;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class CoDocenteController extends Controller
{
    protected function soloTitular(User $user, Aula $aula): void
    {
        abort_unless($user->esSuperadmin() || $aula->docente_id == $user->id, 403, 'Solo el docente titular puede administrar los co-docentes.');
    }

    /** El titular invita por email. */
    public function invitar(Request $request, Aula $aula, Notificador $notificador)
    {
        $user = $request->user();
        $this->soloTitular($user, $aula);

        $request->merge(['email' => mb_strtolower(trim((string) $request->email))]);
        $request->validate(['email' => 'required|email|max:150'], [], ['email' => 'email del docente']);

        $email   = $request->email;
        $invitado = User::where('email', $email)->first();

        if ($invitado && $aula->puedeEditar($invitado)) {
            return back()->with('error', "{$invitado->nombre_completo} ya es docente de esta aula.");
        }

        if ($invitado && $aula->esAlumno($invitado)) {
            return back()->with('error', 'Esa persona es alumno de esta aula: no puede ser co-docente.');
        }

        // Reemplaza una invitación anterior al mismo email
        $aula->invitaciones()->where('email', $email)->where('estado', 'pendiente')->update(['estado' => 'cancelada']);

        $inv = InvitacionAula::create(['aula_id' => $aula->id, 'email' => $email, 'invitado_por' => $user->id]);

        // Email (tenga o no cuenta)
        Notification::route('mail', $email)->notify(new InvitacionCoDocente($inv, (bool) $invitado));

        // Campanita si ya tiene cuenta
        if ($invitado) {
            $notificador->enviar([$invitado], [
                'tipo'   => 'invitacion',
                'titulo' => 'Invitación a co-docente',
                'texto'  => $user->nombre_completo . ' te invitó a ser co-docente de ' . $aula->nombre . '.',
                'url'    => route('invitaciones.ver', $inv->token),
                'icono'  => 'bi-person-plus',
                'aula'   => $aula->nombre,
            ]);
        }

        return back()->with('exito', "Invitación enviada a {$email}. Vence en " . InvitacionAula::DIAS_VIGENCIA . ' días.');
    }

    public function cancelar(Request $request, InvitacionAula $invitacion)
    {
        $this->soloTitular($request->user(), $invitacion->aula);

        $invitacion->update(['estado' => 'cancelada', 'respondida_at' => Carbon::now('America/Argentina/Buenos_Aires')]);

        return back()->with('exito', 'Invitación cancelada.');
    }

    /** Página de la invitación (desde el email o la campanita). */
    public function ver(Request $request, string $token)
    {
        $inv = InvitacionAula::with(['aula.institucion', 'invitador'])->where('token', $token)->firstOrFail();

        return view('aulas.invitacion', [
            'inv'         => $inv,
            'emailCoincide' => $request->user()->email && mb_strtolower($request->user()->email) === $inv->email,
        ]);
    }

    public function responder(Request $request, string $token)
    {
        $user = $request->user();
        $inv  = InvitacionAula::with('aula.institucion.abono')->where('token', $token)->firstOrFail();
        $aula = $inv->aula;

        abort_unless($user->email && mb_strtolower($user->email) === $inv->email, 403, 'Esta invitación es para otro email.');

        if (! $inv->estaVigente()) {
            return redirect()->route('inicio')->with('error', 'La invitación ya no está vigente. Pedile al docente que te invite de nuevo.');
        }

        $ahora = Carbon::now('America/Argentina/Buenos_Aires');

        if ($request->input('respuesta') === 'rechazar') {
            $inv->update(['estado' => 'rechazada', 'respondida_at' => $ahora]);

            return redirect()->route('inicio')->with('exito', 'Rechazaste la invitación.');
        }

        // Aula de institución: el co-docente ocupa un lugar del cupo
        if ($aula->institucion_id) {
            $inst = $aula->institucion;

            if (! $inst->ocupaLugar($user)) {
                if (! $inst->abono || ! $inst->abono->hayCupo()) {
                    return back()->with('error', "{$inst->nombre} no tiene lugares de docente disponibles. Avisale a la institución.");
                }
                $inst->usuarios()->attach($user->id, ['rol' => 'docente', 'activo' => true]);
            }
        }

        DB::transaction(function () use ($aula, $user, $inv, $ahora) {
            if (! $user->hasRole('docente')) {
                $user->assignRole('docente');
            }

            $pivot = DB::table('aula_user')->where('aula_id', $aula->id)->where('user_id', $user->id)->first();
            $datos = ['rol' => 'docente', 'origen' => 'manual', 'estado' => 'activo', 'fecha_alta' => $ahora->toDateString(), 'fecha_baja' => null];

            $pivot ? $aula->miembros()->updateExistingPivot($user->id, $datos) : $aula->miembros()->attach($user->id, $datos);

            $inv->update(['estado' => 'aceptada', 'respondida_at' => $ahora]);
        });

        // Avisar al titular
        app(Notificador::class)->enviar([$aula->titular], [
            'tipo'   => 'invitacion',
            'titulo' => 'Co-docente sumado',
            'texto'  => $user->nombre_completo . ' aceptó ser co-docente de ' . $aula->nombre . '.',
            'url'    => route('aulas.show', $aula),
            'icono'  => 'bi-people',
            'aula'   => $aula->nombre,
        ]);

        return redirect()->route('aulas.show', $aula)->with('exito', "¡Listo! Ya sos co-docente de {$aula->nombre}.");
    }

    /** El titular quita a un co-docente. */
    public function quitar(Request $request, Aula $aula, User $user)
    {
        $this->soloTitular($request->user(), $aula);
        abort_if($user->id == $aula->docente_id, 422, 'No se puede quitar al titular.');

        $aula->miembros()->updateExistingPivot($user->id, [
            'estado' => 'baja', 'fecha_baja' => Carbon::now('America/Argentina/Buenos_Aires')->toDateString(),
        ]);

        return back()->with('exito', "{$user->nombre_completo} ya no es co-docente del aula.");
    }

    /** Un co-docente deja el aula. */
    public function dejar(Request $request, Aula $aula)
    {
        $user = $request->user();
        abort_if($user->id == $aula->docente_id, 422, 'Sos el titular: primero pasale la titularidad a otro docente.');

        $aula->miembros()->updateExistingPivot($user->id, [
            'estado' => 'baja', 'fecha_baja' => Carbon::now('America/Argentina/Buenos_Aires')->toDateString(),
        ]);

        return redirect()->route('inicio')->with('exito', "Dejaste de ser co-docente de {$aula->nombre}.");
    }

    /** El titular le pasa la titularidad a un co-docente (y queda como co-docente). */
    public function hacerTitular(Request $request, Aula $aula, User $user)
    {
        $this->soloTitular($request->user(), $aula);
        abort_unless($aula->docentes()->where('users.id', $user->id)->exists(), 404);

        // Aula personal: la cubre el abono del titular
        if (! $aula->institucion_id && ! $user->abonoActivo()) {
            return back()->with('error', "{$user->nombre_completo} no tiene abono activo: el aula quedaría en solo lectura. Que active su abono antes.");
        }

        $aula->update(['docente_id' => $user->id]);

        return back()->with('exito', "{$user->nombre_completo} ahora es el titular del aula. Vos seguís como co-docente.");
    }
}

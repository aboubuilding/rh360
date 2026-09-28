<?php

namespace App\Http\Controllers\Formation;

use App\Domain\Formation\Actions\AjouterParticipantFormation;
use App\Domain\Formation\Actions\EvaluerParticipantFormation;
use App\Domain\Formation\Enums\StatutPresenceParticipant;
use App\Domain\Formation\Models\ParticipantFormation;
use App\Domain\Formation\Models\SessionFormation;
use App\Domain\Formation\Requests\EvaluerParticipantRequest;
use App\Domain\Formation\Services\CalculateurProgressionParticipant;
use App\Http\Controllers\Controller;

class ParticipantFormationController extends Controller
{
    public function __construct(private CalculateurProgressionParticipant $calculateur) {}

    public function evaluer(EvaluerParticipantRequest $request, SessionFormation $session, ParticipantFormation $participant, EvaluerParticipantFormation $action)
    {
        $this->authorize('evaluer', $participant);

        if ($participant->session_formation_id !== $session->id) {
            abort(404);
        }

        $action->executer($participant, $request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Évaluation enregistrée.']);
        }

        return back()->with('success', 'Évaluation enregistrée.');
    }

    public function retirer(SessionFormation $session, ParticipantFormation $participant, AjouterParticipantFormation $action)
    {
        $this->authorize('update', $participant);

        if ($participant->session_formation_id !== $session->id) {
            abort(404);
        }

        try {
            $action->retirer($participant);
        } catch (\DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Participant retiré.');
    }
}
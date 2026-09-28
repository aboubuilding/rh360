<?php

namespace App\Http\Controllers\Conges;

use App\Domain\Conges\Actions\EnregistrerAbsence;
use App\Domain\Conges\Actions\QualifierAbsence;
use App\Domain\Conges\Actions\RegulariserAbsence;
use App\Domain\Conges\Actions\TransmettreAbsencesPaie;
use App\Domain\Conges\Enums\QualificationAbsence;
use App\Domain\Conges\Enums\StatutTransmissionPaie;
use App\Domain\Conges\Models\Absence;
use App\Domain\Conges\Models\TypeConge;
use App\Domain\Conges\Requests\RegulariserAbsenceRequest;
use App\Domain\Conges\Requests\StoreAbsenceRequest;
use App\Domain\Conges\Requests\TransmettreAbsencesPaieRequest;
use App\Domain\Personnel\Models\Salarie;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AbsenceController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Absence::class);

        $absences = Absence::query()
            ->with(['salarie', 'typeConge'])
            ->recherche($request->q)
            ->when($request->filled('statut_paie'), fn ($q) => $q->where('statut_transmission_paie', $request->statut_paie))
            ->when($request->filled('qualification'), fn ($q) => $q->where('qualification', $request->qualification))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('debut_le', '>=', $request->du))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('debut_le', '<=', $request->au))
            ->orderByDesc('debut_le')
            ->paginate(50)
            ->withQueryString();

        return view('conges.absences.index', [
            'absences' => $absences,
            'qualifications' => QualificationAbsence::options(),
            'statutsPaie' => StatutTransmissionPaie::options(),
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Absence::class);

        return view('conges.absences.create', [
            'salarie' => $request->filled('salarie_id') ? Salarie::findOrFail($request->salarie_id) : null,
            'salaries' => Salarie::orderBy('nom')->get(),
            'types' => TypeConge::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(StoreAbsenceRequest $request, EnregistrerAbsence $action)
    {
        $this->authorize('create', Absence::class);

        $absence = $action->executer($request->validated());

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Absence enregistrée.',
                'redirect' => route('conges.absences.index'),
            ]);
        }

        return redirect()->route('conges.absences.index')
            ->with('success', 'Absence enregistrée.');
    }

    public function show(Absence $absence)
    {
        $this->authorize('view', $absence);
        $absence->load(['salarie', 'typeConge', 'transmisPaiePar', 'creePar']);
        return view('conges.absences.show', compact('absence'));
    }

    public function qualifier(Request $request, Absence $absence, QualifierAbsence $action)
    {
        $this->authorize('qualifier', $absence);

        $donnees = $request->validate([
            'qualification' => ['required', 'in:' . implode(',', array_column(QualificationAbsence::cases(), 'value'))],
            'decision' => ['nullable', 'string', 'max:2000'],
        ]);

        $action->executer($absence, QualificationAbsence::from($donnees['qualification']), $donnees['decision'] ?? null);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Absence qualifiée.']);
        }
        return back()->with('success', 'Absence qualifiée.');
    }

    public function regulariser(RegulariserAbsenceRequest $request, Absence $absence, RegulariserAbsence $action)
    {
        $this->authorize('regulariser', $absence);

        $action->executer($absence, QualificationAbsence::from($request->qualification), $request->decision);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Absence régularisée.']);
        }
        return back()->with('success', 'Absence régularisée.');
    }

    public function transmettrePaie(TransmettreAbsencesPaieRequest $request, TransmettreAbsencesPaie $action)
    {
        $this->authorize('viewAny', Absence::class);

        $count = $action->executer($request->absence_ids, $request->periode_paie);

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$count} absence(s) transmise(s) à la paie."]);
        }
        return back()->with('success', "{$count} absence(s) transmise(s).");
    }

    public function destroy(Absence $absence)
    {
        $this->authorize('delete', $absence);
        $absence->marquerSupprime();

        if (request()->expectsJson()) {
            return response()->json(['message' => 'Absence supprimée.']);
        }

        return redirect()->route('conges.absences.index')
            ->with('success', 'Absence supprimée.');
    }
}
<?php

namespace App\Http\Controllers\Administration;

use App\Domain\Administration\Models\JournalAudit;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class JournalAuditController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', JournalAudit::class);

        $entrees = JournalAudit::query()
            ->with('utilisateur')
            ->when($request->filled('utilisateur_id'), fn ($q) => $q->where('utilisateur_id', $request->utilisateur_id))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('entite'), fn ($q) => $q->where('entite', $request->entite))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('created_at', '>=', $request->du))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('created_at', '<=', $request->au))
            ->orderByDesc('created_at')
            ->paginate(100)
            ->withQueryString();

        $actions = JournalAudit::distinct()->pluck('action')->sort()->values();
        $entites = JournalAudit::distinct()->pluck('entite')->sort()->values();

        return view('administration.audit.index', compact('entrees', 'actions', 'entites'));
    }

    public function show(JournalAudit $audit)
    {
        $this->authorize('viewAny', JournalAudit::class);
        return view('administration.audit.show', ['entree' => $audit]);
    }
}
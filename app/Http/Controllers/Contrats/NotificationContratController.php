<?php

namespace App\Http\Controllers\Contrats;

use App\Domain\Contrats\Actions\MarquerNotificationLue;
use App\Domain\Contrats\Models\NotificationContrat;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationContratController extends Controller
{
    public function index(Request $request)
    {
        // Boîte de réception personnelle : ouverte à tout détenteur de contrats.view (CDC §1.2)
        abort_unless($request->user()->peut('contrats.view'), 403);

        $notifications = NotificationContrat::query()
            ->with(['alerte.contrat.salarie'])
            ->where('utilisateur_id', $request->user()->id)
            ->when($request->filled('statut'), function ($q) use ($request) {
                if ($request->statut === 'lues') $q->whereNotNull('lu_le');
                if ($request->statut === 'non_lues') $q->whereNull('lu_le');
            })
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('contrats.notifications.index', compact('notifications'));
    }

    public function marquerLue(Request $request, NotificationContrat $notification, MarquerNotificationLue $action)
    {
        // Un utilisateur ne peut marquer lue que ses propres notifications
        if ($notification->utilisateur_id !== $request->user()->id) {
            abort(403);
        }

        $action->executer($notification);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Notification marquée comme lue.']);
        }

        return back()->with('success', 'Notification marquée comme lue.');
    }

    public function marquerToutesLues(Request $request, MarquerNotificationLue $action)
    {
        $count = $action->toutesLues($request->user()->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => "{$count} notification(s) marquée(s) comme lue(s)."]);
        }

        return back()->with('success', "{$count} notification(s) marquée(s) comme lue(s).");
    }
}
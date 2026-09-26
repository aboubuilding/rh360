@extends('layouts.app')

@section('title', 'Tableau de bord')

@section('content')
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h1 class="text-2xl font-bold mb-2">Bienvenue, {{ auth()->user()->nom_complet }}</h1>
        <p class="text-gray-600">
            Vous êtes connecté en tant que
            <strong>{{ auth()->user()->libelleRole() }}</strong>
            pour l'entreprise
            <strong>{{ auth()->user()->entreprise->nom ?? '—' }}</strong>.
        </p>

        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-md border border-gray-200 p-4">
                <div class="text-sm text-gray-500">Rôle</div>
                <div class="text-lg font-semibold">{{ auth()->user()->role }}</div>
            </div>
            <div class="rounded-md border border-gray-200 p-4">
                <div class="text-sm text-gray-500">Dernière connexion</div>
                <div class="text-lg font-semibold">
                    {{ auth()->user()->derniere_connexion?->format('d/m/Y H:i') ?? '—' }}
                </div>
            </div>
            <div class="rounded-md border border-gray-200 p-4">
                <div class="text-sm text-gray-500">Nombre de permissions</div>
                <div class="text-lg font-semibold">
                    {{ count(app(\App\Domain\Administration\Services\ServicePermissions::class)->permissionsUtilisateur(auth()->user())) }}
                </div>
            </div>
        </div>
    </div>
@endsection
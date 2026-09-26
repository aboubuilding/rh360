@extends('layouts.app')

@section('title', 'Matrice des permissions')
@section('page_title', 'Matrice rôles × permissions')
@section('page_icon', 'fa-key')

@section('breadcrumb')
    <li><a href="{{ route('dashboard') }}">Accueil</a></li>
    <li>Administration</li>
    <li>Permissions</li>
@endsection

@section('contenu')
@foreach($roles as $roleCode => $roleLibelle)
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong>{{ $roleLibelle }}</strong>
            <span class="badge bg-secondary">{{ $roleCode }}</span>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.permissions.matrice.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="role" value="{{ $roleCode }}">
                <div class="row">
                    @foreach($permissions as $permission)
                        <div class="col-md-4 mb-2">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input"
                                       name="permissions[]"
                                       value="{{ $permission }}"
                                       id="perm-{{ $roleCode }}-{{ $permission }}"
                                       @checked(!empty($matrice[$roleCode][$permission]))>
                                <label class="form-check-label small" for="perm-{{ $roleCode }}-{{ $permission }}">
                                    {{ $permission }}
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>
                @can('permission', 'admin.permissions.manage')
                    <button class="btn btn-primary mt-3"><i class="fas fa-save"></i> Enregistrer</button>
                @endcan
            </form>
        </div>
    </div>
@endforeach
@endsection
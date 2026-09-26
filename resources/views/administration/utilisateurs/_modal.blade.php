<div class="modal fade" id="modal-utilisateur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-utilisateur" action="{{ route('admin.utilisateurs.store') }}">
            @csrf
            <input type="hidden" name="_method" id="method-utilisateur" value="POST">
            <input type="hidden" name="id" id="id-utilisateur">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-utilisateur">Nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <x-field name="nom_complet" label="Nom complet" required />
                        </div>
                        <div class="col-md-6">
                            <x-field name="email" label="Email" type="email" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="identifiant" label="Identifiant de connexion" required
                                     help="Unique dans l'entreprise." />
                        </div>
                        <div class="col-md-6">
                            <x-field name="role" label="Rôle" type="select" required
                                     :options="\App\Domain\Administration\Models\Utilisateur::roles()" />
                        </div>

                        <div class="col-md-6">
                            <x-field name="password" label="Mot de passe" type="password"
                                     help="Laisser vide en modification pour ne pas changer." />
                        </div>
                        <div class="col-md-6">
                            <x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" />
                        </div>

                        <div class="col-md-12">
                            <x-field name="actif" label="Compte actif" type="checkbox" :value="true" />
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
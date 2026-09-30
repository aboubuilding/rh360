<div class="modal fade" id="modal-utilisateur" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <form method="POST" id="form-utilisateur" action="{{ route('admin.utilisateurs.store') }}">
            @csrf
            <input type="hidden" name="_method" value="POST">

            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titre-modal-utilisateur">Nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6"><x-field name="nom_complet" label="Nom complet" required /></div>
                        <div class="col-md-6"><x-field name="email" label="Email" type="email" /></div>
                        <div class="col-md-6"><x-field name="identifiant" label="Identifiant de connexion" required help="Unique dans l'entreprise." /></div>
                        <div class="col-md-6"><x-field name="role" label="Rôle" type="select" required :options="\App\Domain\Administration\Models\Utilisateur::roles()" /></div>
                        <div class="col-md-6"><x-field name="password" label="Mot de passe" type="password" help="Laisser vide en modification pour ne pas changer." /></div>
                        <div class="col-md-6"><x-field name="password_confirmation" label="Confirmer le mot de passe" type="password" /></div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input type="hidden" name="actif" value="0">
                                <input type="checkbox" name="actif" value="1" class="form-check-input" checked>
                                <label class="form-check-label">Compte actif</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </div>
        </form>
    </div>
</div>
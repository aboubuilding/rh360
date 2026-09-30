<?php

namespace App\Domain\Administration\Services;

use App\Domain\Administration\Models\Utilisateur;
use Illuminate\Support\Facades\Hash;

class VerificateurMotDePasseLegacy
{
    public function verifier(Utilisateur $u, string $motDePasseClair): bool
    {
        if ($this->estFormatLegacy($u->password)) {
            if ($this->verifierPbkdf2($u->password, $motDePasseClair)) {
                $this->rehasher($u, $motDePasseClair);
                return true;
            }
            return false;
        }

        if (Hash::check($motDePasseClair, $u->password)) {
            if (Hash::needsRehash($u->password)) {
                $this->rehasher($u, $motDePasseClair);
            }
            return true;
        }

        return false;
    }

    public function estFormatLegacy(string $hash): bool
    {
        return str_starts_with($hash, 'pbkdf2_sha256$');
    }

    private function verifierPbkdf2(string $hashStocke, string $clair): bool
    {
        $parts = explode('$', $hashStocke);
        if (count($parts) !== 4) {
            return false;
        }

        [, $iterations, $salt, $hashBase64] = $parts;
        $iterations = (int) $iterations;

        $calcule = hash_pbkdf2('sha256', $clair, $salt, $iterations, 0, true);
        $calculeBase64 = base64_encode($calcule);

        return hash_equals($hashBase64, $calculeBase64);
    }

    private function rehasher(Utilisateur $u, string $clair): void
    {
        $u->forceFill(['password' => Hash::make($clair)])->save();
    }
}
<?php

namespace App\Domain\Personnel\Models;

use App\Domain\Shared\Traits\AvecEtat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSalarie extends Model
{
    use HasFactory, AvecEtat;

    protected $table = 'documents_salaries';

    protected $fillable = [
        'salarie_id', 'type_document', 'chemin_fichier',
        'date_document', 'date_expiration', 'observations',
        'actif', 'remplace_document_id', 'archive_le', 'motif_archivage',
        'etat',
    ];

    protected function casts(): array
    {
        return [
            'date_document' => 'date',
            'date_expiration' => 'date',
            'archive_le' => 'datetime',
            'actif' => 'boolean',
        ];
    }

    public function salarie(): BelongsTo
    {
        return $this->belongsTo(Salarie::class, 'salarie_id');
    }

    public function documentRemplace(): BelongsTo
    {
        return $this->belongsTo(DocumentSalarie::class, 'remplace_document_id');
    }

    public function estExpire(): bool
    {
        return $this->date_expiration && $this->date_expiration->isPast();
    }

    public function expireBientot(int $jours = 30): bool
    {
        return $this->date_expiration
            && $this->date_expiration->isFuture()
            && $this->date_expiration->diffInDays(now()) <= $jours;
    }
}
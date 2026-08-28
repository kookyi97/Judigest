<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    protected $table = 'documentos';

    protected $fillable = [
        'expediente_id',
        'nombre_original',
        'nombre_archivo',
        'ruta',
        'tipo_mime',
        'tamano',
        'subido_por',
    ];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'subido_por');
    }
}
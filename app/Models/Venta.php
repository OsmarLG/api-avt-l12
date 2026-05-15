<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;


class Venta extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'person_id',
        'aval_id',
        'predio_id',
        'estado',
        'user_id',
        'metodo_pago',
        'costo_lote',
        'enganche',
        'fecha_primer_abono',
        'meses_a_pagar',
        'id_cancelo',
        'comentario_cancelacion',
        'folio',
        "created_at",
        "updated_at",
        "saldo_venta",
        "intereses_activo",
        "intereses_porcentaje",
        "intereses_dias_tregua",
    ];

    protected $casts = [
        'costo_lote' => 'decimal:2',
        'enganche' => 'decimal:2',
        'fecha_primer_abono' => 'date',
        'meses_a_pagar' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (Venta $venta) {
            if ($venta->folio) {
                return;
            }

            $venta->folio = self::generarFolioSiguiente($venta);
        });
    }

    /**
     * Folio con prefijo de zona (dos iniciales del nombre de la zona del predio), p. ej. Los Girasoles → LG-1.
     */
    private static function generarFolioSiguiente(self $venta): string
    {
        $prefijo = self::prefijoInicialesZona($venta);
        $n = self::siguienteConsecutivoFolio($prefijo);

        if ($prefijo === '') {
            return str_pad((string) $n, 4, '0', STR_PAD_LEFT);
        }

        return $prefijo . '-' . $n;
    }

    private static function prefijoInicialesZona(self $venta): string
    {
        if (! $venta->predio_id) {
            return '';
        }

        $nombreZona = DB::table('predios')
            ->join('zones', 'zones.id', '=', 'predios.zona_id')
            ->where('predios.id', $venta->predio_id)
            ->value('zones.nombre');

        if (! $nombreZona || ! is_string($nombreZona)) {
            return '';
        }

        return self::inicialesDesdeNombreZona($nombreZona);
    }

    private static function inicialesDesdeNombreZona(string $nombreZona): string
    {
        $nombreZona = trim($nombreZona);
        $palabras = preg_split('/\s+/u', $nombreZona, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($palabras) >= 2) {
            $a = mb_substr($palabras[0], 0, 1, 'UTF-8');
            $b = mb_substr($palabras[1], 0, 1, 'UTF-8');

            return mb_strtoupper($a . $b, 'UTF-8');
        }

        if (count($palabras) === 1) {
            return mb_strtoupper(mb_substr($palabras[0], 0, 2, 'UTF-8'), 'UTF-8');
        }

        return '';
    }

    /**
     * Consecutivo numérico: por prefijo de zona si existe; si no, sigue la secuencia global por max(id) + 1.
     */
    private static function siguienteConsecutivoFolio(string $prefijo): int
    {
        if ($prefijo === '') {
            return (int) (DB::table('ventas')->max('id') ?? 0) + 1;
        }

        $patron = $prefijo . '-%';

        $maximo = Venta::query()
            ->where('folio', 'like', $patron)
            ->pluck('folio')
            ->map(fn(string $folio) => (int) Str::afterLast($folio, '-'))
            ->max();

        return ($maximo ?? 0) + 1;
    }



    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function aval(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'aval_id');
    }

    public function predio(): BelongsTo
    {
        return $this->belongsTo(Predio::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_cancelo');
    }

    public function letras(): HasMany
    {
        return $this->hasMany(Letra::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function proximaLetra(): BelongsTo
    {
        return $this->belongsTo(
            Letra::class,
            'proxima_letra_id',
            'id'
        );
    }

    public function calcularCache(): void
    {
        //este cache debe usarse en:
        //creacion de venta
        //al cancelar un pago
        //al pagar 


        $proximaLetra = $this->letras()
            ->where('estado', 'pendiente')
            ->orderBy('fecha_vencimiento')
            ->first();

        if (!$proximaLetra) {
            // No hay letras pendientes
            $this->proxima_letra_id = null;
            //$this->monto_restante_letra = 0;
            $this->saldo_venta = 0;
            $this->estado = "pagado";
            $this->save();
            return;
        }

        $this->proxima_letra_id = $proximaLetra->id;
        $this->saldo_venta = $this->letras()->where('estado', 'pendiente')->sum('saldo');
        $this->save();
    }

    public function letrasPendientes()
    {
        return $this->letras()->where('estado', 'pendiente')->get();
    }

    public function letrasVencidas()
    {
        return $this->letras()->where('estado', 'pendiente')
            ->where('fecha_vencimiento', '<', now())
            ->orderBy('fecha_vencimiento', 'asc');
    }

    public function getTotalIntereses()
    {
        return DB::table("letras_intereses")
            ->whereIn("letra_id", $this->letrasVencidas()->pluck("id"))
            ->sum("monto_neto");
    }

    public function calcularIntereses()
    {
        if ($this->intereses_activo == false) {
            return;
        }

        $letras = $this->letrasVencidas()->get();

        if ($letras->isEmpty()) {
            return;
        }

        $letras->each(function (Letra $letra) {
            $diasVencidos = $letra->fecha_vencimiento->diffInDays(now(), false);

            if ($diasVencidos > $this->intereses_dias_tregua) {
                $letra->calcularInteres();
            } else {
                $letra->intereses()->update(['monto_neto' => 0, 'monto_bruto' => 0]);
                $letra->update(['saldo' => $letra->getSaldoSinInteres()]);
            }
        });

        $this->calcularCache();
    }

    public function getSaldoSinIntereses()
    {
        return $this->saldo_venta - $this->getTotalIntereses();
    }
}

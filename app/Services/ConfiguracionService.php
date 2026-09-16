<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\HistorialConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfiguracionService
{
    /**
     * Obtiene el valor tipado de un parámetro del sistema con almacenamiento en caché.
     */
    public function get(string $clave, mixed $default = null): mixed
    {
        return Cache::rememberForever("configuracion_{$clave}", function () use ($clave, $default) {
            $config = Configuracion::where('clave', $clave)->first();
            return $config ? $config->valor_tipado : $default;
        });
    }

    /**
     * Obtiene todas las configuraciones agrupadas por categoría con metadata funcional.
     */
    public function obtenerAgrupadasPorCategoria(): array
    {
        $configs = Configuracion::with('modificadoPor:id,nombre,apellido')
            ->orderBy('id')
            ->get();

        $metaCategorias = [
            'expedientes' => [
                'id' => 'expedientes',
                'titulo' => 'Gestión de Expedientes',
                'subtitulo' => 'Codificación, alertas de inactividad procesal y asignación de personal jurídico.',
                'icono' => 'FolderOpen',
            ],
            'documentos' => [
                'id' => 'documentos',
                'titulo' => 'Gestión Documental',
                'subtitulo' => 'Políticas de almacenamiento, formatos aceptados y cadena de custodia criptográfica.',
                'icono' => 'FileText',
            ],
            'seguridad' => [
                'id' => 'seguridad',
                'titulo' => 'Seguridad y Accesos',
                'subtitulo' => 'Protección de cuentas, tiempos de sesión y robustez de credenciales.',
                'icono' => 'ShieldCheck',
            ],
            'institucional' => [
                'id' => 'institucional',
                'titulo' => 'Despacho Institucional',
                'subtitulo' => 'Datos oficiales del consultorio, correo judicial y calendario procesal hábil.',
                'icono' => 'Building2',
            ],
        ];

        $agrupadas = [];

        foreach ($metaCategorias as $catKey => $meta) {
            $items = $configs->where('categoria', $catKey)->values()->map(function ($config) {
                return [
                    'id' => $config->id,
                    'clave' => $config->clave,
                    'nombre' => $config->nombre,
                    'descripcion' => $config->descripcion,
                    'categoria' => $config->categoria,
                    'tipo' => $config->tipo,
                    'valor' => $config->valor_tipado,
                    'opciones' => $config->opciones,
                    'modificado_por' => $config->modificadoPor ? [
                        'id' => $config->modificadoPor->id,
                        'nombre_completo' => "{$config->modificadoPor->nombre} {$config->modificadoPor->apellido}",
                    ] : null,
                    'updated_at' => $config->updated_at ? $config->updated_at->toIso8601String() : null,
                ];
            });

            $agrupadas[] = array_merge($meta, [
                'parametros' => $items,
            ]);
        }

        return $agrupadas;
    }

    /**
     * Actualiza múltiples parámetros, invalida su caché y registra el historial de cambios.
     */
    public function actualizarParametros(array $nuevosValores, int $usuarioId, ?Request $request = null): array
    {
        $request ??= request();
        $cambiosRealizados = [];

        foreach ($nuevosValores as $clave => $nuevoValor) {
            $config = Configuracion::where('clave', $clave)->first();
            if (!$config) {
                continue;
            }

            // Formateamos el valor para almacenamiento según tipo
            $nuevoValorFormateado = match ($config->tipo) {
                'boolean' => $nuevoValor ? '1' : '0',
                'array' => is_array($nuevoValor) ? json_encode(array_values($nuevoValor)) : (string) $nuevoValor,
                default => trim((string) $nuevoValor),
            };

            $valorAnteriorFormateado = (string) $config->valor;

            // Verificamos si realmente cambió
            if ($nuevoValorFormateado !== $valorAnteriorFormateado) {
                // Actualizamos registro
                $config->valor = $nuevoValorFormateado;
                $config->modificado_por = $usuarioId;
                $config->save();

                // Registramos en auditoría
                HistorialConfiguracion::create([
                    'configuracion_id' => $config->id,
                    'parametro_clave' => $config->clave,
                    'parametro_nombre' => $config->nombre,
                    'categoria' => $config->categoria,
                    'valor_anterior' => $valorAnteriorFormateado,
                    'valor_nuevo' => $nuevoValorFormateado,
                    'usuario_id' => $usuarioId,
                    'ip_address' => $request->ip(),
                    'user_agent' => substr((string) $request->userAgent(), 0, 500),
                    'fecha_hora' => now(),
                ]);

                // Invalidamos la clave en la caché
                Cache::forget("configuracion_{$clave}");

                $cambiosRealizados[] = [
                    'clave' => $clave,
                    'nombre' => $config->nombre,
                    'valor_anterior' => $valorAnteriorFormateado,
                    'valor_nuevo' => $nuevoValorFormateado,
                ];
            }
        }

        return $cambiosRealizados;
    }

    /**
     * Limpia la caché de configuración.
     */
    public function limpiarCache(?string $clave = null): void
    {
        if ($clave) {
            Cache::forget("configuracion_{$clave}");
        } else {
            $claves = Configuracion::pluck('clave');
            foreach ($claves as $c) {
                Cache::forget("configuracion_{$c}");
            }
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\HistorialConfiguracion;
use App\Services\ConfiguracionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConfiguracionController extends Controller
{
    protected ConfiguracionService $configuracionService;

    public function __construct(ConfiguracionService $configuracionService)
    {
        $this->configuracionService = $configuracionService;
    }

    /**
     * Muestra la pantalla principal de configuración con los parámetros agrupados.
     */
    public function index(Request $request): Response
    {
        $categorias = $this->configuracionService->obtenerAgrupadasPorCategoria();

        $historial = HistorialConfiguracion::with('usuario:id,nombre,apellido,rol')
            ->orderBy('fecha_hora', 'desc')
            ->take(50)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'parametro_clave' => $item->parametro_clave,
                    'parametro_nombre' => $item->parametro_nombre,
                    'categoria' => $item->categoria,
                    'valor_anterior' => $item->valor_anterior,
                    'valor_nuevo' => $item->valor_nuevo,
                    'usuario' => $item->usuario ? [
                        'nombre' => "{$item->usuario->nombre} {$item->usuario->apellido}",
                        'rol' => $item->usuario->rol,
                    ] : [
                        'nombre' => 'Sistema / Automático',
                        'rol' => 'sistema',
                    ],
                    'ip_address' => $item->ip_address ?? 'N/A',
                    'fecha_hora' => $item->fecha_hora ? $item->fecha_hora->format('d/m/Y H:i:s') : 'N/A',
                    'hace_tiempo' => $item->fecha_hora ? $item->fecha_hora->diffForHumans() : 'N/A',
                ];
            });

        return Inertia::render('Configuracion/Index', [
            'categorias' => $categorias,
            'historial' => $historial,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Valida y actualiza los parámetros del sistema según su normativa.
     */
    public function update(Request $request)
    {
        $reglas = [
            // Expedientes
            'expedientes_prefijo' => [
                'required',
                'string',
                'min:2',
                'max:6',
                'regex:/^[A-Z0-9]+$/',
            ],
            'expedientes_max_practicantes' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],
            'expedientes_dias_alerta_inactividad' => [
                'required',
                'integer',
                'min:5',
                'max:60',
            ],
            'expedientes_tipos_proceso' => [
                'required',
                'array',
                'min:1',
            ],
            'expedientes_tipos_proceso.*' => [
                'string',
                'max:50',
            ],

            // Documentos
            'documentos_max_tamano_mb' => [
                'required',
                'integer',
                'min:2',
                'max:50',
            ],
            'documentos_formatos_permitidos' => [
                'required',
                'string',
                'max:255',
            ],
            'documentos_verificacion_hash_sha256' => [
                'required',
                'boolean',
            ],

            // Seguridad
            'seguridad_max_intentos_login' => [
                'required',
                'integer',
                'min:3',
                'max:10',
            ],
            'seguridad_tiempo_sesion_minutos' => [
                'required',
                'integer',
                'min:15',
                'max:240',
            ],
            'seguridad_longitud_min_password' => [
                'required',
                'integer',
                'min:6',
                'max:20',
            ],

            // Institucional
            'institucional_nombre_entidad' => [
                'required',
                'string',
                'max:150',
            ],
            'institucional_correo_notificaciones' => [
                'required',
                'email',
                'max:150',
            ],
            'institucional_telefono_contacto' => [
                'required',
                'string',
                'max:30',
            ],
            'institucional_dias_habiles' => [
                'required',
                'string',
                'in:Lunes a Viernes,Lunes a Sábado',
            ],
        ];

        $mensajes = [
            'expedientes_prefijo.required' => 'El prefijo de radicación es obligatorio.',
            'expedientes_prefijo.min' => 'El prefijo debe tener al menos 2 caracteres.',
            'expedientes_prefijo.max' => 'El prefijo no puede superar los 6 caracteres.',
            'expedientes_prefijo.regex' => 'El prefijo solo puede contener letras mayúsculas y números (sin espacios).',

            'expedientes_max_practicantes.required' => 'El límite de practicantes por expediente es obligatorio.',
            'expedientes_max_practicantes.integer' => 'El límite de practicantes debe ser un número entero.',
            'expedientes_max_practicantes.min' => 'El límite de practicantes mínimo permitido es de 1.',
            'expedientes_max_practicantes.max' => 'El límite de practicantes máximo permitido es de 5.',

            'expedientes_dias_alerta_inactividad.required' => 'Los días de inactividad para alerta procesal son obligatorios.',
            'expedientes_dias_alerta_inactividad.integer' => 'Los días de inactividad deben ser un número entero.',
            'expedientes_dias_alerta_inactividad.min' => 'El umbral mínimo de inactividad es de 5 días.',
            'expedientes_dias_alerta_inactividad.max' => 'El umbral máximo de inactividad es de 60 días.',

            'expedientes_tipos_proceso.required' => 'Debe seleccionar al menos una materia jurídica habilitada.',
            'expedientes_tipos_proceso.min' => 'Debe haber al menos 1 materia jurídica activa.',

            'documentos_max_tamano_mb.required' => 'El tamaño máximo de archivo es obligatorio.',
            'documentos_max_tamano_mb.integer' => 'El tamaño máximo de archivo debe ser un número entero.',
            'documentos_max_tamano_mb.min' => 'El tamaño mínimo configurable por documento es de 2 MB.',
            'documentos_max_tamano_mb.max' => 'El tamaño máximo permitido por la plataforma es de 50 MB.',

            'documentos_formatos_permitidos.required' => 'Debe definir los formatos de archivo autorizados.',
            'documentos_verificacion_hash_sha256.required' => 'La validación criptográfica debe estar definida.',

            'seguridad_max_intentos_login.required' => 'El número máximo de intentos fallidos es obligatorio.',
            'seguridad_max_intentos_login.integer' => 'Los intentos de acceso deben ser un número entero.',
            'seguridad_max_intentos_login.min' => 'El mínimo de intentos permitidos es 3.',
            'seguridad_max_intentos_login.max' => 'El máximo de intentos permitidos es 10.',

            'seguridad_tiempo_sesion_minutos.required' => 'El tiempo de inactividad de sesión es obligatorio.',
            'seguridad_tiempo_sesion_minutos.integer' => 'El tiempo de sesión debe ser un valor entero.',
            'seguridad_tiempo_sesion_minutos.min' => 'El tiempo mínimo de sesión es de 15 minutos.',
            'seguridad_tiempo_sesion_minutos.max' => 'El tiempo máximo de sesión es de 240 minutos (4 horas).',

            'seguridad_longitud_min_password.required' => 'La longitud mínima de contraseña es obligatoria.',
            'seguridad_longitud_min_password.integer' => 'La longitud de contraseña debe ser un número entero.',
            'seguridad_longitud_min_password.min' => 'Por normativa de seguridad, la contraseña mínima debe ser de al menos 6 caracteres.',
            'seguridad_longitud_min_password.max' => 'La longitud máxima requerible es de 20 caracteres.',

            'institucional_nombre_entidad.required' => 'El nombre de la entidad jurídica es obligatorio.',
            'institucional_correo_notificaciones.required' => 'El correo de notificaciones es obligatorio.',
            'institucional_correo_notificaciones.email' => 'El correo de notificaciones debe ser una dirección de email válida.',
            'institucional_telefono_contacto.required' => 'El teléfono de atención es obligatorio.',
            'institucional_dias_habiles.required' => 'Debe seleccionar los días hábiles procesales.',
            'institucional_dias_habiles.in' => 'Los días hábiles solo pueden ser "Lunes a Viernes" o "Lunes a Sábado".',
        ];

        // Solo validamos las claves que vengan en la petición (permite guardar categoría por categoría o todo junto)
        $reglasFiltradas = array_intersect_key($reglas, $request->all());
        $datosValidados = $request->validate($reglasFiltradas, $mensajes);

        $usuarioId = auth()->id();
        $cambios = $this->configuracionService->actualizarParametros($datosValidados, $usuarioId, $request);

        $totalCambios = count($cambios);
        $mensajeExito = $totalCambios > 0
            ? "Se actualizaron correctamente {$totalCambios} parámetro(s) del sistema y se aplicaron de forma inmediata."
            : 'No se detectaron cambios en los parámetros ingresados.';

        return redirect()->back()->with('success', $mensajeExito);
    }
}

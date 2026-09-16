<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracion;

class ConfiguracionSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            // Categoría: Expedientes y Procesos
            [
                'clave' => 'expedientes_prefijo',
                'nombre' => 'Prefijo de Radicación de Expedientes',
                'descripcion' => 'Prefijo alfanumérico para la codificación oficial de nuevos expedientes jurídicos (ej. EXP-2026-0001).',
                'categoria' => 'expedientes',
                'tipo' => 'string',
                'valor' => 'EXP',
                'opciones' => [
                    'min' => 2,
                    'max' => 6,
                    'regex' => '^[A-Z0-9]+$',
                    'ayuda' => 'Debe contener entre 2 y 6 caracteres en mayúsculas sin espacios.'
                ],
            ],
            [
                'clave' => 'expedientes_max_practicantes',
                'nombre' => 'Límite de Practicantes por Expediente',
                'descripcion' => 'Número máximo de practicantes que pueden ser asignados simultáneamente a un mismo caso.',
                'categoria' => 'expedientes',
                'tipo' => 'number',
                'valor' => '2',
                'opciones' => [
                    'min' => 1,
                    'max' => 5,
                    'step' => 1,
                    'unidad' => 'practicantes',
                    'ayuda' => 'Rango permitido: 1 a 5 practicantes.'
                ],
            ],
            [
                'clave' => 'expedientes_dias_alerta_inactividad',
                'nombre' => 'Días para Alerta Preventiva de Inactividad',
                'descripcion' => 'Días continuos sin actuaciones registradas antes de catalogar el caso con alerta procesal.',
                'categoria' => 'expedientes',
                'tipo' => 'number',
                'valor' => '15',
                'opciones' => [
                    'min' => 5,
                    'max' => 60,
                    'step' => 1,
                    'unidad' => 'días',
                    'ayuda' => 'Rango permitido: 5 a 60 días calendario.'
                ],
            ],
            [
                'clave' => 'expedientes_tipos_proceso',
                'nombre' => 'Materias Jurídicas Habilitadas',
                'descripcion' => 'Áreas o ramas del derecho permitidas para radicación de nuevos casos.',
                'categoria' => 'expedientes',
                'tipo' => 'array',
                'valor' => json_encode(['Civil', 'Penal', 'Laboral', 'Familia', 'Administrativo']),
                'opciones' => [
                    'posibles' => ['Civil', 'Penal', 'Laboral', 'Familia', 'Administrativo', 'Comercial', 'Constitucional', 'Tributario'],
                    'ayuda' => 'Seleccione al menos una materia legal habilitada.'
                ],
            ],

            // Categoría: Gestión Documental
            [
                'clave' => 'documentos_max_tamano_mb',
                'nombre' => 'Tamaño Máximo de Subida por Documento',
                'descripcion' => 'Límite de peso en Megabytes permitido para cualquier documento o anexo procesal.',
                'categoria' => 'documentos',
                'tipo' => 'number',
                'valor' => '10',
                'opciones' => [
                    'min' => 2,
                    'max' => 50,
                    'step' => 1,
                    'unidad' => 'MB',
                    'ayuda' => 'Rango normativo permitido: 2 a 50 MB.'
                ],
            ],
            [
                'clave' => 'documentos_formatos_permitidos',
                'nombre' => 'Formatos y Extensiones Autorizadas',
                'descripcion' => 'Extensiones de archivo permitidas para salvaguardar la seguridad del archivo digital.',
                'categoria' => 'documentos',
                'tipo' => 'string',
                'valor' => 'pdf,doc,docx,xls,xlsx,png,jpg,jpeg',
                'opciones' => [
                    'ayuda' => 'Formatos separados por coma (ej. pdf,doc,docx).'
                ],
            ],
            [
                'clave' => 'documentos_verificacion_hash_sha256',
                'nombre' => 'Obligatoriedad de Sellado Hash SHA-256',
                'descripcion' => 'Exigir generación y verificación de huella criptográfica SHA-256 en cada documento para cadena de custodia.',
                'categoria' => 'documentos',
                'tipo' => 'boolean',
                'valor' => '1',
                'opciones' => [
                    'ayuda' => 'Recomendado activo por normativa de seguridad jurídica.'
                ],
            ],

            // Categoría: Seguridad y Accesos
            [
                'clave' => 'seguridad_max_intentos_login',
                'nombre' => 'Intentos Fallidos de Inicio de Sesión',
                'descripcion' => 'Número de intentos incorrectos consecutivos antes del bloqueo preventivo de la cuenta de usuario.',
                'categoria' => 'seguridad',
                'tipo' => 'number',
                'valor' => '5',
                'opciones' => [
                    'min' => 3,
                    'max' => 10,
                    'step' => 1,
                    'unidad' => 'intentos',
                    'ayuda' => 'Rango permitido: 3 a 10 intentos.'
                ],
            ],
            [
                'clave' => 'seguridad_tiempo_sesion_minutos',
                'nombre' => 'Tiempo de Inactividad de Sesión',
                'descripcion' => 'Minutos de inactividad antes de cerrar la sesión automáticamente para evitar accesos no autorizados.',
                'categoria' => 'seguridad',
                'tipo' => 'number',
                'valor' => '60',
                'opciones' => [
                    'min' => 15,
                    'max' => 240,
                    'step' => 5,
                    'unidad' => 'minutos',
                    'ayuda' => 'Rango permitido: 15 a 240 minutos.'
                ],
            ],
            [
                'clave' => 'seguridad_longitud_min_password',
                'nombre' => 'Longitud Mínima de Contraseña',
                'descripcion' => 'Cantidad mínima de caracteres exigida al crear o cambiar claves de acceso.',
                'categoria' => 'seguridad',
                'tipo' => 'number',
                'valor' => '8',
                'opciones' => [
                    'min' => 6,
                    'max' => 20,
                    'step' => 1,
                    'unidad' => 'caracteres',
                    'ayuda' => 'Rango permitido: 6 a 20 caracteres.'
                ],
            ],

            // Categoría: Despacho Institucional
            [
                'clave' => 'institucional_nombre_entidad',
                'nombre' => 'Nombre del Consultorio o Entidad Jurídica',
                'descripcion' => 'Nombre oficial de la institución que encabeza las actuaciones y notificaciones.',
                'categoria' => 'institucional',
                'tipo' => 'string',
                'valor' => 'Consultorio Jurídico Judigest',
                'opciones' => [
                    'max' => 150,
                    'ayuda' => 'Máximo 150 caracteres.'
                ],
            ],
            [
                'clave' => 'institucional_correo_notificaciones',
                'nombre' => 'Correo Electrónico de Notificaciones Judiciales',
                'descripcion' => 'Buzón institucional oficial para radicación de comunicaciones procesales y notificaciones de ley.',
                'categoria' => 'institucional',
                'tipo' => 'string',
                'valor' => 'notificaciones@judigest.gob',
                'opciones' => [
                    'tipo_input' => 'email',
                    'ayuda' => 'Debe ser una dirección de correo electrónico institucional válida.'
                ],
            ],
            [
                'clave' => 'institucional_telefono_contacto',
                'nombre' => 'Línea Telefónica de Atención',
                'descripcion' => 'Teléfono o conmutador de atención ciudadana y secretarial.',
                'categoria' => 'institucional',
                'tipo' => 'string',
                'valor' => '+57 (601) 320-0000',
                'opciones' => [
                    'max' => 30,
                    'ayuda' => 'Formato telefónico con indicativo.'
                ],
            ],
            [
                'clave' => 'institucional_dias_habiles',
                'nombre' => 'Jornada Procesal de Días Hábiles',
                'descripcion' => 'Días de la semana considerados hábiles para el cómputo de términos judiciales.',
                'categoria' => 'institucional',
                'tipo' => 'string',
                'valor' => 'Lunes a Viernes',
                'opciones' => [
                    'posibles' => ['Lunes a Viernes', 'Lunes a Sábado'],
                    'ayuda' => 'Seleccione el esquema de días hábiles del despacho.'
                ],
            ],
        ];

        foreach ($parametros as $param) {
            Configuracion::updateOrCreate(
                ['clave' => $param['clave']],
                $param
            );
        }
    }
}

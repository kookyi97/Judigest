<?php

namespace App\Http\Middleware;

use App\Services\AuditoriaService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RegistrarAuditoriaMiddleware
{
    protected AuditoriaService $auditoriaService;

    public function __construct(AuditoriaService $auditoriaService)
    {
        $this->auditoriaService = $auditoriaService;
    }

    /**
     * Maneja la petición entrante y registra automáticamente la autoría de toda acción mutante.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuarioAntes = $request->user();
        $response = $next($request);
        $usuario = $request->user() ?? $usuarioAntes;

        // Solo registramos si hay usuario autenticado (antes o después del ciclo) y la petición modifica estado
        if ($usuario && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            // Evitamos doble registro si la acción ya fue auditada explícitamente en el ciclo
            if ($request->attributes->get('auditoria_registrada')) {
                return $response;
            }

            // Si la petición tuvo errores de validación de formulario, la acción no se ejecutó en el sistema
            $tieneErroresValidacion = ($request->hasSession() && $request->session()->has('errors')) || $response->getStatusCode() === 422;
            if ($tieneErroresValidacion) {
                return $response;
            }

            $ruta = $request->route() ? $request->route()->getName() : null;
            $uri = $request->path();

            [$modulo, $accion, $descripcion] = $this->deducirDetallesAccion($request, $ruta, $uri, $usuario);

            // Deducir entidad y su ID si están presentes en la ruta
            $entidadTipo = null;
            $entidadId = null;

            if ($exp = $request->route('expediente')) {
                $entidadTipo = 'Expediente';
                $entidadId = is_object($exp) ? $exp->id : (is_numeric($exp) ? (int) $exp : null);
            } elseif ($usr = $request->route('usuario')) {
                $entidadTipo = 'Usuario';
                $entidadId = is_object($usr) ? $usr->id : (is_numeric($usr) ? (int) $usr : null);
            } elseif ($doc = $request->route('documento')) {
                $entidadTipo = 'Documento';
                $entidadId = is_object($doc) ? $doc->id : (is_numeric($doc) ? (int) $doc : null);
            }

            $resultado = $response->getStatusCode() < 400 ? 'exitoso' : 'fallido';

            // Registramos con datos inmutables tomados directamente del servidor
            $this->auditoriaService->registrar(
                modulo: $modulo,
                accion: $accion,
                descripcion: $descripcion,
                entidadTipo: $entidadTipo,
                entidadId: $entidadId,
                detalles: [
                    'metodo' => $request->method(),
                    'ruta' => $ruta,
                    'uri' => $uri,
                    'status_code' => $response->getStatusCode(),
                ],
                resultado: $resultado,
                request: $request,
                usuario: $usuario
            );
        }

        return $response;
    }

    /**
     * Infiere nombres legibles de módulo, acción y descripción a partir de la ruta ejecutada.
     */
    protected function deducirDetallesAccion(Request $request, ?string $ruta, string $uri, ?\App\Models\Usuario $usuario = null): array
    {
        $u = $usuario ?? $request->user();
        $usuarioNombre = $u ? "{$u->nombre} {$u->apellido}" : 'Usuario';

        $mapaRutas = [
            'login.procesar' => ['Autenticación', 'Inicio de Sesión', "El usuario {$usuarioNombre} inició sesión en la plataforma."],
            'logout' => ['Autenticación', 'Cierre de Sesión', "El usuario {$usuarioNombre} cerró su sesión."],
            'expedientes.store' => ['Expedientes', 'Crear Expediente', "El usuario {$usuarioNombre} registró un nuevo expediente."],
            'expedientes.update' => ['Expedientes', 'Actualizar Expediente', "El usuario {$usuarioNombre} modificó los datos de un expediente."],
            'expedientes.estado' => ['Expedientes', 'Cambiar Estado de Expediente', "El usuario {$usuarioNombre} actualizó el estado de un expediente."],
            'expedientes.archivar' => ['Expedientes', 'Archivar Expediente', "El usuario {$usuarioNombre} archivó un expediente."],
            'expedientes.documentos.store' => ['Documentos', 'Cargar Documento', "El usuario {$usuarioNombre} anexó un nuevo documento procesal."],
            'documentos.eliminar' => ['Documentos', 'Eliminar Documento', "El usuario {$usuarioNombre} eliminó un documento."],
            'usuarios.store' => ['Usuarios', 'Crear Usuario', "El usuario {$usuarioNombre} dio de alta a un nuevo usuario."],
            'usuarios.update' => ['Usuarios', 'Actualizar Usuario', "El usuario {$usuarioNombre} modificó la información de un usuario."],
            'usuarios.destroy' => ['Usuarios', 'Eliminar Usuario', "El usuario {$usuarioNombre} eliminó a un usuario del sistema."],
            'admin.usuarios.updateRol' => ['Usuarios', 'Modificar Rol de Usuario', "El usuario {$usuarioNombre} cambió el rol de acceso a un usuario."],
            'admin.usuarios.resetPassword' => ['Usuarios', 'Restablecer Contraseña', "El usuario {$usuarioNombre} restableció las credenciales de un usuario."],
            'configuracion.update' => ['Configuración', 'Actualizar Parámetros Globales', "El usuario {$usuarioNombre} modificó los parámetros del sistema."],
            'logout' => ['Autenticación', 'Cierre de Sesión', "El usuario {$usuarioNombre} cerró su sesión."],
        ];

        if ($ruta && isset($mapaRutas[$ruta])) {
            return $mapaRutas[$ruta];
        }

        // Deducir por prefijo de URI si no hay coincidencia exacta de nombre de ruta
        if (str_starts_with($uri, 'expedientes')) {
            $modulo = 'Expedientes';
        } elseif (str_starts_with($uri, 'usuarios') || str_starts_with($uri, 'admin/usuarios')) {
            $modulo = 'Usuarios';
        } elseif (str_starts_with($uri, 'configuracion')) {
            $modulo = 'Configuración';
        } elseif (str_starts_with($uri, 'documentos')) {
            $modulo = 'Documentos';
        } else {
            $modulo = 'Sistema';
        }

        $metodo = $request->method();
        $accionVerbo = match ($metodo) {
            'POST' => 'Creación / Registro',
            'PUT', 'PATCH' => 'Modificación / Actualización',
            'DELETE' => 'Eliminación',
            default => 'Acción',
        };

        $accion = "{$accionVerbo} en {$modulo}";
        $descripcion = "El usuario {$usuarioNombre} ejecutó {$metodo} en {$uri}.";

        return [$modulo, $accion, $descripcion];
    }
}

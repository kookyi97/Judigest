# JUDIGEST

Sistema web de gestión judicial. Permite registrar y dar seguimiento a expedientes, programar audiencias, adjuntar documentos, notificar a los involucrados y auditar las acciones realizadas, con acceso diferenciado por rol.


## Funcionalidades

El sistema tiene cuatro roles. Todos pueden iniciar y cerrar sesión, editar su perfil, cambiar su contraseña y consultar el manual de usuario.

| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Gestionar usuarios y roles, configurar los parámetros del sistema, consultar y exportar el log de auditoría, consultar expedientes, audiencias y calendario. |
| **Secretario** | Registrar y gestionar expedientes, subir y eliminar documentos, programar y cancelar audiencias, consultar el calendario y las notificaciones. |
| **Asesor** | Revisar los casos asignados, asignar practicantes, consultar documentos e historial del expediente, ver el calendario y las notificaciones. |
| **Practicante** | Revisar sus casos asignados, subir y consultar documentos, ver el calendario de audiencias y las notificaciones. |

## Tecnologías

| Tecnología | Versión mínima | Uso |
|---|---|---|
| PHP | 8.2 | Lenguaje del servidor |
| Laravel | 12 | Framework backend |
| Vue 3 | 3.5 | Interfaz de usuario |
| Inertia.js | 3 | Conexión entre Laravel y Vue |
| CSS (Tailwind CSS) | 4 | Estilos |
| Node.js y npm (Vite) | Node 20.19 | Compilación del frontend |
| MySQL / MariaDB (XAMPP) | MariaDB 10.4 | Base de datos `sistema_jd` |
| Composer | 2 | Dependencias de PHP |

## Instalación rápida

El sistema se ejecuta de forma local desde Visual Studio Code. Esta es una versión resumida; la guía completa, con capturas y solución de errores, está en el manual técnico (ver [Anexo](#anexo-manual-técnico)).

**Requisitos:** XAMPP (con PHP 8.2 o superior), Composer, Node.js, Git y Visual Studio Code.

1. Clonar el repositorio y abrirlo en Visual Studio Code:

   ```bash
   git clone <URL_DEL_REPOSITORIO> judigest
   cd judigest
   ```

2. Instalar dependencias:

   ```bash
   composer install
   npm install
   ```

3. Iniciar **MySQL** y **Apache** desde el Panel de control de XAMPP.

4. En phpMyAdmin (`http://localhost/phpmyadmin`), crear la base `sistema_jd` e importar el archivo `sistema_jd.sql` desde la pestaña **Importar**.

5. Crear el archivo de entorno y ajustar la conexión a la base de datos:

   ```bash
   copy .env.example .env
   ```

   Editar `.env` con estos valores (ajustar `DB_PORT` si tu MySQL usa otro puerto, por ejemplo `3307`):

   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sistema_jd
   DB_USERNAME=root
   DB_PASSWORD=
   ```

6. Generar la clave de la aplicación:

   ```bash
   php artisan key:generate
   ```

7. Levantar el frontend y el sistema, cada uno en su propia terminal:

   ```bash
   npm run dev
   ```

   ```bash
   php artisan serve
   ```

8. Abrir `http://127.0.0.1:8000` en el navegador.

## Estructura del proyecto

```text
judigest/
|-- app/                Controladores, middleware, modelos, servicios y reglas
|-- bootstrap/          Arranque de la aplicación
|-- config/             Configuración de Laravel
|-- database/           Migraciones y seeders
|-- public/             Única carpeta expuesta al web
|-- resources/js/       Interfaz Vue (páginas, layouts y componentes)
|-- routes/web.php      Rutas y su protección por rol
|-- storage/            Documentos subidos y manuales en PDF
|-- tests/              Pruebas automatizadas
`-- sistema_jd.sql      Exportación de la base de datos
```

## Seguridad

- Contraseñas almacenadas con hash bcrypt y política de contraseña segura.
- Bloqueo temporal de la cuenta tras intentos fallidos de inicio de sesión.
- Control de acceso por rol en todas las rutas protegidas.
- Protección CSRF y validación de entradas en el servidor.
- Perfil de usuario protegido contra acceso a cuentas ajenas.
- Log de auditoría de las acciones y de los intentos no autorizados.
- Expiración de sesión configurable.
- Documentos guardados fuera de la carpeta pública, con verificación de integridad (SHA-256).

El detalle de cada control y cómo verificarlo está en el manual técnico (ver [Anexo](#anexo-manual-técnico)).

## Equipo

Desarrollado por el **Equipo de JUDIGEST**.

Mayerlin Yisel Aguilar Cruz                  
Brayan Audiel Chavarría Romero                
Esmeralda Isabel Cruz Roldán             
Marleny Jamileth Martínez Méndez               
Marielena Velásquez Escobar                 
Ariel Esaú Yanes Quintanilla              

## Anexo: Manual técnico              

Manual técnico de JUDIGEST (instalación, arquitectura, base de datos, seguridad, respaldos y errores comunes).

Enlace: https://me-l.co/az8v0n6j

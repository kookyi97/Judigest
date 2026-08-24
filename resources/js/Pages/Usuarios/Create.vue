<script setup>
import { ref, computed, watch } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { User, Mail, Shield, Activity, Save, X, AlertTriangle, Lock } from 'lucide-vue-next';

const roles = ['Administrador', 'Secretario', 'Asesor', 'Practicante'];

const form = useForm({
    nombre_usuario: '',
    nombre: '',
    apellido: '',
    correo: '',
    contrasena: '',
    contrasena_confirmation: '',
    rol: 'Practicante',
    activo: true,
});

// Autogenerar correo basado en nombre y apellido
watch([() => form.nombre, () => form.apellido], ([nuevoNombre, nuevoApellido]) => {
    let base = '';
    if (nuevoNombre) base += nuevoNombre.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
    if (nuevoApellido) {
        base += nuevoApellido.split(' ')[0].toLowerCase().replace(/[^a-z0-9]/g, '');
    }
    
    if (base) {
        form.correo = `${base}@judigest.com`;
    } else {
        form.correo = '';
    }
});

const submit = () => {
    form.post('/usuarios', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
  <Head title="Nuevo Usuario" />
  <AppLayout>
    <div class="db">
      <!-- Cabecera -->
      <div class="db__header">
        <div>
          <h1 class="db__titulo">Registrar Nuevo Usuario</h1>
          <p class="db__sub">Completa el formulario para agregar un usuario al sistema.</p>
        </div>
      </div>

      <div class="inferior">
        <!-- Panel Principal del Formulario -->
        <div class="panel">
          <div class="panel__head">
            <h2 class="panel__titulo">Información del Usuario</h2>
          </div>

          <form class="formulario" @submit.prevent="submit" novalidate>
            <!-- Campo Nombre de Usuario -->
            <div class="campo" :class="{'has-error': form.errors.nombre_usuario}">
              <label class="campo__label">Nombre de Usuario</label>
              <div class="input-grupo">
                <User class="input-ico" />
                <input type="text" class="input-text" v-model="form.nombre_usuario" placeholder="Ej. administrador123" />
              </div>
              <span class="error-msg" v-if="form.errors.nombre_usuario">{{ form.errors.nombre_usuario }}</span>
            </div>

            <div class="campos-fila">
              <!-- Campo Nombre -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.nombre}">
                <label class="campo__label">Nombre</label>
                <div class="input-grupo">
                  <User class="input-ico" />
                  <input type="text" class="input-text" v-model="form.nombre" placeholder="Nombre(s)" />
                </div>
                <span class="error-msg" v-if="form.errors.nombre">{{ form.errors.nombre }}</span>
              </div>

              <!-- Campo Apellido -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.apellido}">
                <label class="campo__label">Apellido</label>
                <div class="input-grupo">
                  <User class="input-ico" />
                  <input type="text" class="input-text" v-model="form.apellido" placeholder="Apellido(s)" />
                </div>
                <span class="error-msg" v-if="form.errors.apellido">{{ form.errors.apellido }}</span>
              </div>
            </div>

            <!-- Campo Correo (Autogenerado) -->
            <div class="campo" :class="{'has-error': form.errors.correo}">
              <label class="campo__label">Correo Institucional (Generado automáticamente)</label>
              <div class="input-grupo">
                <Mail class="input-ico" />
                <input type="email" class="input-text bg-gray" v-model="form.correo" readonly placeholder="nombreapellido@judigest.com" />
              </div>
              <span class="error-msg" v-if="form.errors.correo">{{ form.errors.correo }}</span>
            </div>

            <div class="campos-fila">
              <!-- Campo Rol -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.rol}">
                <label class="campo__label">Rol del Sistema</label>
                <div class="input-grupo">
                  <Shield class="input-ico" />
                  <select class="input-select" v-model="form.rol">
                    <option v-for="rol in roles" :key="rol" :value="rol.toLowerCase()">
                      {{ rol }}
                    </option>
                  </select>
                </div>
                <span class="error-msg" v-if="form.errors.rol">{{ form.errors.rol }}</span>
              </div>

              <!-- Campo Estado -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.activo}">
                <label class="campo__label">Estado de la Cuenta</label>
                <div class="input-grupo">
                  <Activity class="input-ico" />
                  <select class="input-select" v-model="form.activo">
                    <option :value="true">Activo</option>
                    <option :value="false">Inactivo</option>
                  </select>
                </div>
                <span class="error-msg" v-if="form.errors.activo">{{ form.errors.activo }}</span>
              </div>
            </div>

            <div class="campos-fila">
              <!-- Campo Contraseña -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.contrasena}">
                <label class="campo__label">Contraseña</label>
                <div class="input-grupo">
                  <Lock class="input-ico" />
                  <input type="password" class="input-text" v-model="form.contrasena" placeholder="••••••••" />
                </div>
                <span class="error-msg" v-if="form.errors.contrasena">{{ form.errors.contrasena }}</span>
              </div>

              <!-- Campo Confirmar Contraseña -->
              <div class="campo campo--mitad" :class="{'has-error': form.errors.contrasena_confirmation}">
                <label class="campo__label">Confirmar Contraseña</label>
                <div class="input-grupo">
                  <Lock class="input-ico" />
                  <input type="password" class="input-text" v-model="form.contrasena_confirmation" placeholder="••••••••" />
                </div>
                <span class="error-msg" v-if="form.errors.contrasena_confirmation">{{ form.errors.contrasena_confirmation }}</span>
              </div>
            </div>

            <!-- Alerta general de error (Opcional) -->
            <div class="alerta alerta--error mt-4" v-if="Object.keys(form.errors).length > 0">
              <AlertTriangle class="alerta__ico" /><span>Hay errores en el formulario. Por favor, revísalos.</span>
            </div>

            <hr class="divider" />

            <!-- Botones de Acción -->
            <div class="formulario__acciones">
              <a href="/usuarios" class="btn-sec">
                <X class="btn-sec__ico" /> Cancelar
              </a>
              <button type="submit" class="btn" :disabled="form.processing" :class="{'opacity-50 cursor-not-allowed': form.processing}">
                <Save class="btn__ico" /> Registrar Usuario
              </button>
            </div>
          </form>
        </div>

        <!-- Panel Lateral Informativo -->
        <div class="panel panel--chico panel-info">
          <div class="panel__head">
            <h2 class="panel__titulo">Directrices</h2>
          </div>
          <div class="info-lista">
            <div class="info-item">
              <span class="info-label">Formato de Correo</span>
              <span class="info-valor">Se autogenera como: nombreapellido@judigest.com</span>
            </div>
            <div class="info-item">
              <span class="info-label">Contraseña</span>
              <span class="info-valor">Debe tener al menos 8 caracteres de longitud.</span>
            </div>
          </div>
          <hr class="divider" />
          <div class="alerta alerta--info">
            <AlertTriangle class="alerta__ico" />
            <span style="font-size: 11px;">Asegúrate de asignar el rol correcto, ya que define a qué módulos tendrá acceso el usuario.</span>
          </div>
        </div>

      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* ── Estilos base heredados del diseño actual ── */
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub    { font-size:13px; color:#64748B; margin:0; }

.inferior { display:grid; grid-template-columns:1fr 280px; gap:16px; align-items:start; }

/* ── Paneles ── */
.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel--chico { padding:20px; }
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; }
.panel__titulo { font-size:15px; font-weight:600; color:#1E293B; margin:0; }
.divider { border:0; border-top:1px solid #E2E8F0; margin:20px 0; }

/* ── Formulario y Campos ── */
.formulario { display:flex; flex-direction:column; gap:16px; }
.campos-fila { display:flex; gap:16px; }
.campo { display:flex; flex-direction:column; gap:6px; flex: 1; }
.campo--mitad { width: 50%; }

.campo__label { font-size:13px; font-weight:500; color:#475569; }
.error-msg { font-size:11px; color:#ef4444; margin-top:2px; font-weight: 500; }
.has-error .input-text, .has-error .input-select { border-color: #ef4444; }

.input-grupo { position: relative; display: flex; align-items: center; }
.input-ico { position: absolute; left: 12px; width: 16px; height: 16px; color: #94A3B8; }
.bg-gray { background-color: #F8FAFC !important; color: #64748B !important; cursor: not-allowed; }

.input-text, .input-select {
  width: 100%;
  padding: 10px 12px 10px 38px;
  border: 1px solid #CBD5E1;
  border-radius: 8px;
  font-size: 13px;
  font-family: inherit;
  color: #1E293B;
  background-color: #fff;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}

.input-text:focus, .input-select:focus {
  outline: none;
  border-color: #185FA5;
  box-shadow: 0 0 0 3px #EFF6FF;
}
.has-error .input-text:focus, .has-error .input-select:focus {
  box-shadow: 0 0 0 3px #FEE2E2;
}

.input-select {
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748B'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 14px;
}

/* ── Botones ── */
.formulario__acciones { display:flex; justify-content:flex-end; gap:12px; }

.btn { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 18px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; transition:background .15s; border:none; cursor:pointer; font-family:inherit; }
.btn:hover:not(:disabled) { background:#144d87; }
.btn__ico { width:16px; height:16px; }

.btn-sec { display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:10px 18px; background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; font-size:13px; font-weight:500; color:#475569; cursor:pointer; font-family:inherit; transition:background .15s; text-decoration: none; }
.btn-sec:hover { background:#F1F5F9; color:#1E293B; }
.btn-sec__ico { width:16px; height:16px; }

.opacity-50 { opacity: 0.5; }
.cursor-not-allowed { cursor: not-allowed; }

/* ── Panel Info Lateral ── */
.info-lista { display:flex; flex-direction:column; gap:12px; }
.info-item { display:flex; flex-direction:column; gap:2px; }
.info-label { font-size:11px; color:#64748B; font-weight:500; text-transform:uppercase; letter-spacing:0.02em; }
.info-valor { font-size:13px; color:#1E293B; font-weight:500; }

/* ── Alertas (Para validación visual) ── */
.alerta { display:flex; align-items:flex-start; gap:8px; padding:10px 12px; border-radius:8px; font-size:12px; border-left:3px solid currentColor; line-height:1.4; }
.alerta--info    { background:#DBEAFE; color:#1E40AF; }
.alerta--error   { background:#FEE2E2; color:#991B1B; }
.alerta__ico { width:16px; height:16px; flex-shrink:0; margin-top:2px; }
.mt-4 { margin-top: 16px; }

/* ── Responsive ── */
@media (max-width:1100px) {
  .inferior { grid-template-columns:1fr; }
  .panel-info { order: -1; }
}
@media (max-width:640px) {
  .campos-fila { flex-direction: column; }
  .campo--mitad { width: 100%; }
  .formulario__acciones { flex-direction: column-reverse; }
  .btn, .btn-sec { width: 100%; }
}
</style>

<script setup>
import { computed } from 'vue';
import { useForm, usePage, Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { User, Mail, Phone, Shield, Activity, Clock, Briefcase, Lock, Save, CheckCircle2, AlertTriangle, Check, X } from 'lucide-vue-next';

const props = defineProps({
  perfil: { type: Object, required: true },
  longitudMinPass: { type: Number, default: 8 },
});

const page = usePage();
const flashExito = computed(() => page.props.flash?.exito);

const etiquetaRol = computed(() => props.perfil.rol.charAt(0).toUpperCase() + props.perfil.rol.slice(1));

/* ── JD049: datos editables ─────────────────────────────────────────── */
const formPerfil = useForm({
  nombre: props.perfil.nombre,
  apellido: props.perfil.apellido,
  telefono: props.perfil.telefono ?? '',
});

const guardarPerfil = () => {
  // Solo se envían los 3 campos editables; rol, estado y correo nunca salen del navegador.
  formPerfil.put('/perfil', { preserveScroll: true });
};

/* ── JD050: cambio de contraseña ────────────────────────────────────── */
const formPass = useForm({
  password_actual: '',
  password_nueva: '',
  password_nueva_confirmation: '',
});

const cambiarPassword = () => {
  formPass.put('/perfil/password', {
    preserveScroll: true,
    onSuccess: () => formPass.reset(),
    onError: () => formPass.reset('password_actual'),
  });
};

// Ayuda visual en vivo. La validación real y definitiva la hace el servidor.
const requisitos = computed(() => {
  const p = formPass.password_nueva;
  return [
    { ok: p.length >= props.longitudMinPass, texto: `Mínimo ${props.longitudMinPass} caracteres (ideal 12)` },
    { ok: /\p{Lu}/u.test(p), texto: 'Una mayúscula' },
    { ok: /\p{Ll}/u.test(p), texto: 'Una minúscula' },
    { ok: /\d/.test(p), texto: 'Un número' },
    { ok: /[^\p{L}\p{N}\s]/u.test(p), texto: 'Un símbolo' },
  ];
});
</script>

<template>
  <Head title="Mi perfil" />
  <AppLayout>
    <div class="db">
      <div class="db__header">
        <div>
          <h1 class="db__titulo">Mi perfil</h1>
          <p class="db__sub">Consulta tus datos de cuenta, actualiza tu información de contacto y protege tu acceso.</p>
        </div>
      </div>

      <div v-if="flashExito" class="alerta alerta--ok"><CheckCircle2 class="alerta__ico" /><span>{{ flashExito }}</span></div>

      <div class="grid">
        <!-- ── Datos de la cuenta ── -->
        <section class="panel">
          <h2 class="panel__titulo">Información personal</h2>

          <form class="formulario" @submit.prevent="guardarPerfil" novalidate>
            <div class="alerta alerta--error" v-if="formPerfil.errors.general">
              <AlertTriangle class="alerta__ico" /><span>{{ formPerfil.errors.general }}</span>
            </div>

            <div class="campos-fila">
              <div class="campo" :class="{ 'has-error': formPerfil.errors.nombre }">
                <label class="campo__label" for="nombre">Nombre</label>
                <div class="input-grupo">
                  <User class="input-ico" />
                  <input id="nombre" type="text" class="input-text" v-model="formPerfil.nombre" maxlength="100" autocomplete="given-name" />
                </div>
                <span class="error-msg" v-if="formPerfil.errors.nombre">{{ formPerfil.errors.nombre }}</span>
              </div>

              <div class="campo" :class="{ 'has-error': formPerfil.errors.apellido }">
                <label class="campo__label" for="apellido">Apellido</label>
                <div class="input-grupo">
                  <User class="input-ico" />
                  <input id="apellido" type="text" class="input-text" v-model="formPerfil.apellido" maxlength="100" autocomplete="family-name" />
                </div>
                <span class="error-msg" v-if="formPerfil.errors.apellido">{{ formPerfil.errors.apellido }}</span>
              </div>
            </div>

            <div class="campo" :class="{ 'has-error': formPerfil.errors.telefono }">
              <label class="campo__label" for="telefono">Teléfono de contacto</label>
              <div class="input-grupo">
                <Phone class="input-ico" />
                <input id="telefono" type="tel" class="input-text" v-model="formPerfil.telefono" maxlength="20" autocomplete="tel" placeholder="Ej. 7123-4567" />
              </div>
              <span class="error-msg" v-if="formPerfil.errors.telefono">{{ formPerfil.errors.telefono }}</span>
            </div>

            <hr class="divider" />
            <p class="nota">Datos de solo lectura. Solo un Administrador puede modificarlos.</p>

            <div class="campos-fila">
              <div class="campo">
                <label class="campo__label">Nombre de usuario</label>
                <div class="input-grupo"><User class="input-ico" /><input class="input-text" :value="perfil.nombre_usuario || '—'" disabled /></div>
              </div>
              <div class="campo">
                <label class="campo__label">Correo institucional</label>
                <div class="input-grupo"><Mail class="input-ico" /><input class="input-text" :value="perfil.correo" disabled /></div>
              </div>
            </div>

            <div class="campos-fila">
              <div class="campo">
                <label class="campo__label">Rol</label>
                <div class="input-grupo"><Shield class="input-ico" /><input class="input-text" :value="etiquetaRol" disabled /></div>
              </div>
              <div class="campo">
                <label class="campo__label">Estado</label>
                <div class="input-grupo"><Activity class="input-ico" /><input class="input-text" :value="perfil.estado" disabled /></div>
              </div>
            </div>

            <div class="campos-fila">
              <div class="campo">
                <label class="campo__label">Último acceso</label>
                <div class="input-grupo"><Clock class="input-ico" /><input class="input-text" :value="perfil.ultimo_acceso || 'Sin registro'" disabled /></div>
              </div>
              <div class="campo" v-if="perfil.asesores !== null">
                <label class="campo__label">Asesor asignado</label>
                <div class="input-grupo">
                  <Briefcase class="input-ico" />
                  <input class="input-text" :value="perfil.asesores.length ? perfil.asesores.join(', ') : 'Aún sin asesor asignado'" disabled />
                </div>
              </div>
            </div>

            <div class="formulario__acciones">
              <button type="submit" class="btn" :disabled="formPerfil.processing || !formPerfil.isDirty">
                <Save class="btn__ico" /> Guardar cambios
              </button>
            </div>
          </form>
        </section>

        <!-- ── Seguridad ── -->
        <section class="panel">
          <h2 class="panel__titulo">Seguridad · Cambiar contraseña</h2>

          <form class="formulario" @submit.prevent="cambiarPassword" novalidate autocomplete="off">
            <div class="alerta alerta--error" v-if="formPass.errors.general">
              <AlertTriangle class="alerta__ico" /><span>{{ formPass.errors.general }}</span>
            </div>

            <div class="campo" :class="{ 'has-error': formPass.errors.password_actual }">
              <label class="campo__label" for="pa">Contraseña actual</label>
              <div class="input-grupo">
                <Lock class="input-ico" />
                <input id="pa" type="password" class="input-text" v-model="formPass.password_actual" autocomplete="current-password" maxlength="255" />
              </div>
              <span class="error-msg" v-if="formPass.errors.password_actual">{{ formPass.errors.password_actual }}</span>
            </div>

            <div class="campo" :class="{ 'has-error': formPass.errors.password_nueva }">
              <label class="campo__label" for="pn">Nueva contraseña</label>
              <div class="input-grupo">
                <Lock class="input-ico" />
                <input id="pn" type="password" class="input-text" v-model="formPass.password_nueva" autocomplete="new-password" maxlength="72" />
              </div>
              <span class="error-msg" v-if="formPass.errors.password_nueva">{{ formPass.errors.password_nueva }}</span>
            </div>

            <ul class="requisitos">
              <li v-for="r in requisitos" :key="r.texto" :class="{ ok: r.ok }">
                <component :is="r.ok ? Check : X" class="req-ico" /> {{ r.texto }}
              </li>
            </ul>

            <div class="campo">
              <label class="campo__label" for="pc">Confirmar nueva contraseña</label>
              <div class="input-grupo">
                <Lock class="input-ico" />
                <input id="pc" type="password" class="input-text" v-model="formPass.password_nueva_confirmation" autocomplete="new-password" maxlength="72" />
              </div>
            </div>

            <p class="nota">Al cambiarla se cerrarán tus otras sesiones activas. No puede contener tu correo ni tu nombre de usuario.</p>

            <div class="formulario__acciones">
              <button type="submit" class="btn" :disabled="formPass.processing">
                <Lock class="btn__ico" /> Cambiar contraseña
              </button>
            </div>
          </form>
        </section>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }
.db__titulo { font-size:22px; font-weight:600; margin:0 0 4px; }
.db__sub { font-size:13px; color:#64748B; margin:0; }

.grid { display:grid; grid-template-columns:3fr 2fr; gap:16px; align-items:start; }

.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel__titulo { font-size:15px; font-weight:600; margin:0 0 20px; }
.divider { border:0; border-top:1px solid #E2E8F0; margin:8px 0; }
.nota { font-size:12px; color:#64748B; margin:0; }

.formulario { display:flex; flex-direction:column; gap:16px; }
.campos-fila { display:flex; gap:16px; }
.campo { display:flex; flex-direction:column; gap:6px; flex:1; min-width:0; }
.campo__label { font-size:13px; font-weight:500; color:#475569; }

.input-grupo { position:relative; display:flex; align-items:center; }
.input-ico { position:absolute; left:12px; width:16px; height:16px; color:#94A3B8; }
.input-text { width:100%; padding:10px 12px 10px 38px; border:1px solid #CBD5E1; border-radius:8px; font-size:13px; font-family:inherit; color:#1E293B; background:#fff; box-sizing:border-box; transition:border-color .15s, box-shadow .15s; }
.input-text:focus { outline:none; border-color:#185FA5; box-shadow:0 0 0 3px #EFF6FF; }
.input-text:disabled { background:#F1F5F9; color:#64748B; cursor:not-allowed; }
.has-error .input-text { border-color:#ef4444; }
.error-msg { font-size:11px; color:#ef4444; font-weight:500; white-space:pre-line; }

.requisitos { list-style:none; margin:0; padding:0; display:grid; grid-template-columns:1fr 1fr; gap:4px 12px; font-size:12px; color:#94A3B8; }
.requisitos li { display:flex; align-items:center; gap:6px; }
.requisitos li.ok { color:#15803D; }
.req-ico { width:13px; height:13px; }

.formulario__acciones { display:flex; justify-content:flex-end; }
.btn { display:inline-flex; align-items:center; gap:6px; padding:10px 18px; background:#185FA5; color:#fff; border:none; border-radius:8px; font-size:13px; font-weight:500; cursor:pointer; font-family:inherit; transition:background .15s; }
.btn:hover:not(:disabled) { background:#144d87; }
.btn:disabled { opacity:.5; cursor:not-allowed; }
.btn__ico { width:16px; height:16px; }

.alerta { display:flex; align-items:flex-start; gap:8px; padding:10px 12px; border-radius:8px; font-size:12px; line-height:1.4; border-left:3px solid currentColor; }
.alerta--ok { background:#DCFCE7; color:#166534; }
.alerta--error { background:#FEE2E2; color:#991B1B; }
.alerta__ico { width:16px; height:16px; flex-shrink:0; margin-top:1px; }

@media (max-width:1100px) { .grid { grid-template-columns:1fr; } }
@media (max-width:640px) {
  .campos-fila { flex-direction:column; }
  .requisitos { grid-template-columns:1fr; }
  .formulario__acciones .btn { width:100%; justify-content:center; }
  .panel { padding:18px; }
}
</style>
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Users, Plus, Edit, Trash2, CheckCircle, AlertTriangle, Key } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps({
  usuarios: {
    type: Array,
    default: () => []
  }
});

const getRoleColor = (rol) => {
  const roles = { 
    administrador: '#185FA5', 
    secretario: '#16A34A', 
    asesor: '#D97706', 
    practicante: '#9333EA' 
  };
  return roles[rol?.toLowerCase()] || '#94A3B8';
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const d = new Date(dateString);
    return d.toLocaleString('es-ES', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const page = usePage();
const showPasswordModal = ref(false);
const usuarioSeleccionado = ref(null);
const nuevaContrasenaManual = ref('');
const errorContrasena = ref('');

const abrirModalContrasena = (usuario) => {
  usuarioSeleccionado.value = usuario;
  nuevaContrasenaManual.value = '';
  errorContrasena.value = '';
  showPasswordModal.value = true;
};

const guardarNuevaContrasena = () => {
  if (nuevaContrasenaManual.value.length < 8) {
    errorContrasena.value = 'La contraseña debe tener al menos 8 caracteres.';
    return;
  }
  
  router.post(`/admin/usuarios/${usuarioSeleccionado.value.id}/reset-password`, {
    contrasena: nuevaContrasenaManual.value
  }, { 
    preserveScroll: true,
    onSuccess: () => {
      showPasswordModal.value = false;
      usuarioSeleccionado.value = null;
      alert('La contraseña se ha restablecido y guardado exitosamente.');
    }
  });
};
</script>

<template>
  <Head title="Gestión de Usuarios" />
  <AppLayout>
    <div class="db">
      <!-- Cabecera -->
      <div class="db__header">
        <div>
          <h1 class="db__titulo">Gestión de Usuarios</h1>
          <p class="db__sub">Administra las cuentas y accesos del sistema</p>
        </div>
        <div class="db__accesos">
          <Link href="/usuarios/create" class="btn"><Plus class="btn__ico"/> Nuevo Usuario</Link>
        </div>
      </div>

      <!-- Contenido Principal -->
      <div class="panel">
        <div class="panel__head">
          <h2 class="panel__titulo">Todos los usuarios registrados</h2>
        </div>

        <!-- Tabla -->
        <div class="table-container" v-if="usuarios.length > 0">
          <table class="table">
            <thead>
              <tr>
                <th>Usuario</th>
                <th>Correo Electrónico</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Última Modificación</th>
                <th class="text-right">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="usuario in usuarios" :key="usuario.id">
                <td>
                  <div class="user-cell">
                    <div class="user-avatar"><Users class="user-avatar__ico" /></div>
                    <div>
                      <div class="user-name">{{ usuario.nombre }} {{ usuario.apellido }}</div>
                      <div style="font-size: 11px; color: #64748B;">@{{ usuario.nombre_usuario }}</div>
                    </div>
                  </div>
                </td>
                <td>{{ usuario.correo }}</td>
                <td>
                  <span class="role-badge" :style="{ backgroundColor: getRoleColor(usuario.rol) + '1A', color: getRoleColor(usuario.rol) }">
                    <span class="role-dot" :style="{ backgroundColor: getRoleColor(usuario.rol) }"></span>
                    {{ usuario.rol.charAt(0).toUpperCase() + usuario.rol.slice(1) }}
                  </span>
                </td>
                <td>
                  <span class="status-badge" :class="usuario.activo ? 'status-active' : 'status-inactive'">
                    <CheckCircle v-if="usuario.activo" class="status-ico" />
                    <AlertTriangle v-else class="status-ico" />
                    {{ usuario.activo ? 'Activo' : 'Inactivo' }}
                  </span>
                </td>
                <td>
                  <div style="font-size: 12px; color: #1E293B;">{{ formatDate(usuario.updated_at) }}</div>
                  <div style="font-size: 11px; color: #64748B;">por {{ usuario.modificador ? (usuario.modificador.nombre + ' ' + usuario.modificador.apellido) : 'Sistema' }}</div>
                </td>
                <td class="text-right">
                  <div class="actions">
                    <button @click="abrirModalContrasena(usuario)" class="action-btn action-key" title="Asignar Nueva Contraseña">
                      <Key class="action-ico" />
                    </button>
                    <Link :href="`/usuarios/${usuario.id}/edit`" class="action-btn action-edit" title="Editar">
                      <Edit class="action-ico" />
                    </Link>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Estado Vacío -->
        <div class="empty-state" v-else>
          <div class="empty-state__icon">
            <Users class="empty-state__svg" />
          </div>
          <h3 class="empty-state__title">No hay usuarios registrados</h3>
          <p class="empty-state__desc">Aún no se han agregado usuarios al sistema.</p>
        </div>
      </div>
    </div>

    <!-- Modal de Asignar Contraseña -->
    <div v-if="showPasswordModal" class="modal-overlay">
      <div class="modal">
        <h3 class="modal-title">Asignar Nueva Contraseña</h3>
        <p class="modal-desc">Ingresa la nueva contraseña para <strong>{{ usuarioSeleccionado?.nombre }} {{ usuarioSeleccionado?.apellido }}</strong>.</p>
        
        <div class="form-group" style="margin-bottom: 20px;">
          <label style="display: block; font-size: 13px; font-weight: 500; color: #475569; margin-bottom: 6px;">Nueva Contraseña</label>
          <input type="text" v-model="nuevaContrasenaManual" class="input-form" placeholder="Escribe la nueva contraseña..." />
          <p v-if="errorContrasena" style="color: #EF4444; font-size: 12px; margin-top: 6px; margin-bottom: 0;">{{ errorContrasena }}</p>
        </div>

        <div class="modal-actions">
          <button @click="showPasswordModal = false" class="btn btn-outline">Cancelar</button>
          <button @click="guardarNuevaContrasena" class="btn">Guardar Contraseña</button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
/* ── Reset y base ── */
.db { font-family:'Poppins','Inter',sans-serif; color:#1E293B; display:flex; flex-direction:column; gap:22px; width:100%; box-sizing:border-box; }

/* ── Cabecera ── */
.db__header { display:flex; align-items:flex-start; justify-content:space-between; flex-wrap:wrap; gap:12px; }
.db__titulo { font-size:22px; font-weight:600; color:#1E293B; margin:0 0 4px; }
.db__sub    { font-size:13px; color:#64748B; margin:0; }
.db__accesos{ display:flex; gap:8px; flex-wrap:wrap; }

/* ── Botones ── */
.btn { display:inline-flex; align-items:center; gap:6px; padding:8px 16px; background:#185FA5; color:#fff; border-radius:8px; font-size:13px; font-weight:500; text-decoration:none; white-space:nowrap; transition:background .15s; border:none; cursor:pointer; font-family:'Poppins',sans-serif; }
.btn:hover { background:#144d87; }
.btn__ico { width:16px; height:16px; }

/* ── Panel ── */
.panel { background:#fff; border:1px solid #E2E8F0; border-radius:12px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,.05); min-width:0; }
.panel__head { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.panel__titulo { font-size:16px; font-weight:600; color:#1E293B; margin:0; }

/* ── Tabla ── */
.table-container { overflow-x: auto; margin: -20px; margin-top: 0; padding: 20px; padding-top: 0; }
.table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
.table th { font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; padding: 12px 16px; border-bottom: 1px solid #E2E8F0; background: #F8FAFC; }
.table td { padding: 16px; font-size: 13px; color: #334155; border-bottom: 1px solid #F1F5F9; vertical-align: middle; }
.table tr:last-child td { border-bottom: none; }
.table tr:hover td { background-color: #F8FAFC; }
.text-right { text-align: right; }

/* ── Celdas de usuario ── */
.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #EFF6FF; color: #185FA5; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.user-avatar__ico { width: 18px; height: 18px; }
.user-name { font-weight: 600; color: #1E293B; }

/* ── Badges ── */
.role-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.role-dot { width: 6px; height: 6px; border-radius: 50%; }

.status-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.status-active { background: #DCFCE7; color: #166534; border: 1px solid #BBF7D0; }
.status-inactive { background: #F1F5F9; color: #475569; border: 1px solid #E2E8F0; }
.status-ico { width: 12px; height: 12px; }

/* ── Acciones ── */
.actions { display: inline-flex; gap: 8px; justify-content: flex-end; }
.action-btn { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; background: transparent; border: 1px solid #E2E8F0; color: #64748B; cursor: pointer; transition: all 0.2s; }
.action-btn:hover { background: #F8FAFC; color: #1E293B; }
.action-edit:hover { color: #185FA5; border-color: #185FA5; background: #EFF6FF; }
.action-key:hover { color: #D97706; border-color: #D97706; background: #FEF3C7; }
.action-ico { width: 16px; height: 16px; }

/* ── Empty State ── */
.empty-state { text-align: center; padding: 60px 20px; background: #F8FAFC; border-radius: 12px; border: 1px dashed #CBD5E1; }
.empty-state__icon { width: 64px; height: 64px; margin: 0 auto 16px; background: #EFF6FF; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #185FA5; }
.empty-state__svg { width: 32px; height: 32px; }
.empty-state__title { font-size: 18px; font-weight: 600; color: #1E293B; margin: 0 0 8px; }
.empty-state__desc { font-size: 14px; color: #64748B; margin: 0; }

/* ── Modal ── */
.modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15,23,42,0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal { background: #fff; padding: 24px; border-radius: 12px; width: 100%; max-width: 420px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); }
.modal-title { margin: 0 0 10px; font-size: 18px; color: #1E293B; font-weight: 600; }
.modal-desc { margin: 0 0 20px; font-size: 14px; color: #64748B; line-height: 1.5; }
.password-box { display: flex; justify-content: space-between; align-items: center; background: #F1F5F9; padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; border: 1px dashed #CBD5E1; }
.password-box code { font-size: 18px; font-weight: 700; color: #0F172A; letter-spacing: 2px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 12px; }
.btn-sm { padding: 6px 12px; font-size: 13px; border-radius: 6px; }
.btn-outline { background: transparent; color: #64748B; border: 1px solid #CBD5E1; padding: 8px 16px; border-radius: 8px; font-weight: 500; cursor: pointer; transition: all 0.2s; }
.btn-outline:hover { background: #F8FAFC; color: #0F172A; border-color: #94A3B8; }
.input-form { width: 100%; padding: 10px 14px; border: 1px solid #CBD5E1; border-radius: 8px; font-size: 14px; color: #1E293B; outline: none; transition: border-color 0.2s; box-sizing: border-box; font-family: 'Inter', sans-serif; }
.input-form:focus { border-color: #185FA5; }
</style>

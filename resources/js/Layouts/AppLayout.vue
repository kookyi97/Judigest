<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
// Iconos limpios y profesionales
import { 
  LayoutDashboard, 
  Scale, 
  FolderOpen, 
  ShieldAlert, 
  Bell, 
  Users,
  ChevronDown,
  User,
  LogOut 
} from 'lucide-vue-next';

const { props } = usePage();
const usuario = computed(() => props.auth.usuario);

const dropdownAbierto = ref(false);
const toggleDropdown = () => {
  dropdownAbierto.value = !dropdownAbierto.value;
};

const cerrarDropdown = (e) => {
  if (!e.target.closest('.user-dropdown-wrapper')) {
    dropdownAbierto.value = false;
  }
};

onMounted(() => window.addEventListener('click', cerrarDropdown));
onUnmounted(() => window.removeEventListener('click', cerrarDropdown));

const menuItems = computed(() => {
    const rol = usuario.value?.rol;
    const items = [
        { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: ['administrador','secretario','asesor','practicante'] },
        { label: 'Expedientes', href: '/expedientes', icon: FolderOpen, roles: ['administrador','secretario','asesor','practicante'] },
        { label: 'Audiencias', href: '/audiencias', icon: Scale, roles: ['administrador','secretario','asesor'] },
        { label: 'Auditoría', href: '/auditoria', icon: ShieldAlert, roles: ['administrador'] },
        { label: 'Usuarios', href: '/usuarios', icon: Users, roles: ['administrador'] },
    ];
    return items.filter(i => i.roles.includes(rol));
});
</script>

<template>
  <div class="app-layout">
    
    <aside class="sidebar">
      <div class="sidebar-brand">
        <div class="logo-box">
          <Scale class="logo-icon" />
        </div>
        <div class="brand-text">
          <span class="brand-title">Judigest</span>
          <span class="brand-subtitle">Gestión Judicial</span>
        </div>
      </div>

      <div class="menu-section">
        <span class="section-title">PROCESOS JUDICIALES</span>
        <nav class="sidebar-nav">
          <Link 
            v-for="item in menuItems" 
            :key="item.href"
            :href="item.href" 
            class="nav-item"
            :class="{ 'active': $page.url.startsWith(item.href) }"
          >
            <component :is="item.icon" class="nav-icon" />
            <span class="nav-label">{{ item.label }}</span>
          </Link>
        </nav>
      </div>

      <div class="menu-section accountability">
        <span class="section-title">ALERTAS</span>
        <nav class="sidebar-nav">
          <Link 
            href="/notificaciones" 
            class="nav-item"
            :class="{ 'active': $page.url.startsWith('/notificaciones') }"
          >
            <div class="icon-wrapper">
              <Bell class="nav-icon" />
              <span class="badge-dot"></span>
            </div>
            <span class="nav-label">Notificaciones</span>
          </Link>
        </nav>
      </div>
    </aside>

    <div class="main-container">
      
      <header class="navbar">
        <div class="navbar-left">
          <span class="page-context">Panel de Control</span>
        </div>

        <div class="navbar-right">
          <div class="user-dropdown-wrapper">
            <button @click.stop="toggleDropdown" class="navbar-user-btn">
              <div class="nav-avatar">
                {{ usuario?.nombre?.charAt(0).toUpperCase() }}
              </div>
              <span class="nav-username">{{ usuario?.nombre }}</span>
              <ChevronDown class="chevron-icon" :class="{ 'rotate': dropdownAbierto }" />
            </button>

            <div v-if="dropdownAbierto" class="dropdown-menu">
              <div class="dropdown-header">
                <p class="dp-name">{{ usuario?.nombre }} {{ usuario?.apellido }}</p>
                <p class="dp-role">{{ usuario?.rol }}</p>
              </div>
              
              <Link href="/perfil" class="dropdown-item">
                <User class="dp-icon" />
                <span>Mi Perfil</span>
              </Link>
              
              <hr class="dropdown-divider" />
              
              <Link href="/logout" method="post" as="button" class="dropdown-item logout">
                <LogOut class="dp-icon" />
                <span>Cerrar Sesión</span>
              </Link>
            </div>
          </div>
        </div>
      </header>

      <main class="main-content">
        <slot />
      </main>

    </div>
  </div>
</template>

<style scoped>
/* Estructura Principal */
.app-layout {
  display: flex;
  min-height: 100vh;
  background-color: #f8fafc;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ==========================================================================
   SIDEBAR: Fondo Azul Primario (#185fa5), Letras en Blanco
   ========================================================================== */
.sidebar {
  width: 260px;
  background-color: #185fa5; /* Fondo del menú azul solicitado */
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0;
  bottom: 0;
  left: 0;
  z-index: 100;
  padding: 16px 0;
  box-shadow: 4px 0 20px rgba(24, 95, 165, 0.08);
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 24px;
  margin-bottom: 24px;
}

.logo-box {
  background-color: rgba(255, 255, 255, 0.15); /* Blanco traslúcido */
  color: #ffffff;
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
}

.logo-icon {
  width: 18px;
  height: 18px;
}

.brand-text {
  display: flex;
  flex-direction: column;
}

.brand-title {
  color: #ffffff; /* fff */
  font-weight: 700;
  font-size: 18px;
  letter-spacing: -0.03em;
  line-height: 1.1;
}

.brand-subtitle {
  font-size: 11px;
  color: #bfdbfe; /* Azul clarito sutil */
  font-weight: 500;
}

.menu-section {
  padding: 0 16px;
  margin-bottom: 28px;
}

.section-title {
  display: block;
  font-size: 10px;
  font-weight: 700;
  color: #93c5fd; /* Azul suave para títulos de sección */
  letter-spacing: 0.06em;
  padding-left: 12px;
  margin-bottom: 12px;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

/* Enlaces del menú laterales (Letras blancas y texto primary de fondo al activarse) */
.nav-item {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 11px 14px;
  color: rgba(255, 255, 255, 0.85); /* Letras en blanco suaves */
  text-decoration: none;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.nav-item:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: #ffffff;
}

/* Item activo: Resalta con fondo blanco opaco o cambio de contraste */
.nav-item.active {
  background-color: #ffffff;
  color: #185fa5; /* El texto toma el color azul de la barra */
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.nav-icon {
  width: 18px;
  height: 18px;
  stroke-width: 2;
}

.icon-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.badge-dot {
  position: absolute;
  top: -2px;
  right: -2px;
  width: 8px;
  height: 8px;
  background-color: #ef4444;
  border-radius: 50%;
  border: 2px solid #185fa5; /* Margen del color del fondo del menú */
}

.accountability {
  margin-top: auto;
}

/* ==========================================================================
   CONTENEDOR DERECHO Y NAVBAR (Barra Superior Blanca)
   ========================================================================== */
.main-container {
  flex: 1;
  margin-left: 260px;
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.navbar {
  height: 68px;
  background-color: #ffffff; /* Fondo blanco pedido */
  border-bottom: 1px solid #e2e8f0; /* Borde solicitado */
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  position: sticky;
  top: 0;
  z-index: 90;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02); /* Sombra sutil profesional */
}

.page-context {
  font-size: 15px;
  font-weight: 600;
  color: #1e293b; /* Text Primary */
}

/* Dropdown del perfil */
.user-dropdown-wrapper {
  position: relative;
}

.navbar-user-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background: none;
  border: none;
  padding: 6px 10px;
  border-radius: 12px;
  cursor: pointer;
  transition: background-color 0.2s;
}

.navbar-user-btn:hover {
  background-color: #f1f5f9;
}

.nav-avatar {
  width: 34px;
  height: 34px;
  background-color: #e2e8f0;
  color: #185fa5;
  font-weight: 700;
  font-size: 13px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
}

.nav-username {
  font-size: 14px;
  font-weight: 500;
  color: #1e293b; /* Text Primary */
}

.chevron-icon {
  width: 16px;
  height: 16px;
  color: #64748b;
  transition: transform 0.2s ease;
}

.chevron-icon.rotate {
  transform: rotate(180deg); /* Voltea el icono hacia arriba cuando se abre */
}

/* Menú Desplegable Flotante */
.dropdown-menu {
  position: absolute;
  top: calc(100% + 8px);
  right: 0;
  width: 220px;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -4px rgba(0, 0, 0, 0.05);
  padding: 6px;
  display: flex;
  flex-direction: column;
}

.dropdown-header {
  padding: 10px 12px;
}

.dp-name {
  font-size: 13px;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.dp-role {
  font-size: 11px;
  color: #64748b;
  margin: 2px 0 0 0;
  text-transform: capitalize;
}

.dropdown-divider {
  border: 0;
  border-top: 1px solid #e2e8f0;
  margin: 4px 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  background: none;
  border: none;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  color: #475569;
  text-align: left;
  text-decoration: none;
  cursor: pointer;
  transition: all 0.15s;
}

.dropdown-item:hover {
  background-color: #f1f5f9;
  color: #111827;
}

.dropdown-item.logout:hover {
  background-color: #fee2e2;
  color: #ef4444;
}

.dp-icon {
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.dropdown-item:hover .dp-icon {
  color: inherit;
}

/* Área de contenido del sistema */
.main-content {
  padding: 32px;
}
</style>
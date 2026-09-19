<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
  ShieldAlert,
  ShieldCheck,
  Search,
  Filter,
  Calendar,
  User,
  Activity,
  CheckCircle2,
  AlertTriangle,
  RotateCcw,
  Eye,
  X,
  Laptop,
  Clock,
  ChevronLeft,
  ChevronRight,
  FolderOpen,
  FileText,
  Users,
  Sliders,
  Lock,
  Globe,
  ArrowUpDown,
  ArrowUp,
  ArrowDown
} from 'lucide-vue-next';

const props = defineProps({
  auditorias: {
    type: Object,
    required: true,
  },
  filtros: {
    type: Object,
    default: () => ({}),
  },
  accionesDisponibles: {
    type: Array,
    default: () => [],
  },
  modulosDisponibles: {
    type: Array,
    default: () => [],
  },
  usuariosDisponibles: {
    type: Array,
    default: () => [],
  },
  estadisticas: {
    type: Object,
    default: () => ({ total: 0, hoy: 0, exitosas: 0, fallidas: 0 }),
  },
});

// Estado reactivo de los filtros
const buscar = ref(props.filtros.buscar || '');
const moduloSeleccionado = ref(props.filtros.modulo || '');
const accionSeleccionada = ref(props.filtros.accion || '');
const usuarioSeleccionado = ref(props.filtros.usuario_id || '');
const resultadoSeleccionado = ref(props.filtros.resultado || '');
const periodoSeleccionado = ref(props.filtros.periodo || 'todos');
const fechaDesde = ref(props.filtros.desde || '');
const fechaHasta = ref(props.filtros.hasta || '');
const ordenSeleccionado = ref(props.filtros.orden || 'desc');

// Modal para inspeccionar detalles JSON
const auditoriaDetalle = ref(null);
const abrirDetalle = (item) => {
  auditoriaDetalle.value = item;
};
const cerrarDetalle = () => {
  auditoriaDetalle.value = null;
};

// Aplicar filtros a través de Inertia
let debounceBusqueda = null;
const aplicarFiltros = () => {
  router.get(
    '/auditoria',
    {
      buscar: buscar.value || undefined,
      modulo: moduloSeleccionado.value || undefined,
      accion: accionSeleccionada.value || undefined,
      usuario_id: usuarioSeleccionado.value || undefined,
      resultado: resultadoSeleccionado.value || undefined,
      periodo: periodoSeleccionado.value !== 'todos' ? periodoSeleccionado.value : undefined,
      desde: fechaDesde.value || undefined,
      hasta: fechaHasta.value || undefined,
      orden: ordenSeleccionado.value !== 'desc' ? ordenSeleccionado.value : undefined,
    },
    {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    }
  );
};

// Alternar orden cronológico (descendente / ascendente)
const alternarOrden = () => {
  ordenSeleccionado.value = ordenSeleccionado.value === 'desc' ? 'asc' : 'desc';
  aplicarFiltros();
};

// Búsqueda en vivo con debounce
watch(buscar, () => {
  if (debounceBusqueda) clearTimeout(debounceBusqueda);
  debounceBusqueda = setTimeout(() => {
    aplicarFiltros();
  }, 400);
});

// Limpiar todos los filtros
const limpiarFiltros = () => {
  buscar.value = '';
  moduloSeleccionado.value = '';
  accionSeleccionada.value = '';
  usuarioSeleccionado.value = '';
  resultadoSeleccionado.value = '';
  periodoSeleccionado.value = 'todos';
  fechaDesde.value = '';
  fechaHasta.value = '';
  ordenSeleccionado.value = 'desc';
  router.get('/auditoria', {}, { preserveState: true, replace: true });
};

// Badges de colores por módulo
const getModuloBadge = (modulo) => {
  const map = {
    Expedientes: 'badge--azul',
    Documentos: 'badge--morado',
    Usuarios: 'badge--ambar',
    Configuración: 'badge--verde',
    Autenticación: 'badge--indigo',
  };
  return map[modulo] || 'badge--gris';
};

const getModuloIcon = (modulo) => {
  const map = {
    Expedientes: FolderOpen,
    Documentos: FileText,
    Usuarios: Users,
    Configuración: Sliders,
    Autenticación: Lock,
  };
  return map[modulo] || Activity;
};
</script>

<template>
  <AppLayout>
    <Head title="Bitácora de Auditoría y Registro Cronológico" />

    <div class="audit-page">
      <!-- Encabezado -->
      <header class="audit-header">
        <div>
          <div class="audit-tag">
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>Trazabilidad Inmutable del Sistema</span>
          </div>
          <h1 class="audit-title">Registro Cronológico y Bitácora de Acciones</h1>
          <p class="audit-sub">
            Monitoreo general y auditoría inmutable: consulte quién, cuándo y qué acción exacta se ejecutó en <strong>Judigest</strong>.
          </p>
        </div>
      </header>

      <!-- Tarjetas de Estadísticas Rápidas -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__icon bg-blue-50 text-blue-700">
            <Activity class="w-5 h-5" />
          </div>
          <div>
            <p class="stat-card__label">Total de Acciones</p>
            <p class="stat-card__val">{{ estadisticas.total }}</p>
            <p class="stat-card__sub">en el registro histórico</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon bg-purple-50 text-purple-700">
            <Clock class="w-5 h-5" />
          </div>
          <div>
            <p class="stat-card__label">Acciones Registradas Hoy</p>
            <p class="stat-card__val">{{ estadisticas.hoy }}</p>
            <p class="stat-card__sub">últimas 24 horas</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon bg-emerald-50 text-emerald-700">
            <CheckCircle2 class="w-5 h-5" />
          </div>
          <div>
            <p class="stat-card__label">Operaciones Exitosas</p>
            <p class="stat-card__val">{{ estadisticas.exitosas }}</p>
            <p class="stat-card__sub">concluidas satisfactoriamente</p>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-card__icon bg-amber-50 text-amber-700">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <p class="stat-card__label">Incidentes o Rechazos</p>
            <p class="stat-card__val">{{ estadisticas.fallidas }}</p>
            <p class="stat-card__sub">intentos fallidos / errores</p>
          </div>
        </div>
      </div>

      <!-- Panel de Filtros -->
      <div class="filter-panel">
        <div class="filter-grid">
          <!-- Búsqueda rápida -->
          <div class="filter-group filter-group--search">
            <label class="filter-label">Búsqueda rápida</label>
            <div class="search-box">
              <Search class="search-box__icon" />
              <input
                v-model="buscar"
                type="text"
                placeholder="Buscar por usuario, acción, descripción o IP..."
                class="search-box__input"
              />
            </div>
          </div>

          <!-- Filtro por Tipo de Acción -->
          <div class="filter-group">
            <label class="filter-label">Tipo de Acción</label>
            <select v-model="accionSeleccionada" @change="aplicarFiltros" class="filter-select">
              <option value="">Todas las acciones</option>
              <option v-for="a in accionesDisponibles" :key="a" :value="a">
                {{ a }}
              </option>
            </select>
          </div>

          <!-- Filtro por Usuario Responsable -->
          <div class="filter-group">
            <label class="filter-label">Usuario Responsable</label>
            <select v-model="usuarioSeleccionado" @change="aplicarFiltros" class="filter-select">
              <option value="">Todos los usuarios</option>
              <option v-for="u in usuariosDisponibles" :key="u.id" :value="u.id">
                {{ u.nombre }}
              </option>
            </select>
          </div>

          <!-- Filtro por Módulo -->
          <div class="filter-group">
            <label class="filter-label">Módulo</label>
            <select v-model="moduloSeleccionado" @change="aplicarFiltros" class="filter-select">
              <option value="">Todos los módulos</option>
              <option v-for="m in modulosDisponibles" :key="m" :value="m">
                {{ m }}
              </option>
            </select>
          </div>

          <!-- Orden Cronológico -->
          <div class="filter-group">
            <label class="filter-label">Orden Cronológico</label>
            <select v-model="ordenSeleccionado" @change="aplicarFiltros" class="filter-select">
              <option value="desc">Más recientes primero (Desc)</option>
              <option value="asc">Más antiguos primero (Asc)</option>
            </select>
          </div>
        </div>

        <!-- Filtros secundarios de fechas y estado -->
        <div class="filter-subgrid mt-3">
          <!-- Período rápido -->
          <div class="filter-group">
            <label class="filter-label">Período</label>
            <select v-model="periodoSeleccionado" @change="aplicarFiltros" class="filter-select">
              <option value="todos">Cualquier momento</option>
              <option value="hoy">Hoy</option>
              <option value="semana">Últimos 7 días</option>
              <option value="mes">Últimos 30 días</option>
            </select>
          </div>

          <!-- Fecha Desde -->
          <div class="filter-group">
            <label class="filter-label">Fecha Desde</label>
            <input
              v-model="fechaDesde"
              type="date"
              @change="aplicarFiltros"
              class="filter-date-input"
            />
          </div>

          <!-- Fecha Hasta -->
          <div class="filter-group">
            <label class="filter-label">Fecha Hasta</label>
            <input
              v-model="fechaHasta"
              type="date"
              @change="aplicarFiltros"
              class="filter-date-input"
            />
          </div>

          <!-- Resultado -->
          <div class="filter-group">
            <label class="filter-label">Resultado</label>
            <select v-model="resultadoSeleccionado" @change="aplicarFiltros" class="filter-select">
              <option value="">Todos los estados</option>
              <option value="exitoso">Solo Exitosos</option>
              <option value="fallido">Solo Fallidos</option>
            </select>
          </div>

          <div class="filter-group filter-group--btn-reset">
            <button
              type="button"
              @click="limpiarFiltros"
              class="btn-reset"
              title="Restablecer filtros"
            >
              <RotateCcw class="w-3.5 h-3.5" />
              <span>Limpiar Filtros</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Tabla de Registro Cronológico de Auditoría Inmutable -->
      <div class="table-card">
        <div class="table-card__header">
          <div class="flex items-center gap-2">
            <Activity class="w-4 h-4 text-blue-700" />
            <h2 class="table-card__title">Registro Cronológico de Eventos</h2>
          </div>
          <div class="flex items-center gap-3">
            <span class="text-xs text-slate-500">
              Mostrando <strong>{{ auditorias.from || 0 }} - {{ auditorias.to || 0 }}</strong> de <strong>{{ auditorias.total }}</strong> acciones registradas
            </span>
            <button
              type="button"
              @click="alternarOrden"
              class="btn-sort-toggle"
              :title="ordenSeleccionado === 'desc' ? 'Cambiar a más antiguos primero' : 'Cambiar a más recientes primero'"
            >
              <ArrowDown v-if="ordenSeleccionado === 'desc'" class="w-3.5 h-3.5 text-blue-700" />
              <ArrowUp v-else class="w-3.5 h-3.5 text-blue-700" />
              <span>{{ ordenSeleccionado === 'desc' ? 'Cronológico: Más recientes' : 'Cronológico: Más antiguos' }}</span>
            </button>
          </div>
        </div>

        <div class="table-responsive">
          <table v-if="auditorias.data.length > 0" class="audit-tbl">
            <thead>
              <tr>
                <th class="cursor-pointer hover:text-blue-700 select-none" @click="alternarOrden">
                  <div class="flex items-center gap-1">
                    <span>Fecha</span>
                    <ArrowDown v-if="ordenSeleccionado === 'desc'" class="w-3 h-3 text-blue-700 inline" />
                    <ArrowUp v-else class="w-3 h-3 text-blue-700 inline" />
                  </div>
                </th>
                <th>Hora</th>
                <th>Usuario Responsable</th>
                <th>Tipo de Acción</th>
                <th>Descripción de la Acción</th>
                <th>Módulo</th>
                <th>Dirección IP</th>
                <th>Resultado</th>
                <th class="text-right">Detalles</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in auditorias.data" :key="item.id" class="audit-tbl__row">
                <!-- Fecha -->
                <td class="audit-tbl__date">
                  <div class="font-semibold text-slate-800">{{ item.fecha }}</div>
                  <div class="text-xs text-slate-400">{{ item.hace_tiempo }}</div>
                </td>

                <!-- Hora -->
                <td class="audit-tbl__time">
                  <span class="font-mono text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded">
                    {{ item.hora }}
                  </span>
                </td>

                <!-- Usuario Responsable -->
                <td>
                  <div class="user-cell">
                    <div class="user-cell__avatar">
                      {{ item.usuario.nombre.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-800 leading-tight">
                        {{ item.usuario.nombre }}
                      </div>
                      <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="user-cell__role capitalize">{{ item.usuario.rol }}</span>
                        <span v-if="item.usuario.correo !== 'N/A'" class="text-xs text-slate-400">
                          · {{ item.usuario.correo }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>

                <!-- Tipo de Acción -->
                <td>
                  <div class="font-bold text-slate-800 text-sm">
                    {{ item.accion }}
                  </div>
                  <div v-if="item.entidad_tipo" class="text-xs text-slate-500 font-mono">
                    {{ item.entidad_tipo }} #{{ item.entidad_id || '—' }}
                  </div>
                </td>

                <!-- Descripción de la Acción -->
                <td class="audit-tbl__desc">
                  <p class="text-sm text-slate-700 line-clamp-2" :title="item.descripcion">
                    {{ item.descripcion }}
                  </p>
                </td>

                <!-- Módulo -->
                <td>
                  <span class="badge" :class="getModuloBadge(item.modulo)">
                    <component :is="getModuloIcon(item.modulo)" class="w-3 h-3 inline-block mr-1" />
                    {{ item.modulo }}
                  </span>
                </td>

                <!-- Dirección IP -->
                <td>
                  <div class="font-mono text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded w-fit">
                    {{ item.ip_address }}
                  </div>
                </td>

                <!-- Resultado -->
                <td>
                  <span
                    class="status-pill"
                    :class="item.resultado === 'exitoso' ? 'status-pill--ok' : 'status-pill--err'"
                  >
                    <CheckCircle2 v-if="item.resultado === 'exitoso'" class="w-3.5 h-3.5" />
                    <AlertTriangle v-else class="w-3.5 h-3.5" />
                    <span class="capitalize">{{ item.resultado }}</span>
                  </span>
                </td>

                <!-- Botón de Inspección Técnica -->
                <td class="text-right">
                  <button
                    type="button"
                    @click="abrirDetalle(item)"
                    class="btn-inspect"
                    title="Visualizar detalles completos de la acción registrada"
                  >
                    <Eye class="w-3.5 h-3.5" />
                    <span>Ver</span>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>

          <!-- Estado Vacío -->
          <div v-else class="empty-state">
            <ShieldAlert class="empty-state__icon" />
            <h3 class="empty-state__title">No se encontraron registros de auditoría</h3>
            <p class="empty-state__sub">
              No hay eventos en la bitácora que coincidan con los filtros aplicados.
            </p>
            <button type="button" @click="limpiarFiltros" class="btn-clear mt-3">
              Restablecer filtros de búsqueda
            </button>
          </div>
        </div>

        <!-- Paginación -->
        <div v-if="auditorias.links && auditorias.links.length > 3" class="pagination-footer">
          <div class="text-xs text-slate-500">
            Mostrando <strong>{{ auditorias.from || 0 }} - {{ auditorias.to || 0 }}</strong> de <strong>{{ auditorias.total }}</strong> registros (Página {{ auditorias.current_page }} de {{ auditorias.last_page }})
          </div>
          <div class="pagination-links">
            <template v-for="(link, i) in auditorias.links" :key="i">
              <span
                v-if="!link.url"
                class="page-btn page-btn--disabled"
                v-html="link.label"
              />
              <Link
                v-else
                :href="link.url"
                class="page-btn"
                :class="{ 'page-btn--active': link.active }"
                v-html="link.label"
                preserve-scroll
              />
            </template>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Detalle Técnico / Autoría Certificada -->
    <div v-if="auditoriaDetalle" class="modal-backdrop" @click.self="cerrarDetalle">
      <div class="modal-card">
        <div class="modal-card__header">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-5 h-5 text-blue-700" />
            <h3 class="modal-card__title">Certificado Inmutable de Autoría</h3>
          </div>
          <button @click="cerrarDetalle" class="modal-close-btn" type="button">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="modal-card__body">
          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-item__label">ID de Registro</span>
              <span class="detail-item__val font-mono">#{{ auditoriaDetalle.id }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-item__label">Fecha y Hora Exacta</span>
              <span class="detail-item__val">{{ auditoriaDetalle.fecha_hora }} ({{ auditoriaDetalle.hace_tiempo }})</span>
            </div>

            <div class="detail-item">
              <span class="detail-item__label">Usuario Autenticado</span>
              <span class="detail-item__val font-bold text-slate-800">
                {{ auditoriaDetalle.usuario.nombre }} ({{ auditoriaDetalle.usuario.rol }})
              </span>
            </div>

            <div class="detail-item">
              <span class="detail-item__label">Dirección IP de Origen</span>
              <span class="detail-item__val font-mono">{{ auditoriaDetalle.ip_address }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-item__label">Módulo</span>
              <span class="detail-item__val">{{ auditoriaDetalle.modulo }}</span>
            </div>

            <div class="detail-item">
              <span class="detail-item__label">Acción Ejecutada</span>
              <span class="detail-item__val font-semibold">{{ auditoriaDetalle.accion }}</span>
            </div>
          </div>

          <div class="detail-block">
            <span class="detail-item__label">Descripción Completa</span>
            <p class="text-sm text-slate-800 bg-slate-50 p-3 rounded border border-slate-200 mt-1">
              {{ auditoriaDetalle.descripcion }}
            </p>
          </div>

          <div v-if="auditoriaDetalle.user_agent" class="detail-block">
            <span class="detail-item__label">Navegador y Sistema (User Agent)</span>
            <p class="text-xs font-mono text-slate-600 bg-slate-50 p-2.5 rounded border border-slate-200 mt-1 break-all">
              {{ auditoriaDetalle.user_agent }}
            </p>
          </div>

          <div v-if="auditoriaDetalle.detalles" class="detail-block">
            <span class="detail-item__label">Datos Técnicos del Payload (JSON)</span>
            <pre class="json-box">{{ JSON.stringify(auditoriaDetalle.detalles, null, 2) }}</pre>
          </div>
        </div>

        <div class="modal-card__footer">
          <button @click="cerrarDetalle" type="button" class="btn-close-modal">
            Cerrar Visor
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.audit-page {
  padding: 24px 32px;
  max-width: 1440px;
  margin: 0 auto;
  font-family: 'Inter', system-ui, -apple-system, sans-serif;
}

/* ── Encabezado ── */
.audit-header {
  margin-bottom: 24px;
}

.audit-tag {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #eff6ff;
  color: #185fa5;
  border: 1px solid #bfdbfe;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 8px;
}

.audit-title {
  font-size: 1.65rem;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.025em;
  margin: 0 0 4px 0;
}

.audit-sub {
  font-size: 0.925rem;
  color: #64748b;
  margin: 0;
}

/* ── Tarjetas de Estadísticas ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  display: flex;
  align-items: center;
  gap: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.stat-card__icon {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-card__label {
  font-size: 0.775rem;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 2px 0;
}

.stat-card__val {
  font-size: 1.5rem;
  font-weight: 800;
  color: #0f172a;
  margin: 0 0 2px 0;
  line-height: 1.1;
}

.stat-card__sub {
  font-size: 0.75rem;
  color: #94a3b8;
  margin: 0;
}

/* ── Panel de Filtros ── */
.filter-panel {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 24px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.filter-grid {
  display: grid;
  grid-template-columns: 2fr 1fr 1fr 1fr 1fr;
  gap: 14px;
  align-items: flex-end;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.filter-label {
  font-size: 0.775rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.search-box {
  position: relative;
}

.search-box__icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  width: 16px;
  height: 16px;
  color: #94a3b8;
}

.search-box__input {
  width: 100%;
  padding: 9px 12px 9px 36px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #0f172a;
  outline: none;
  transition: all 0.15s;
}

.search-box__input:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
}

.filter-select {
  width: 100%;
  padding: 9px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #0f172a;
  background-color: #ffffff;
  outline: none;
  transition: all 0.15s;
}

.filter-select:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
}

.filter-subgrid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr 1fr auto;
  gap: 14px;
  align-items: flex-end;
  padding-top: 12px;
  border-top: 1px dashed #e2e8f0;
}

.filter-date-input {
  width: 100%;
  padding: 8px 10px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #0f172a;
  background-color: #ffffff;
  outline: none;
  transition: all 0.15s;
}

.filter-date-input:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
}

.filter-group--btn-reset {
  justify-content: flex-end;
}

.btn-sort-toggle {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background-color: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
  font-size: 0.775rem;
  font-weight: 600;
  padding: 5px 10px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-sort-toggle:hover {
  background-color: #dbeafe;
}

.btn-reset {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border: none;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
  padding: 4px 8px;
  border-radius: 4px;
}

.btn-reset:hover {
  color: #0f172a;
  background-color: #f1f5f9;
}

/* ── Tabla de Auditoría ── */
.table-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.02);
}

.table-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
  background-color: #fcfdfe;
}

.table-card__title {
  font-size: 0.975rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.table-responsive {
  overflow-x: auto;
}

.audit-tbl {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.85rem;
  text-align: left;
}

.audit-tbl th {
  background-color: #f8fafc;
  padding: 12px 16px;
  font-size: 0.725rem;
  font-weight: 700;
  color: #475569;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-bottom: 1px solid #e2e8f0;
}

.audit-tbl td {
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
  vertical-align: middle;
}

.audit-tbl__row:hover {
  background-color: #fcfdfe;
}

.audit-tbl__time {
  white-space: nowrap;
}

.audit-tbl__desc {
  max-width: 320px;
}

.user-cell {
  display: flex;
  align-items: center;
  gap: 10px;
}

.user-cell__avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background-color: #185fa5;
  color: #ffffff;
  font-weight: 700;
  font-size: 0.85rem;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.user-cell__role {
  font-size: 0.725rem;
  background-color: #f1f5f9;
  color: #475569;
  padding: 1px 6px;
  border-radius: 4px;
  font-weight: 600;
}

/* Badges */
.badge {
  display: inline-flex;
  align-items: center;
  padding: 3px 8px;
  border-radius: 6px;
  font-size: 0.725rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

.badge--azul {
  background-color: #eff6ff;
  color: #1e40af;
}

.badge--morado {
  background-color: #faf5ff;
  color: #6b21a8;
}

.badge--ambar {
  background-color: #fffbeb;
  color: #b45309;
}

.badge--verde {
  background-color: #ecfdf5;
  color: #065f46;
}

.badge--indigo {
  background-color: #eef2ff;
  color: #3730a3;
}

.badge--gris {
  background-color: #f1f5f9;
  color: #475569;
}

/* Status Pill */
.status-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 8px;
  border-radius: 9999px;
  font-size: 0.75rem;
  font-weight: 600;
}

.status-pill--ok {
  background-color: #ecfdf5;
  color: #059669;
}

.status-pill--err {
  background-color: #fef2f2;
  color: #dc2626;
}

.btn-inspect {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 5px 10px;
  border: 1px solid #cbd5e1;
  background-color: #ffffff;
  color: #475569;
  border-radius: 6px;
  font-size: 0.775rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-inspect:hover {
  background-color: #f1f5f9;
  color: #0f172a;
  border-color: #94a3b8;
}

/* Empty State */
.empty-state {
  padding: 48px 24px;
  text-align: center;
  color: #94a3b8;
}

.empty-state__icon {
  width: 44px;
  height: 44px;
  margin: 0 auto 12px auto;
  opacity: 0.35;
}

.empty-state__title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #334155;
  margin: 0 0 4px 0;
}

.empty-state__sub {
  font-size: 0.85rem;
  margin: 0;
}

.btn-clear {
  background-color: #185fa5;
  color: #ffffff;
  border: none;
  padding: 7px 14px;
  border-radius: 6px;
  font-size: 0.825rem;
  font-weight: 600;
  cursor: pointer;
}

/* Paginación */
.pagination-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  background-color: #fcfdfe;
}

.pagination-links {
  display: flex;
  gap: 4px;
}

.page-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 32px;
  height: 32px;
  padding: 0 8px;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  font-size: 0.8rem;
  color: #475569;
  text-decoration: none;
  transition: all 0.15s;
}

.page-btn:hover:not(.page-btn--disabled) {
  background-color: #f1f5f9;
  color: #0f172a;
}

.page-btn--active {
  background-color: #185fa5 !important;
  color: #ffffff !important;
  border-color: #185fa5 !important;
  font-weight: 700;
}

.page-btn--disabled {
  opacity: 0.4;
  cursor: not-allowed;
  background-color: #f8fafc;
}

/* ── Modal de Detalle Técnico ── */
.modal-backdrop {
  position: fixed;
  inset: 0;
  background-color: rgba(15, 23, 42, 0.5);
  backdrop-filter: blur(2px);
  z-index: 1000;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.modal-card {
  background-color: #ffffff;
  border-radius: 14px;
  max-width: 680px;
  width: 100%;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
  overflow: hidden;
}

.modal-card__header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #e2e8f0;
}

.modal-card__title {
  font-size: 1.05rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}

.modal-close-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  border-radius: 4px;
}

.modal-close-btn:hover {
  background-color: #f1f5f9;
  color: #0f172a;
}

.modal-card__body {
  padding: 20px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  background-color: #f8fafc;
  padding: 14px;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.detail-item__label {
  font-size: 0.725rem;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.detail-item__val {
  font-size: 0.85rem;
  color: #1e293b;
}

.detail-block {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.json-box {
  background-color: #0f172a;
  color: #38bdf8;
  padding: 14px;
  border-radius: 8px;
  font-family: monospace;
  font-size: 0.775rem;
  overflow-x: auto;
  margin-top: 4px;
  max-height: 200px;
}

.modal-card__footer {
  display: flex;
  justify-content: flex-end;
  padding: 14px 20px;
  border-top: 1px solid #e2e8f0;
  background-color: #f8fafc;
}

.btn-close-modal {
  padding: 8px 16px;
  background-color: #185fa5;
  color: #ffffff;
  border: none;
  border-radius: 6px;
  font-size: 0.85rem;
  font-weight: 600;
  cursor: pointer;
}

.btn-close-modal:hover {
  background-color: #134e87;
}

/* Responsive */
@media (max-width: 1024px) {
  .filter-grid {
    grid-template-columns: 1fr 1fr;
  }
  .filter-subgrid {
    grid-template-columns: 1fr 1fr;
  }
}

@media (max-width: 640px) {
  .audit-page {
    padding: 16px;
  }
  .filter-grid {
    grid-template-columns: 1fr;
  }
  .filter-subgrid {
    grid-template-columns: 1fr;
  }
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>

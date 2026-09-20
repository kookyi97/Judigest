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
  ArrowDown,
  Download,
  FileSpreadsheet,
  AlertOctagon,
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
    default: () => ({ total: 0, hoy: 0, exitosas: 0, fallidas: 0, sospechosas: 0 }),
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
const horaDesde = ref(props.filtros.hora_desde || '');
const horaHasta = ref(props.filtros.hora_hasta || '');
const soloSospechosas = ref(Boolean(props.filtros.solo_sospechosas));
const ordenSeleccionado = ref(props.filtros.orden || 'desc');

// Modal para inspeccionar detalles JSON
const auditoriaDetalle = ref(null);
const abrirDetalle = (item) => {
  auditoriaDetalle.value = item;
};
const cerrarDetalle = () => {
  auditoriaDetalle.value = null;
};

// Generar objeto con parámetros de filtrado actuales
const generarParamsFiltro = () => {
  return {
    buscar: buscar.value || undefined,
    modulo: moduloSeleccionado.value || undefined,
    accion: accionSeleccionada.value || undefined,
    usuario_id: usuarioSeleccionado.value || undefined,
    resultado: resultadoSeleccionado.value || undefined,
    periodo: periodoSeleccionado.value !== 'todos' ? periodoSeleccionado.value : undefined,
    desde: fechaDesde.value || undefined,
    hasta: fechaHasta.value || undefined,
    hora_desde: horaDesde.value || undefined,
    hora_hasta: horaHasta.value || undefined,
    solo_sospechosas: soloSospechosas.value ? '1' : undefined,
    orden: ordenSeleccionado.value !== 'desc' ? ordenSeleccionado.value : undefined,
  };
};

// Aplicar filtros a través de Inertia
let debounceBusqueda = null;
const aplicarFiltros = () => {
  router.get(
    '/auditoria',
    generarParamsFiltro(),
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

// Alternar filtro de solo actividades sospechosas o anomalías
const toggleSoloSospechosas = () => {
  soloSospechosas.value = !soloSospechosas.value;
  aplicarFiltros();
};

// Exportar bitácora respetando exactamente los filtros aplicados
const obtenerQueryStringExport = () => {
  const params = new URLSearchParams();
  const obj = generarParamsFiltro();
  for (const [key, val] of Object.entries(obj)) {
    if (val !== undefined && val !== null && val !== '') {
      params.append(key, val);
    }
  }
  return params.toString();
};

const exportarExcel = () => {
  const qs = obtenerQueryStringExport();
  window.location.href = `/auditoria/exportar/excel${qs ? '?' + qs : ''}`;
};

const exportarCsv = () => {
  const qs = obtenerQueryStringExport();
  window.location.href = `/auditoria/exportar/csv${qs ? '?' + qs : ''}`;
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
  horaDesde.value = '';
  horaHasta.value = '';
  soloSospechosas.value = false;
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
    Seguridad: 'badge--rojo',
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
    Seguridad: ShieldAlert,
  };
  return map[modulo] || Activity;
};
</script>

<template>
  <AppLayout>
    <Head title="Bitácora de Auditoría y Registro Cronológico" />

    <div class="audit-page">
      <!-- Encabezado con Acciones de Exportación -->
      <header class="audit-header">
        <div>
          <div class="audit-tag">
            <ShieldCheck class="w-3.5 h-3.5" />
            <span>Trazabilidad Inmutable del Sistema</span>
          </div>
          <h1 class="audit-title">Registro Cronológico y Respaldo de Auditoría</h1>
          <p class="audit-sub">
            Monitoreo continuo para auditorías externas y detección de actividades sospechosas: consulte quién, cuándo y qué acción exacta se ejecutó en <strong>Judigest</strong>.
          </p>
        </div>

        <div class="audit-header-actions">
          <button
            type="button"
            class="btn-export btn-export--excel"
            @click="exportarExcel"
            title="Exportar bitácora filtrada a archivo Microsoft Excel (.xlsx) para auditoría externa"
          >
            <Download class="w-4 h-4" />
            <span>Exportar a Excel (.xlsx)</span>
          </button>
          <button
            type="button"
            class="btn-export btn-export--csv"
            @click="exportarCsv"
            title="Exportar bitácora filtrada a formato plano CSV (.csv)"
          >
            <FileText class="w-4 h-4" />
            <span>Exportar CSV</span>
          </button>
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
          <div class="stat-card__icon bg-rose-50 text-rose-700">
            <AlertTriangle class="w-5 h-5" />
          </div>
          <div>
            <p class="stat-card__label">Incidentes o Rechazos</p>
            <p class="stat-card__val">{{ estadisticas.fallidas }}</p>
            <p class="stat-card__sub">intentos fallidos / errores</p>
          </div>
        </div>

        <!-- Tarjeta de Actividades Sospechosas / Críticas -->
        <div
          class="stat-card stat-card--interactive"
          :class="{ 'stat-card--selected': soloSospechosas }"
          @click="toggleSoloSospechosas"
          title="Haga clic para filtrar solo actividades sospechosas o críticas"
        >
          <div class="stat-card__icon bg-amber-50 text-amber-700">
            <AlertOctagon class="w-5 h-5" />
          </div>
          <div>
            <p class="stat-card__label">Actividades Sospechosas / Críticas</p>
            <p class="stat-card__val text-amber-700">{{ estadisticas.sospechosas }}</p>
            <p class="stat-card__sub">
              {{ soloSospechosas ? 'Filtro activo (clic para ver todas)' : 'clic para filtrar anomalías' }}
            </p>
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

        <!-- Filtros secundarios de fechas, horas y estado -->
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

          <!-- Hora Desde -->
          <div class="filter-group">
            <label class="filter-label">Hora Desde</label>
            <input
              v-model="horaDesde"
              type="time"
              @change="aplicarFiltros"
              class="filter-time-input"
              title="Hora inicial del filtro"
            />
          </div>

          <!-- Hora Hasta -->
          <div class="filter-group">
            <label class="filter-label">Hora Hasta</label>
            <input
              v-model="horaHasta"
              type="time"
              @change="aplicarFiltros"
              class="filter-time-input"
              title="Hora final del filtro"
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
        </div>

        <!-- Barra inferior de acciones de filtro -->
        <div class="filter-actions-bar mt-3 pt-3 border-t border-slate-100 flex items-center justify-between flex-wrap gap-2">
          <!-- Botón de alternancia de actividades sospechosas -->
          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="toggleSoloSospechosas"
              class="btn-toggle-suspicious"
              :class="{ 'btn-toggle-suspicious--active': soloSospechosas }"
            >
              <AlertTriangle class="w-3.5 h-3.5" />
              <span>Solo actividades sospechosas o críticas</span>
              <span v-if="estadisticas.sospechosas > 0" class="badge-count">
                {{ estadisticas.sospechosas }}
              </span>
            </button>

            <span v-if="soloSospechosas" class="text-xs text-amber-700 font-medium">
              Mostrando intentos fallidos, bloqueos, eliminaciones y cambios sensibles.
            </span>
          </div>

          <!-- Limpiar filtros -->
          <button
            type="button"
            @click="limpiarFiltros"
            class="btn-reset"
            title="Restablecer todos los filtros"
          >
            <RotateCcw class="w-3.5 h-3.5" />
            <span>Restablecer Filtros</span>
          </button>
        </div>
      </div>

      <!-- Tabla de Registro Cronológico de Auditoría Inmutable -->
      <div class="table-card">
        <div class="table-card__header">
          <div class="flex items-center gap-2">
            <Activity class="w-4 h-4 text-blue-700" />
            <h2 class="table-card__title">Historial Cronológico Inmutable</h2>
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
              <tr
                v-for="item in auditorias.data"
                :key="item.id"
                class="audit-tbl__row"
                :class="{
                  'audit-tbl__row--sospechosa': item.es_critica && item.resultado !== 'fallido',
                  'audit-tbl__row--fallido': item.resultado === 'fallido',
                }"
              >
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

                <!-- Tipo de Acción con Resaltado Visual -->
                <td>
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-bold text-slate-800 text-sm">
                      {{ item.accion }}
                    </span>
                    <!-- Badge indicador de actividad sospechosa o crítica -->
                    <span
                      v-if="item.es_critica"
                      class="badge-alerta"
                      :class="item.resultado === 'fallido' ? 'badge-alerta--alto' : 'badge-alerta--medio'"
                      :title="item.motivo_critica"
                    >
                      <AlertTriangle class="w-3 h-3 inline mr-0.5" />
                      {{ item.resultado === 'fallido' ? 'Sospechosa' : 'Crítica' }}
                    </span>
                  </div>
                  <div v-if="item.entidad_tipo" class="text-xs text-slate-500 font-mono mt-0.5">
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
                    title="Visualizar detalles completos y certificado de la acción registrada"
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

    <!-- Modal de Detalle Técnico / Certificado Inmutable de Autoría -->
    <div v-if="auditoriaDetalle" class="modal-backdrop" @click.self="cerrarDetalle">
      <div class="modal-card">
        <div class="modal-card__header">
          <div class="flex items-center gap-2">
            <ShieldCheck class="w-5 h-5 text-blue-700" />
            <h3 class="modal-card__title">Certificado Inmutable de Autoría y Trazabilidad</h3>
          </div>
          <button @click="cerrarDetalle" class="modal-close-btn" type="button">
            <X class="w-4 h-4" />
          </button>
        </div>

        <div class="modal-card__body">
          <!-- Alerta de Actividad Sospechosa o Crítica -->
          <div
            v-if="auditoriaDetalle.es_critica"
            class="alert-box-suspicious"
            :class="auditoriaDetalle.resultado === 'fallido' ? 'alert-box--danger' : 'alert-box--warning'"
          >
            <div class="flex items-center gap-2 font-bold text-sm">
              <AlertTriangle class="w-4 h-4 shrink-0" />
              <span>
                Alerta de Auditoría: Actividad {{ auditoriaDetalle.resultado === 'fallido' ? 'Sospechosa / Anómala' : 'Crítica de Alto Impacto' }}
              </span>
            </div>
            <p class="text-xs mt-1.5 opacity-90">
              {{ auditoriaDetalle.motivo_critica || 'Esta acción requiere atención o verificación en auditoría externa.' }}
            </p>
          </div>

          <div class="detail-grid">
            <div class="detail-item">
              <span class="detail-item__label">ID de Registro</span>
              <span class="detail-item__val font-mono font-bold">#{{ auditoriaDetalle.id }}</span>
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
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 20px;
  margin-bottom: 24px;
  flex-wrap: wrap;
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
  max-width: 820px;
}

.audit-header-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.btn-export {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: 8px;
  font-size: 0.825rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.15s ease;
  border: 1px solid transparent;
}

.btn-export--excel {
  background-color: #047857;
  color: #ffffff;
}

.btn-export--excel:hover {
  background-color: #065f46;
  box-shadow: 0 2px 6px rgba(4, 120, 87, 0.25);
}

.btn-export--csv {
  background-color: #ffffff;
  color: #1e293b;
  border-color: #cbd5e1;
}

.btn-export--csv:hover {
  background-color: #f8fafc;
  border-color: #94a3b8;
}

/* ── Tarjetas de Estadísticas ── */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
  transition: all 0.15s ease;
}

.stat-card--interactive {
  cursor: pointer;
}

.stat-card--interactive:hover {
  border-color: #f59e0b;
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);
}

.stat-card--selected {
  border-color: #d97706;
  background-color: #fffbeb;
  box-shadow: 0 0 0 2px rgba(217, 119, 6, 0.2);
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
  font-size: 0.75rem;
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

.filter-select,
.filter-date-input,
.filter-time-input {
  width: 100%;
  padding: 8px 12px;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  font-size: 0.85rem;
  color: #0f172a;
  background-color: #ffffff;
  outline: none;
  transition: all 0.15s;
}

.filter-select:focus,
.filter-date-input:focus,
.filter-time-input:focus {
  border-color: #185fa5;
  box-shadow: 0 0 0 3px rgba(24, 95, 165, 0.12);
}

.filter-subgrid {
  display: grid;
  grid-template-columns: 1.2fr 1fr 1fr 1fr 1fr 1.2fr;
  gap: 12px;
  align-items: flex-end;
  padding-top: 12px;
  border-top: 1px dashed #e2e8f0;
}

.btn-toggle-suspicious {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 6px 12px;
  border-radius: 6px;
  font-size: 0.8rem;
  font-weight: 600;
  border: 1px solid #cbd5e1;
  background-color: #f8fafc;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s ease;
}

.btn-toggle-suspicious:hover {
  background-color: #fffbeb;
  border-color: #fcd34d;
  color: #b45309;
}

.btn-toggle-suspicious--active {
  background-color: #fef3c7;
  border-color: #f59e0b;
  color: #92400e;
  font-weight: 700;
}

.badge-count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: #b45309;
  color: #ffffff;
  font-size: 0.7rem;
  border-radius: 9999px;
  padding: 0 6px;
  height: 18px;
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
  padding: 6px 10px;
  border-radius: 6px;
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

.audit-tbl__row {
  transition: background-color 0.15s;
}

.audit-tbl__row:hover {
  background-color: #fcfdfe;
}

/* Resaltado visual de actividades sospechosas y fallidas */
.audit-tbl__row--sospechosa {
  border-left: 4px solid #f59e0b;
  background-color: #fffdf8;
}

.audit-tbl__row--sospechosa:hover {
  background-color: #fffbeb;
}

.audit-tbl__row--fallido {
  border-left: 4px solid #ef4444;
  background-color: #fef8f8;
}

.audit-tbl__row--fallido:hover {
  background-color: #fef2f2;
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

.badge--azul { background-color: #eff6ff; color: #1e40af; }
.badge--morado { background-color: #faf5ff; color: #6b21a8; }
.badge--ambar { background-color: #fffbeb; color: #b45309; }
.badge--verde { background-color: #ecfdf5; color: #065f46; }
.badge--indigo { background-color: #eef2ff; color: #3730a3; }
.badge--rojo { background-color: #fee2e2; color: #991b1b; }
.badge--gris { background-color: #f1f5f9; color: #475569; }

/* Badge Alerta para eventos sospechosos */
.badge-alerta {
  display: inline-flex;
  align-items: center;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

.badge-alerta--alto {
  background-color: #fee2e2;
  color: #b91c1c;
  border: 1px solid #fca5a5;
}

.badge-alerta--medio {
  background-color: #fef3c7;
  color: #92400e;
  border: 1px solid #fcd34d;
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

.alert-box-suspicious {
  border-radius: 8px;
  padding: 12px 14px;
  border: 1px solid;
}

.alert-box--danger {
  background-color: #fef2f2;
  border-color: #f87171;
  color: #991b1b;
}

.alert-box--warning {
  background-color: #fffbeb;
  border-color: #fcd34d;
  color: #92400e;
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
    grid-template-columns: 1fr 1fr 1fr;
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
  .audit-header {
    flex-direction: column;
  }
  .audit-header-actions {
    width: 100%;
    justify-content: flex-start;
  }
}
</style>

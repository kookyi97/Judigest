<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Bell, FolderOpen, Calendar, RefreshCw, CheckCircle2, CheckCheck } from 'lucide-vue-next';

const props = defineProps({
    notificaciones: { type: Object, required: true }, // paginador de Laravel
    noLeidas: { type: Number, default: 0 },
});

const page = usePage();
const flashExito = computed(() => page.props.flash?.exito);

const iconoPorCategoria = { caso: FolderOpen, audiencia: Calendar, estado: RefreshCw };
const colorPorCategoria = {
    caso: { bg: '#EFF6FF', color: '#185FA5' },
    audiencia: { bg: '#D1FAE5', color: '#065F46' },
    estado: { bg: '#FEF3C7', color: '#92400E' },
};

const marcarLeida = (n) => {
    if (n.leida) return;
    router.patch(`/notificaciones/${n.id}/leida`, {}, { preserveScroll: true, preserveState: true });
};

const marcarTodas = () => {
    router.patch('/notificaciones/leidas', {}, { preserveScroll: true });
};

const irA = (url) => {
    if (url) router.visit(url);
};
</script>

<template>
    <Head title="Notificaciones" />

    <AppLayout>
        <div class="notif-page">
            <div class="notif-header">
                <div>
                    <h1>Notificaciones</h1>
                    <p>{{ noLeidas }} sin leer · historial completo de avisos del sistema</p>
                </div>

                <button v-if="noLeidas > 0" type="button" class="btn-todas" @click="marcarTodas">
                    <CheckCheck /> Marcar todas como leídas
                </button>
            </div>

            <div v-if="flashExito" class="alerta">
                <CheckCircle2 /> <span>{{ flashExito }}</span>
            </div>

            <div v-if="notificaciones.data.length === 0" class="vacio">
                <Bell />
                <h3>No tienes notificaciones</h3>
                <p>Cuando se te asigne un caso o ocurra actividad relevante, aparecerá aquí.</p>
            </div>

            <ul v-else class="lista">
                <li
                    v-for="n in notificaciones.data"
                    :key="n.id"
                    class="item"
                    :class="{ 'item--nueva': !n.leida }"
                >
                    <div
                        class="item__icono"
                        :style="{ background: colorPorCategoria[n.categoria]?.bg, color: colorPorCategoria[n.categoria]?.color }"
                    >
                        <component :is="iconoPorCategoria[n.categoria] || Bell" />
                    </div>

                    <div class="item__cuerpo">
                        <p class="item__titulo">{{ n.titulo }}</p>
                        <p class="item__mensaje">{{ n.mensaje }}</p>
                        <p class="item__meta">
                            {{ n.fecha }} · {{ n.fecha_relativa }}
                            <span v-if="n.numero_expediente"> · Exp. {{ n.numero_expediente }}</span>
                        </p>
                    </div>

                    <div class="item__acciones">
                        <button v-if="n.url" type="button" class="btn-link" @click="irA(n.url)">Ver caso</button>
                        <button v-if="!n.leida" type="button" class="btn-link" @click="marcarLeida(n)">
                            Marcar leída
                        </button>
                    </div>
                </li>
            </ul>

            <div v-if="notificaciones.prev_page_url || notificaciones.next_page_url" class="paginacion">
                <Link v-if="notificaciones.prev_page_url" :href="notificaciones.prev_page_url" preserve-scroll>
                    ← Anteriores
                </Link>
                <span>Página {{ notificaciones.current_page }} de {{ notificaciones.last_page }}</span>
                <Link v-if="notificaciones.next_page_url" :href="notificaciones.next_page_url" preserve-scroll>
                    Más antiguas →
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.notif-page { font-family: 'Poppins', 'Inter', sans-serif; color: #1E293B; max-width: 900px; }
.notif-header { display: flex; justify-content: space-between; align-items: flex-end; gap: 16px; margin-bottom: 20px; }
.notif-header h1 { margin: 0 0 4px; font-size: 22px; font-weight: 600; }
.notif-header p { margin: 0; font-size: 13px; color: #64748B; }
.btn-todas { display: inline-flex; align-items: center; gap: 6px; padding: 9px 14px; background: #185FA5; color: #fff; border: 0; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-todas svg { width: 15px; height: 15px; }
.alerta { display: flex; align-items: center; gap: 8px; padding: 10px 14px; margin-bottom: 14px; background: #D1FAE5; color: #065F46; border-radius: 8px; font-size: 13px; }
.alerta svg { width: 16px; height: 16px; }
.lista { list-style: none; margin: 0; padding: 0; display: flex; flex-direction: column; gap: 10px; }
.item { display: flex; gap: 14px; align-items: flex-start; padding: 14px 16px; background: #fff; border: 1px solid #E2E8F0; border-radius: 12px; }
.item--nueva { border-left: 4px solid #185FA5; background: #F8FBFF; }
.item__icono { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.item__icono svg { width: 18px; height: 18px; }
.item__cuerpo { flex: 1; min-width: 0; }
.item__titulo { margin: 0 0 2px; font-size: 14px; font-weight: 600; }
.item__mensaje { margin: 0 0 6px; font-size: 13px; color: #475569; overflow-wrap: anywhere; }
.item__meta { margin: 0; font-size: 11px; color: #94A3B8; }
.item__acciones { display: flex; flex-direction: column; gap: 4px; align-items: flex-end; }
.btn-link { background: none; border: 0; padding: 2px 0; font-size: 12px; font-weight: 600; color: #185FA5; cursor: pointer; }
.btn-link:hover { text-decoration: underline; }
.vacio { text-align: center; padding: 48px 16px; background: #fff; border: 1px dashed #CBD5E1; border-radius: 12px; color: #64748B; }
.vacio svg { width: 34px; height: 34px; color: #94A3B8; }
.vacio h3 { margin: 10px 0 4px; font-size: 15px; color: #334155; }
.vacio p { margin: 0; font-size: 13px; }
.paginacion { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; font-size: 12px; color: #64748B; }
.paginacion a { color: #185FA5; font-weight: 600; text-decoration: none; }
</style>
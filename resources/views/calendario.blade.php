@extends('layouts.app')

@section('title', 'Calendario')

@section('content')
<style>
:root{
    --uv-rojo: #cd1f32;
    --uv-rojo-dark: #b31a2a;
    --uv-azul: #1e40af;
}

/* Contenedor centrado como en tus capturas */
.calendar-page{ margin-top: 0; }
.calendar-frame{
    max-width: 1100px;          /* ajusta si quieres más ancho */
    margin: 0 auto;
    background: transparent;
}
#calendar{ min-height: 760px; }

/* Barra roja INTERNA del calendario (la “barrita” del mes) */
.fc .fc-toolbar.fc-header-toolbar{
    background: var(--uv-rojo);
    color: #fff;
    border-radius: 10px 10px 0 0;
    padding: 10px 14px;
    margin-bottom: 0;            /* que pegue al grid */
}
.fc .fc-toolbar-title{
    color: #fff;
    font-weight: 700;
}
.fc .fc-button-primary{
    background: transparent;
    border: 0;
    color: #fff;
    box-shadow: none;
}
.fc .fc-button-primary:not(:disabled):hover{
    background: rgba(255,255,255,.15);
}
/* Bordes inferiores y sombra para “encajar” con la barrita */
.fc .fc-scrollgrid{
    border-radius: 0 0 10px 10px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,.08);
}

/* MES (dayGrid) — píldoras rojas + puntito azul, como tu original */
.fc .fc-daygrid-event{
    border: 1px solid var(--uv-rojo-dark);
    background: var(--uv-rojo);
    color: #fff;
    border-radius: 999px;
    padding: 2px 10px;
    line-height: 1.25;
    box-shadow: 0 1px 2px rgba(0,0,0,.12);
    transition: transform .06s ease, box-shadow .12s ease, filter .12s ease;
}
.fc .fc-daygrid-event .fc-event-title{
    position: relative;
    padding-left: 14px;          /* espacio para el puntito */
}
.fc .fc-daygrid-event .fc-event-title:before{
    content: '';
    position: absolute;
    left: 0; top: 50%;
    transform: translateY(-50%);
    width: 7px; height: 7px;
    border-radius: 50%;
    background: var(--uv-azul);
    box-shadow: 0 0 0 1px rgba(255,255,255,.8);
}
/* Separación entre eventos del mismo día */
.fc .fc-daygrid-day-events .fc-daygrid-event-harness{ margin-top: 4px; }

/* Hover sutil (no persiste) */
.fc .fc-daygrid-event:hover,
.fc .fc-timegrid-event:hover{
    filter: brightness(1.05);
    box-shadow: 0 0 0 2px rgba(255,255,255,.75) inset, 0 2px 6px rgba(0,0,0,.18);
    transform: translateY(-1px);
}

/* SEMANA / DÍA (timeGrid) — más limpio cuando se solapan */
.fc .fc-timegrid-event{
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid rgba(0,0,0,.08);
    background: #3b82f6;         /* azul legible sobre la grilla */
    color: #fff;
    box-shadow: 0 0 0 2px #fff inset, 0 1px 2px rgba(0,0,0,.12); /* borde interior blanco para separar solapes */
}
.fc .fc-timegrid-event .fc-event-main{ padding: 6px 8px; }
.fc .fc-timegrid-event .fc-event-title{
    display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical;
    overflow: hidden;
}
/* Popover de “+N más” agradable */
.fc-popover{ border-radius:.6rem; box-shadow:0 .75rem 1.5rem rgba(0,0,0,.18); }
</style>

<div class="calendar-page">
<div class="calendar-frame">
    <div id="calendar"></div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@fullcalendar/core/locales/es.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');

    const calendar = new FullCalendar.Calendar(calendarEl, {
    themeSystem: 'bootstrap5',
    locale: 'es',
    initialView: 'dayGridMonth',
    buttonText: { today: 'hoy', month: 'Mes', week: 'Semana', day: 'Día' },
    headerToolbar: {
        left: 'prev,next',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    /* MES: orden cuando hay varios eventos */
    dayMaxEventRows: true,
    views: { dayGridMonth: { dayMaxEventRows: 3 } },
    moreLinkClick: 'popover',

    /* SEMANA/DÍA: solapes más agradables */
    slotEventOverlap: true,
    nowIndicator: true,

    /* Mantén tu feed actual */
    events: '/obtener-eventos',

    /* Click: SweetAlert sin cambiar estilos permanentes */
    eventClick: function(info){
        info.jsEvent.preventDefault();
        const e = info.event;
        const start = e.start, end = e.end;
        const fFecha = start ? start.toLocaleDateString(undefined, { year:'numeric', month:'long', day:'numeric' }) : '';
        const fHoraI = start ? start.toLocaleTimeString(undefined, { hour:'numeric', minute:'numeric', hour12:true }) : '';
        const fHoraF = end   ? end.toLocaleTimeString(undefined, { hour:'numeric', minute:'numeric', hour12:true })   : '';
        const { lugar = '', espacio = '' } = e.extendedProps || {};
        Swal.fire({
        title: e.title || 'Evento',
        html: `
            ${fFecha ? '<p>Fecha de realización: ' + fFecha + '</p>' : ''}
            ${fHoraI ? '<p>Hora de inicio: ' + fHoraI + '</p>' : ''}
            ${fHoraF ? '<p>Hora de finalización: ' + fHoraF + '</p>' : ''}
            ${lugar   ? '<p>Lugar: ' + lugar + '</p>'   : ''}
            ${espacio ? '<p>Espacio: ' + espacio + '</p>' : ''}
        `,
        icon: 'info',
        confirmButtonText: 'Ok'
        });
    }
    });

    calendar.render();

    /* Ajuste de altura al viewport (opcional) */
    function resizeCalendar() {
    const nav = document.querySelector('.navbar');
    const navH = nav ? Math.ceil(nav.getBoundingClientRect().height) : 74;
    const available = window.innerHeight - navH - 80; // aire extra
    calendar.setOption('height', Math.max(available, 640));
    }
    resizeCalendar();
    window.addEventListener('resize', resizeCalendar);
});
</script>
@endpush
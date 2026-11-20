import { Link } from '@inertiajs/react';
import {
    Activity,
    ArrowRight,
    HeartPulse,
    PawPrint,
    Pill,
    TriangleAlert,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import PetAlert from '../PetAlert.jsx';
import { ExportPdfButton, MetricCard, formatDateTime, formatNumber } from '../../Reportes/Shared';

type ReportKey = 'ingresos' | 'ocupacion' | 'medicamentos' | 'resultados';

interface ReportLink {
    nombre: string;
    url: string;
}

interface ReportStatistics {
    mascotas_ingresadas: number;
    urgencias_registradas: number;
    egresos_registrados: number;
    medicamentos_administrados?: number;
    resultados_clinicos_registrados?: number;
}

interface Props {
    reportes: Partial<Record<ReportKey, ReportLink>>;
    estadisticas: ReportStatistics;
    fecha_consulta: string;
}

export default function ReportesIndex({ reportes, estadisticas, fecha_consulta }: Props) {
    const metrics = [
        {
            label: 'Mascotas ingresadas',
            value: estadisticas.mascotas_ingresadas,
            caption: 'En el mes actual',
            icon: PawPrint,
            tone: 'teal' as const,
        },
        {
            label: 'Urgencias registradas',
            value: estadisticas.urgencias_registradas,
            caption: 'En el mes actual',
            icon: TriangleAlert,
            tone: 'blue' as const,
        },
        ...(estadisticas.medicamentos_administrados !== undefined
            ? [{
                label: 'Medicamentos administrados',
                value: estadisticas.medicamentos_administrados,
                caption: 'Administraciones reales',
                icon: Pill,
                tone: 'violet' as const,
            }]
            : []),
        {
            label: 'Egresos registrados',
            value: estadisticas.egresos_registrados,
            caption: 'Salidas físicas registradas',
            icon: HeartPulse,
            tone: 'green' as const,
        },
    ];

    const cards: Array<{
        key: ReportKey;
        description: string;
        statistic: number;
        statisticLabel: string;
        icon: LucideIcon;
        tone: 'teal' | 'blue' | 'green' | 'violet';
    }> = [
        {
            key: 'ingresos',
            description: 'Consulta ingresos por día, semana o mes, especies y motivos frecuentes.',
            statistic: estadisticas.mascotas_ingresadas,
            statisticLabel: 'mascotas ingresadas este mes',
            icon: PawPrint,
            tone: 'teal',
        },
        {
            key: 'ocupacion',
            description: 'Revisa urgencias activas, egresos y tiempos de permanencia registrados.',
            statistic: estadisticas.egresos_registrados,
            statisticLabel: 'egresos físicos este mes',
            icon: Activity,
            tone: 'blue',
        },
        {
            key: 'medicamentos',
            description: 'Analiza administraciones realizadas, medicamentos, vías y responsables.',
            statistic: estadisticas.medicamentos_administrados ?? 0,
            statisticLabel: 'administraciones este mes',
            icon: Pill,
            tone: 'violet',
        },
        {
            key: 'resultados',
            description: 'Consulta los resultados clínicos guardados y su evolución temporal.',
            statistic: estadisticas.resultados_clinicos_registrados ?? 0,
            statisticLabel: 'resultados registrados este mes',
            icon: HeartPulse,
            tone: 'green',
        },
    ];

    return (
        <PetAlert
            reportContent={
                <div className="space-y-7">
                    <section className="flex flex-col gap-3 rounded-2xl bg-gradient-to-r from-[#102b38] to-[#1d5b62] p-5 text-white shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-7">
                        <div>
                            <p className="text-sm font-semibold text-teal-100">Panel de análisis veterinario</p>
                            <h2 className="mt-1 text-xl font-bold tracking-tight sm:text-2xl">Datos claros para mejores decisiones</h2>
                            <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-200">
                                Consulta información registrada por el equipo de Pet Alert Ipiales. Los indicadores del centro resumen el mes actual.
                            </p>
                        </div>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div className="flex shrink-0 items-center gap-3 rounded-xl border border-white/15 bg-white/10 px-4 py-3">
                                <span className="grid size-10 place-items-center rounded-lg bg-white/10 text-teal-100">
                                    <Activity size={21} aria-hidden="true" />
                                </span>
                                <span>
                                    <span className="block text-[11px] font-medium text-slate-200">Fecha de consulta</span>
                                    <strong className="mt-0.5 block text-sm">{formatDateTime(fecha_consulta)}</strong>
                                </span>
                            </div>
                            <ExportPdfButton url="/reports/pdf" fileName="centro-reportes.pdf" />
                        </div>
                    </section>

                    <section aria-label="Indicadores del mes actual" className={`grid gap-3 sm:grid-cols-2 ${metrics.length > 3 ? 'xl:grid-cols-4' : 'xl:grid-cols-3'}`}>
                        {metrics.map((metric) => (
                            <MetricCard key={metric.label} {...metric} />
                        ))}
                    </section>

                    <section>
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-bold text-[#102b38]">Reportes disponibles</h2>
                                <p className="mt-1 text-sm text-slate-500">Selecciona un área para explorar sus indicadores.</p>
                            </div>
                            <p className="text-xs font-medium text-slate-500">
                                {formatNumber(Object.keys(reportes).length)} reportes habilitados para tu rol
                            </p>
                        </div>
                        <div className="grid gap-4 md:grid-cols-2 2xl:grid-cols-4">
                            {cards.filter((card) => reportes[card.key]).map((card) => {
                                const Icon = card.icon;
                                const report = reportes[card.key]!;

                                return (
                                    <article key={card.key} className="flex min-h-64 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-teal-200 hover:shadow-md">
                                        <span className={`grid size-12 place-items-center rounded-xl ${
                                            card.tone === 'teal' ? 'bg-teal-50 text-teal-800'
                                                : card.tone === 'blue' ? 'bg-blue-50 text-blue-800'
                                                    : card.tone === 'violet' ? 'bg-violet-50 text-violet-800'
                                                        : 'bg-emerald-50 text-emerald-800'
                                        }`}>
                                            <Icon size={23} strokeWidth={1.8} aria-hidden="true" />
                                        </span>
                                        <h3 className="mt-4 text-base font-bold text-[#102b38]">{report.nombre}</h3>
                                        <p className="mt-2 flex-1 text-sm leading-6 text-slate-500">{card.description}</p>
                                        <div className="mt-4 border-t border-slate-100 pt-4">
                                            <p className="text-xl font-bold text-[#102b38]">{formatNumber(card.statistic)}</p>
                                            <p className="text-xs text-slate-500">{card.statisticLabel}</p>
                                        </div>
                                        <Link
                                            href={report.url}
                                            className="mt-4 inline-flex min-h-10 items-center justify-between rounded-xl bg-[#102b38] px-4 text-sm font-semibold text-white transition hover:bg-[#174653]"
                                        >
                                            Consultar reporte <ArrowRight size={17} aria-hidden="true" />
                                        </Link>
                                    </article>
                                );
                            })}
                        </div>
                    </section>
                </div>
            }
        />
    );
}

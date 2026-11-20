import { useEffect, useState, type FormEvent, type ReactNode } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { CalendarDays, Download, LoaderCircle } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

export type PeriodKey =
    | 'hoy'
    | 'ultimos_7_dias'
    | 'ultimos_30_dias'
    | 'mes_actual'
    | 'anio_actual'
    | 'personalizado';

export interface ReportFilters {
    periodo: PeriodKey;
    fecha_inicio: string;
    fecha_fin: string;
}

export interface SeriesPoint {
    periodo: string;
    total: number;
}

export interface Paginator<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

const periodOptions: Array<{ value: PeriodKey; label: string }> = [
    { value: 'hoy', label: 'Hoy' },
    { value: 'ultimos_7_dias', label: 'Últimos 7 días' },
    { value: 'ultimos_30_dias', label: 'Últimos 30 días' },
    { value: 'mes_actual', label: 'Mes actual' },
    { value: 'anio_actual', label: 'Año actual' },
    { value: 'personalizado', label: 'Personalizado' },
];

export function ReportFilter({
    action,
    filtros,
    exportUrl,
    exportFileName,
}: {
    action: string;
    filtros: ReportFilters;
    exportUrl: string;
    exportFileName: string;
}) {
    const form = useForm<ReportFilters>({
        periodo: filtros.periodo,
        fecha_inicio: filtros.periodo === 'personalizado' ? filtros.fecha_inicio : '',
        fecha_fin: filtros.periodo === 'personalizado' ? filtros.fecha_fin : '',
    });

    useEffect(() => {
        form.setData({
            periodo: filtros.periodo,
            fecha_inicio: filtros.periodo === 'personalizado' ? filtros.fecha_inicio : '',
            fecha_fin: filtros.periodo === 'personalizado' ? filtros.fecha_fin : '',
        });
    }, [filtros.fecha_fin, filtros.fecha_inicio, filtros.periodo]);

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.get(action, {
            preserveScroll: true,
            preserveState: true,
        });
    }

    function updatePeriod(value: string) {
        if (!periodOptions.some((option) => option.value === value)) {
            return;
        }

        const period = value as PeriodKey;
        form.setData('periodo', period);
        form.clearErrors();

        if (period !== 'personalizado') {
            form.setData('fecha_inicio', '');
            form.setData('fecha_fin', '');
        }
    }

    const selectedPeriod = periodOptions.find((option) => option.value === filtros.periodo)?.label
        ?? filtros.periodo;
    const validationMessage = form.errors.periodo
        ?? form.errors.fecha_inicio
        ?? form.errors.fecha_fin;

    return (
        <form
            onSubmit={submit}
            className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5"
        >
            <div className="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div className="grid flex-1 gap-4 sm:grid-cols-2 xl:max-w-3xl">
                    <label className="flex flex-col gap-1.5 text-sm font-semibold text-slate-700">
                        Período
                        <select
                            value={form.data.periodo}
                            onChange={(event) => updatePeriod(event.currentTarget.value)}
                            className="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-800"
                        >
                            {periodOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                    </label>

                    {form.data.periodo === 'personalizado' && (
                        <>
                            <label className="flex flex-col gap-1.5 text-sm font-semibold text-slate-700">
                                Fecha inicial
                                <input
                                    type="date"
                                    value={form.data.fecha_inicio}
                                    onChange={(event) => form.setData('fecha_inicio', event.currentTarget.value)}
                                    className="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-800"
                                    required
                                />
                            </label>
                            <label className="flex flex-col gap-1.5 text-sm font-semibold text-slate-700">
                                Fecha final
                                <input
                                    type="date"
                                    value={form.data.fecha_fin}
                                    onChange={(event) => form.setData('fecha_fin', event.currentTarget.value)}
                                    className="min-h-11 rounded-xl border border-slate-300 bg-white px-3 text-sm font-medium text-slate-800"
                                    required
                                />
                            </label>
                        </>
                    )}
                </div>

                <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <Link
                        href="/reports"
                        className="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    >
                        Centro de reportes
                    </Link>
                    <p className="inline-flex items-center gap-2 text-xs text-slate-500" aria-live="polite">
                        {form.processing
                            ? <LoaderCircle size={16} className="animate-spin text-teal-700" aria-hidden="true" />
                            : <CalendarDays size={16} className="text-teal-700" aria-hidden="true" />}
                        {form.processing
                            ? 'Actualizando reporte…'
                            : `Período consultado: ${selectedPeriod} · ${filtros.fecha_inicio} — ${filtros.fecha_fin}`}
                    </p>
                    <ExportPdfButton
                        url={exportUrl}
                        fileName={exportFileName}
                        filtros={form.data}
                    />
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-[#102b38] px-4 text-sm font-semibold text-white transition hover:bg-[#174653] disabled:cursor-wait disabled:opacity-70"
                    >
                        {form.processing && <LoaderCircle size={16} className="animate-spin" aria-hidden="true" />}
                        {form.processing ? 'Cargando…' : 'Aplicar filtros'}
                    </button>
                </div>
            </div>
            {validationMessage && (
                <p className="mt-3 text-sm font-medium text-rose-700" role="alert">{validationMessage}</p>
            )}
        </form>
    );
}

export function ExportPdfButton({
    url,
    fileName,
    filtros,
}: {
    url: string;
    fileName: string;
    filtros?: ReportFilters;
}) {
    const [processing, setProcessing] = useState(false);
    const [error, setError] = useState('');

    async function downloadPdf() {
        setProcessing(true);
        setError('');

        try {
            const target = new URL(url, window.location.origin);

            if (filtros) {
                target.searchParams.set('periodo', filtros.periodo);

                if (filtros.periodo === 'personalizado') {
                    target.searchParams.set('fecha_inicio', filtros.fecha_inicio);
                    target.searchParams.set('fecha_fin', filtros.fecha_fin);
                }
            }

            const response = await fetch(target.toString(), {
                headers: { Accept: 'application/pdf' },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                setError(response.status === 422
                    ? 'Revisa las fechas seleccionadas antes de exportar.'
                    : response.status === 403
                        ? 'No tienes permiso para exportar este reporte.'
                        : 'No fue posible generar el PDF. Intenta nuevamente.');
                return;
            }

            if (!response.headers.get('content-type')?.includes('application/pdf')) {
                setError('La respuesta del servidor no es un documento PDF.');
                return;
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = objectUrl;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
        } catch {
            setError('Ocurrió un problema de conexión al exportar. Intenta nuevamente.');
        } finally {
            setProcessing(false);
        }
    }

    return (
        <div className="flex flex-col gap-1">
            <button
                type="button"
                onClick={downloadPdf}
                disabled={processing}
                aria-busy={processing}
                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-teal-700 bg-white px-4 text-sm font-semibold text-teal-800 transition hover:bg-teal-50 disabled:cursor-wait disabled:opacity-70"
            >
                {processing
                    ? <LoaderCircle size={16} className="animate-spin" aria-hidden="true" />
                    : <Download size={16} aria-hidden="true" />}
                {processing ? 'Generando PDF…' : 'Exportar PDF'}
            </button>
            {error && <p className="max-w-52 rounded-lg bg-white px-2 py-1 text-xs text-rose-700 shadow-sm" role="alert">{error}</p>}
        </div>
    );
}

export function MetricCard({
    label,
    value,
    caption,
    icon: Icon,
    tone = 'teal',
}: {
    label: string;
    value: number | string;
    caption?: string;
    icon: LucideIcon;
    tone?: 'teal' | 'blue' | 'green' | 'navy' | 'violet';
}) {
    const toneClasses = {
        teal: 'bg-teal-50 text-teal-800 ring-teal-100',
        blue: 'bg-blue-50 text-blue-800 ring-blue-100',
        green: 'bg-emerald-50 text-emerald-800 ring-emerald-100',
        navy: 'bg-slate-100 text-slate-800 ring-slate-200',
        violet: 'bg-violet-50 text-violet-800 ring-violet-100',
    };

    return (
        <article className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-medium text-slate-500">{label}</p>
                    <p className="mt-2 text-2xl font-bold tracking-tight text-[#102b38]">
                        {typeof value === 'number' ? formatNumber(value) : value}
                    </p>
                    {caption && <p className="mt-1 text-xs text-slate-500">{caption}</p>}
                </div>
                <span className={`grid size-11 shrink-0 place-items-center rounded-xl ring-1 ${toneClasses[tone]}`}>
                    <Icon size={21} strokeWidth={1.8} aria-hidden="true" />
                </span>
            </div>
        </article>
    );
}

export function ChartPanel({
    title,
    description,
    empty,
    children,
}: {
    title: string;
    description?: string;
    empty: boolean;
    children: ReactNode;
}) {
    return (
        <section className="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
            <header className="mb-4">
                <h2 className="text-base font-bold text-[#102b38]">{title}</h2>
                {description && <p className="mt-1 text-xs leading-5 text-slate-500">{description}</p>}
            </header>
            {empty ? (
                <EmptyState title="Sin registros para mostrar" />
            ) : (
                <div className="h-72 min-w-0">{children}</div>
            )}
        </section>
    );
}

export function EmptyState({ title, description = 'Prueba con otro período o vuelve a consultar más adelante.' }: {
    title: string;
    description?: string;
}) {
    return (
        <div className="flex min-h-44 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-5 py-8 text-center">
            <span className="mb-3 grid size-10 place-items-center rounded-full bg-teal-50 text-teal-800">
                <CalendarDays size={19} aria-hidden="true" />
            </span>
            <p className="text-sm font-semibold text-slate-700">{title}</p>
            <p className="mt-1 max-w-md text-xs leading-5 text-slate-500">{description}</p>
        </div>
    );
}

export function Pagination<T>({ paginator }: { paginator: Paginator<T> }) {
    if (paginator.last_page <= 1) {
        return null;
    }

    return (
        <nav className="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between" aria-label="Paginación">
            <p className="text-xs text-slate-500">
                {formatNumber(paginator.total)} registros · Página {paginator.current_page} de {paginator.last_page}
            </p>
            <div className="flex flex-wrap gap-1">
                {paginator.links.map((link, index) => {
                    const label = link.label
                        .replace('&laquo;', '‹')
                        .replace('&raquo;', '›')
                        .replace('Previous', 'Anterior')
                        .replace('Next', 'Siguiente');

                    return link.url ? (
                        <Link
                            key={`${link.label}-${index}`}
                            href={link.url}
                            preserveScroll
                            className={`grid min-h-9 min-w-9 place-items-center rounded-lg border px-3 text-xs font-semibold transition ${
                                link.active
                                    ? 'border-teal-700 bg-teal-700 text-white'
                                    : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'
                            }`}
                            aria-current={link.active ? 'page' : undefined}
                        >
                            {label}
                        </Link>
                    ) : (
                        <span
                            key={`${link.label}-${index}`}
                            className="grid min-h-9 min-w-9 place-items-center rounded-lg border border-slate-100 px-3 text-xs text-slate-300"
                        >
                            {label}
                        </span>
                    );
                })}
            </div>
        </nav>
    );
}

export function formatNumber(value: number | string): string {
    return new Intl.NumberFormat('es-CO').format(Number(value));
}

export function formatPeriod(value: string): string {
    const week = value.match(/^(\d{4})-W(\d{2})$/);
    if (week) {
        return `Sem. ${week[2]} · ${week[1]}`;
    }

    const month = value.match(/^(\d{4})-(\d{2})$/);
    if (month) {
        const date = new Date(Date.UTC(Number(month[1]), Number(month[2]) - 1, 1));
        return new Intl.DateTimeFormat('es-CO', {
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(date);
    }

    const date = value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return date ? `${date[3]}/${date[2]}` : value;
}

export function formatDateTime(value: string | null): string {
    if (!value) {
        return 'Sin egreso';
    }

    const [date, time = ''] = value.replace('T', ' ').split(' ');
    const formattedDate = date.split('-').reverse().join('/');

    return time ? `${formattedDate} · ${time.slice(0, 5)}` : formattedDate;
}

export function formatDuration(minutes: number | string): string {
    const totalMinutes = Math.max(0, Math.round(Number(minutes)));
    const days = Math.floor(totalMinutes / 1440);
    const hours = Math.floor((totalMinutes % 1440) / 60);
    const remainingMinutes = totalMinutes % 60;

    if (days > 0) {
        return `${days} d ${hours} h`;
    }

    if (hours > 0) {
        return `${hours} h ${remainingMinutes} min`;
    }

    return `${remainingMinutes} min`;
}

export function mergeSeries(left: SeriesPoint[], right: SeriesPoint[]) {
    const points = new Map<string, { periodo: string; ingresos: number; egresos: number }>();

    left.forEach((point) => {
        points.set(point.periodo, {
            periodo: point.periodo,
            ingresos: point.total,
            egresos: 0,
        });
    });

    right.forEach((point) => {
        const existing = points.get(point.periodo) ?? {
            periodo: point.periodo,
            ingresos: 0,
            egresos: 0,
        };
        points.set(point.periodo, { ...existing, egresos: point.total });
    });

    return [...points.values()].sort((first, second) => first.periodo.localeCompare(second.periodo));
}

export function statusLabel(status: string): string {
    const labels: Record<string, string> = {
        pendiente: 'Pendiente',
        en_atencion: 'En atención',
        en_observacion: 'En observación',
        finalizada: 'Finalizada',
        cancelada: 'Cancelada',
    };

    return labels[status] ?? status;
}

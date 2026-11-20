import PetAlert from '../PetAlert.jsx';
import { Activity, Clock3, LogOut, PawPrint } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Legend,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import {
    ChartPanel,
    EmptyState,
    MetricCard,
    Pagination,
    ReportFilter,
    formatDateTime,
    formatDuration,
    formatNumber,
    formatPeriod,
    mergeSeries,
    statusLabel,
    type Paginator,
    type ReportFilters,
    type SeriesPoint,
} from '../../Reportes/Shared';

interface StayRecord {
    id: number;
    mascota_id: number;
    estado: string;
    fecha_ingreso: string;
    fecha_egreso: string;
    permanencia_minutos: number;
}

interface OpenCase {
    id: number;
    mascota_id: number;
    estado: string;
    fecha_ingreso: string;
}

interface OccupancyData {
    resumen: {
        total_urgencias: number;
        mascotas_con_urgencias_activas: number;
        egresos_registrados: number;
        mascotas_con_egreso_registrado: number;
        casos_sin_egreso: number;
        activas_sin_egreso: number;
        finalizadas_sin_egreso: number;
        promedio_permanencia_minutos: number;
        maximo_permanencia_minutos: number;
    };
    ingresos_y_egresos: {
        por_mes: {
            ingresos: SeriesPoint[];
            egresos: SeriesPoint[];
        };
    };
    distribucion_permanencia_dias: Array<{ dias_completos: number; casos: number }>;
    estancias_registradas: Paginator<StayRecord>;
    casos_sin_egreso: Paginator<OpenCase>;
}

interface Props {
    filtros: ReportFilters;
    datos: OccupancyData;
}

export default function ReporteOcupacion({ filtros, datos }: Props) {
    const monthlySeries = mergeSeries(
        datos.ingresos_y_egresos.por_mes.ingresos,
        datos.ingresos_y_egresos.por_mes.egresos,
    );

    return (
        <PetAlert
            reportContent={
                <div className="space-y-5">
                    <ReportFilter
                        action="/reports/ocupacion"
                        filtros={filtros}
                        exportUrl="/reports/ocupacion/pdf"
                        exportFileName="reporte-ocupacion.pdf"
                    />

                    <div className="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
                        Los casos activos se identifican por su estado vigente; no se clasifican automáticamente como hospitalizaciones.
                    </div>

                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen de ocupación y permanencia">
                        <MetricCard
                            label="Mascotas con urgencias activas"
                            value={datos.resumen.mascotas_con_urgencias_activas}
                            caption="Casos en estados activos"
                            icon={Activity}
                            tone="blue"
                        />
                        <MetricCard
                            label="Egresos registrados"
                            value={datos.resumen.egresos_registrados}
                            caption="Salidas físicas registradas"
                            icon={LogOut}
                            tone="green"
                        />
                        <MetricCard
                            label="Permanencia promedio"
                            value={formatDuration(datos.resumen.promedio_permanencia_minutos)}
                            caption="Casos con egreso válido"
                            icon={Clock3}
                            tone="teal"
                        />
                        <MetricCard
                            label="Permanencia máxima"
                            value={formatDuration(datos.resumen.maximo_permanencia_minutos)}
                            caption="Casos con egreso válido"
                            icon={PawPrint}
                            tone="violet"
                        />
                    </section>

                    <section className="grid min-w-0 gap-4 xl:grid-cols-2">
                        <ChartPanel
                            title="Ingresos y egresos por mes"
                            description="Los ingresos se agrupan por fecha de ingreso y los egresos por fecha de salida física."
                            empty={monthlySeries.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={monthlySeries} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" />
                                    <XAxis dataKey="periodo" tickFormatter={(value) => formatPeriod(String(value))} tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => formatPeriod(String(value))}
                                        formatter={(value, name) => [formatNumber(String(value ?? 0)), String(name)]}
                                    />
                                    <Legend />
                                    <Line type="monotone" dataKey="ingresos" name="Ingresos" stroke="#2563eb" strokeWidth={3} dot={{ r: 3 }} />
                                    <Line type="monotone" dataKey="egresos" name="Egresos" stroke="#0f766e" strokeWidth={3} dot={{ r: 3 }} />
                                </LineChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Distribución de permanencias"
                            description="Duración en días completos para urgencias con fecha de egreso registrada."
                            empty={datos.distribucion_permanencia_dias.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={datos.distribucion_permanencia_dias}
                                    margin={{ top: 8, right: 12, bottom: 4, left: -18 }}
                                >
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" vertical={false} />
                                    <XAxis
                                        dataKey="dias_completos"
                                        tickFormatter={(value) => `${value} d`}
                                        tick={{ fontSize: 11 }}
                                    />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => `${value} días completos`}
                                        formatter={(value) => [formatNumber(String(value ?? 0)), 'Casos']}
                                    />
                                    <Legend />
                                    <Bar dataKey="casos" name="Casos" fill="#0f766e" radius={[7, 7, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>
                    </section>

                    <section className="grid gap-4 2xl:grid-cols-2">
                        <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                            <div className="mb-4">
                                <h2 className="text-base font-bold text-[#102b38]">Estancias con egreso</h2>
                                <p className="mt-1 text-xs leading-5 text-slate-500">Duración calculada con ingreso y egreso físico registrados.</p>
                            </div>
                            {datos.estancias_registradas.data.length === 0 ? (
                                <EmptyState title="Sin estancias con egreso" />
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[640px] text-left text-sm">
                                            <thead className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th className="px-3 py-3 font-semibold">Mascota</th>
                                                    <th className="px-3 py-3 font-semibold">Ingreso</th>
                                                    <th className="px-3 py-3 font-semibold">Egreso</th>
                                                    <th className="px-3 py-3 font-semibold">Permanencia</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {datos.estancias_registradas.data.map((stay) => (
                                                    <tr key={stay.id} className="text-slate-700">
                                                        <td className="px-3 py-3">
                                                            <span className="font-semibold text-[#102b38]">Mascota #{stay.mascota_id}</span>
                                                            <span className="mt-0.5 block text-xs text-slate-500">{statusLabel(stay.estado)}</span>
                                                        </td>
                                                        <td className="px-3 py-3 text-xs">{formatDateTime(stay.fecha_ingreso)}</td>
                                                        <td className="px-3 py-3 text-xs">{formatDateTime(stay.fecha_egreso)}</td>
                                                        <td className="px-3 py-3 font-semibold">{formatDuration(stay.permanencia_minutos)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    <div className="mt-4"><Pagination paginator={datos.estancias_registradas} /></div>
                                </>
                            )}
                        </div>

                        <div className="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                            <div className="mb-4">
                                <h2 className="text-base font-bold text-[#102b38]">Casos sin egreso registrado</h2>
                                <p className="mt-1 text-xs leading-5 text-slate-500">
                                    {formatNumber(datos.resumen.activas_sin_egreso)} activos y {formatNumber(datos.resumen.finalizadas_sin_egreso)} finalizados sin egreso físico.
                                </p>
                            </div>
                            {datos.casos_sin_egreso.data.length === 0 ? (
                                <EmptyState title="No hay casos sin egreso" />
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[500px] text-left text-sm">
                                            <thead className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th className="px-3 py-3 font-semibold">Mascota</th>
                                                    <th className="px-3 py-3 font-semibold">Estado</th>
                                                    <th className="px-3 py-3 font-semibold">Fecha de ingreso</th>
                                                    <th className="px-3 py-3 font-semibold">Egreso</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-slate-100">
                                                {datos.casos_sin_egreso.data.map((item) => (
                                                    <tr key={item.id} className="text-slate-700">
                                                        <td className="px-3 py-3 font-semibold text-[#102b38]">Mascota #{item.mascota_id}</td>
                                                        <td className="px-3 py-3">{statusLabel(item.estado)}</td>
                                                        <td className="px-3 py-3 text-xs">{formatDateTime(item.fecha_ingreso)}</td>
                                                        <td className="px-3 py-3 text-xs font-medium text-amber-700">Sin egreso</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    <div className="mt-4"><Pagination paginator={datos.casos_sin_egreso} /></div>
                                </>
                            )}
                        </div>
                    </section>
                </div>
            }
        />
    );
}

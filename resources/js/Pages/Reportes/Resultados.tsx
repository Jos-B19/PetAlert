import PetAlert from '../PetAlert.jsx';
import { Activity, HeartPulse, PawPrint, Stethoscope } from 'lucide-react';
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
    ReportFilter,
    formatNumber,
    formatPeriod,
    type ReportFilters,
    type SeriesPoint,
} from '../../Reportes/Shared';

interface OutcomePoint {
    resultado: string;
    evoluciones: number;
    mascotas: number;
    porcentaje: number;
}

interface UrgencyStatusPoint {
    estado: string;
    total: number;
}

interface OutcomeData {
    resumen: {
        resultados_clinicos_registrados: number;
        recuperaciones_favorables: number;
        continuan_en_tratamiento: number;
        requieren_seguimiento: number;
        remisiones: number;
    };
    resultados_registrados: OutcomePoint[];
    distribucion_por_mes: SeriesPoint[];
    urgencias_por_estado: UrgencyStatusPoint[];
}

interface Props {
    filtros: ReportFilters;
    datos: OutcomeData;
}

export default function ReporteResultados({ filtros, datos }: Props) {
    const metrics = [
        {
            label: 'Resultados registrados',
            value: datos.resumen.resultados_clinicos_registrados,
            caption: 'Evoluciones clínicas con resultado',
            icon: Activity,
            tone: 'navy' as const,
        },
        {
            label: 'Recuperación favorable',
            value: datos.resumen.recuperaciones_favorables,
            caption: 'Mascotas con este estado registrado',
            icon: HeartPulse,
            tone: 'green' as const,
        },
        {
            label: 'Continúan en tratamiento',
            value: datos.resumen.continuan_en_tratamiento,
            caption: 'Mascotas con este estado registrado',
            icon: Stethoscope,
            tone: 'blue' as const,
        },
        {
            label: 'Requieren seguimiento',
            value: datos.resumen.requieren_seguimiento,
            caption: 'Mascotas con este estado registrado',
            icon: PawPrint,
            tone: 'teal' as const,
        },
    ];

    return (
        <PetAlert
            reportContent={
                <div className="space-y-5">
                    <ReportFilter
                        action="/reports/resultados"
                        filtros={filtros}
                        exportUrl="/reports/resultados/pdf"
                        exportFileName="reporte-resultados.pdf"
                    />

                    <div className="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-950">
                        Los resultados muestran únicamente estados registrados en evoluciones clínicas. Un estado favorable no implica que exista un alta médica o egreso físico.
                    </div>

                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen de resultados clínicos">
                        {metrics.map((metric) => <MetricCard key={metric.label} {...metric} />)}
                    </section>

                    <section className="grid min-w-0 gap-4 xl:grid-cols-2">
                        <ChartPanel
                            title="Distribución de resultados clínicos"
                            description="Porcentaje calculado por registros de evolución; se muestran también las mascotas asociadas."
                            empty={datos.resultados_registrados.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={datos.resultados_registrados}
                                    layout="vertical"
                                    margin={{ top: 4, right: 24, bottom: 4, left: 10 }}
                                >
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" horizontal={false} />
                                    <XAxis type="number" domain={[0, 100]} unit="%" tick={{ fontSize: 11 }} />
                                    <YAxis type="category" dataKey="resultado" width={150} tick={{ fontSize: 10 }} />
                                    <Tooltip
                                        formatter={(value) => [`${formatNumber(String(value ?? 0))}%`, 'Porcentaje']}
                                        labelFormatter={(value) => String(value)}
                                    />
                                    <Legend />
                                    <Bar dataKey="porcentaje" name="Porcentaje" fill="#0f766e" radius={[0, 7, 7, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Resultados por mes"
                            description="Cantidad de evoluciones con un estado clínico registrado."
                            empty={datos.distribucion_por_mes.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={datos.distribucion_por_mes} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" />
                                    <XAxis dataKey="periodo" tickFormatter={(value) => formatPeriod(String(value))} tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => formatPeriod(String(value))}
                                        formatter={(value) => [formatNumber(String(value ?? 0)), 'Resultados']}
                                    />
                                    <Legend />
                                    <Line type="monotone" dataKey="total" name="Resultados" stroke="#2563eb" strokeWidth={3} dot={{ r: 3 }} />
                                </LineChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Urgencias por estado"
                            description="Estados de urgencia registrados en el período; no representan por sí solos un resultado clínico."
                            empty={datos.urgencias_por_estado.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={datos.urgencias_por_estado} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" vertical={false} />
                                    <XAxis dataKey="estado" tick={{ fontSize: 10 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip formatter={(value) => [formatNumber(String(value ?? 0)), 'Urgencias']} />
                                    <Legend />
                                    <Bar dataKey="total" name="Urgencias" fill="#0891b2" radius={[7, 7, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div className="mb-4">
                            <h2 className="text-base font-bold text-[#102b38]">Resultados registrados</h2>
                            <p className="mt-1 text-xs leading-5 text-slate-500">Conteos por estado clínico existente en las evoluciones.</p>
                        </div>
                        {datos.resultados_registrados.length === 0 ? (
                            <EmptyState title="No hay resultados clínicos registrados" />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[560px] text-left text-sm">
                                    <thead className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                                        <tr>
                                            <th className="px-3 py-3 font-semibold">Resultado registrado</th>
                                            <th className="px-3 py-3 font-semibold">Evoluciones</th>
                                            <th className="px-3 py-3 font-semibold">Mascotas</th>
                                            <th className="px-3 py-3 font-semibold">Porcentaje</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-100">
                                        {datos.resultados_registrados.map((result) => (
                                            <tr key={result.resultado} className="text-slate-700">
                                                <td className="px-3 py-3 font-semibold text-[#102b38]">{result.resultado}</td>
                                                <td className="px-3 py-3">{formatNumber(result.evoluciones)}</td>
                                                <td className="px-3 py-3">{formatNumber(result.mascotas)}</td>
                                                <td className="px-3 py-3 font-semibold text-teal-800">{formatNumber(result.porcentaje)}%</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </section>
                </div>
            }
        />
    );
}

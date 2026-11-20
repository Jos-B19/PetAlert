import PetAlert from '../PetAlert.jsx';
import { ClipboardList, Pill, Stethoscope, Syringe } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
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
    formatNumber,
    formatPeriod,
    type Paginator,
    type ReportFilters,
    type SeriesPoint,
} from '../../Reportes/Shared';

interface MedicationPoint {
    medicamento_id: number;
    medicamento: string;
    presentacion: string;
    total_administraciones: number;
}

interface VeterinarianPoint {
    veterinario_id: number;
    veterinario: string;
    total_administraciones: number;
}

interface RoutePoint {
    via_administracion: string;
    total_administraciones: number;
}

interface Administration {
    id: number;
    atencion_medicamento_id: number;
    fecha_hora: string;
    medicamento: string;
    presentacion: string;
    dosis_administrada: string;
    cantidad_administrada: number;
    via_administracion: string;
    atencion_id: number;
    veterinario_id: number;
    veterinario: string;
}

interface MedicationData {
    resumen: {
        total_administraciones: number;
    };
    medicamentos_mas_utilizados: MedicationPoint[];
    administraciones_por_mes: SeriesPoint[];
    administraciones_por_veterinario: VeterinarianPoint[];
    distribucion_por_via: RoutePoint[];
    historial: Paginator<Administration>;
}

interface Props {
    filtros: ReportFilters;
    datos: MedicationData;
}

const chartColors = ['#0f766e', '#2563eb', '#06b6d4', '#84cc16', '#8b5cf6', '#f59e0b'];

export default function ReporteMedicamentos({ filtros, datos }: Props) {
    return (
        <PetAlert
            reportContent={
                <div className="space-y-5">
                    <ReportFilter
                        action="/reports/medicamentos"
                        filtros={filtros}
                        exportUrl="/reports/medicamentos/pdf"
                        exportFileName="reporte-medicamentos.pdf"
                    />

                    <div className="flex items-start gap-3 rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm text-teal-950">
                        <Pill size={18} className="mt-0.5 shrink-0 text-teal-800" aria-hidden="true" />
                        <p>
                            Estas estadísticas cuentan únicamente administraciones registradas. Una indicación o prescripción, por sí sola, no se considera un medicamento administrado.
                        </p>
                    </div>

                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen de medicamentos">
                        <MetricCard
                            label="Administraciones realizadas"
                            value={datos.resumen.total_administraciones}
                            caption="Registros de administración"
                            icon={Syringe}
                            tone="teal"
                        />
                        <MetricCard
                            label="Medicamentos destacados"
                            value={datos.medicamentos_mas_utilizados.length}
                            caption="Hasta 10 en el resumen"
                            icon={Pill}
                            tone="blue"
                        />
                        <MetricCard
                            label="Veterinarios registrados"
                            value={datos.administraciones_por_veterinario.length}
                            caption="Con administraciones"
                            icon={Stethoscope}
                            tone="green"
                        />
                        <MetricCard
                            label="Vías registradas"
                            value={datos.distribucion_por_via.length}
                            caption="En las administraciones"
                            icon={ClipboardList}
                            tone="violet"
                        />
                    </section>

                    <section className="grid min-w-0 gap-4 xl:grid-cols-2">
                        <ChartPanel
                            title="Medicamentos más administrados"
                            description="Conteo de administraciones efectivamente registradas por medicamento."
                            empty={datos.medicamentos_mas_utilizados.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={datos.medicamentos_mas_utilizados}
                                    layout="vertical"
                                    margin={{ top: 4, right: 20, bottom: 4, left: 8 }}
                                >
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" horizontal={false} />
                                    <XAxis type="number" allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <YAxis type="category" dataKey="medicamento" width={130} tick={{ fontSize: 10 }} />
                                    <Tooltip formatter={(value) => [formatNumber(String(value ?? 0)), 'Administraciones']} />
                                    <Legend />
                                    <Bar dataKey="total_administraciones" name="Administraciones" fill="#0f766e" radius={[0, 7, 7, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Administraciones por mes"
                            description="Tendencia mensual según la fecha y hora de administración."
                            empty={datos.administraciones_por_mes.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={datos.administraciones_por_mes} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" />
                                    <XAxis dataKey="periodo" tickFormatter={(value) => formatPeriod(String(value))} tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => formatPeriod(String(value))}
                                        formatter={(value) => [formatNumber(String(value ?? 0)), 'Administraciones']}
                                    />
                                    <Legend />
                                    <Line type="monotone" dataKey="total" name="Administraciones" stroke="#2563eb" strokeWidth={3} dot={{ r: 3 }} />
                                </LineChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Administraciones por veterinario"
                            description="Responsable que registró la administración."
                            empty={datos.administraciones_por_veterinario.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={datos.administraciones_por_veterinario}
                                    layout="vertical"
                                    margin={{ top: 4, right: 20, bottom: 4, left: 8 }}
                                >
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" horizontal={false} />
                                    <XAxis type="number" allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <YAxis type="category" dataKey="veterinario" width={130} tick={{ fontSize: 10 }} />
                                    <Tooltip formatter={(value) => [formatNumber(String(value ?? 0)), 'Administraciones']} />
                                    <Legend />
                                    <Bar dataKey="total_administraciones" name="Administraciones" fill="#2563eb" radius={[0, 7, 7, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Vías de administración"
                            description="Distribución según la vía registrada en cada administración."
                            empty={datos.distribucion_por_via.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={datos.distribucion_por_via}
                                        dataKey="total_administraciones"
                                        nameKey="via_administracion"
                                        cx="50%"
                                        cy="48%"
                                        innerRadius={48}
                                        outerRadius={90}
                                        paddingAngle={3}
                                    >
                                        {datos.distribucion_por_via.map((item, index) => (
                                            <Cell key={item.via_administracion} fill={chartColors[index % chartColors.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => [formatNumber(String(value ?? 0)), 'Administraciones']} />
                                    <Legend verticalAlign="bottom" height={32} />
                                </PieChart>
                            </ResponsiveContainer>
                        </ChartPanel>
                    </section>

                    <section className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div className="mb-4">
                            <h2 className="text-base font-bold text-[#102b38]">Historial de administraciones</h2>
                            <p className="mt-1 text-xs leading-5 text-slate-500">Detalle paginado con medicamento, dosis, vía, fecha y responsable.</p>
                        </div>
                        {datos.historial.data.length === 0 ? (
                            <EmptyState title="No hay administraciones registradas" />
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full min-w-[880px] text-left text-sm">
                                        <thead className="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500">
                                            <tr>
                                                <th className="px-3 py-3 font-semibold">Fecha</th>
                                                <th className="px-3 py-3 font-semibold">Medicamento</th>
                                                <th className="px-3 py-3 font-semibold">Dosis / cantidad</th>
                                                <th className="px-3 py-3 font-semibold">Vía</th>
                                                <th className="px-3 py-3 font-semibold">Veterinario</th>
                                                <th className="px-3 py-3 font-semibold">Atención</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-100">
                                            {datos.historial.data.map((administration) => (
                                                <tr key={administration.id} className="text-slate-700">
                                                    <td className="whitespace-nowrap px-3 py-3 text-xs">{formatDateTime(administration.fecha_hora)}</td>
                                                    <td className="px-3 py-3">
                                                        <span className="font-semibold text-[#102b38]">{administration.medicamento}</span>
                                                        <span className="mt-0.5 block text-xs text-slate-500">{administration.presentacion}</span>
                                                    </td>
                                                    <td className="px-3 py-3">
                                                        <span className="font-medium">{administration.dosis_administrada}</span>
                                                        <span className="mt-0.5 block text-xs text-slate-500">Cantidad: {formatNumber(administration.cantidad_administrada)}</span>
                                                    </td>
                                                    <td className="px-3 py-3">{administration.via_administracion}</td>
                                                    <td className="px-3 py-3">{administration.veterinario}</td>
                                                    <td className="px-3 py-3 text-slate-500">#{administration.atencion_id}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="mt-4"><Pagination paginator={datos.historial} /></div>
                            </>
                        )}
                    </section>
                </div>
            }
        />
    );
}

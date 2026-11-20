import PetAlert from '../PetAlert.jsx';
import {
    Activity,
    CalendarDays,
    PawPrint,
    TriangleAlert,
} from 'lucide-react';
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
    MetricCard,
    ReportFilter,
    formatNumber,
    formatPeriod,
    type ReportFilters,
    type SeriesPoint,
} from '../../Reportes/Shared';

interface SpeciesPoint {
    especie: string;
    total: number;
}

interface ReasonPoint {
    motivo: string;
    total: number;
}

interface IncomeData {
    resumen: {
        total_urgencias: number;
        ingresos_hoy: number;
        ingresos_ultimos_7_dias: number;
        ingresos_mes_actual: number;
    };
    ingresos_por_dia: SeriesPoint[];
    ingresos_por_semana: SeriesPoint[];
    ingresos_por_mes: SeriesPoint[];
    distribucion_por_especie: SpeciesPoint[];
    motivos_frecuentes: ReasonPoint[];
}

interface Props {
    filtros: ReportFilters;
    datos: IncomeData;
}

const chartColors = ['#0f766e', '#2563eb', '#06b6d4', '#84cc16', '#8b5cf6', '#f59e0b'];

export default function ReporteIngresos({ filtros, datos }: Props) {
    const stats = [
        { label: 'Urgencias registradas', value: datos.resumen.total_urgencias, icon: TriangleAlert, tone: 'navy' as const },
        { label: 'Ingresos hoy', value: datos.resumen.ingresos_hoy, icon: CalendarDays, tone: 'teal' as const },
        { label: 'Últimos 7 días', value: datos.resumen.ingresos_ultimos_7_dias, icon: Activity, tone: 'blue' as const },
        { label: 'Mes actual', value: datos.resumen.ingresos_mes_actual, icon: PawPrint, tone: 'green' as const },
    ];

    return (
        <PetAlert
            reportContent={
                <div className="space-y-5">
                    <ReportFilter
                        action="/reports/ingresos"
                        filtros={filtros}
                        exportUrl="/reports/ingresos/pdf"
                        exportFileName="reporte-ingresos.pdf"
                    />

                    <section className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Resumen de ingresos">
                        {stats.map((stat) => <MetricCard key={stat.label} {...stat} />)}
                    </section>

                    <section className="grid min-w-0 gap-4 xl:grid-cols-2">
                        <ChartPanel
                            title="Tendencia diaria de ingresos"
                            description="Urgencias registradas por fecha de ingreso."
                            empty={datos.ingresos_por_dia.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={datos.ingresos_por_dia} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" />
                                    <XAxis dataKey="periodo" tickFormatter={(value) => formatPeriod(String(value))} tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => formatPeriod(String(value))}
                                        formatter={(value) => [formatNumber(String(value ?? 0)), 'Ingresos']}
                                    />
                                    <Legend />
                                    <Line type="monotone" dataKey="total" name="Ingresos" stroke="#0f766e" strokeWidth={3} dot={{ r: 3 }} activeDot={{ r: 5 }} />
                                </LineChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Distribución por especie"
                            description="Proporción de urgencias según la especie registrada."
                            empty={datos.distribucion_por_especie.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={datos.distribucion_por_especie}
                                        dataKey="total"
                                        nameKey="especie"
                                        cx="50%"
                                        cy="48%"
                                        innerRadius={48}
                                        outerRadius={90}
                                        paddingAngle={3}
                                    >
                                        {datos.distribucion_por_especie.map((item, index) => (
                                            <Cell key={item.especie} fill={chartColors[index % chartColors.length]} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(value) => [formatNumber(String(value ?? 0)), 'Urgencias']} />
                                    <Legend verticalAlign="bottom" height={32} />
                                </PieChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Ingresos por semana"
                            description="Comparación semanal dentro del período seleccionado."
                            empty={datos.ingresos_por_semana.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={datos.ingresos_por_semana} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" vertical={false} />
                                    <XAxis dataKey="periodo" tickFormatter={(value) => formatPeriod(String(value))} tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => formatPeriod(String(value))}
                                        formatter={(value) => [formatNumber(String(value ?? 0)), 'Ingresos']}
                                    />
                                    <Legend />
                                    <Bar dataKey="total" name="Ingresos" fill="#2563eb" radius={[7, 7, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Ingresos por mes"
                            description="Volumen mensual según la fecha de ingreso."
                            empty={datos.ingresos_por_mes.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={datos.ingresos_por_mes} margin={{ top: 8, right: 12, bottom: 4, left: -18 }}>
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" vertical={false} />
                                    <XAxis dataKey="periodo" tickFormatter={(value) => formatPeriod(String(value))} tick={{ fontSize: 11 }} />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <Tooltip
                                        labelFormatter={(value) => formatPeriod(String(value))}
                                        formatter={(value) => [formatNumber(String(value ?? 0)), 'Ingresos']}
                                    />
                                    <Legend />
                                    <Bar dataKey="total" name="Ingresos" fill="#0f766e" radius={[7, 7, 0, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>

                        <ChartPanel
                            title="Motivos frecuentes"
                            description="Motivos de urgencia más registrados en el período."
                            empty={datos.motivos_frecuentes.length === 0}
                        >
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart
                                    data={datos.motivos_frecuentes}
                                    layout="vertical"
                                    margin={{ top: 4, right: 20, bottom: 4, left: 8 }}
                                >
                                    <CartesianGrid stroke="#e8eef1" strokeDasharray="4 4" horizontal={false} />
                                    <XAxis type="number" allowDecimals={false} tick={{ fontSize: 11 }} />
                                    <YAxis type="category" dataKey="motivo" width={115} tick={{ fontSize: 10 }} />
                                    <Tooltip formatter={(value) => [formatNumber(String(value ?? 0)), 'Registros']} />
                                    <Legend />
                                    <Bar dataKey="total" name="Registros" fill="#0891b2" radius={[0, 7, 7, 0]} />
                                </BarChart>
                            </ResponsiveContainer>
                        </ChartPanel>
                    </section>
                </div>
            }
        />
    );
}

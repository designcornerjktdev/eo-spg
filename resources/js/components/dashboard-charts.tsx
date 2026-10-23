import { useState } from 'react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    LabelList,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    PolarAngleAxis,
    RadialBar,
    RadialBarChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

// Colors are CSS tokens (resources/css/app.css) so light/dark mode swap in one place.
// Bar animation is off: with Recharts 2 + React 19 bars keep their first-render geometry when the
// container resizes after mount (axes update, bars do not), leaving them squeezed to one side.
const SERIES_1 = 'var(--viz-1)';
const SERIES_2 = 'var(--viz-2)';
// Categorical slots in fixed order (validated for colour-vision deficiency); never cycled.
const CATEGORICAL = Array.from({ length: 8 }, (_, i) => `var(--viz-${i + 1})`);
const MAX_SLICES = 7; // the 8th slot is reserved for "Other"
const GRID = 'var(--viz-grid)';
const AXIS = 'var(--viz-axis)';

const axisProps = {
    stroke: AXIS,
    tick: { fill: AXIS, fontSize: 12 },
    tickLine: false,
    axisLine: false,
} as const;

/** Tooltip in the app's own card style; values in text ink, the swatch carries identity. */
function ChartTooltip({
    active,
    label,
    payload,
    labelFormatter,
}: {
    active?: boolean;
    label?: string | number;
    payload?: { name?: string; value?: number; color?: string; payload?: Record<string, unknown> }[];
    labelFormatter?: (label: string | number) => string;
}) {
    if (!active || !payload?.length) return null;
    const total = payload.reduce((sum, item) => sum + (Number(item.value) || 0), 0);

    return (
        <div className="bg-popover text-popover-foreground min-w-36 rounded-md border px-3 py-2 text-sm shadow-md">
            <div className="mb-1 font-medium">{labelFormatter && label !== undefined ? labelFormatter(label) : label}</div>
            {payload.map((item) => (
                <div key={item.name} className="flex items-center justify-between gap-4">
                    <span className="flex items-center gap-2">
                        <span className="size-2.5 rounded-sm" style={{ background: item.color }} />
                        {item.name}
                    </span>
                    <span className="tabular-nums">{item.value}</span>
                </div>
            ))}
            {payload.length > 1 && (
                <div className="text-muted-foreground mt-1 flex justify-between gap-4 border-t pt-1">
                    <span>Total</span>
                    <span className="tabular-nums">{total}</span>
                </div>
            )}
        </div>
    );
}

function Empty() {
    return <div className="text-muted-foreground flex h-full min-h-40 items-center justify-center text-sm">No entries for these filters.</div>;
}

/** Entries over time: stacked areas per day, or a cumulative running total. */
export function TrendChart({ rows }: { rows: { day: string; total: number; qualified: number }[] }) {
    const [mode, setMode] = useState<'daily' | 'cumulative'>('daily');
    if (rows.length === 0) return <Empty />;

    let running = 0;
    const data = rows.map((row) => {
        running += row.total;
        return { ...row, label: shortDay(row.day), notQualified: row.total - row.qualified, cumulative: running };
    });

    return (
        <div className="space-y-2">
            <div className="flex justify-end">
                <div className="bg-muted inline-flex rounded-md p-0.5 text-xs" role="tablist" aria-label="Trend view">
                    {(['daily', 'cumulative'] as const).map((value) => (
                        <button
                            key={value}
                            type="button"
                            role="tab"
                            aria-selected={mode === value}
                            onClick={() => setMode(value)}
                            className={
                                mode === value ? 'bg-background rounded px-2.5 py-1 font-medium shadow-sm' : 'text-muted-foreground px-2.5 py-1'
                            }
                        >
                            {value === 'daily' ? 'Daily' : 'Cumulative'}
                        </button>
                    ))}
                </div>
            </div>
            <ResponsiveContainer width="100%" height={260}>
                {mode === 'daily' ? (
                    <AreaChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -16 }}>
                        <CartesianGrid vertical={false} stroke={GRID} />
                        <XAxis dataKey="label" {...axisProps} interval="preserveStartEnd" minTickGap={16} />
                        <YAxis {...axisProps} allowDecimals={false} width={40} />
                        <Tooltip cursor={{ stroke: AXIS, strokeDasharray: '3 3' }} content={<ChartTooltip />} />
                        <Legend
                            iconType="square"
                            iconSize={10}
                            wrapperStyle={{ fontSize: 12, paddingTop: 8 }}
                            formatter={(value) => <span className="text-foreground">{value}</span>}
                        />
                        <Area
                            isAnimationActive={false}
                            type="monotone"
                            dataKey="qualified"
                            name="Qualified"
                            stackId="day"
                            stroke={SERIES_1}
                            strokeWidth={2}
                            fill={SERIES_1}
                            fillOpacity={0.25}
                        />
                        <Area
                            isAnimationActive={false}
                            type="monotone"
                            dataKey="notQualified"
                            name="Not qualified"
                            stackId="day"
                            stroke={SERIES_2}
                            strokeWidth={2}
                            fill={SERIES_2}
                            fillOpacity={0.2}
                        />
                    </AreaChart>
                ) : (
                    <LineChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -16 }}>
                        <CartesianGrid vertical={false} stroke={GRID} />
                        <XAxis dataKey="label" {...axisProps} interval="preserveStartEnd" minTickGap={16} />
                        <YAxis {...axisProps} allowDecimals={false} width={48} />
                        <Tooltip cursor={{ stroke: AXIS, strokeDasharray: '3 3' }} content={<ChartTooltip />} />
                        <Line
                            isAnimationActive={false}
                            type="monotone"
                            dataKey="cumulative"
                            name="Total entries"
                            stroke={SERIES_1}
                            strokeWidth={2}
                            dot={false}
                            activeDot={{ r: 5 }}
                        />
                    </LineChart>
                )}
            </ResponsiveContainer>
        </div>
    );
}

/** Entries by hour of day: when the booth is busiest. */
export function HourlyChart({ rows }: { rows: { hour: number; total: number }[] }) {
    if (rows.length === 0) return <Empty />;
    const data = rows.map((row) => ({ ...row, label: `${String(row.hour).padStart(2, '0')}:00` }));

    return (
        <ResponsiveContainer width="100%" height={260}>
            <BarChart data={data} margin={{ top: 8, right: 8, bottom: 0, left: -16 }} barCategoryGap="15%">
                <CartesianGrid vertical={false} stroke={GRID} />
                <XAxis dataKey="label" {...axisProps} interval="preserveStartEnd" minTickGap={8} />
                <YAxis {...axisProps} allowDecimals={false} width={40} />
                <Tooltip
                    cursor={{ fill: 'var(--muted)', opacity: 0.6 }}
                    content={
                        <ChartTooltip labelFormatter={(label) => `${label}–${String(Number(String(label).slice(0, 2)) + 1).padStart(2, '0')}:00`} />
                    }
                />
                <Bar isAnimationActive={false} dataKey="total" name="Entries" fill={SERIES_1} radius={[4, 4, 0, 0]} />
            </BarChart>
        </ResponsiveContainer>
    );
}

/** Horizontal bars, largest first. Clicking a bar calls `onSelect` (used to filter by location). */
export function RankedBars({
    rows,
    selectedId,
    onSelect,
    name = 'Entries',
}: {
    rows: { id?: number; label: string; total: number }[];
    selectedId?: number | null;
    onSelect?: (id: number) => void;
    name?: string;
}) {
    if (rows.length === 0) return <Empty />;
    const height = Math.max(120, rows.length * 40 + 16);

    return (
        <ResponsiveContainer width="100%" height={height}>
            <BarChart data={rows} layout="vertical" margin={{ top: 0, right: 40, bottom: 0, left: 0 }} barCategoryGap="25%">
                <XAxis type="number" hide allowDecimals={false} />
                <YAxis type="category" dataKey="label" {...axisProps} width={130} />
                <Tooltip cursor={{ fill: 'var(--muted)', opacity: 0.6 }} content={<ChartTooltip />} />
                <Bar
                    isAnimationActive={false}
                    dataKey="total"
                    name={name}
                    fill={SERIES_1}
                    radius={[0, 4, 4, 0]}
                    cursor={onSelect ? 'pointer' : undefined}
                    onClick={(entry: { id?: number }) => entry.id !== undefined && onSelect?.(entry.id)}
                >
                    {rows.map((row) => (
                        <Cell key={row.label} fillOpacity={selectedId && row.id !== selectedId ? 0.35 : 1} />
                    ))}
                    <LabelList dataKey="total" position="right" className="fill-foreground" style={{ fontSize: 12 }} />
                </Bar>
            </BarChart>
        </ResponsiveContainer>
    );
}

/** Distribution of a number field (e.g. age groups). */
export function Histogram({ bins }: { bins: { label: string; total: number }[] }) {
    if (bins.length === 0) return <Empty />;

    return (
        <ResponsiveContainer width="100%" height={180}>
            <BarChart data={bins} margin={{ top: 8, right: 8, bottom: 0, left: -16 }} barCategoryGap="10%">
                <CartesianGrid vertical={false} stroke={GRID} />
                <XAxis dataKey="label" {...axisProps} />
                <YAxis {...axisProps} allowDecimals={false} width={40} />
                <Tooltip cursor={{ fill: 'var(--muted)', opacity: 0.6 }} content={<ChartTooltip />} />
                <Bar isAnimationActive={false} dataKey="total" name="Responses" fill={SERIES_1} radius={[4, 4, 0, 0]} />
            </BarChart>
        </ResponsiveContainer>
    );
}

/** Share of qualified entries as a radial gauge with the percentage in the middle. */
export function QualifiedGauge({ qualified, total }: { qualified: number; total: number }) {
    const pct = total ? Math.round((qualified / total) * 100) : 0;

    return (
        <div className="relative mx-auto aspect-square w-full max-w-60">
            <ResponsiveContainer width="100%" height="100%">
                <RadialBarChart data={[{ value: pct }]} innerRadius="78%" outerRadius="100%" startAngle={90} endAngle={-270}>
                    <PolarAngleAxis type="number" domain={[0, 100]} tick={false} />
                    <RadialBar isAnimationActive={false} dataKey="value" cornerRadius={8} fill={SERIES_1} background={{ fill: 'var(--muted)' }} />
                </RadialBarChart>
            </ResponsiveContainer>
            <div className="absolute inset-0 flex flex-col items-center justify-center text-center">
                <span className="text-4xl font-semibold tabular-nums">{pct}%</span>
                <span className="text-muted-foreground text-xs">
                    {qualified.toLocaleString('en-GB')} of {total.toLocaleString('en-GB')} qualified
                </span>
            </div>
        </div>
    );
}

/**
 * Part-to-whole donut with a legend that doubles as the table view (label, count, share).
 * More than 7 categories fold into "Other"; clicking a slice or row calls `onSelect`.
 */
export function ShareDonut({
    rows,
    selectedId,
    onSelect,
}: {
    rows: { id?: number; label: string; total: number }[];
    selectedId?: number | null;
    onSelect?: (id: number) => void;
}) {
    if (rows.length === 0) return <Empty />;

    const sorted = [...rows].sort((a, b) => b.total - a.total);
    const slices: { id?: number; label: string; total: number }[] =
        sorted.length > MAX_SLICES + 1
            ? [...sorted.slice(0, MAX_SLICES), { label: 'Other', total: sorted.slice(MAX_SLICES).reduce((sum, row) => sum + row.total, 0) }]
            : sorted;
    const sum = slices.reduce((total, row) => total + row.total, 0);
    const pct = (value: number) => (sum ? Math.round((value / sum) * 100) : 0);
    const dimmed = (row: { id?: number }) => selectedId != null && row.id !== selectedId;

    return (
        <div className="grid items-center gap-4 sm:grid-cols-[minmax(0,220px)_1fr]">
            <div className="relative mx-auto aspect-square w-full max-w-56">
                <ResponsiveContainer width="100%" height="100%">
                    <PieChart>
                        <Pie
                            isAnimationActive={false}
                            data={slices}
                            dataKey="total"
                            nameKey="label"
                            innerRadius="62%"
                            outerRadius="100%"
                            paddingAngle={1}
                            stroke="var(--card)"
                            strokeWidth={2}
                            cursor={onSelect ? 'pointer' : undefined}
                            onClick={(slice: { id?: number }) => slice.id !== undefined && onSelect?.(slice.id)}
                        >
                            {slices.map((row, index) => (
                                <Cell key={row.label} fill={CATEGORICAL[index]} fillOpacity={dimmed(row) ? 0.3 : 1} />
                            ))}
                        </Pie>
                        <Tooltip content={<ChartTooltip />} />
                    </PieChart>
                </ResponsiveContainer>
                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span className="text-2xl font-semibold tabular-nums">{sum.toLocaleString('en-GB')}</span>
                    <span className="text-muted-foreground text-xs">total</span>
                </div>
            </div>
            <ul className="space-y-1 text-sm">
                {slices.map((row, index) => {
                    const interactive = onSelect && row.id !== undefined;
                    const content = (
                        <>
                            <span className="size-2.5 shrink-0 rounded-sm" style={{ background: CATEGORICAL[index] }} />
                            <span className="flex-1 truncate text-left">{row.label}</span>
                            <span className="font-medium tabular-nums">{row.total.toLocaleString('en-GB')}</span>
                            <span className="text-muted-foreground w-10 text-right tabular-nums">{pct(row.total)}%</span>
                        </>
                    );
                    return (
                        <li key={row.label} className={dimmed(row) ? 'opacity-50' : undefined}>
                            {interactive ? (
                                <button
                                    type="button"
                                    onClick={() => onSelect(row.id!)}
                                    className="hover:bg-muted flex w-full items-center gap-2 rounded px-2 py-1"
                                >
                                    {content}
                                </button>
                            ) : (
                                <div className="flex items-center gap-2 px-2 py-1">{content}</div>
                            )}
                        </li>
                    );
                })}
            </ul>
        </div>
    );
}

function shortDay(day: string): string {
    const date = new Date(`${day}T00:00:00+07:00`);
    return Number.isNaN(date.getTime()) ? day : date.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', timeZone: 'Asia/Jakarta' });
}

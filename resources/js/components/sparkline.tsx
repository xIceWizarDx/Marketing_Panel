export default function Sparkline({ values = [] as number[] }: { values?: number[] }) {
    if (!values.length) values = [2,4,3,5,6,5,7,4,8,6];
    const max = Math.max(...values, 1);
    return (
        <div className="mt-2 flex h-8 items-end gap-1">
            {values.map((v, i) => (
                <div key={i} className="w-2 rounded bg-accent" style={{ height: `${(v / max) * 100}%` }} />
            ))}
        </div>
    );
}


import RankIcon from '@/Components/RankIcon';
import { PublicRank } from '@/types';

export default function RankBadge({ rank, compact = false }: { rank?: PublicRank | null; compact?: boolean }) {
    if (!rank) {
        return null;
    }

    return (
        <span
            className={`inline-flex items-center gap-2 rounded-full px-3 py-1 font-bold text-white shadow-sm ${compact ? 'text-xs' : 'text-sm'}`}
            style={{ backgroundColor: rank.color }}
        >
            {rank.icon && <RankIcon value={rank.icon} className={compact ? 'size-4' : 'size-5'} />}
            {rank.name}
        </span>
    );
}

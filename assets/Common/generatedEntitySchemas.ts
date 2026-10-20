import analyticsBreakdown from '../data/entity/analytics_breakdown.json';

type EntitySchema = { name: string };

export default function getGeneratedEntitySchemas(): Record<string, EntitySchema> {
  return {
    [analyticsBreakdown.name]: analyticsBreakdown,
  };
}

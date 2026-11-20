import analyticsApiBreakdown from '../data/entity/analytics_api_breakdown.json';
import analyticsBreakdown from '../data/entity/analytics_breakdown.json';

type EntitySchema = { name: string };

export default function getGeneratedEntitySchemas(): Record<string, EntitySchema> {
  return {
    [analyticsApiBreakdown.name]: analyticsApiBreakdown,
    [analyticsBreakdown.name]: analyticsBreakdown,
  };
}

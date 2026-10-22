import AbstractApiEntity from '@wexample/js-api-entity/Common/AbstractApiEntity';
import schema from '../data/entity/analytics_breakdown.json';

export default class AnalyticsBreakdown extends AbstractApiEntity {
  static readonly entityName = 'analyticsBreakdown';

  static retrieveEntitySchema() {
    return schema;
  }
}

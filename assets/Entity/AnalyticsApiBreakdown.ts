import AbstractApiEntity from '@wexample/js-api-entity/Common/AbstractApiEntity';
import type { ApiEntitySchema } from '@wexample/js-api-entity/Common/AbstractApiEntity';
import schema from '../data/entity/analytics_api_breakdown.json';

export default class AnalyticsApiBreakdown extends AbstractApiEntity {
  static readonly entityName = 'analyticsApiBreakdown';

  // Typed as any schema, so that a subclass may answer its own.
  static retrieveEntitySchema(): ApiEntitySchema {
    return schema;
  }
}

import AbstractApiRepository from '@wexample/js-api-entity/Common/AbstractApiRepository';
import AnalyticsApiBreakdown from '../Entity/AnalyticsApiBreakdown.js';

export default class AnalyticsApiBreakdownRepository extends AbstractApiRepository<AnalyticsApiBreakdown> {
  static getEntityType() {
    return AnalyticsApiBreakdown;
  }
}

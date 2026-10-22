import AbstractApiRepository from '@wexample/js-api-entity/Common/AbstractApiRepository';
import AnalyticsBreakdown from '../Entity/AnalyticsBreakdown.js';

export default class AnalyticsBreakdownRepository extends AbstractApiRepository<AnalyticsBreakdown> {
  static getEntityType() {
    return AnalyticsBreakdown;
  }
}

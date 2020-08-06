import Rate from './application/component/Rate';
import Dashboard from "./application/component/Dashboard/Dashboard";
import DashboardHelper from "./application/component/Dashboard/DashboardHelper";
import {
    DATA_QUALITY_INSIGHTS_DASHBOARD_CHANGE_TIME_PERIOD,
    DATA_QUALITY_INSIGHTS_DASHBOARD_FILTER_CATEGORY,
    DATA_QUALITY_INSIGHTS_DASHBOARD_FILTER_FAMILY,
    PRODUCT_DATA_QUALITY_INSIGHTS_TAB_NAME,
    PRODUCT_MODEL_DATA_QUALITY_INSIGHTS_TAB_NAME,
    PRODUCT_ATTRIBUTES_TAB_NAME,
    PRODUCT_MODEL_ATTRIBUTES_TAB_NAME,
} from "./application/constant";

import {DataQualityInsightsFeature, getDataQualityInsightsFeature} from "./infrastructure/fetcher/data-quality-insights-feature";

import {
    CATALOG_CONTEXT_CHANNEL_CHANGED,
    CATALOG_CONTEXT_LOCALE_CHANGED,
    DATA_QUALITY_INSIGHTS_FILTER_ALL_IMPROVABLE_ATTRIBUTES,
    DATA_QUALITY_INSIGHTS_FILTER_ALL_MISSING_ATTRIBUTES,
    DATA_QUALITY_INSIGHTS_PRODUCT_SAVED,
    DATA_QUALITY_INSIGHTS_PRODUCT_SAVING,
    DATA_QUALITY_INSIGHTS_SHOW_ATTRIBUTE,
    PRODUCT_ATTRIBUTES_TAB_LOADED,
    PRODUCT_ATTRIBUTES_TAB_LOADING,
    PRODUCT_MODEL_LEVEL_CHANGED,
    PRODUCT_TAB_CHANGED,
} from './application/listener';

import ProductEditFormApp from './application/ProductEditFormApp';
import ProductModelEditFormApp from './application/ProductModelEditFormApp';
import {DATA_QUALITY_INSIGHTS_TAB_CONTENT_CONTAINER_ELEMENT_ID} from "./application/component/ProductEditForm/TabContent";
import {DATA_QUALITY_INSIGHTS_AXIS_RATES_OVERVIEW_SIDEBAR_CONTAINER_ELEMENT_ID} from "./application/component/ProductEditForm/Sidebar";

import fetchProductDataQualityEvaluation
    from './infrastructure/fetcher/ProductEditForm/fetchProductDataQualityEvaluation';
import fetchProductModelEvaluation from './infrastructure/fetcher/ProductEditForm/fetchProductModelEvaluation';

import {CriterionEvaluationResult, ProductEvaluation} from "./domain";

export {
    Rate,
    Dashboard,
    DashboardHelper,
    DATA_QUALITY_INSIGHTS_DASHBOARD_FILTER_CATEGORY,
    DATA_QUALITY_INSIGHTS_DASHBOARD_CHANGE_TIME_PERIOD,
    DATA_QUALITY_INSIGHTS_DASHBOARD_FILTER_FAMILY,
    DataQualityInsightsFeature,
    getDataQualityInsightsFeature,
    CATALOG_CONTEXT_CHANNEL_CHANGED,
    CATALOG_CONTEXT_LOCALE_CHANGED,
    PRODUCT_ATTRIBUTES_TAB_LOADED,
    PRODUCT_ATTRIBUTES_TAB_LOADING,
    PRODUCT_TAB_CHANGED,
    DATA_QUALITY_INSIGHTS_SHOW_ATTRIBUTE,
    DATA_QUALITY_INSIGHTS_FILTER_ALL_MISSING_ATTRIBUTES,
    DATA_QUALITY_INSIGHTS_FILTER_ALL_IMPROVABLE_ATTRIBUTES,
    DATA_QUALITY_INSIGHTS_PRODUCT_SAVING,
    DATA_QUALITY_INSIGHTS_PRODUCT_SAVED,
    PRODUCT_MODEL_LEVEL_CHANGED,
    PRODUCT_ATTRIBUTES_TAB_NAME,
    PRODUCT_MODEL_ATTRIBUTES_TAB_NAME,
    ProductEditFormApp,
    ProductModelEditFormApp,
    DATA_QUALITY_INSIGHTS_TAB_CONTENT_CONTAINER_ELEMENT_ID,
    DATA_QUALITY_INSIGHTS_AXIS_RATES_OVERVIEW_SIDEBAR_CONTAINER_ELEMENT_ID,
    PRODUCT_DATA_QUALITY_INSIGHTS_TAB_NAME,
    PRODUCT_MODEL_DATA_QUALITY_INSIGHTS_TAB_NAME,
    fetchProductDataQualityEvaluation,
    fetchProductModelEvaluation,
    ProductEvaluation, CriterionEvaluationResult,
};

/**
 * External dependencies
 */
import { plugins } from '@moderntribe/common/data';
import { store } from '@moderntribe/common/store';

const { dispatch } = store;
const { TICKETS_PLUS } = plugins.constants;
dispatch( plugins.actions.addPlugin( TICKETS_PLUS ) );
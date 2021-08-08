/**
 * Registers a new plugin provided a unique name and an object defining its behavior.
 */
import { registerPlugin } from '@wordpress/plugins';
import PrimaryCategory from './components/PrimaryCategory';

// register the plugin and attach it's react component
registerPlugin(
    'plugin-document-setting-panel-primary-category', {
        render: PrimaryCategory,
        icon: 'heart',
    }
);

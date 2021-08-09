import { __ } from '@wordpress/i18n';
import {
    AsyncModeProvider,
    useSelect,
    withSelect,
    withDispatch
} from '@wordpress/data';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

let CategoryDropdown = (props) => {
    const select = useSelect((select) => {
            return {
                categories: select('core').getEntityRecords('taxonomy', 'category'),
            }
        }
    );

    return (
        <>
            <select
                name="pcfp_primary_category"
                onChange={(event) => props.onMetaFieldChange(event)}
            >
                <option value="">{__('Select Category')}</option>
                {select.categories &&
                    select.categories.map(
                        (option) => <option key={option.slug} value={option.slug} selected={ props.pcfp_primary_category == option.slug ? 'selected' : '' }>{__(option.name)}</option>
                    )}
            </select>
        </>
    );
};

CategoryDropdown = withSelect(
    (select) => {
        return {
            pcfp_primary_category: select('core/editor').getEditedPostAttribute('meta')['pcfp_primary_category'],
        };
    }
)(CategoryDropdown);

CategoryDropdown = withDispatch(
    (dispatch) => {
    return {
            onMetaFieldChange: (event) => {
                dispatch('core/editor').editPost(
                    {
                        meta: { pcfp_primary_category: event.target.value },
                    }
                );
            },
        };
    }
)(CategoryDropdown);

/**
 * The PrimaryCategory function describes the structure of your block in the context of the
 * meta box. This represents what the editor will render when the block is used.
 */
export default function PrimaryCategory()
{
    return (
    <PluginDocumentSettingPanel
        name="Primary Category"
        title="Primary Category"
        className="primary-category-wrapper"
    >
            <AsyncModeProvider value={true}>
                <CategoryDropdown />
            </AsyncModeProvider>
    </PluginDocumentSettingPanel>
    );
}

import { ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

// unfortunately, this needs to be in sync with ast-template-view.php
const PAGE_TEMPLATE_META_KEY = 'ast_page_is_template';

const ASTDocumentSettingsPanel = () => {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const metaFieldValue = meta[ PAGE_TEMPLATE_META_KEY ];
	const updateMetaValue = ( newValue ) => {
		setMeta( { ...meta, [ PAGE_TEMPLATE_META_KEY ]: newValue } );
	};

	return (
		<PluginDocumentSettingPanel
			name="ast-page-attributes-panel"
			title="AST Page Attributes"
			className="ast-page-attributes-panel"
		>
			<ToggleControl
				label="Template"
				checked={ metaFieldValue }
				onChange={ updateMetaValue }
			/>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'ast-page-attributes-panel', {
	render: ASTDocumentSettingsPanel,
} );

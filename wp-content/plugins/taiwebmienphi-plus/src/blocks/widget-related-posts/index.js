import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import block from "./block.json";
import icons from "../../icons.js";

registerBlockType(block.name, {
  icon: icons.primary,
  edit({ attributes, setAttributes }) {
    const { showDescription, showDate, showAuthor, showCategory, showViewMore } = attributes;
    const blockProps = useBlockProps();
    return (
      <>
        <InspectorControls>
          <PanelBody title={__('General', 'taiwebmienphi-plus')}>
            <ToggleControl
              label={__('Show / Hide Description', 'taiwebmienphi-plus')}
              checked={showDescription}
              onChange={showDescription => setAttributes({ showDescription })}
            />

            <ToggleControl
              label={__('Show / Hide Date', 'taiwebmienphi-plus')}
              checked={showDate}
              onChange={showDate => setAttributes({ showDate })}
            />

            <ToggleControl
              label={__('Show / Hide Author', 'taiwebmienphi-plus')}
              checked={showAuthor}
              onChange={showAuthor => setAttributes({ showAuthor })}
            />

            <ToggleControl
              label={__('Show / Hide Category', 'taiwebmienphi-plus')}
              checked={showCategory}
              onChange={showCategory => setAttributes({ showCategory })}
            />

            <ToggleControl
              label={__('Show / Hide View More', 'taiwebmienphi-plus')}
              checked={showViewMore}
              onChange={showViewMore => setAttributes({ showViewMore })}
            />
          </PanelBody>
        </InspectorControls>
        <div {...blockProps}>
          <div className="widget-recent-posts">
            widget-related-posts
          </div>
        </div>
      </>
    );
  }
});

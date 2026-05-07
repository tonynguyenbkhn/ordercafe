import { useSelect } from '@wordpress/data';
import { useMemo } from '@wordpress/element';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, FormTokenField, TextControl } from '@wordpress/components';
import { __ } from "@wordpress/i18n";
import block from "./block.json";
import icons from "./../../icons.js";



registerBlockType(block.name, {
  icon: icons.primary,
  edit({ attributes, setAttributes }) {
    const { showDescription, showDate, showAuthor, showCategory, showViewMore, selectedPostIds, postsPerPage, titleLimit, excerptLimit, textViewMore } = attributes;
    const blockProps = useBlockProps();

    const MultiPostSelector = ({ selectedPostIds, onChange }) => {
      const posts = useSelect((select) =>
        select('core').getEntityRecords('postType', 'post', { per_page: 100 })
        , []);

      const options = useMemo(() => {
        if (!posts) return [];
        return posts.map((post) => ({
          id: post.id,
          title: post.title.rendered,
        }));
      }, [posts]);

      const selectedTitles = useMemo(() => {
        return selectedPostIds
          .map((id) => {
            const post = options.find((p) => p.id === id);
            return post ? post.title : null;
          })
          .filter(Boolean);
      }, [selectedPostIds, options]);

      const handleChange = (titles) => {
        const ids = titles.map((title) => {
          const found = options.find((p) => p.title === title);
          return found ? found.id : null;
        }).filter(Boolean);

        onChange(ids);
      };

      return (
        <FormTokenField
          label={__('Select Posts', 'taiwebmienphi-plus')}
          value={selectedTitles}
          suggestions={options.map((p) => p.title)}
          onChange={handleChange}
        />
      );
    };
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

            <MultiPostSelector
              selectedPostIds={selectedPostIds}
              onChange={(ids) => setAttributes({ selectedPostIds: ids })}
            />

            <TextControl
              label={__('Number of Posts to Display', 'taiwebmienphi-plus')}
              type="number"
              min={1}
              value={postsPerPage}
              onChange={(value) => setAttributes({ postsPerPage: parseInt(value) || 5 })}
            />

            <TextControl
              label={__('Text Limit', 'taiwebmienphi-plus')}
              type="number"
              min={1}
              value={titleLimit}
              onChange={(value) => setAttributes({ titleLimit: parseInt(value) || 20 })}
            />

            <TextControl
              label={__('Excerpt Limit', 'taiwebmienphi-plus')}
              type="number"
              min={1}
              value={excerptLimit}
              onChange={(value) => setAttributes({ excerptLimit: parseInt(value) || 20 })}
            />

            <TextControl
              label={__('Button Text', 'taiwebmienphi-plus')}
              type="string"
              value={textViewMore}
              onChange={(value) => setAttributes({ textViewMore: value || __('View More', 'taiwebmienphi-plus') })}
            />
          </PanelBody>
        </InspectorControls>
        <div {...blockProps}>
          <div className="widget-recent-posts">
            widget-recent-posts
          </div>
        </div>
      </>
    );
  }
});

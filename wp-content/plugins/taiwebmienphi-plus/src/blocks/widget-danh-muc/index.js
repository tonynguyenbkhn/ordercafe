import { registerBlockType } from "@wordpress/blocks";
import { useBlockProps } from "@wordpress/block-editor";
import { __ } from "@wordpress/i18n";
import block from "./block.json";
import icons from "../../icons.js";

registerBlockType(block.name, {
  icon: icons.primary,
  edit() {
    const blockProps = useBlockProps();
    return (
      <div {...blockProps}>
        <div className="widget-platform">widget-danh-muc</div>
      </div>
    );
  },
});

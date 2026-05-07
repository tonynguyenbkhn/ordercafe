import { registerPlugin } from "@wordpress/plugins";
import { PluginSidebar } from "@wordpress/edit-post";
import { __ } from "@wordpress/i18n";
import { useSelect, useDispatch } from "@wordpress/data";
import {
  PanelBody,
  TextControl,
  TextareaControl,
  ToggleControl,
  Button,
} from "@wordpress/components";
import { MediaUpload, MediaUploadCheck } from "@wordpress/block-editor";

registerPlugin("twmp-sidebar", {
  render() {
    const {
      meta_title,
      meta_description,
      meta_keyword,
      og_title,
      og_description,
      og_url,
      og_image,
      og_type,
      twitter_title,
      twitter_description,
      twitter_image,
      twitter_card,
      og_image_url,
      twitter_image_url,
      canonical_url
    } = useSelect((select) => {
      const meta = select("core/editor").getEditedPostAttribute("meta") || {};

      const ogMedia = meta.og_image
        ? select("core").getMedia(meta.og_image)
        : null;

      const twitterMedia = meta.twitter_image
        ? select("core").getMedia(meta.twitter_image)
        : null;

      return {
        ...meta,
        og_image_url: ogMedia?.source_url || "",
        twitter_image_url: twitterMedia?.source_url || "",
      };
    });

    const { editPost } = useDispatch("core/editor");

    return (
      <PluginSidebar
        name="twmp_sidebar"
        icon="share"
        title={__("SEO Settings", "taiwebmienphi-plus")}
      >
        <PanelBody title={__("Content", "taiwebmienphi-plus")}>
          {/* Meta Title */}
          <TextControl
            label={__("Meta title", "taiwebmienphi-plus")}
            value={meta_title}
            onChange={(meta_title) =>
              editPost({
                meta: {
                  meta_title,
                },
              })
            }
          />

          {/* Meta Description */}
          <TextareaControl
            label={__("Meta description", "taiwebmienphi-plus")}
            value={meta_description}
            onChange={(meta_description) => {
              editPost({
                meta: {
                  meta_description,
                },
              });
            }}
          />

          {/* Meta Keyword */}
          <TextareaControl
            label={__("Meta keyword", "taiwebmienphi-plus")}
            value={meta_keyword}
            onChange={(meta_keyword) =>
              editPost({
                meta: {
                  meta_keyword,
                },
              })
            }
          />

          <TextControl
            label={__("Canonical url", "taiwebmienphi-plus")}
            value={canonical_url}
            onChange={(canonical_url) =>
              editPost({
                meta: {
                  canonical_url,
                },
              })
            }
          />

          {/* OpenGraph Meta Tags */}
          <TextControl
            label={__("OG Title", "taiwebmienphi-plus")}
            value={og_title}
            onChange={(og_title) =>
              editPost({
                meta: {
                  og_title,
                },
              })
            }
          />
          <TextareaControl
            label={__("OG Description", "taiwebmienphi-plus")}
            value={og_description}
            onChange={(og_description) => {
              editPost({
                meta: {
                  og_description,
                },
              });
            }}
          />
          <TextControl
            label={__("OG URL", "taiwebmienphi-plus")}
            value={og_url}
            onChange={(og_url) =>
              editPost({
                meta: {
                  og_url,
                },
              })
            }
          />
          <>
            {og_image_url && (
              <img
                src={og_image_url}
                alt="Open Graph Image"
                style={{ maxWidth: "100%", height: "auto", marginBottom: "10px" }}
              />
            )}

            <MediaUploadCheck>
              <MediaUpload
                accept={["image"]}
                onSelect={(image) => {
                  editPost({
                    meta: {
                      og_image: image.id, // lưu ID ảnh
                    },
                  });
                }}
                render={({ open }) => (
                  <Button className="is-primary" onClick={open} style={{ marginRight: "10px", marginBottom: "10px" }}>
                    {__(og_image ? "Change Image" : "Choose Image", "taiwebmienphi-plus")}
                  </Button>
                )}
              />
            </MediaUploadCheck>
            {Number(og_image) > 0 && (
              <Button
                style={{ marginBottom: "10px" }}
                className="is-secondary"
                onClick={() => {
                  editPost({
                    meta: {
                      og_image: "", // xoá ID ảnh
                    },
                  });
                }}
              >
                {__("Remove Image", "taiwebmienphi-plus")}
              </Button>
            )}
          </>
          <TextControl
            label={__("OG Type", "taiwebmienphi-plus")}
            value={og_type}
            onChange={(og_type) =>
              editPost({
                meta: {
                  og_type,
                },
              })
            }
          />

          {/* Twitter Meta Tags */}
          <TextControl
            label={__("Twitter Title", "taiwebmienphi-plus")}
            value={twitter_title}
            onChange={(twitter_title) =>
              editPost({
                meta: {
                  twitter_title,
                },
              })
            }
          />
          <TextareaControl
            label={__("Twitter Description", "taiwebmienphi-plus")}
            value={twitter_description}
            onChange={(twitter_description) => {
              editPost({
                meta: {
                  twitter_description,
                },
              });
            }}
          />
          <>
            {twitter_image_url && (
              <img
                src={twitter_image_url}
                alt="Twitter Image"
                style={{ maxWidth: "100%", height: "auto", marginBottom: "10px" }}
              />
            )}

            <MediaUploadCheck>
              <MediaUpload
                accept={["image"]}
                onSelect={(image) => {
                  editPost({
                    meta: {
                      twitter_image: image.id, // lưu ID ảnh
                    },
                  });
                }}
                render={({ open }) => (
                  <Button className="is-primary" onClick={open} style={{ marginRight: "10px", marginBottom: "10px" }}>
                    {__(twitter_image ? "Change Image" : "Choose Image", "taiwebmienphi-plus")}
                  </Button>
                )}
              />
            </MediaUploadCheck>

            {/* Nút xóa ảnh */}
            {Number(twitter_image) > 0 && (
              <Button
                className="is-secondary"
                style={{ marginBottom: "10px" }}
                onClick={() => {
                  editPost({
                    meta: {
                      twitter_image: null, // xoá ID ảnh
                    },
                  });
                }}
              >
                {__("Remove Image", "taiwebmienphi-plus")}
              </Button>
            )}
          </>
          <TextControl
            label={__("Twitter Card", "taiwebmienphi-plus")}
            value={twitter_card}
            onChange={(twitter_card) =>
              editPost({
                meta: {
                  twitter_card,
                },
              })
            }
          />
        </PanelBody>
      </PluginSidebar>
    );
  },
});

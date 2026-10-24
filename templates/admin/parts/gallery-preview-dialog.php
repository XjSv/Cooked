<?php
/**
 * Recipe editor › Gallery: the "Preview Gallery" dialog (cooked-admin-functions.js loads
 * Cooked_Ajax::gallery_preview() into its frame).
 *
 * @package     Cooked
 * @subpackage  Admin
 * @since       2.0.0
 */

?>
<dialog id="cooked-gallery-preview-dialog" class="cooked-dialog cooked-gallery-preview-dialog" aria-labelledby="cooked-gallery-preview-title">
    <div class="cooked-dialog-header">
        <h2 id="cooked-gallery-preview-title"><?php esc_html_e( 'Gallery preview', 'cooked' ); ?></h2>
        <button type="button" class="cooked-dialog-close" aria-label="<?php esc_attr_e( 'Close', 'cooked' ); ?>"><i class="cooked-icon cooked-icon-close" aria-hidden="true"></i></button>
    </div>
    <div class="cooked-dialog-body">
        <iframe class="cooked-gallery-preview-frame" title="<?php esc_attr_e( 'Gallery preview', 'cooked' ); ?>"></iframe>
    </div>
</dialog>

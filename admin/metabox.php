<?php

wp_enqueue_media(); // メディアアップローダー用のスクリプトをロードする

// カスタムメディアアップローダー用のJavaScript
wp_enqueue_script(
	'my-media-uploader',
	plugins_url( "js/media-uploader.js", TIRS_PLUGIN_FILE ),
	array( 'jquery' ),
	filemtime( TIRS_PLUGIN_PATH . '/js/media-uploader.js' ),
	false
);

// tableの中身はループで生成
$custom_fields = array(
	'large_slide_image' => 'Large Slide Image',
	'small_slide_image' => 'Small Slide Image',
);

// スライドの幅をオプションから取得
$slide_width = array(
	'large_slide_image' => 100,
	'small_slide_image' => 100 * ( get_option( 'small-slider-width' ) / get_option( 'large-slider-width' ) ),
);
?>

<?php wp_nonce_field( plugin_basename(TIRS_PLUGIN_FILE), 'slide_meta_wpnonce' ); ?>

<table class="slide-table">
	<?php foreach ( $custom_fields as $key => $key_name ) : $field_value = get_post_meta( get_the_ID(), $key, true ); ?> 
		<tr class="set-<?php echo $key; ?>">
			<th><label for="<?php echo $key; ?>-from-media"><?php _e( $key_name, 'tirs-text-domain' ); ?></label></th>
			<td>
				<div id="preview-<?php echo $key; ?>"  data-slide-width="<?php echo esc_attr( $slide_width[ $key ] ); ?>">
					<?php if ( $field_value ): $image_url = wp_get_attachment_url( $field_value ); ?> 
						<img src="<?php echo esc_url( $image_url ); ?>" width="<?php echo esc_attr( $slide_width[ $key ] ); ?>%" />
					<?php endif; ?> 
				</div>
				<input type="hidden" name="<?php echo $key; ?>" id="<?php echo $key; ?>" value="<?php echo esc_attr( $field_value ); ?>" />
				<input type="button" id="<?php echo $key; ?>-from-media" value="<?php _e( 'Choose from Media Uploader', 'tirs-text-domain' ); ?>" onClick="responsive_<?php echo $key; ?>_uploader( event, '<?php _e( 'Choose Image', 'tirs-text-domain' ); ?>')" />
				<input type="button" id="<?php echo $key; ?>-unset" value="<?php _e( 'Unset this Image', 'tirs-text-domain' ); ?>" onClick="responsive_slider_image_unset( '<?php echo $key; ?>' );" <?php echo $field_value ? '' : 'disabled'; ?>/>
				<?php if ( 'large_slide_image' == $key ): ?>
					<p><?php _e( 'Width', 'tirs-text-domain' ) ?>: <?php echo esc_html( get_option( 'large-slider-width' ) ); ?>, <?php _e( 'Height', 'tirs-text-domain' ) ?>: <?php echo esc_html( get_option( 'large-slider-height' ) ); ?></p>
				<?php elseif ( 'small_slide_image' == $key ): ?>
					<p><?php _e( 'Width', 'tirs-text-domain' ) ?>: <?php echo esc_html( get_option( 'small-slider-width' ) ); ?>, <?php _e( 'Height', 'tirs-text-domain' ) ?>: <?php echo esc_html( get_option( 'small-slider-height' ) ); ?></p>
				<?php endif; ?>
			</td>
		</tr>
	<?php endforeach; ?>
</table>

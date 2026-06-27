<?php
if ( ! defined( 'BIHRWI_TOOL_SYNC_SKU' ) ) {
    exit;
}

global $wpdb;

$action       = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
$offset       = isset( $_GET['offset'] ) ? max( 0, intval( wp_unslash( $_GET['offset'] ) ) ) : 0;
$nonce_action = 'bihr_sync_product_sku';

if ( 'sync' === $action ) {
    check_admin_referer( $nonce_action );
}

$wrapper_class = 'wrap bihr-tool-page';
?>
<style>
.bihr-tool-page .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
.bihr-tool-page .stat-box { background: #f0f6fc; padding: 15px; border-radius: 5px; border-left: 4px solid #2271b1; }
.bihr-tool-page .stat-box strong { display: block; font-size: 24px; color: #2271b1; }
.bihr-tool-page .success { color: #00a32a; }
.bihr-tool-page .warning { color: #dba617; }
.bihr-tool-page .error { color: #d63638; }
.bihr-tool-page .progress { background: #e0e0e0; border-radius: 10px; height: 30px; margin: 20px 0; overflow: hidden; }
.bihr-tool-page .progress-bar { background: linear-gradient(90deg, #2271b1, #5fa3d0); height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
.bihr-tool-page .log { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 11px; }
.bihr-tool-page .log-entry { padding: 4px 0; border-bottom: 1px solid #eee; }
.bihr-tool-page table { width: 100%; border-collapse: collapse; font-size: 12px; }
.bihr-tool-page th { background: #2271b1; color: white; padding: 8px; text-align: left; }
.bihr-tool-page td { padding: 6px; border-bottom: 1px solid #ddd; }
</style>

<div class="<?php echo esc_attr( $wrapper_class ); ?>">
	<h1><?php esc_html_e( 'Synchronisation des SKU - Produits BIHR → WooCommerce', 'bihr-synch' ); ?></h1>

	<?php
	if ( 'sync' === $action ) {
		echo '<h2>' . esc_html__( 'Synchronisation en cours...', 'bihr-synch' ) . '</h2>';

		$batch_size = 500;

		$total = $wpdb->get_var(
			"SELECT COUNT(DISTINCT pm.post_id)
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = '_bihr_product_code'
			AND pm.meta_value IS NOT NULL
			AND pm.meta_value != ''
			AND p.post_type = 'product'"
		);

		echo '<div class="stats">';
		echo '<div class="stat-box"><strong>' . number_format( $total ) . '</strong> ' . esc_html__( 'Produits à traiter', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong>' . number_format( $offset ) . '</strong> ' . esc_html__( 'Déjà traités', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong>' . number_format( $total - $offset ) . '</strong> ' . esc_html__( 'Restants', 'bihr-synch' ) . '</div>';
		echo '</div>';

		$progress_percent = $total > 0 ? ( $offset / $total ) * 100 : 0;
		echo '<div class="progress">';
		echo '<div class="progress-bar" style="width: ' . esc_attr( (string) $progress_percent ) . '%">' . esc_html( round( $progress_percent, 1 ) ) . '%</div>';
		echo '</div>';

		$products = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					pm.post_id as wc_product_id,
					pm.meta_value as product_code,
					bp.id as bihr_id,
					p.post_title as name
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				LEFT JOIN {$wpdb->prefix}bihr_products bp ON bp.product_code = pm.meta_value
				WHERE pm.meta_key = '_bihr_product_code'
				AND pm.meta_value IS NOT NULL
				AND pm.meta_value != ''
				AND p.post_type = 'product'
				LIMIT %d OFFSET %d",
				$batch_size,
				$offset
			),
			ARRAY_A
		);

		echo '<div class="log">';
		$linked = 0;
		$sku_inserted = 0;
		$sku_updated = 0;
		$not_found = 0;
		$errors = 0;

		foreach ( $products as $product ) {
			$wc_product_id = $product['wc_product_id'];
			$bihr_id       = $product['bihr_id'];
			$product_code  = $product['product_code'];
			$sku           = $product_code;

			if ( $bihr_id && $bihr_id > 0 ) {
				$wpdb->update(
					$wpdb->prefix . 'bihr_products',
					array( 'product_id' => $wc_product_id ),
					array( 'id' => $bihr_id ),
					array( '%d' ),
					array( '%d' )
				);
				$linked++;
			}

			$existing_sku = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key = '_sku'",
					$wc_product_id
				)
			);

			if ( $existing_sku ) {
				$wpdb->update(
					$wpdb->postmeta,
					array( 'meta_value' => $sku ),
					array( 'post_id' => $wc_product_id, 'meta_key' => '_sku' ),
					array( '%s' ),
					array( '%d', '%s' )
				);
				echo '<div class="log-entry success">' . sprintf(
					/* translators: 1: WC product ID, 2: BIHR ID, 3: SKU value */
					esc_html__( 'WC #%1$d ← BIHR #%2$d : SKU → %3$s', 'bihr-synch' ),
					intval( $wc_product_id ),
					intval( $bihr_id ),
					esc_html( $sku )
				) . '</div>';
				$sku_updated++;
			} else {
				$result = $wpdb->insert(
					$wpdb->postmeta,
					array(
						'post_id'    => $wc_product_id,
						'meta_key'   => '_sku',
						'meta_value' => $sku,
					),
					array( '%d', '%s', '%s' )
				);

				if ( $result ) {
					echo '<div class="log-entry success">' . sprintf(
						esc_html__( 'WC #%1$d ← BIHR #%2$d : SKU créé → %3$s', 'bihr-synch' ),
						intval( $wc_product_id ),
						intval( $bihr_id ),
						esc_html( $sku )
					) . '</div>';
					$sku_inserted++;
				} else {
					echo '<div class="log-entry error">' . sprintf(
						esc_html__( 'WC #%d : Erreur INSERT', 'bihr-synch' ),
						intval( $wc_product_id )
					) . '</div>';
					$errors++;
				}
			}
		}

		echo '</div>';

		echo '<div class="stats">';
		echo '<div class="stat-box"><strong class="success">' . esc_html( (string) $linked ) . '</strong> ' . esc_html__( 'Produits liés', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong class="success">' . esc_html( (string) $sku_inserted ) . '</strong> ' . esc_html__( 'SKU créés', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong class="warning">' . esc_html( (string) $sku_updated ) . '</strong> ' . esc_html__( 'SKU mis à jour', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong class="error">' . esc_html( (string) $not_found ) . '</strong> ' . esc_html__( 'Non trouvés', 'bihr-synch' ) . '</div>';
		echo '</div>';

		$new_offset = $offset + $batch_size;

		if ( $new_offset < $total ) {
			$continue_url = wp_nonce_url(
				add_query_arg(
					array(
						'action' => 'sync',
						'offset' => $new_offset,
					)
				),
				$nonce_action
			);

			echo '<script>
				setTimeout(function() {
					window.location.href = ' . wp_json_encode( $continue_url ) . ';
				}, 1000);
			</script>';
			echo '<p class="warning">' . esc_html__( 'Rechargement dans 1 seconde...', 'bihr-synch' ) . '</p>';
		} else {
			echo '<h2 class="success">' . esc_html__( 'SYNCHRONISATION TERMINÉE !', 'bihr-synch' ) . '</h2>';
			echo '<a href="?page=bihr-sync-sku"><button class="button button-primary">' . esc_html__( 'Retour', 'bihr-synch' ) . '</button></a>';

			$final_sku = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != %s",
					'_sku',
					''
				)
			);
			echo '<p class="success">' . sprintf(
				esc_html__( 'Total SKU synchronisés : %s', 'bihr-synch' ),
				number_format( $final_sku )
			) . '</p>';
		}
	} else {
		$wc_with_bihr = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value IS NOT NULL AND meta_value != ''",
				'_bihr_product_code'
			)
		);
		$wc_products = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
				'product',
				'publish'
			)
		);
		$wc_sku_count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value != ''",
				'_sku'
			)
		);
		$missing = $wc_with_bihr - $wc_sku_count;

		echo '<div class="stats">';
		echo '<div class="stat-box"><strong>' . esc_html( number_format( $wc_with_bihr ) ) . '</strong> ' . esc_html__( 'Produits WC avec code BIHR', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong>' . esc_html( number_format( $wc_products ) ) . '</strong> ' . esc_html__( 'Total produits WooCommerce', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong class="success">' . esc_html( number_format( $wc_sku_count ) ) . '</strong> ' . esc_html__( 'SKU actuels', 'bihr-synch' ) . '</div>';
		echo '<div class="stat-box"><strong class="error">' . esc_html( number_format( $missing ) ) . '</strong> ' . esc_html__( 'SKU manquants', 'bihr-synch' ) . '</div>';
		echo '</div>';

		if ( $missing > 0 || 0 === $wc_sku_count ) {
			echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">';
			echo '<h3>' . esc_html__( 'Action requise', 'bihr-synch' ) . '</h3>';
			echo '<p>' . esc_html__( 'Ce script va lier les produits BIHR aux produits WooCommerce via leur code, puis synchroniser les SKU.', 'bihr-synch' ) . '</p>';
			echo '</div>';

			$sync_url = wp_nonce_url( add_query_arg( 'action', 'sync' ), $nonce_action );
			echo '<button class="button button-primary button-hero" onclick="if(confirm(\'' . esc_js(
				sprintf(
					__( 'Lancer la synchronisation de %s produits WooCommerce ?', 'bihr-synch' ),
					number_format( $wc_with_bihr )
				)
			) . '\')) window.location.href=' . wp_json_encode( $sync_url ) . '">'
				. esc_html__( 'LANCER LA SYNCHRONISATION', 'bihr-synch' ) . '</button>';
		} else {
			echo '<div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">';
			echo '<h3 class="success">' . esc_html__( 'Tous les SKU sont synchronisés !', 'bihr-synch' ) . '</h3>';
			echo '</div>';
		}

		echo '<h3>' . esc_html__( 'Aperçu (10 premiers produits)', 'bihr-synch' ) . '</h3>';
		$samples = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					pm_code.post_id as wc_id,
					p.post_title as name,
					pm_code.meta_value as product_code,
					pm_sku.meta_value as current_sku,
					bp.id as bihr_id
				FROM {$wpdb->postmeta} pm_code
				INNER JOIN {$wpdb->posts} p ON p.ID = pm_code.post_id
				LEFT JOIN {$wpdb->postmeta} pm_sku ON pm_sku.post_id = pm_code.post_id AND pm_sku.meta_key = %s
				LEFT JOIN {$wpdb->prefix}bihr_products bp ON bp.product_code = pm_code.meta_value
				WHERE pm_code.meta_key = %s
				AND pm_code.meta_value IS NOT NULL
				AND pm_code.meta_value != ''
				AND p.post_type = %s
				LIMIT 10",
				'_sku',
				'_bihr_product_code',
				'product'
			),
			ARRAY_A
		);

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr><th>' . esc_html__( 'WC ID', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Nom Produit', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Code BIHR', 'bihr-synch' ) . '</th><th>' . esc_html__( 'SKU Actuel', 'bihr-synch' ) . '</th><th>' . esc_html__( 'BIHR ID', 'bihr-synch' ) . '</th><th>' . esc_html__( 'Statut', 'bihr-synch' ) . '</th></tr></thead>';
		echo '<tbody>';

		foreach ( $samples as $row ) {
			$has_sku  = ! empty( $row['current_sku'] );
			$has_bihr = ! empty( $row['bihr_id'] );

			if ( ! $has_bihr ) {
				$status = '<span class="warning">' . esc_html__( 'Pas de lien BIHR', 'bihr-synch' ) . '</span>';
			} elseif ( ! $has_sku ) {
				$status = '<span class="warning">' . esc_html__( 'Manque SKU', 'bihr-synch' ) . '</span>';
			} else {
				$status = '<span class="success">' . esc_html__( 'OK', 'bihr-synch' ) . '</span>';
			}

			echo '<tr>';
			echo '<td>' . esc_html( $row['wc_id'] ) . '</td>';
			echo '<td>' . esc_html( mb_substr( $row['name'], 0, 50 ) ) . '...</td>';
			echo '<td><strong>' . esc_html( $row['product_code'] ) . '</strong></td>';
			echo '<td>' . ( ! empty( $row['current_sku'] ) ? esc_html( $row['current_sku'] ) : '-' ) . '</td>';
			echo '<td>' . ( ! empty( $row['bihr_id'] ) ? esc_html( $row['bihr_id'] ) : '-' ) . '</td>';
			echo '<td>' . wp_kses_post( $status ) . '</td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo '</table>';
	}
	?>
</div>

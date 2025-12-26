<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Variables fournies par BihrWI_Admin::render_products_page()
 *
 * @var array $products
 * @var int   $current_page
 * @var int   $per_page
 * @var int   $total
 * @var int   $total_pages
 * @var string $filter_search
 * @var string $filter_stock
 * @var string $filter_price_min
 * @var string $filter_price_max
 * @var string $filter_category
 * @var string $sort_by
 */

// Initialiser les variables de filtre si elles n'existent pas
if ( ! isset( $filter_search ) ) {
    $filter_search = '';
}
if ( ! isset( $filter_stock ) ) {
    $filter_stock = '';
}
if ( ! isset( $filter_price_min ) ) {
    $filter_price_min = '';
}
if ( ! isset( $filter_price_max ) ) {
    $filter_price_max = '';
}
if ( ! isset( $filter_category ) ) {
    $filter_category = '';
}
if ( ! isset( $sort_by ) ) {
    $sort_by = '';
}

$status_data = get_option( 'bihrwi_prices_generation', array() );
?>

<div class="wrap">
    <h1>Bihr Import – Produits Bihr</h1>

    <?php if ( ! empty( $bihrwi_debug ) && current_user_can( 'manage_woocommerce' ) ) : ?>
        <div class="notice notice-info" style="padding:10px;">
            <p style="margin:0;"><strong>BIHR Debug</strong></p>
            <p style="margin:6px 0 0;">
                Page: <strong><?php echo intval( $current_page ); ?></strong> / <strong><?php echo intval( $total_pages ); ?></strong>
                — Total: <strong><?php echo intval( $total ); ?></strong>
                — Per page: <strong><?php echo intval( $per_page ); ?></strong>
            </p>
            <p style="margin:6px 0 0;">
                Filtres: search=<code><?php echo esc_html( $filter_search ); ?></code>,
                stock=<code><?php echo esc_html( $filter_stock ); ?></code>,
                price_min=<code><?php echo esc_html( $filter_price_min ); ?></code>,
                price_max=<code><?php echo esc_html( $filter_price_max ); ?></code>,
                category=<code><?php echo esc_html( $filter_category ); ?></code>,
                sort_by=<code><?php echo esc_html( $sort_by ); ?></code>
            </p>
            <?php if ( ! empty( $debug_count_last_error ) || ! empty( $debug_products_last_error ) ) : ?>
                <p style="margin:6px 0 0; color:#b91c1c;"><strong>DB errors:</strong>
                    <code><?php echo esc_html( trim( (string) $debug_count_last_error ) ); ?></code>
                    <code><?php echo esc_html( trim( (string) $debug_products_last_error ) ); ?></code>
                </p>
            <?php endif; ?>
            <details style="margin-top:8px;">
                <summary>Requêtes SQL</summary>
                <div style="margin-top:6px; font-family: monospace; font-size: 12px; white-space: pre-wrap;">
                    <div><strong>COUNT</strong>: <?php echo esc_html( (string) $debug_count_last_query ); ?></div>
                    <div style="margin-top:6px;"><strong>PRODUCTS</strong>: <?php echo esc_html( (string) $debug_products_last_query ); ?></div>
                </div>
            </details>
        </div>
    <?php endif; ?>

    <?php
    /* =======================
     *  NOTIFICATIONS (GET)
     * ======================= */

    // Fusion catalogues
    if ( isset( $_GET['bihrwi_merge_success'] ) ) : ?>
        <div class="notice notice-success"><p>
            Fusion des catalogues terminée. <?php echo intval( $_GET['bihrwi_merge_count'] ); ?> produits fusionnés.
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_merge_error'] ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors de la fusion des catalogues :
            <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?>
        </p></div>
    <?php endif; ?>

    <!-- Import produit -->
    <?php if ( isset( $_GET['bihrwi_import_success'] ) ) : ?>
        <div class="notice notice-success"><p>
            Produit importé dans WooCommerce (ID : <?php echo intval( $_GET['imported_id'] ); ?>).
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_import_error'] ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors de l’import du produit :
            <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?>
        </p></div>
    <?php endif; ?>

    <!-- Statut vérification manuelle du catalog Prices -->
    <?php if ( isset( $_GET['bihrwi_check_status'] ) ) : ?>
        <?php if ( $_GET['bihrwi_check_status'] === 'processing' ) : ?>
            <div class="notice notice-warning"><p>
                Le fichier Prices est toujours en cours de génération (PROCESSING).
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'done_and_merged' ) : ?>
            <div class="notice notice-success"><p>
                <strong>✓ Succès complet !</strong><br>
                Le catalogue Prices a été téléchargé et automatiquement fusionné avec les autres catalogues.<br>
                <strong><?php echo isset( $_GET['total_products'] ) ? intval( $_GET['total_products'] ) : 0; ?> produits</strong> sont maintenant disponibles.
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'done_merge_failed' ) : ?>
            <div class="notice notice-warning"><p>
                Le catalogue Prices a été téléchargé, mais la fusion automatique a échoué.<br>
                Vous pouvez essayer de lancer la fusion manuellement ci-dessous.
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'done' ) : ?>
            <div class="notice notice-success"><p>
                Le fichier Prices est prêt et a été téléchargé.
                <?php if ( isset( $_GET['merge_error'] ) ) : ?>
                    <br><strong>Note:</strong> La fusion automatique a rencontré une erreur : <?php echo esc_html( wp_unslash( $_GET['merge_error'] ) ); ?>
                <?php endif; ?>
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'error' ) : ?>
            <div class="notice notice-error"><p>
                Erreur lors de la génération du fichier Prices :
                <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?>
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'downloadfail' ) : ?>
            <div class="notice notice-error"><p>
                Le fichier Prices est marqué comme prêt, mais le téléchargement a échoué.
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'exception' ) : ?>
            <div class="notice notice-error"><p>
                Erreur inattendue lors de la vérification du catalog Prices :
                <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?>
            </p></div>
        <?php elseif ( $_GET['bihrwi_check_status'] === 'noticket' ) : ?>
            <div class="notice notice-error"><p>
                Aucun TicketID en cours. Lance d’abord la génération du catalog Prices.
            </p></div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_prices_started'] ) ) : ?>
        <div class="notice notice-success"><p>
            Génération du catalog Prices démarrée. Le statut sera vérifié automatiquement par WP-Cron.
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_prices_error'] ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors du démarrage du catalog Prices :
            <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?>
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_reset_success'] ) ) : ?>
        <div class="notice notice-success"><p>
            Toutes les données ont été effacées avec succès.
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_download_success'] ) ) : ?>
        <div class="notice notice-success"><p>
            Téléchargement terminé ! 
            <?php 
            $catalogs = isset( $_GET['bihrwi_catalogs_count'] ) ? intval( $_GET['bihrwi_catalogs_count'] ) : 0;
            $files = intval( $_GET['bihrwi_files_count'] );
            echo $catalogs > 0 ? $catalogs . ' catalogue(s) téléchargé(s), ' : '';
            echo $files . ' fichier(s) CSV extrait(s) dans le dossier d\'import.';
            ?>
        </p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_download_error'] ) ) : ?>
        <div class="notice notice-error"><p>
            Erreur lors du téléchargement des catalogues :
            <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?>
        </p></div>
    <?php endif; ?>


    <!-- =========================================================
         1. FUSION DES CATALOGUES CSV
    ========================================================== -->

    <h2>1. Fusion des catalogues CSV</h2>

    <div class="bihr-section">
        <h3>Option A : Téléchargement automatique depuis l'API Bihr</h3>
        <p>
            Télécharge automatiquement les catalogues <code>References</code>, <code>ExtendedReferences</code>, 
            <code>Attributes</code>, <code>Images</code> et <code>Stocks</code> depuis l'API Bihr et les extrait dans le dossier d'import.
            <br><strong>⚠️ Cette opération peut prendre plusieurs minutes.</strong>
        </p>

        <form method="post" id="bihr-download-all-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'bihrwi_download_all_action', 'bihrwi_download_all_nonce' ); ?>
            <input type="hidden" name="action" value="bihrwi_download_all_catalogs" />
            <?php submit_button( '📥 Télécharger tous les catalogues (References, ExtendedReferences, Attributes, Images, Stocks)', 'primary large', 'submit', false ); ?>
        </form>

        <div id="bihr-download-progress" class="bihr-progress-container">
            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-download-progress-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-download-progress-text" class="bihr-progress-text">Initialisation...</div>
        </div>
    </div>

    <div class="bihr-section">
        <h3>Option B : Import manuel des fichiers CSV</h3>
        <p>
            Place tous les fichiers CSV Bihr (<code>references</code>, <code>extendedreferences</code>, 
            <code>prices</code>, <code>images</code>, <code>inventory</code>, <code>attributes</code>) dans
            <code>wp-content/uploads/bihr-import/</code>, puis clique sur le bouton ci-dessous.
        </p>

        <form method="post" id="bihr-merge-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'bihrwi_merge_catalogs_action', 'bihrwi_merge_catalogs_nonce' ); ?>
            <input type="hidden" name="action" value="bihrwi_merge_catalogs" />
            <?php submit_button( 'Fusionner les catalogues', 'secondary', 'submit', false ); ?>
        </form>

        <div id="bihr-merge-progress" class="bihr-progress-container">
            <div class="bihr-progress-bar-wrapper">
                <div id="bihr-merge-progress-bar" class="bihr-progress-bar"></div>
            </div>
            <div id="bihr-merge-progress-text" class="bihr-progress-text">Initialisation...</div>
        </div>
    </div>

    <!-- Bouton pour effacer les données -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;" onsubmit="return confirm('Êtes-vous sûr de vouloir effacer toutes les données de la table wp_bihr_products ?');">
        <?php wp_nonce_field( 'bihrwi_reset_data_action', 'bihrwi_reset_data_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_reset_data" />
        <?php submit_button( 'Effacer les données', 'delete', '', false ); ?>
    </form>

    <hr />

    <!-- =========================================================
         2. CATALOG PRICES (ASYNC)
    ========================================================== -->
    <h2>2. Catalog Prices (gestion asynchrone)</h2>

    <p>
        Le catalog <strong>Prices</strong> est spécifique à ton compte et peut prendre 30 à 60 minutes
        pour être généré lors de la première demande de la journée. Pour éviter les timeouts,
        la génération est surveillée en tâche de fond via WP-Cron.
    </p>

    <!-- Bouton pour démarrer la génération -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'bihrwi_start_prices_action', 'bihrwi_start_prices_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_start_prices_generation" />
        <?php submit_button( 'Lancer la génération du catalog Prices', 'secondary' ); ?>
    </form>

    <!-- Bouton pour vérifier immédiatement -->
    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top:10px;">
        <?php wp_nonce_field( 'bihrwi_check_prices_now_action', 'bihrwi_check_prices_now_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_check_prices_now" />
        <?php submit_button( 'Vérifier maintenant si le catalog Prices est prêt', 'secondary' ); ?>
    </form>

    <p>
        <?php if ( ! empty( $status_data['ticket_id'] ) ) : ?>
            <strong>TicketID actuel :</strong> <?php echo esc_html( $status_data['ticket_id'] ); ?><br />
            <?php if ( ! empty( $status_data['started_at'] ) ) : ?>
                <em>Démarré le : <?php echo esc_html( $status_data['started_at'] ); ?></em><br />
            <?php endif; ?>
            <?php if ( ! empty( $status_data['last_status'] ) ) : ?>
                <strong>Dernier statut :</strong> <?php echo esc_html( $status_data['last_status'] ); ?><br />
            <?php endif; ?>
            <?php if ( ! empty( $status_data['last_checked'] ) ) : ?>
                <em>Dernière vérification cron :</em> <?php echo esc_html( $status_data['last_checked'] ); ?><br />
            <?php endif; ?>
            Le plugin vérifie automatiquement le statut toutes les 5 minutes via WP-Cron
            et télécharge le fichier dès qu’il est prêt.
        <?php else : ?>
            Aucune génération de catalog Prices en cours actuellement.
        <?php endif; ?>
    </p>

    <hr />

    <!-- =========================================================
         3. PREVIEW TABLE wp_bihr_products
    ========================================================== -->

    <h2>3. Prévisualisation des produits Bihr (table wp_bihr_products)</h2>

    <p>
        <strong>Total :</strong> <?php echo intval( $total ); ?> produits
        <?php if ( ! empty( $filter_search ) || ! empty( $filter_stock ) || $filter_price_min !== '' || $filter_price_max !== '' || ! empty( $filter_category ) || ! empty( $sort_by ) ) : ?>
            (filtrés)
        <?php endif; ?>
    </p>

    <!-- Filtres -->
    <div class="bihr-section">
        <h3>🔍 Filtres de recherche</h3>
        <form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="bihr-filters-form">
            <input type="hidden" name="page" value="bihrwi_products" />
            <?php wp_nonce_field( 'bihrwi_filter_nonce', 'bihrwi_filter_nonce_field' ); ?>
            
            <!-- Recherche (largeur complète) -->
            <div class="bihr-filter-field bihr-filter-search">
                <label for="search">
                    Recherche (code, NewPartNumber, nom, description)
                </label>
                <input type="text" 
                       name="search" 
                       id="search" 
                       value="<?php echo esc_attr( $filter_search ); ?>" 
                       placeholder="Saisir un mot-clé..." />
            </div>

            <!-- Grille des filtres 2-3 colonnes -->
            <div class="bihr-filters-grid">
                <div class="bihr-filter-field">
                    <label for="stock_filter">
                        Stock
                    </label>
                    <select name="stock_filter" id="stock_filter">
                        <option value="">Tous</option>
                        <option value="in_stock" <?php selected( $filter_stock, 'in_stock' ); ?>>En stock</option>
                        <option value="out_of_stock" <?php selected( $filter_stock, 'out_of_stock' ); ?>>Hors stock</option>
                    </select>
                </div>

                <div class="bihr-filter-field">
                    <label for="price_min">
                        Prix minimum (€)
                    </label>
                    <input type="number" 
                           name="price_min" 
                           id="price_min" 
                           value="<?php echo esc_attr( $filter_price_min ); ?>" 
                           placeholder="0.00"
                           step="0.01"
                           min="0" />
                </div>

                <div class="bihr-filter-field">
                    <label for="price_max">
                        Prix maximum (€)
                    </label>
                    <input type="number" 
                           name="price_max" 
                           id="price_max" 
                           value="<?php echo esc_attr( $filter_price_max ); ?>" 
                           placeholder="9999.99"
                           step="0.01"
                           min="0" />
                </div>

                <div class="bihr-filter-field">
                    <label for="category_filter">
                        Catégorie
                    </label>
                    <select name="category_filter" id="category_filter">
                        <option value="">Toutes</option>
                        <?php if ( ! empty( $available_categories ) ) : ?>
                            <?php foreach ( $available_categories as $cat ) : ?>
                                <option value="<?php echo esc_attr( $cat ); ?>" <?php selected( $filter_category, $cat ); ?>>
                                    <?php echo esc_html( $cat ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="bihr-filter-field">
                    <label for="sort_by">
                        Trier par
                    </label>
                    <select name="sort_by" id="sort_by">
                        <option value="">Par défaut (ID)</option>
                        <option value="name_asc" <?php selected( $sort_by, 'name_asc' ); ?>>Nom (A → Z)</option>
                        <option value="name_desc" <?php selected( $sort_by, 'name_desc' ); ?>>Nom (Z → A)</option>
                        <option value="price_asc" <?php selected( $sort_by, 'price_asc' ); ?>>Prix croissant</option>
                        <option value="price_desc" <?php selected( $sort_by, 'price_desc' ); ?>>Prix décroissant</option>
                        <option value="stock_asc" <?php selected( $sort_by, 'stock_asc' ); ?>>Stock croissant</option>
                        <option value="stock_desc" <?php selected( $sort_by, 'stock_desc' ); ?>>Stock décroissant</option>
                    </select>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="bihr-filters-actions">
                <?php submit_button( 'Filtrer', 'secondary', 'submit', false ); ?>
                <?php if ( ! empty( $filter_search ) || ! empty( $filter_stock ) || ! empty( $filter_price_min ) || ! empty( $filter_price_max ) || ! empty( $filter_category ) || ! empty( $sort_by ) ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=bihrwi_products' ) ); ?>" class="button">
                        Réinitialiser
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Barre de progression et import multiple -->
    <div id="bihr-import-progress" style="display:none; margin: 20px 0; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
        <h3 style="margin-top: 0;">Import en cours...</h3>
        <div style="background: #f0f0f1; height: 30px; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
            <div id="bihr-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;"></div>
        </div>
        <p id="bihr-progress-text">0 / 0 produits importés</p>
        <div id="bihr-progress-details" style="max-height: 200px; overflow-y: auto; background: #f9f9f9; padding: 10px; border-radius: 4px; font-size: 12px;"></div>
    </div>

    <div style="margin-bottom: 15px;">
        <button id="bihr-select-all" class="button" style="margin-right: 5px;">Sélectionner tout</button>
        <button id="bihr-deselect-all" class="button" style="margin-right: 5px;">Désélectionner tout</button>
        <button id="bihr-import-selected" class="button button-primary" disabled>
            <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
            Importer les produits sélectionnés (<span id="bihr-selected-count">0</span>)
        </button>
    </div>

    <table class="widefat fixed striped">
        <thead>
            <tr>
                <th style="width:40px;">
                    <input type="checkbox" id="bihr-select-all-checkbox" title="Tout sélectionner" />
                </th>
                <th style="width:60px;">ID</th>
                <th style="width:120px;">Code produit</th>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Description</th>
                <th style="width:80px;">Image</th>
                <th style="width:80px;">Stock</th>
                <th style="width:120px;">Prix HT (dealer)</th>
                <th style="width:160px;">Action</th>
            </tr>
        </thead>
        <tbody>
        <?php if ( ! empty( $products ) ) : ?>
            <?php foreach ( $products as $row ) : ?>
                <tr>
                    <td>
                        <input type="checkbox" class="bihr-product-checkbox" 
                               value="<?php echo intval( $row->id ); ?>" 
                               data-name="<?php echo esc_attr( $row->name ?: $row->product_code ); ?>" />
                    </td>
                    <td><?php echo intval( $row->id ); ?></td>
                    <td><?php echo esc_html( ! empty( $row->new_part_number ) ? $row->new_part_number : $row->product_code ); ?></td>
                    <td><?php echo esc_html( $row->name ); ?></td>
                    <td>
                        <?php
                        if ( ! empty( $row->category ) ) {
                            echo esc_html( $row->category );
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        $desc = $row->description;
                        if ( $desc ) {
                            // On tronque un peu pour l'affichage
                            $desc = wp_trim_words( $desc, 30, '…' );
                            echo nl2br( esc_html( $desc ) );
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ( ! empty( $row->image_url ) ) {
                            $img_url = $row->image_url;
                            // Si ce n’est pas une URL absolue, on ajoute le préfixe https://api.mybihr.com
                            if ( ! preg_match( '#^https?://#i', $img_url ) ) {
                                $img_url = rtrim( BIHRWI_IMAGE_BASE_URL, '/' ) . '/' . ltrim( $img_url, '/' );
                            }
                            ?>
                            <img src="<?php echo esc_url( $img_url ); ?>" style="max-width:60px;height:auto;" />
                            <?php
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ( $row->stock_level !== null ) {
                            echo intval( $row->stock_level );
                            if ( ! empty( $row->stock_description ) ) {
                                echo '<br /><small>' . esc_html( $row->stock_description ) . '</small>';
                            }
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <?php
                        if ( $row->dealer_price_ht !== null ) {
                            $price = (float) $row->dealer_price_ht;
                            echo esc_html( number_format( $price, 2, ',', ' ' ) ) . ' €';
                        } else {
                            echo '&mdash;';
                        }
                        ?>
                    </td>
                    <td>
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                            <?php wp_nonce_field( 'bihrwi_import_product_action', 'bihrwi_import_product_nonce' ); ?>
                            <input type="hidden" name="action" value="bihrwi_import_product" />
                            <input type="hidden" name="bihrwi_product_id" value="<?php echo intval( $row->id ); ?>" />
                            <?php submit_button( 'Importer dans WooCommerce', 'secondary small', '', false ); ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else : ?>
            <tr><td colspan="8">Aucun produit trouvé dans la table <code>wp_bihr_products</code>.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php
                // Préserver exactement les filtres actuellement dans l'URL.
                // Important: ne pas utiliser empty() (ex: "0" serait perdu).
                $allowed_keys = array( 'search', 'stock_filter', 'price_min', 'price_max', 'category_filter', 'sort_by' );
                $params = array( 'page' => 'bihr-products' );

                foreach ( $allowed_keys as $key ) {
                    if ( isset( $_GET[ $key ] ) && wp_unslash( $_GET[ $key ] ) !== '' ) {
                        $params[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) );
                    }
                }

                $base_admin_url = admin_url( 'admin.php' );

                if ( $current_page > 1 ) {
                    $prev_params = array_merge( $params, array( 'paged' => $current_page - 1 ) );
                    $prev_url = $base_admin_url . '?' . http_build_query( $prev_params, '', '&', PHP_QUERY_RFC3986 );
                    echo '<a class="button" href="' . esc_url( $prev_url ) . '">&laquo; Page précédente</a> ';
                }

                if ( $current_page < $total_pages ) {
                    $next_params = array_merge( $params, array( 'paged' => $current_page + 1 ) );
                    $next_url = $base_admin_url . '?' . http_build_query( $next_params, '', '&', PHP_QUERY_RFC3986 );
                    echo '<a class="button" href="' . esc_url( $next_url ) . '">Page suivante &raquo;</a>';
                }
                ?>
                <span style="margin-left:10px;">
                    Page <?php echo intval( $current_page ); ?> / <?php echo intval( $total_pages ); ?>
                </span>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap">
    <h1><?php esc_html_e( 'Paramètres de Synchronisation des Commandes', 'bihr-woocommerce-importer' ); ?></h1>

    <?php if ( isset( $success_message ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html( $success_message ); ?></p>
        </div>
    <?php endif; ?>

    <div class="bihr-section">
        <h2><?php esc_html_e( 'Configuration de la Synchronisation Automatique', 'bihr-woocommerce-importer' ); ?></h2>
        <p>
            <?php esc_html_e( 'Lorsqu\'une commande est créée dans WooCommerce, le plugin peut automatiquement la transmettre à l\'API BIHR pour traitement.', 'bihr-woocommerce-importer' ); ?>
        </p>

        <form method="post" action="">
            <?php wp_nonce_field( 'bihrwi_order_settings_action', 'bihrwi_order_settings_nonce' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="bihrwi_auto_sync_orders">
                            <?php esc_html_e( 'Synchronisation automatique', 'bihr-woocommerce-importer' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="bihrwi_auto_sync_orders" 
                                   name="bihrwi_auto_sync_orders" 
                                   value="1" 
                                   <?php checked( $auto_sync_orders, 1 ); ?>>
                            <?php esc_html_e( 'Activer la synchronisation automatique des commandes vers BIHR', 'bihr-woocommerce-importer' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Si activé, les commandes WooCommerce seront automatiquement envoyées à l\'API BIHR lors de leur création.', 'bihr-woocommerce-importer' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bihrwi_auto_checkout">
                            <?php esc_html_e( 'Validation automatique', 'bihr-woocommerce-importer' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="bihrwi_auto_checkout" 
                                   name="bihrwi_auto_checkout" 
                                   value="1" 
                                   <?php checked( $auto_checkout, 1 ); ?>>
                            <?php esc_html_e( 'Activer la validation automatique des commandes (IsAutomaticCheckoutActivated)', 'bihr-woocommerce-importer' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Si activé, les commandes seront automatiquement validées côté BIHR sans intervention manuelle.', 'bihr-woocommerce-importer' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bihrwi_weekly_free_shipping">
                            <?php esc_html_e( 'Livraison gratuite hebdomadaire', 'bihr-woocommerce-importer' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="bihrwi_weekly_free_shipping" 
                                   name="bihrwi_weekly_free_shipping" 
                                   value="1" 
                                   <?php checked( $weekly_free_shipping, 1 ); ?>>
                            <?php esc_html_e( 'Activer la livraison gratuite hebdomadaire (IsWeeklyFreeShippingActivated)', 'bihr-woocommerce-importer' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Si activé, bénéficiez de la livraison gratuite hebdomadaire selon les conditions BIHR.', 'bihr-woocommerce-importer' ); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="bihrwi_delivery_mode">
                            <?php esc_html_e( 'Mode de livraison', 'bihr-woocommerce-importer' ); ?>
                        </label>
                    </th>
                    <td>
                        <select id="bihrwi_delivery_mode" name="bihrwi_delivery_mode">
                            <option value="Default" <?php selected( $delivery_mode, 'Default' ); ?>>
                                <?php esc_html_e( 'Par défaut (Default)', 'bihr-woocommerce-importer' ); ?>
                            </option>
                            <option value="Express" <?php selected( $delivery_mode, 'Express' ); ?>>
                                <?php esc_html_e( 'Express', 'bihr-woocommerce-importer' ); ?>
                            </option>
                            <option value="Standard" <?php selected( $delivery_mode, 'Standard' ); ?>>
                                <?php esc_html_e( 'Standard', 'bihr-woocommerce-importer' ); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Sélectionnez le mode de livraison par défaut pour toutes les commandes.', 'bihr-woocommerce-importer' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" name="bihrwi_save_order_settings" class="button button-primary">
                    <?php esc_html_e( 'Enregistrer les paramètres', 'bihr-woocommerce-importer' ); ?>
                </button>
            </p>
        </form>
    </div>

    <div class="bihr-section">
        <h2><?php esc_html_e( 'Order/Data (BIHR)', 'bihr-woocommerce-importer' ); ?></h2>
        <p>
            <?php esc_html_e( 'Saisissez un ID de commande WooCommerce pour récupérer les informations BIHR via GET /api/v2.1/Order/Data (TicketId provenant de la commande).', 'bihr-woocommerce-importer' ); ?>
        </p>
        <p style="margin: 0 0 10px;">
            <label for="bihrwi_order_data_order_id" style="display:inline-block; min-width: 160px;">
                <?php esc_html_e( 'ID commande WooCommerce', 'bihr-woocommerce-importer' ); ?>
            </label>
            <input type="number" id="bihrwi_order_data_order_id" min="1" style="width: 140px;" />
            <button type="button" class="button" id="bihrwi_order_data_fetch_btn">
                <?php esc_html_e( 'Récupérer', 'bihr-woocommerce-importer' ); ?>
            </button>
        </p>
        <div id="bihrwi_order_data_manual_status" style="margin: 8px 0; color:#666;"></div>
        <pre id="bihrwi_order_data_manual_pre" style="margin:0; max-height: 380px; overflow:auto; white-space: pre-wrap; word-break: break-word; background:#f6f7f7; padding:10px; border:1px solid #dcdcde; border-radius:4px;"><?php
            echo esc_html__( 'Renseignez un ID de commande et cliquez sur “Récupérer”.', 'bihr-woocommerce-importer' );
        ?></pre>
    </div>

    <div class="bihr-section">
        <h2><?php esc_html_e( 'Informations de Synchronisation', 'bihr-woocommerce-importer' ); ?></h2>
        
        <h3><?php esc_html_e( 'Comment ça fonctionne ?', 'bihr-woocommerce-importer' ); ?></h3>
        <ol>
            <li><?php esc_html_e( 'Un client passe une commande sur votre boutique WooCommerce', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Le plugin vérifie si la commande contient des produits BIHR (avec un code produit BIHR)', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'La commande est automatiquement envoyée à l\'API BIHR avec les informations de livraison', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Une note est ajoutée à la commande WooCommerce avec l\'ID de commande BIHR', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Les logs détaillés sont disponibles dans la page "Logs"', 'bihr-woocommerce-importer' ); ?></li>
        </ol>

        <h3><?php esc_html_e( 'Format de la commande envoyée à BIHR', 'bihr-woocommerce-importer' ); ?></h3>
        <pre style="background: #f0f0f1; padding: 15px; border: 1px solid #c3c4c7; border-radius: 4px; overflow-x: auto;">
{
  "Order": {
    "CustomerReference": "Order for John Doe's motorbike",
    "Lines": [
      {
        "ProductId": "TPCI07495",
        "Quantity": 14,
        "ReferenceType": "Not used anymore",
        "CustomerReference": "Brakes for John Doe's motorbike",
        "ReservedQuantity": 0
      }
    ],
    "IsAutomaticCheckoutActivated": true,
    "IsWeeklyFreeShippingActivated": true,
    "DeliveryMode": "Default"
  },
  "DropShippingAddress": {
    "FirstName": "André",
    "LastName": "Millet",
    "Line1": "19, rue Blondel",
    "Line2": "1er étage",
    "ZipCode": "22106",
    "Town": "Toussaint",
    "Country": "FR",
    "Phone": "+33123456789"
  }
}</pre>

        <h3><?php esc_html_e( 'Données stockées dans WooCommerce', 'bihr-woocommerce-importer' ); ?></h3>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Meta Key', 'bihr-woocommerce-importer' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'bihr-woocommerce-importer' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>_bihr_sync_ticket_id</code></td>
                    <td><?php esc_html_e( 'Ticket ID unique pour tracer toutes les étapes', 'bihr-woocommerce-importer' ); ?></td>
                </tr>
                <tr>
                    <td><code>_bihr_order_synced</code></td>
                    <td><?php esc_html_e( 'Indique si la commande a été synchronisée avec succès', 'bihr-woocommerce-importer' ); ?></td>
                </tr>
                <tr>
                    <td><code>_bihr_order_id</code></td>
                    <td><?php esc_html_e( 'ID de la commande côté BIHR', 'bihr-woocommerce-importer' ); ?></td>
                </tr>
                <tr>
                    <td><code>_bihr_sync_date</code></td>
                    <td><?php esc_html_e( 'Date et heure de synchronisation', 'bihr-woocommerce-importer' ); ?></td>
                </tr>
                <tr>
                    <td><code>_bihr_order_sync_failed</code></td>
                    <td><?php esc_html_e( 'Indique si la synchronisation a échoué', 'bihr-woocommerce-importer' ); ?></td>
                </tr>
                <tr>
                    <td><code>_bihr_sync_error</code></td>
                    <td><?php esc_html_e( 'Message d\'erreur en cas d\'échec', 'bihr-woocommerce-importer' ); ?></td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top: 20px;"><?php esc_html_e( 'Notes importantes', 'bihr-woocommerce-importer' ); ?></h3>
        <ul>
            <li><?php esc_html_e( 'Seuls les produits avec un code produit BIHR (meta _bihr_product_code) seront synchronisés', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Les commandes sont envoyées au moment de la création (hook: woocommerce_checkout_order_processed)', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Les numéros de téléphone français sont automatiquement formatés au format international (+33)', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Les adresses de livraison sont utilisées en priorité, sinon les adresses de facturation', 'bihr-woocommerce-importer' ); ?></li>
            <li><?php esc_html_e( 'Toutes les opérations sont loguées dans la page "Logs"', 'bihr-woocommerce-importer' ); ?></li>
        </ul>
    </div>

    <?php
    // Afficher les dernières commandes synchronisées
    $recent_orders = get_posts( array(
        'post_type'      => 'shop_order',
        'posts_per_page' => 10,
        'meta_key'       => '_bihr_order_synced',
        'meta_value'     => '1',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    if ( ! empty( $recent_orders ) ) :
    ?>
        <div class="bihr-section">
            <h2><?php esc_html_e( 'Dernières Commandes Synchronisées', 'bihr-woocommerce-importer' ); ?></h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Ticket WC', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'Ticket BIHR API', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'Commande WC', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'Client', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'ID BIHR', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'Date Sync', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'Statut', 'bihr-woocommerce-importer' ); ?></th>
                        <th><?php esc_html_e( 'Order/Data', 'bihr-woocommerce-importer' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $recent_orders as $post ) :
                        $order            = wc_get_order( $post->ID );
                        $ticket_id        = get_post_meta( $post->ID, '_bihr_sync_ticket_id', true );
                        $bihr_ticket_id   = get_post_meta( $post->ID, '_bihr_api_ticket_id', true );
                        $bihr_order_id    = get_post_meta( $post->ID, '_bihr_order_id', true );
                        $sync_date        = get_post_meta( $post->ID, '_bihr_sync_date', true );
                        $sync_failed      = get_post_meta( $post->ID, '_bihr_order_sync_failed', true );

                        $cached_order_data_json = get_post_meta( $post->ID, '_bihr_order_data_json', true );
                        $cached_order_data_at   = get_post_meta( $post->ID, '_bihr_order_data_fetched_at', true );
                    ?>
                        <tr>
                            <td><code style="font-size: 11px;"><?php echo esc_html( $ticket_id ?: 'N/A' ); ?></code></td>
                            <td>
                                <?php if ( $bihr_ticket_id ) : ?>
                                    <code style="font-size: 11px; color: #2271b1;"><?php echo esc_html( $bihr_ticket_id ); ?></code>
                                <?php else : ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">
                                    #<?php echo esc_html( $order->get_order_number() ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></td>
                            <td><?php echo esc_html( $bihr_order_id ?: 'N/A' ); ?></td>
                            <td><?php echo esc_html( $sync_date ?: 'N/A' ); ?></td>
                            <td>
                                <?php if ( $sync_failed ) : ?>
                                    <span style="color: red;">❌ <?php esc_html_e( 'Échec', 'bihr-woocommerce-importer' ); ?></span>
                                <?php else : ?>
                                    <span style="color: green;">✅ <?php esc_html_e( 'Synchronisé', 'bihr-woocommerce-importer' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ( $bihr_ticket_id ) : ?>
                                    <button
                                        type="button"
                                        class="button bihrwi-order-data-btn"
                                        data-order-id="<?php echo esc_attr( $post->ID ); ?>"
                                    >
                                        <?php esc_html_e( 'Voir', 'bihr-woocommerce-importer' ); ?>
                                    </button>

                                    <?php if ( ! empty( $cached_order_data_at ) ) : ?>
                                        <div style="margin-top: 6px; font-size: 11px; color: #666;">
                                            <?php echo esc_html( sprintf( __( 'Cache: %s', 'bihr-woocommerce-importer' ), $cached_order_data_at ) ); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else : ?>
                                    <span style="color: #999;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <tr class="bihrwi-order-data-row" data-order-id="<?php echo esc_attr( $post->ID ); ?>" style="display:none;">
                            <td colspan="8" style="background:#fff;">
                                <div style="padding: 10px;">
                                    <div class="bihrwi-order-data-status" style="margin-bottom: 8px; color:#666;"></div>
                                    <pre class="bihrwi-order-data-pre" style="margin:0; max-height: 380px; overflow:auto; white-space: pre-wrap; word-break: break-word; background:#f6f7f7; padding:10px; border:1px solid #dcdcde; border-radius:4px;"><?php
                                        if ( ! empty( $cached_order_data_json ) ) {
                                            $decoded_cached = json_decode( $cached_order_data_json, true );
                                            if ( json_last_error() === JSON_ERROR_NONE ) {
                                                echo esc_html( wp_json_encode( $decoded_cached, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
                                            } else {
                                                echo esc_html( $cached_order_data_json );
                                            }
                                        } else {
                                            echo esc_html__( 'Cliquez sur “Voir” pour récupérer les données via l’API BIHR (Order/Data).', 'bihr-woocommerce-importer' );
                                        }
                                    ?></pre>
                                    <div style="margin-top: 8px;">
                                        <button
                                            type="button"
                                            class="button button-secondary bihrwi-order-data-refresh-btn"
                                            data-order-id="<?php echo esc_attr( $post->ID ); ?>"
                                        >
                                            <?php esc_html_e( 'Actualiser', 'bihr-woocommerce-importer' ); ?>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else : ?>
        <div class="bihr-section">
            <h2><?php esc_html_e( 'Dernières Commandes Synchronisées', 'bihr-woocommerce-importer' ); ?></h2>
            <p style="margin:0; color:#666;">
                <?php esc_html_e( 'Aucune commande synchronisée BIHR n’a été trouvée pour le moment. La section Order/Data ci-dessus reste disponible si vous connaissez l’ID de commande WooCommerce.', 'bihr-woocommerce-importer' ); ?>
            </p>
        </div>
    <?php endif; ?>
</div>

<script>
(function($){
    // ============================================================
    // UTILITAIRES DE FORMATAGE
    // ============================================================
    
    /**
     * Échappe le HTML pour prévenir les XSS
     * @param {string} text Le texte à échapper
     * @returns {string} Texte échappé
     */
    function escapeHtml(text) {
        if (!text && text !== 0) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    function prettyJson(value){
        try {
            return JSON.stringify(value, null, 2);
        } catch (e) {
            return String(value);
        }
    }

    /**
     * Formate un prix en € français avec gestion d'erreurs
     * @param {number|string} price Le prix à formater
     * @param {string} defaultValue Valeur par défaut
     * @returns {string} Format "XX,XX €"
     */
    function formatPrice(price, defaultValue = 'N/A') {
        if (price === null || price === undefined || price === '') {
            console.log('[BIHR] Prix vide:', price);
            return defaultValue;
        }
        const p = parseFloat(price);
        if (isNaN(p)) {
            console.warn('[BIHR] Prix invalide:', price);
            return defaultValue;
        }
        return p.toFixed(2).replace('.', ',') + ' €';
    }

    /**
     * Formate une date ISO en français avec gestion d'erreurs
     * @param {string} dateStr La date au format ISO
     * @returns {string} Date formatée
     */
    function formatDate(dateStr) {
        if (!dateStr || dateStr === '0001-01-01T00:00:00') {
            return 'N/A';
        }
        try {
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) {
                console.warn('[BIHR] Date invalide:', dateStr);
                return dateStr;
            }
            return d.toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            console.error('[BIHR] Erreur formatDate:', e);
            return dateStr;
        }
    }

    /**
     * Formate une adresse avec retours à la ligne HTML
     * @param {Object} addr Objet adresse
     * @returns {string} HTML formaté
     */
    function formatAddress(addr) {
        if (!addr) {
            console.warn('[BIHR] Adresse vide');
            return '';
        }
        const parts = [];
        if (addr.Line1) parts.push(escapeHtml(addr.Line1));
        if (addr.Line2) parts.push(escapeHtml(addr.Line2));
        
        const zipCity = [];
        if (addr.ZipCode) zipCity.push(escapeHtml(addr.ZipCode));
        if (addr.City) zipCity.push(escapeHtml(addr.City));
        if (zipCity.length) parts.push(zipCity.join(' '));
        
        if (addr.Country) parts.push('<strong>🌍 ' + escapeHtml(addr.Country) + '</strong>');
        
        if (parts.length === 0) {
            console.warn('[BIHR] Adresse incomplète:', addr);
            return '<em>Données incomplètes</em>';
        }
        return parts.join('<br>');
    }

    /**
     * Valide les données Order/Data avec messages détaillés
     * @param {Object} data Les données à valider
     * @returns {Object} {valid: bool, errors: [], hasWarnings: bool, missingFields: []}
     */
    function validateData(data) {
        const errors = [];
        const missingFields = [];
        
        if (!data) {
            errors.push('Données vides reçues de l\'API');
            console.error('[BIHR] Validation: Data est null/undefined');
            return { valid: false, errors, hasWarnings: true, missingFields: [] };
        }

        if (typeof data !== 'object') {
            errors.push('Format de données invalide: attendu Object, reçu ' + typeof data);
            console.error('[BIHR] Validation: Type invalide:', typeof data);
            return { valid: false, errors, hasWarnings: true, missingFields: [] };
        }

        // Contrôles critiques
        if (!data.ResultCode) {
            missingFields.push('ResultCode');
            console.warn('[BIHR] Validation: ResultCode manquant');
        }

        if (!data.OrderLines || !Array.isArray(data.OrderLines)) {
            missingFields.push('OrderLines');
            errors.push('⚠️ Pas d\'articles trouvés (OrderLines manquant ou invalide)');
            console.warn('[BIHR] Validation: OrderLines manquant ou invalide');
        } else if (data.OrderLines.length === 0) {
            errors.push('⚠️ Panier vide (0 articles)');
            console.warn('[BIHR] Validation: OrderLines vide');
        }

        if (!data.DeliveryOrders || !Array.isArray(data.DeliveryOrders)) {
            missingFields.push('DeliveryOrders');
            errors.push('⚠️ Pas de commande de livraison (DeliveryOrders manquant ou invalide)');
            console.warn('[BIHR] Validation: DeliveryOrders manquant ou invalide');
        } else if (data.DeliveryOrders.length === 0) {
            errors.push('⚠️ Aucune commande de livraison créée');
            console.warn('[BIHR] Validation: DeliveryOrders vide');
        }

        if (!data.ShippingAddress) {
            missingFields.push('ShippingAddress');
            console.warn('[BIHR] Validation: ShippingAddress manquant');
        }

        // Avertissements optionnels
        const hasWarnings = !data.CustomerReference || 
                           !data.Code || 
                           (data.ShippingAddress && (!data.ShippingAddress.City || !data.ShippingAddress.Line1));

        console.log('[BIHR] Validation complétée:', {
            valid: errors.length === 0,
            errorCount: errors.length,
            hasWarnings,
            missingFields
        });

        return { 
            valid: errors.length === 0, 
            errors,
            hasWarnings,
            missingFields
        };
    }

    // ============================================================
    // SECTIONS DE FORMATAGE
    // ============================================================

    function buildStatusSection(data) {
        if (!data.ResultCode) return '';
        
        const statusClasses = {
            'Cart': 'status-cart',
            'Order': 'status-order',
            'Delivered': 'status-delivered',
            'Processing': 'status-processing'
        };
        const statusClass = statusClasses[data.ResultCode] || 'status-pending';
        
        return `
            <div class="bihrwi-section section-status">
                <h3>📦 Statut de la Commande</h3>
                <p>
                    <strong>Statut:</strong> 
                    <span class="status-badge ${statusClass}">
                        ${escapeHtml(data.ResultCode)}
                    </span>
                </p>
            </div>
        `;
    }

    function buildClientSection(data) {
        const parts = [];
        
        if (data.CustomerId) parts.push(`<p><strong>🆔 Client:</strong> ${escapeHtml(data.CustomerId)}</p>`);
        if (data.Code) parts.push(`<p><strong>📦 Code Commande:</strong> <code>${escapeHtml(data.Code)}</code></p>`);
        if (data.CustomerReference) parts.push(`<p><strong>📝 Référence:</strong> ${escapeHtml(data.CustomerReference)}</p>`);
        if (data.InternalCustomerId && data.InternalCustomerId !== data.CustomerId) {
            parts.push(`<p><strong>🔗 ID Interne:</strong> ${escapeHtml(data.InternalCustomerId)}</p>`);
        }

        if (parts.length === 0) return '';

        return `
            <div class="bihrwi-section section-client">
                <h3>👤 Informations Client</h3>
                ${parts.join('')}
            </div>
        `;
    }

    function buildAddressSection(data) {
        if (!data.DeliveryOrders || !Array.isArray(data.DeliveryOrders) || data.DeliveryOrders.length === 0) {
            return `
                <div class="bihrwi-section section-warning">
                    <h3>🏠 Adresse de Livraison</h3>
                    <p><em>❌ Pas d'adresse disponible</em></p>
                </div>
            `;
        }

        const firstOrder = data.DeliveryOrders[0];
        const addr = firstOrder.ShippingAddress;

        if (!addr) {
            return `
                <div class="bihrwi-section section-warning">
                    <h3>🏠 Adresse de Livraison</h3>
                    <p><em>⚠️ Adresse non disponible</em></p>
                </div>
            `;
        }

        return `
            <div class="bihrwi-section section-address">
                <h3>🏠 Adresse de Livraison</h3>
                <div class="address-box">
                    ${formatAddress(addr)}
                </div>
            </div>
        `;
    }

    function buildArticlesSection(data) {
        if (!data.OrderLines || !Array.isArray(data.OrderLines) || data.OrderLines.length === 0) {
            return `
                <div class="bihrwi-section section-warning">
                    <h3>📋 Articles de la Commande</h3>
                    <p><em>❌ Aucun article trouvé</em></p>
                </div>
            `;
        }

        let tableHtml = '<table class="bihrwi-items-table"><thead><tr>';
        tableHtml += '<th>#</th><th>Produit</th><th>Quantité</th><th>Référence</th>';
        tableHtml += '</tr></thead><tbody>';

        data.OrderLines.forEach((line, idx) => {
            const productId = line.ProductId ? escapeHtml(line.ProductId) : '<em>N/A</em>';
            const qty = line.Quantity || 0;
            const ref = line.CustomerReference ? escapeHtml(line.CustomerReference) : '<em>Non fourni</em>';
            
            tableHtml += `<tr>
                <td class="cell-number">${idx + 1}</td>
                <td class="cell-product">${productId}</td>
                <td class="cell-qty">${qty}</td>
                <td class="cell-ref">${ref}</td>
            </tr>`;
        });

        tableHtml += '</tbody></table>';

        return `
            <div class="bihrwi-section section-articles">
                <h3>📋 Articles (${data.OrderLines.length})</h3>
                ${tableHtml}
            </div>
        `;
    }

    function buildPricesSection(data) {
        if (!data.DeliveryOrders || !Array.isArray(data.DeliveryOrders) || data.DeliveryOrders.length === 0) {
            console.warn('[BIHR] buildPricesSection: DeliveryOrders manquant');
            return '';
        }

        let totalExclVat = 0;
        let totalInclVat = 0;

        data.DeliveryOrders.forEach((order, idx) => {
            if (order.ExclVatPrice !== undefined && order.ExclVatPrice !== null) {
                const price = parseFloat(order.ExclVatPrice) || 0;
                totalExclVat += price;
                console.log('[BIHR] Prix HT [' + idx + ']:', price);
            }
            if (order.InclVatPrice !== undefined && order.InclVatPrice !== null) {
                const price = parseFloat(order.InclVatPrice) || 0;
                totalInclVat += price;
                console.log('[BIHR] Prix TTC [' + idx + ']:', price);
            }
        });

        const tva = (totalInclVat - totalExclVat);
        const hasPrice = totalInclVat > 0 || totalExclVat > 0;

        if (!hasPrice) {
            console.warn('[BIHR] buildPricesSection: Aucun montant trouvé');
            return `
                <div class="bihrwi-section section-prices">
                    <h3>💰 Montants</h3>
                    <p><em>⚠️ Aucun montant disponible</em></p>
                </div>
            `;
        }

        console.log('[BIHR] Montants: HT=' + totalExclVat.toFixed(2) + ' € | TVA=' + tva.toFixed(2) + ' € | TTC=' + totalInclVat.toFixed(2) + ' €');

        return `
            <div class="bihrwi-section section-prices">
                <h3>💰 Montants</h3>
                ${totalExclVat > 0 ? `<p><strong>🏷️ HT:</strong> ${formatPrice(totalExclVat)}</p>` : ''}
                ${tva > 0 ? `<p><strong>📊 TVA:</strong> ${formatPrice(tva)}</p>` : ''}
                <p><strong>✅ TTC:</strong> <span style="color: #d32f2f; font-weight: 700; font-size: 14px;">${formatPrice(totalInclVat)}</span></p>
            </div>
        `;
    }

    function buildShippingSection(data) {
        if (!data.DeliveryOrders || !Array.isArray(data.DeliveryOrders) || data.DeliveryOrders.length === 0) {
            return '';
        }

        const firstOrder = data.DeliveryOrders[0];
        const parts = [];

        if (firstOrder.CreationDate && firstOrder.CreationDate !== '0001-01-01T00:00:00') {
            parts.push(`<p><strong>📅 Créé:</strong> ${formatDate(firstOrder.CreationDate)}</p>`);
        }

        if (firstOrder.DispatchDate) {
            parts.push(`<p><strong>🚚 Expédié:</strong> ${formatDate(firstOrder.DispatchDate)}</p>`);
        }

        if (firstOrder.Weight) {
            parts.push(`<p><strong>⚖️ Poids:</strong> ${escapeHtml(firstOrder.Weight.toString())} kg</p>`);
        }

        if (firstOrder.TransporterId) {
            parts.push(`<p><strong>🚛 Transporteur:</strong> ${escapeHtml(firstOrder.TransporterId)}</p>`);
        } else {
            parts.push(`<p><em>🚛 Transporteur: Non assigné</em></p>`);
        }

        if (parts.length === 0) return '';

        return `
            <div class="bihrwi-section section-shipping">
                <h3>📦 Informations de Livraison</h3>
                ${parts.join('')}
            </div>
        `;
    }

    function buildWarningsSection(validation) {
        if (!validation.errors || validation.errors.length === 0) return '';

        return `
            <div class="bihrwi-section section-warning">
                <h3>⚠️ Avertissements</h3>
                <ul class="warning-list">
                    ${validation.errors.map(err => `<li>${escapeHtml(err)}</li>`).join('')}
                </ul>
            </div>
        `;
    }

    function buildJsonSection(data) {
        return `
            <div class="bihrwi-section section-json">
                <h3>📄 Données JSON Complètes</h3>
                <pre class="bihrwi-json-raw">${escapeHtml(prettyJson(data))}</pre>
            </div>
        `;
    }

    // ============================================================
    // FORMATAGE PRINCIPAL
    // ============================================================

    function formatOrderDataForClient(data) {
        const validation = validateData(data);

        if (!validation.valid && data === null) {
            return `
                <div class="bihrwi-order-data-formatted">
                    <div class="bihrwi-section section-error">
                        <h3>❌ Erreur de Données</h3>
                        <ul class="error-list">
                            ${validation.errors.map(err => `<li>${escapeHtml(err)}</li>`).join('')}
                        </ul>
                    </div>
                </div>
            `;
        }

        let html = '<div class="bihrwi-order-data-formatted">';

        // Section d'avertissements si données incohérentes
        if (validation.hasWarnings) {
            html += buildWarningsSection(validation);
        }

        // Sections principales
        html += buildStatusSection(data);
        html += buildClientSection(data);
        html += buildAddressSection(data);
        html += buildArticlesSection(data);
        html += buildPricesSection(data);
        html += buildShippingSection(data);

        // JSON brut
        html += buildJsonSection(data);

        html += '</div>';
        return html;
    }

    // ============================================================
    // RÉCUPÉRATION ET AFFICHAGE
    // ============================================================

    /**
     * Récupère et affiche les données Order/Data pour une commande WC
     * @param {number} orderId ID de la commande WooCommerce
     * @param {boolean} force Force la réactualisation (pas de cache)
     */
    function fetchOrderData(orderId, force){
        const $row = $(".bihrwi-order-data-row[data-order-id='" + orderId + "']");
        const $status = $row.find('.bihrwi-order-data-status');
        const $pre = $row.find('.bihrwi-order-data-pre');

        $status.text('⏳ Chargement depuis BIHR…');
        console.log('[BIHR] Récupération Order/Data pour ordre WC #' + orderId);
        console.log('[BIHR] Force=' + (force ? 'OUI (pas de cache)' : 'NON (cache OK)'));

        return $.post(ajaxurl, {
            action: 'bihrwi_get_order_data',
            nonce: '<?php echo esc_js( wp_create_nonce( 'bihrwi_ajax_nonce' ) ); ?>',
            order_id: orderId,
            force: force ? 1 : 0
        }).done(function(resp){
            console.log('[BIHR] Réponse reçue:', resp);
            
            if (!resp || !resp.success) {
                const payload = (resp && resp.data) ? resp.data : {};
                const msg = payload.message ? payload.message : 'Erreur inconnue.';
                console.error('[BIHR] ❌ Erreur API:', msg);
                
                let extra = '';
                if (payload.request_status) {
                    extra += '\n📊 Statut: ' + payload.request_status;
                    console.log('[BIHR] Statut API:', payload.request_status);
                }
                if (payload.order_url) {
                    extra += '\n🔗 URL: ' + payload.order_url;
                    console.log('[BIHR] URL:', payload.order_url);
                }
                if (payload.ticket_id) {
                    extra += '\n🎫 Ticket: ' + payload.ticket_id;
                    console.log('[BIHR] Ticket:', payload.ticket_id);
                }
                
                $status.html('<span style="color:#d32f2f;">❌ Erreur:</span> ' + escapeHtml(msg));
                if (extra) {
                    $pre.html('<div class="bihrwi-error-details">' + escapeHtml(msg + extra).replace(/\n/g, '<br>') + '</div>');
                }
                return;
            }

            const payload = resp.data || {};
            const fetchedAt = payload.fetched_at ? payload.fetched_at : '';
            const cached = payload.cached ? ' <em style="color:#2196f3;">💾 cache</em>' : '';

            console.log('[BIHR] ✅ Données reçues avec succès');
            if (payload.data) {
                console.log('[BIHR] Nombre articles:', (payload.data.OrderLines || []).length);
                console.log('[BIHR] Nombre commandes:', (payload.data.DeliveryOrders || []).length);
            }
            
            $status.html('✅ OK' + cached + (fetchedAt ? ' - ' + escapeHtml(fetchedAt) : ''));
            $pre.html(formatOrderDataForClient(payload.data));
        }).fail(function(jqXHR, textStatus, errorThrown){
            console.error('[BIHR] ❌ Erreur réseau/serveur:', textStatus, errorThrown);
            console.log('[BIHR] Status HTTP:', jqXHR.status);
            console.log('[BIHR] Response Text:', jqXHR.responseText);
            $status.html('<span style="color:#d32f2f;">❌ Erreur réseau</span> (' + escapeHtml(textStatus) + ')');
        });
    }

    /**
     * Récupère les données Order/Data avec saisie manuelle
     * @param {number} orderId ID de la commande WooCommerce à récupérer
     */
    function fetchOrderDataManual(orderId){
        const $status = $('#bihrwi_order_data_manual_status');
        const $pre = $('#bihrwi_order_data_manual_pre');

        if (!orderId || parseInt(orderId, 10) <= 0) {
            $status.text('⚠️ Veuillez saisir un ID de commande valide.');
            console.warn('[BIHR] ID invalide:', orderId);
            return;
        }

        $status.text('⏳ Chargement depuis BIHR…');
        $pre.text('');
        console.log('[BIHR] Récupération manuelle Order/Data pour ordre WC #' + orderId);

        $.post(ajaxurl, {
            action: 'bihrwi_get_order_data',
            nonce: '<?php echo esc_js( wp_create_nonce( 'bihrwi_ajax_nonce' ) ); ?>',
            order_id: orderId,
            force: 1
        }).done(function(resp){
            console.log('[BIHR] Réponse manuelle reçue:', resp);
            
            if (!resp || !resp.success) {
                const payload = (resp && resp.data) ? resp.data : {};
                const msg = payload.message ? payload.message : 'Erreur inconnue.';
                console.error('[BIHR] ❌ Erreur API (manuelle):', msg);
                
                let extra = '';
                if (payload.request_status) extra += '\n📊 Statut: ' + payload.request_status;
                if (payload.order_url) extra += '\n🔗 URL: ' + payload.order_url;
                if (payload.ticket_id) extra += '\n🎫 Ticket: ' + payload.ticket_id;
                
                $status.html('<span style="color:#d32f2f;">❌ Erreur:</span> ' + escapeHtml(msg));
                if (extra) {
                    $pre.html('<div class="bihrwi-error-details">' + escapeHtml(msg + extra).replace(/\n/g, '<br>') + '</div>');
                }
                return;
            }

            const payload = resp.data || {};
            const fetchedAt = payload.fetched_at ? payload.fetched_at : '';

            console.log('[BIHR] ✅ Données manuelle reçues avec succès');
            if (payload.data) {
                console.log('[BIHR] Contenu: Articles=' + (payload.data.OrderLines || []).length + ', Commandes=' + (payload.data.DeliveryOrders || []).length);
            }
            
            $status.html('✅ OK' + (fetchedAt ? ' - ' + escapeHtml(fetchedAt) : ''));
            $pre.html(formatOrderDataForClient(payload.data));
        }).fail(function(jqXHR, textStatus, errorThrown){
            console.error('[BIHR] ❌ Erreur réseau/serveur (manuelle):', textStatus, errorThrown);
            $status.html('<span style="color:#d32f2f;">❌ Erreur réseau</span> (' + escapeHtml(textStatus) + ')');
        });
    }

    // ============================================================
    // EVENT HANDLERS
    // ============================================================

    $(document).on('click', '.bihrwi-order-data-btn', function(){
        const orderId = $(this).data('order-id');
        const $row = $(".bihrwi-order-data-row[data-order-id='" + orderId + "']");
        const isVisible = $row.is(':visible');

        $('.bihrwi-order-data-row').hide();
        if (isVisible) {
            return;
        }

        $row.show();
        fetchOrderData(orderId, false);
    });

    $(document).on('click', '.bihrwi-order-data-refresh-btn', function(){
        const orderId = $(this).data('order-id');
        const $row = $(".bihrwi-order-data-row[data-order-id='" + orderId + "']");
        $row.show();
        fetchOrderData(orderId, true);
    });

    $(document).on('click', '#bihrwi_order_data_fetch_btn', function(){
        const orderId = $('#bihrwi_order_data_order_id').val();
        fetchOrderDataManual(orderId);
    });

    console.log('[BIHR] Plugin Order/Data initialisé');
})(jQuery);
</script>

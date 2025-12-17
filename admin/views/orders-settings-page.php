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
    function prettyJson(value){
        try {
            return JSON.stringify(value, null, 2);
        } catch (e) {
            return String(value);
        }
    }

    function formatOrderDataForClient(data) {
        if (!data || typeof data !== 'object') {
            return prettyJson(data);
        }

        let html = '<div class="bihrwi-order-data-formatted">';

        // 📦 ResultCode/Status
        if (data.ResultCode) {
            html += '<div class="bihrwi-section">';
            html += '<h3>📦 Statut de la Commande</h3>';
            html += '<p><strong>Résultat:</strong> <span class="status-badge status-' + data.ResultCode.toLowerCase() + '">' + escapeHtml(data.ResultCode) + '</span></p>';
            html += '</div>';
        }

        // 👤 Informations Client
        if (data.CustomerId || data.CustomerReference || data.Code) {
            html += '<div class="bihrwi-section">';
            html += '<h3>👤 Informations Client</h3>';
            if (data.CustomerId) html += '<p><strong>Client ID:</strong> ' + escapeHtml(data.CustomerId) + '</p>';
            if (data.Code) html += '<p><strong>Code Commande:</strong> ' + escapeHtml(data.Code) + '</p>';
            if (data.CustomerReference) html += '<p><strong>Référence:</strong> ' + escapeHtml(data.CustomerReference) + '</p>';
            html += '</div>';
        }

        // 🏠 Adresse de Livraison
        if (data.DeliveryOrders && Array.isArray(data.DeliveryOrders) && data.DeliveryOrders.length > 0) {
            const firstOrder = data.DeliveryOrders[0];
            if (firstOrder.ShippingAddress) {
                html += '<div class="bihrwi-section">';
                html += '<h3>🏠 Adresse de Livraison</h3>';
                const addr = firstOrder.ShippingAddress;
                html += '<p>';
                if (addr.Line1) html += escapeHtml(addr.Line1) + '<br>';
                if (addr.Line2) html += escapeHtml(addr.Line2) + '<br>';
                const zipCity = [];
                if (addr.ZipCode) zipCity.push(escapeHtml(addr.ZipCode));
                if (addr.City) zipCity.push(escapeHtml(addr.City));
                if (zipCity.length) html += zipCity.join(' ') + '<br>';
                if (addr.Country) html += '<strong>Pays:</strong> ' + escapeHtml(addr.Country);
                html += '</p>';
                html += '</div>';
            }
        }

        // 📋 Articles/Lignes de Commande
        let totalPrice = 0;
        if (data.OrderLines && Array.isArray(data.OrderLines) && data.OrderLines.length > 0) {
            html += '<div class="bihrwi-section">';
            html += '<h3>📋 Articles de la Commande</h3>';
            html += '<table class="bihrwi-items-table">';
            html += '<tr><th>Produit</th><th>Quantité</th><th>Référence</th></tr>';
            
            data.OrderLines.forEach(line => {
                html += '<tr>';
                html += '<td>' + (line.ProductId ? escapeHtml(line.ProductId) : 'N/A') + '</td>';
                html += '<td style="text-align:center;">' + (line.Quantity || 0) + '</td>';
                html += '<td>' + (line.CustomerReference ? escapeHtml(line.CustomerReference) : 'N/A') + '</td>';
                html += '</tr>';
            });
            html += '</table>';
            html += '</div>';
        } else {
            html += '<div class="bihrwi-section">';
            html += '<h3>📋 Articles de la Commande</h3>';
            html += '<p><em>Aucun article dans cette commande.</em></p>';
            html += '</div>';
        }

        // 💰 Montants et Livraison
        if (data.DeliveryOrders && Array.isArray(data.DeliveryOrders) && data.DeliveryOrders.length > 0) {
            html += '<div class="bihrwi-section">';
            html += '<h3>💰 Montants</h3>';
            
            let totalInclVat = 0;
            let totalExclVat = 0;
            
            data.DeliveryOrders.forEach(order => {
                if (order.InclVatPrice !== undefined && order.InclVatPrice !== null) {
                    totalInclVat += parseFloat(order.InclVatPrice) || 0;
                }
                if (order.ExclVatPrice !== undefined && order.ExclVatPrice !== null) {
                    totalExclVat += parseFloat(order.ExclVatPrice) || 0;
                }
            });
            
            if (totalExclVat > 0) {
                html += '<p><strong>Prix HT:</strong> ' + totalExclVat.toFixed(2) + ' €</p>';
            }
            if (totalInclVat > 0) {
                html += '<p><strong>Prix TTC:</strong> <strong style="color:#2271b1; font-size:16px;">' + totalInclVat.toFixed(2) + ' €</strong></p>';
            }
            
            const tva = (totalInclVat - totalExclVat).toFixed(2);
            if (parseFloat(tva) > 0) {
                html += '<p><strong>TVA:</strong> ' + tva + ' €</p>';
            }
            
            html += '</div>';
        }

        // 📅 Informations de Statut
        if (data.DeliveryOrders && Array.isArray(data.DeliveryOrders) && data.DeliveryOrders.length > 0) {
            const firstOrder = data.DeliveryOrders[0];
            html += '<div class="bihrwi-section">';
            html += '<h3>📅 Statut de Livraison</h3>';
            if (firstOrder.CreationDate && firstOrder.CreationDate !== '0001-01-01T00:00:00') {
                html += '<p><strong>Date de Création:</strong> ' + new Date(firstOrder.CreationDate).toLocaleString('fr-FR') + '</p>';
            }
            if (firstOrder.DispatchDate) {
                html += '<p><strong>Date d\'Envoi:</strong> ' + new Date(firstOrder.DispatchDate).toLocaleString('fr-FR') + '</p>';
            }
            if (firstOrder.Weight) {
                html += '<p><strong>Poids:</strong> ' + firstOrder.Weight + ' kg</p>';
            }
            if (firstOrder.Volume) {
                html += '<p><strong>Volume:</strong> ' + firstOrder.Volume + '</p>';
            }
            html += '</div>';
        }

        // 🏢 Informations Supplémentaires
        html += '<div class="bihrwi-section">';
        html += '<h3>🏢 Informations Supplémentaires</h3>';
        if (data.InternalCustomerId) {
            html += '<p><strong>ID Client Interne:</strong> ' + escapeHtml(data.InternalCustomerId) + '</p>';
        }
        if (data.DeliveryOrders && Array.isArray(data.DeliveryOrders)) {
            html += '<p><strong>Nombre de Commandes Livraison:</strong> ' + data.DeliveryOrders.length + '</p>';
        }
        if (data.OrderLines && Array.isArray(data.OrderLines)) {
            html += '<p><strong>Nombre d\'Articles:</strong> ' + data.OrderLines.length + '</p>';
        }
        html += '</div>';

        // Afficher le JSON brut formaté
        html += '<div class="bihrwi-section">';
        html += '<h3>📄 Données JSON Complètes</h3>';
        html += '<pre class="bihrwi-json-raw">' + escapeHtml(prettyJson(data)) + '</pre>';
        html += '</div>';

        html += '</div>';
        return html;
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function fetchOrderData(orderId, force){
        const $row = $(".bihrwi-order-data-row[data-order-id='" + orderId + "']");
        const $status = $row.find('.bihrwi-order-data-status');
        const $pre = $row.find('.bihrwi-order-data-pre');

        $status.text('Chargement depuis BIHR…');

        return $.post(ajaxurl, {
            action: 'bihrwi_get_order_data',
            nonce: '<?php echo esc_js( wp_create_nonce( 'bihrwi_ajax_nonce' ) ); ?>',
            order_id: orderId,
            force: force ? 1 : 0
        }).done(function(resp){
            if (!resp || !resp.success) {
                const payload = (resp && resp.data) ? resp.data : {};
                const msg = payload.message ? payload.message : 'Erreur inconnue.';
                let extra = '';
                if (payload.request_status) extra += '\nrequest_status: ' + payload.request_status;
                if (payload.order_url) extra += '\norder_url: ' + payload.order_url;
                if (payload.ticket_id) extra += '\nticket_id: ' + payload.ticket_id;
                $status.text('Erreur: ' + msg);
                if (extra) {
                    $pre.html('<div class="bihrwi-error-details">' + escapeHtml(msg + extra).replace(/\n/g, '<br>') + '</div>');
                }
                return;
            }

            const payload = resp.data || {};
            const fetchedAt = payload.fetched_at ? payload.fetched_at : '';
            const cached = payload.cached ? ' (cache)' : '';

            $status.text('OK' + cached + (fetchedAt ? ' - ' + fetchedAt : ''));
            $pre.html(formatOrderDataForClient(payload.data));
        }).fail(function(){
            $status.text('Erreur réseau ou serveur (AJAX).');
        });
    }

    function fetchOrderDataManual(orderId){
        const $status = $('#bihrwi_order_data_manual_status');
        const $pre = $('#bihrwi_order_data_manual_pre');

        if (!orderId || parseInt(orderId, 10) <= 0) {
            $status.text('Veuillez saisir un ID de commande valide.');
            return;
        }

        $status.text('Chargement depuis BIHR…');
        $pre.text('');

        $.post(ajaxurl, {
            action: 'bihrwi_get_order_data',
            nonce: '<?php echo esc_js( wp_create_nonce( 'bihrwi_ajax_nonce' ) ); ?>',
            order_id: orderId,
            force: 1
        }).done(function(resp){
            if (!resp || !resp.success) {
                const payload = (resp && resp.data) ? resp.data : {};
                const msg = payload.message ? payload.message : 'Erreur inconnue.';
                let extra = '';
                if (payload.request_status) extra += '\nrequest_status: ' + payload.request_status;
                if (payload.order_url) extra += '\norder_url: ' + payload.order_url;
                if (payload.ticket_id) extra += '\nticket_id: ' + payload.ticket_id;
                $status.text('Erreur: ' + msg);
                if (extra) {
                    $pre.html('<div class="bihrwi-error-details">' + escapeHtml(msg + extra).replace(/\n/g, '<br>') + '</div>');
                }
                return;
            }

            const payload = resp.data || {};
            const fetchedAt = payload.fetched_at ? payload.fetched_at : '';
            $status.text('OK' + (fetchedAt ? ' - ' + fetchedAt : ''));
            $pre.html(formatOrderDataForClient(payload.data));
        }).fail(function(){
            $status.text('Erreur réseau ou serveur (AJAX).');
        });
    }

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
})(jQuery);
</script>

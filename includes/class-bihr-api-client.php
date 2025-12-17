<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_API_Client {

    protected $logger;
    protected $base_url = 'https://api.bihr.net/api/v2.1';

    public function __construct( BihrWI_Logger $logger ) {
        $this->logger = $logger;
    }

    /**
     * Récupère les identifiants stockés dans les options WP
     */
    protected function get_credentials() {
        $username = get_option( 'bihrwi_username', '' );
        $password = get_option( 'bihrwi_password', '' );

        return array(
            'username' => $username,
            'password' => $password,
        );
    }

    /**
     * Récupère un token valide, sinon en demande un nouveau.
     * L'API Bihr renvoie un JSON avec une clé "access_token".
     */
    public function get_token() {
        // On essaie d'abord de réutiliser un token déjà en cache
        $cached = get_transient( 'bihrwi_api_token' );
        if ( ! empty( $cached ) ) {
            return $cached;
        }

        $creds = $this->get_credentials();
        if ( empty( $creds['username'] ) || empty( $creds['password'] ) ) {
            throw new Exception( 'Identifiants Bihr non configurés.' );
        }

        $this->logger->log( 'Auth: demande d’un nouveau token.' );

        // D'après la doc : POST /Authentication/Token avec UserName & PassWord
        $response = wp_remote_post(
            $this->base_url . '/Authentication/Token',
            array(
                'timeout' => 30,
                'headers' => array(
                    'Accept' => 'text/json',
                ),
                'body'    => array(
                    'UserName' => $creds['username'],
                    'PassWord' => $creds['password'],
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->logger->log( 'Auth: erreur HTTP : ' . $response->get_error_message() );
            throw new Exception( 'Erreur HTTP lors de la récupération du token.' );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        $this->logger->log( 'Auth: code ' . $code . ' – réponse : ' . $body );

        if ( $code < 200 || $code >= 300 ) {
            throw new Exception( 'Erreur API Bihr lors de la récupération du token.' );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) || empty( $data['access_token'] ) ) {
            throw new Exception( 'Réponse de token invalide (pas de champ access_token).' );
        }

        $token = $data['access_token'];

        // Token valable 30 min -> on garde 25 min
        set_transient( 'bihrwi_api_token', $token, 25 * MINUTE_IN_SECONDS );

        return $token;
    }

    /**
     * Lance la génération d’un catalog (References, Prices, Images, Attributes, Stocks, etc.)
     * Exemple type: 'Prices/Full' -> Catalog/LZMA/CSV/Prices/Full
     */
    public function start_catalog_generation( $catalog_path ) {
        $token = $this->get_token();

        // L'API attend le format: /Catalog/ZIP/CSV/{CatalogName}/Full
        $url = $this->base_url . '/Catalog/ZIP/CSV/' . ltrim( $catalog_path, '/' ) . '/Full';
        $this->logger->log( 'Catalog: démarrage génération -> ' . $url );

        $response = wp_remote_post(
            $url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Accept'        => 'application/json',
                    // IMPORTANT : Bihr attend "Authorization: Bearer <token>"
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type'  => 'text/json',
                ),
                'body'    => null, // équivalent d'un body vide (Content-Length: 0)
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->logger->log( 'Catalog start: erreur HTTP : ' . $response->get_error_message() );
            throw new Exception( 'Erreur HTTP lors du démarrage du catalog.' );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        $this->logger->log( 'Catalog start: code ' . $code . ' – réponse : ' . $body );

        if ( $code < 200 || $code >= 300 ) {
            throw new Exception( 'Erreur API Bihr lors du démarrage du catalog : ' . $body );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) || ( empty( $data['TicketId'] ) && empty( $data['ticketId'] ) ) ) {
            throw new Exception( 'Réponse sans ticketId pour le catalog.' );
        }

        return ! empty( $data['ticketId'] ) ? $data['ticketId'] : $data['TicketId'];
    }

    /**
     * Vérifie le status de génération d’un catalog
     * Appelle GET /Catalog/GenerationStatus?ticketId=...
     */
    public function get_catalog_status( $ticket_id ) {
        $token = $this->get_token();

        $url = $this->base_url . '/Catalog/GenerationStatus?ticketId=' . urlencode( $ticket_id );
        $this->logger->log( 'Catalog status: GET ' . $url );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Accept'        => 'application/json',
                    'Authorization' => 'Bearer ' . $token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->logger->log( 'Catalog status: erreur HTTP : ' . $response->get_error_message() );
            throw new Exception( 'Erreur HTTP lors de la récupération du status.' );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        $this->logger->log( 'Catalog status: code ' . $code . ' – réponse : ' . $body );

        if ( $code < 200 || $code >= 300 ) {
            throw new Exception( 'Erreur API Bihr lors de la récupération du status : ' . $body );
        }

        $data = json_decode( $body, true );
        if ( ! is_array( $data ) ) {
            throw new Exception( 'Réponse status invalide (pas JSON).' );
        }

        // Compat : normalise les clés en minuscules pour compatibilité
        // L'API Bihr retourne parfois RequestStatus, parfois requestStatus, parfois status
        if ( isset( $data['RequestStatus'] ) && ! isset( $data['status'] ) ) {
            $data['status'] = $data['RequestStatus'];
        } elseif ( isset( $data['requestStatus'] ) && ! isset( $data['status'] ) ) {
            $data['status'] = $data['requestStatus'];
        }

        // Même chose pour DownloadId
        if ( isset( $data['DownloadId'] ) && ! isset( $data['downloadId'] ) ) {
            $data['downloadId'] = $data['DownloadId'];
        }

        return $data;
    }

    /**
     * Télécharge un catalog généré
     * Appelle GET /Catalog/GeneratedFile?downloadId=...
     * Sauvegarde le contenu dans un fichier .zip dans le dossier des logs.
     */
    public function download_catalog_file( $download_id, $prefix = 'catalog' ) {
        $token = $this->get_token();

        $url = $this->base_url . '/Catalog/GeneratedFile?downloadId=' . urlencode( $download_id );
        $this->logger->log( 'Catalog download: GET ' . $url );

        $response = wp_remote_get(
            $url,
            array(
                'timeout' => 120,
                'headers' => array(
                    'Accept'        => '*/*',
                    'Authorization' => 'Bearer ' . $token,
                ),
            )
        );

        if ( is_wp_error( $response ) ) {
            $this->logger->log( 'Catalog download: erreur HTTP : ' . $response->get_error_message() );
            return false;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );

        if ( $code < 200 || $code >= 300 ) {
            $this->logger->log(
                'Catalog download: code HTTP ' . $code . ' – body: ' . $body
            );
            return false;
        }

        $upload_dir = dirname( BIHRWI_LOG_FILE );
        if ( ! file_exists( $upload_dir ) ) {
            wp_mkdir_p( $upload_dir );
        }

        $filename = $prefix . '-' . date( 'Ymd-His' ) . '.zip';
        $filepath = trailingslashit( $upload_dir ) . $filename;

        file_put_contents( $filepath, $body );

        return $filepath;
    }

    /**
     * Récupère le stock en temps réel pour un produit
     * 
     * @param string $product_code Code produit BIHR
     * @return array|false Tableau avec 'stock_level' ou false si erreur
     */
    public function get_real_time_stock( $product_code ) {
        try {
            $token = $this->get_token();
            
            // Construction de l'URL avec le bon paramètre
            $url = $this->base_url . '/Inventory/StockValue';
            $url_with_param = add_query_arg( 'productCode', $product_code, $url );
            
            $this->logger->log( "=== APPEL API STOCK ===" );
            $this->logger->log( "URL: {$url_with_param}" );
            $this->logger->log( "Code produit: {$product_code}" );
            
            $response = wp_remote_get(
                $url_with_param,
                array(
                    'timeout' => 15,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ),
                )
            );

            if ( is_wp_error( $response ) ) {
                $this->logger->log( 'Stock API Error: ' . $response->get_error_message() );
                return false;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            
            $this->logger->log( "HTTP Status: {$code}" );
            $this->logger->log( "Response Body: {$body}" );
            
            if ( $code !== 200 ) {
                $this->logger->log( "ÉCHEC: HTTP {$code} pour produit {$product_code}" );
                return false;
            }

            // L'API retourne directement la valeur du stock (nombre entier)
            $stock_level = intval( trim( $body ) );
            
            $this->logger->log( "✓ Stock récupéré: {$stock_level}" );
            $this->logger->log( "=======================" );
            
            return array(
                'stock_level' => $stock_level,
                'product_code' => $product_code
            );

        } catch ( Exception $e ) {
            $this->logger->log( 'Stock API Exception: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Vérifie le statut de génération d'une commande
     * Endpoint: GET /api/v2.1/Order/GenerationStatus?TicketId={ticketId}
     * 
     * @param string $ticket_id Le TicketId retourné par Order/Creation
     * @return array Données de statut incluant OrderUrl et RequestStatus
     */
    public function get_order_generation_status( $ticket_id ) {
        try {
            $token = $this->get_token();
            
            $url = $this->base_url . '/Order/GenerationStatus';
            $url_with_param = add_query_arg( 'TicketId', $ticket_id, $url );
            
            $this->logger->log( "Order Status Check: {$url_with_param}" );
            
            $response = wp_remote_get(
                $url_with_param,
                array(
                    'timeout' => 15,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ),
                )
            );

            if ( is_wp_error( $response ) ) {
                $this->logger->log( 'Order Status Error: ' . $response->get_error_message() );
                return false;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            
            $this->logger->log( "Order Status HTTP: {$code}" );
            $this->logger->log( "=== RÉPONSE GenerationStatus ===" );
            
            // Logger la réponse JSON formatée
            if ( ! empty( $body ) ) {
                $formatted_json = json_encode( json_decode( $body ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                if ( $formatted_json ) {
                    foreach ( explode( "\n", $formatted_json ) as $line ) {
                        $this->logger->log( "  " . $line );
                    }
                } else {
                    $this->logger->log( "  Body: {$body}" );
                }
            }
            $this->logger->log( "================================" );
            
            if ( $code !== 200 ) {
                $this->logger->log( "Order Status Check Failed: HTTP {$code}" );
                return false;
            }

            $data = json_decode( $body, true );
            
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $this->logger->log( 'Order Status: Invalid JSON response' );
                return false;
            }
            
            return array(
                'order_url'      => $data['OrderUrl'] ?? '',
                'request_status' => $data['RequestStatus'] ?? '',
                'data'           => $data,
            );

        } catch ( Exception $e ) {
            $this->logger->log( 'Order Status Exception: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Tente d'extraire un OrderId depuis une réponse GenerationStatus.
     * L'API BIHR peut exposer l'identifiant dans le JSON ou via l'URL (OrderUrl).
     *
     * @param array $generation_status Résultat retourné par get_order_generation_status()
     * @return string|false
     */
    public function extract_order_id_from_generation_status( $generation_status ) {
        if ( ! is_array( $generation_status ) ) {
            return false;
        }

        $data = $generation_status['data'] ?? array();
        if ( is_array( $data ) ) {
            foreach ( array( 'OrderId', 'orderId', 'orderID', 'OrderID' ) as $key ) {
                if ( ! empty( $data[ $key ] ) && is_string( $data[ $key ] ) ) {
                    return $data[ $key ];
                }
            }
        }

        $order_url = $generation_status['order_url'] ?? '';
        if ( empty( $order_url ) || ! is_string( $order_url ) ) {
            return false;
        }

        $parts = wp_parse_url( $order_url );
        if ( ! empty( $parts['query'] ) ) {
            parse_str( $parts['query'], $query );
            foreach ( array( 'orderId', 'OrderId', 'orderID', 'OrderID', 'id', 'Id' ) as $key ) {
                if ( ! empty( $query[ $key ] ) && is_string( $query[ $key ] ) ) {
                    return $query[ $key ];
                }
            }
        }

        if ( ! empty( $parts['path'] ) && is_string( $parts['path'] ) ) {
            $path = trim( $parts['path'], '/' );
            if ( $path ) {
                $segments = explode( '/', $path );
                $last = end( $segments );
                if ( is_string( $last ) && preg_match( '/^[a-f0-9]{32}$/i', $last ) ) {
                    return $last;
                }
            }
        }

        if ( preg_match( '/orderId=([a-f0-9]{32})/i', $order_url, $m ) ) {
            return $m[1];
        }

        return false;
    }

    /**
     * Récupère les données détaillées d'une commande par identifiant de commande BIHR.
     * Certaines instances de l’API BIHR exigent le paramètre orderId (et non TicketId).
     * Endpoint: GET /api/v2.1/Order/Data?orderId={orderId}
     *
     * @param string $order_id Identifiant de commande BIHR
     * @return array|false
     */
    public function get_order_data_by_order_id( $order_id ) {
        try {
            $token = $this->get_token();

            $url = $this->base_url . '/Order/Data';
            $url_with_param = add_query_arg( 'orderId', $order_id, $url );

            $this->logger->log( "Order Data Request (orderId): {$url_with_param}" );

            $response = wp_remote_get(
                $url_with_param,
                array(
                    'timeout' => 15,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ),
                )
            );

            if ( is_wp_error( $response ) ) {
                $this->logger->log( 'Order Data Error: ' . $response->get_error_message() );
                return false;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );

            $this->logger->log( "Order Data HTTP: {$code}" );
            $this->logger->log( "=== RÉPONSE Order/Data ===" );

            if ( ! empty( $body ) ) {
                $formatted_json = json_encode( json_decode( $body ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                if ( $formatted_json ) {
                    foreach ( explode( "\n", $formatted_json ) as $line ) {
                        $this->logger->log( "  " . $line );
                    }
                } else {
                    $this->logger->log( "  Body: {$body}" );
                }
            }
            $this->logger->log( "==========================" );

            if ( $code !== 200 ) {
                $this->logger->log( "Order Data Request Failed: HTTP {$code}" );
                return false;
            }

            $data = json_decode( $body, true );
            if ( json_last_error() !== JSON_ERROR_NONE ) {
                $this->logger->log( 'Order Data: Invalid JSON response' );
                return false;
            }

            return $data;

        } catch ( Exception $e ) {
            $this->logger->log( 'Order Data Exception: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Récupère les données détaillées d'une commande
     * Endpoint (Swagger BIHR): GET /api/v2.1/Order/Data?orderId={orderGenerationTicketId}
     * 
     * @param string $ticket_id Le TicketId retourné par Order/Creation
     * @return array|false Données de la commande ou false
     */
    public function get_order_data( $ticket_id ) {
        try {
            $token = $this->get_token();
            
            $url = $this->base_url . '/Order/Data';
            // D'après le Swagger BIHR, le paramètre requis est `orderId` (qui correspond au ticket de génération).
            $url_with_param = add_query_arg( 'orderId', $ticket_id, $url );
            
            $this->logger->log( "Order Data Request: {$url_with_param}" );
            
            $response = wp_remote_get(
                $url_with_param,
                array(
                    'timeout' => 15,
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                        'Accept'        => 'application/json',
                    ),
                )
            );

            if ( is_wp_error( $response ) ) {
                $this->logger->log( 'Order Data Error: ' . $response->get_error_message() );
                return false;
            }

            $code = wp_remote_retrieve_response_code( $response );
            $body = wp_remote_retrieve_body( $response );
            
            $this->logger->log( "Order Data HTTP: {$code}" );
            $this->logger->log( "=== RÉPONSE Order/Data ===" );
            
            // Logger la réponse JSON formatée
            if ( ! empty( $body ) ) {
                $formatted_json = json_encode( json_decode( $body ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
                if ( $formatted_json ) {
                    foreach ( explode( "\n", $formatted_json ) as $line ) {
                        $this->logger->log( "  " . $line );
                    }
                } else {
                    $this->logger->log( "  Body: {$body}" );
                }
            }
            $this->logger->log( "==========================" );
            
            // Traiter la réponse même si le code HTTP n'est pas 200
            if ( ! empty( $body ) ) {
                $data = json_decode( $body, true );
                
                if ( json_last_error() === JSON_ERROR_NONE && is_array( $data ) ) {
                    // Retourner les données même si le ResultCode est "Cart"
                    // L'interface affichera les données reçues
                    return $data;
                }
            }
            
            // Si pas de données valides et code HTTP n'est pas 200
            if ( $code !== 200 ) {
                $this->logger->log( "Order Data Request Failed: HTTP {$code}" );

                // Fallback rare: certaines instances pourraient accepter TicketId (ancien comportement).
                if ( $code === 400 && stripos( $body, 'TicketId' ) !== false ) {
                    $fallback_url = add_query_arg( 'TicketId', $ticket_id, $url );
                    $this->logger->log( "Order Data Fallback (TicketId): {$fallback_url}" );

                    $fallback_response = wp_remote_get(
                        $fallback_url,
                        array(
                            'timeout' => 15,
                            'headers' => array(
                                'Authorization' => 'Bearer ' . $token,
                                'Accept'        => 'application/json',
                            ),
                        )
                    );

                    if ( ! is_wp_error( $fallback_response ) ) {
                        $fallback_code = wp_remote_retrieve_response_code( $fallback_response );
                        $fallback_body = wp_remote_retrieve_body( $fallback_response );
                        $this->logger->log( "Order Data Fallback HTTP: {$fallback_code}" );
                        if ( $fallback_code === 200 ) {
                            $fallback_data = json_decode( $fallback_body, true );
                            if ( json_last_error() === JSON_ERROR_NONE ) {
                                return $fallback_data;
                            }
                        }
                    }
                }
                return false;
            }

            return false;

        } catch ( Exception $e ) {
            $this->logger->log( 'Order Data Exception: ' . $e->getMessage() );
            return false;
        }
    }
}

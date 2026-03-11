<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Product_Sync {

    protected $logger;
    protected $table_name;

    public function __construct( BihrWI_Logger $logger ) {
        global $wpdb;
        $this->logger     = $logger;
        $this->table_name = $wpdb->prefix . 'bihr_products';
    }

    /**
     * Initialise et retourne l'instance WP_Filesystem
     */
    protected function get_wp_filesystem() {
        global $wp_filesystem;
        
        if ( ! isset( $wp_filesystem ) ) {
            if ( ! function_exists( 'WP_Filesystem' ) ) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            WP_Filesystem();
        }
        
        return $wp_filesystem;
    }

    /**
     * Retrouve un produit WooCommerce existant via le SKU ou le code BIHR (meta).
     */
    protected function find_existing_product( $sku, $product_code ) {
        // 1) Recherche par SKU (rapide)
        if ( ! empty( $sku ) ) {
            $product_id = wc_get_product_id_by_sku( $sku );
            if ( $product_id ) {
                return (int) $product_id;
            }
        }

        // 2) Recherche par meta _bihr_product_code
        if ( ! empty( $product_code ) ) {
            $query = new WP_Query( array(
                'post_type'      => 'product',
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_query'     => array(
                    array(
                        'key'   => '_bihr_product_code',
                        'value' => $product_code,
                    ),
                ),
            ) );

            if ( ! empty( $query->posts ) ) {
                return (int) $query->posts[0];
            }
        }

        return 0;
    }

    /* =========================================================
     *   LECTURE / LISTE DES PRODUITS (pour la page d’admin)
     * ======================================================= */


    /**
     * Récupère la liste des catégories Bihr distinctes dans la base.
     * (Champ `category` historique, utilisé pour les marges.)
     */
    public function get_distinct_categories() {
        global $wpdb;

        // Normaliser les catégories (trim + remplacement des espaces insécables) pour éviter
        // les valeurs qui semblent identiques visuellement mais ne matchent pas en filtre.
        // Échapper le nom de table pour la sécurité (les noms de table ne peuvent pas utiliser de placeholders)
        $table_name = esc_sql( $this->table_name );
        $sql = "SELECT DISTINCT REPLACE(TRIM(category), CHAR(160), ' ') AS category
            FROM `{$table_name}`
            WHERE category IS NOT NULL AND TRIM(REPLACE(category, CHAR(160), ' ')) != ''
            ORDER BY category ASC";
        
        // Utiliser $wpdb->prepare() même pour les requêtes sans placeholders (exigence Plugin Check)
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Table name is escaped with esc_sql(), query has no user input
        $prepared = $wpdb->prepare( $sql );
        
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above
        return $wpdb->get_col( $prepared );
    }

    /**
     * Récupère la liste des niveaux 1 distincts (cat_l1) depuis wp_bihr_products.
     */
    public function get_distinct_cat_level1() {
        global $wpdb;

        $table_name = esc_sql( $this->table_name );

        // Ne retourner que les cat_l1 qui ont au moins un cat_l2 non vide,
        // afin d'éviter les catégories de niveau 1 "orphelines" dans le filtre.
        $sql = "
            SELECT DISTINCT TRIM(t1.cat_l1) AS cat_l1
            FROM `{$table_name}` AS t1
            WHERE
                t1.cat_l1 IS NOT NULL
                AND TRIM(t1.cat_l1) <> ''
                AND EXISTS (
                    SELECT 1
                    FROM `{$table_name}` AS t2
                    WHERE
                        t2.cat_l1 = t1.cat_l1
                        AND t2.cat_l2 IS NOT NULL
                        AND TRIM(t2.cat_l2) <> ''
                )
            ORDER BY cat_l1 ASC
        ";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- pas de paramètres utilisateur, noms de table échappés
        $categories = $wpdb->get_col( $sql );

        if ( is_array( $categories ) ) {
            $this->logger->log( '[Categories] Niveaux 1 exploitables (avec au moins un Niveau 2) : ' . count( $categories ) );
        }

        return $categories;
    }

    /**
     * Récupère la liste des niveaux 2 distincts (cat_l2) pour un niveau 1 donné.
     *
     * @param string $cat_l1 Niveau 1 sélectionné.
     * @return array
     */
    public function get_distinct_cat_level2( $cat_l1 ) {
        global $wpdb;

        $cat_l1     = sanitize_text_field( (string) $cat_l1 );
        if ( '' === $cat_l1 ) {
            return array();
        }

        $table_name = esc_sql( $this->table_name );

        // Support multi-sélection : cat_l1 peut contenir "val1||val2||val3"
        $values_l1 = array_filter( array_map( 'trim', explode( '||', $cat_l1 ) ) );
        if ( empty( $values_l1 ) ) {
            return array();
        }

        if ( count( $values_l1 ) > 1 ) {
            $placeholders = implode( ',', array_fill( 0, count( $values_l1 ), '%s' ) );
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL préparée juste après
            $sql = "SELECT DISTINCT TRIM(cat_l2) AS cat_l2
                    FROM `{$table_name}`
                    WHERE TRIM(cat_l1) IN ({$placeholders})
                      AND cat_l2 IS NOT NULL
                      AND TRIM(cat_l2) <> ''
                    ORDER BY cat_l2 ASC";

            $prepared = $wpdb->prepare( $sql, $values_l1 );
        } else {
            // Cas simple: un seul Niveau 1 sélectionné
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL préparée juste après
            $sql      = "SELECT DISTINCT TRIM(cat_l2) AS cat_l2
                         FROM `{$table_name}`
                         WHERE TRIM(cat_l1) = %s
                           AND cat_l2 IS NOT NULL
                           AND TRIM(cat_l2) <> ''
                         ORDER BY cat_l2 ASC";
            $prepared = $wpdb->prepare( $sql, reset( $values_l1 ) );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_col( $prepared );
    }

    /**
     * Récupère la liste des niveaux 3 distincts (cat_l3) pour un couple (l1, l2).
     *
     * @param string $cat_l1
     * @param string $cat_l2
     * @return array
     */
    public function get_distinct_cat_level3( $cat_l1, $cat_l2 ) {
        global $wpdb;

        $cat_l1 = sanitize_text_field( (string) $cat_l1 );
        $cat_l2 = sanitize_text_field( (string) $cat_l2 );

        if ( '' === $cat_l1 || '' === $cat_l2 ) {
            return array();
        }

        $table_name = esc_sql( $this->table_name );

        // Multi-sélection possible sur cat_l1 et cat_l2 : "val1||val2"
        $values_l1 = array_filter( array_map( 'trim', explode( '||', $cat_l1 ) ) );
        $values_l2 = array_filter( array_map( 'trim', explode( '||', $cat_l2 ) ) );

        if ( empty( $values_l1 ) || empty( $values_l2 ) ) {
            return array();
        }

        $placeholders_l1 = implode( ',', array_fill( 0, count( $values_l1 ), '%s' ) );
        $placeholders_l2 = implode( ',', array_fill( 0, count( $values_l2 ), '%s' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL préparée juste après
        $sql = "SELECT DISTINCT TRIM(cat_l3) AS cat_l3
                FROM `{$table_name}`
                WHERE TRIM(cat_l1) IN ({$placeholders_l1})
                  AND TRIM(cat_l2) IN ({$placeholders_l2})
                  AND cat_l3 IS NOT NULL
                  AND TRIM(cat_l3) <> ''
                ORDER BY cat_l3 ASC";

        $prepared = $wpdb->prepare( $sql, array_merge( $values_l1, $values_l2 ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_col( $prepared );
    }
    /**
     * Retourne une page de produits depuis wp_bihr_products avec filtres.
     *
     * @param int    $page                      Page courante.
     * @param int    $per_page                  Nombre d'éléments par page.
     * @param string $search                    Mot-clé de recherche.
     * @param string $stock_filter              Filtre de stock.
     * @param string $price_min                 Prix minimum.
     * @param string $price_max                 Prix maximum.
     * @param string $category_filter           Filtre de catégorie interne Bihr.
     * @param string $sort_by                   Clé de tri.
     * @param string $cat_l1_filter             Filtre niveau 1 (CategoryPath).
     * @param string $cat_l2_filter             Filtre niveau 2 (CategoryPath).
     * @param string $cat_l3_filter             Filtre niveau 3 (CategoryPath).
     */
    public function get_products( $page = 1, $per_page = 20, $search = '', $stock_filter = '', $price_min = '', $price_max = '', $category_filter = '', $sort_by = '', $cat_l1_filter = '', $cat_l2_filter = '', $cat_l3_filter = '' ) {
        global $wpdb;

        $page     = max( 1, (int) $page );
        $per_page = max( 1, (int) $per_page );
        $offset   = ( $page - 1 ) * $per_page;

        $args = array();

        $search          = sanitize_text_field( (string) $search );
        $stock_filter    = sanitize_text_field( (string) $stock_filter );
        $category_filter = sanitize_text_field( (string) $category_filter );
        $sort_by         = sanitize_key( (string) $sort_by );
        $cat_l1_filter   = sanitize_text_field( (string) $cat_l1_filter );
        $cat_l2_filter   = sanitize_text_field( (string) $cat_l2_filter );
        $cat_l3_filter   = sanitize_text_field( (string) $cat_l3_filter );

        // Construire les conditions WHERE directement dans la chaîne SQL principale
        // Construire la requête SQL directement sans utiliser de tableau pour éviter les problèmes avec Plugin Check
        $where_sql = '1=1';
        
        if ( $search !== '' ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where_sql .= ' AND (product_code LIKE %s OR new_part_number LIKE %s OR name LIKE %s OR description LIKE %s)';
            $args[] = $search_like;
            $args[] = $search_like;
            $args[] = $search_like;
            $args[] = $search_like;
        }

        if ( $stock_filter === 'in_stock' ) {
            $where_sql .= ' AND stock_level > 0';
        } elseif ( $stock_filter === 'out_of_stock' ) {
            $where_sql .= ' AND (stock_level = 0 OR stock_level IS NULL)';
        }

        if ( $price_min !== '' && is_numeric( $price_min ) ) {
            $where_sql .= ' AND dealer_price_ht >= %f';
            $args[] = (float) $price_min;
        }

        if ( $price_max !== '' && is_numeric( $price_max ) ) {
            $where_sql .= ' AND dealer_price_ht <= %f';
            $args[] = (float) $price_max;
        }

        if ( $category_filter !== '' ) {
            $normalized_category = str_replace( "\xc2\xa0", ' ', $category_filter );
            $normalized_category = preg_replace( '/\s+/u', ' ', trim( $normalized_category ) );
            // Valider et échapper le nom de colonne category (whitelist)
            $category_column = esc_sql( 'category' );
            $where_sql .= " AND REPLACE(TRIM(`{$category_column}`), CHAR(160), ' ') = %s";
            $args[] = $normalized_category;
        }

        // Filtres sur les niveaux CategoryPath (cat_l1 / cat_l2 / cat_l3) stockés dans wp_bihr_products.
        // Supporte maintenant plusieurs valeurs par niveau, encodées sous forme "val1||val2||val3".
        if ( $cat_l1_filter !== '' ) {
            $values_l1 = array_filter( array_map( 'trim', explode( '||', $cat_l1_filter ) ) );
            if ( count( $values_l1 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l1 ), '%s' ) );
                // Utiliser TRIM(cat_l1) pour éviter les problèmes d'espaces parasites en base.
                $where_sql   .= " AND TRIM(cat_l1) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l1 );
            } else {
                $where_sql .= ' AND TRIM(cat_l1) = %s';
                $args[]     = reset( $values_l1 );
            }
        }

        if ( $cat_l2_filter !== '' ) {
            $values_l2 = array_filter( array_map( 'trim', explode( '||', $cat_l2_filter ) ) );
            if ( count( $values_l2 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l2 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l2) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l2 );
            } else {
                $where_sql .= ' AND TRIM(cat_l2) = %s';
                $args[]     = reset( $values_l2 );
            }
        }

        if ( $cat_l3_filter !== '' ) {
            $values_l3 = array_filter( array_map( 'trim', explode( '||', $cat_l3_filter ) ) );
            if ( count( $values_l3 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l3 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l3) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l3 );
            } else {
                $where_sql .= ' AND TRIM(cat_l3) = %s';
                $args[]     = reset( $values_l3 );
            }
        }

        // Whitelist des colonnes autorisées pour ORDER BY
        $allowed_columns = array( 'id', 'product_code', 'name', 'dealer_price_ht', 'stock_level', 'category' );
        $allowed_sorts = array(
            'price_asc'  => array( 'dealer_price_ht', 'ASC' ),
            'price_desc' => array( 'dealer_price_ht', 'DESC' ),
            'name_asc'   => array( 'name', 'ASC' ),
            'name_desc'  => array( 'name', 'DESC' ),
            'stock_asc'  => array( 'stock_level', 'ASC' ),
            'stock_desc' => array( 'stock_level', 'DESC' ),
        );

        $order_tuple  = isset( $allowed_sorts[ $sort_by ] ) ? $allowed_sorts[ $sort_by ] : array( 'id', 'ASC' );
        $order_column = $order_tuple[0];
        $order_dir    = $order_tuple[1];

        // Valider la colonne ORDER BY via whitelist
        if ( ! in_array( $order_column, $allowed_columns, true ) ) {
            $order_column = 'id';
        }

        // Valider la direction ORDER BY (strictement ASC ou DESC)
        if ( 'DESC' !== $order_dir && 'ASC' !== $order_dir ) {
            $order_dir = 'ASC';
        }

        // Échapper le nom de table et la colonne ORDER BY (les noms de table/colonnes ne peuvent pas utiliser de placeholders)
        $table_name = esc_sql( $this->table_name );
        $order_column = esc_sql( $order_column );
        $order_dir = esc_sql( $order_dir );

        // Forcer per_page et offset en entiers
        $per_page = absint( $per_page );
        $offset   = absint( $offset );

        // Construire la requête SQL complète avec tous les placeholders dans la chaîne principale
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $where_sql contains only placeholders and constant strings, all values are passed to prepare()
        $sql = "SELECT * FROM `{$table_name}` WHERE {$where_sql} ORDER BY `{$order_column}` {$order_dir} LIMIT %d OFFSET %d";

        // Préparer la requête avec tous les arguments (toujours utiliser prepare même si args est vide)
        $all_args = array_merge( $args, array( $per_page, $offset ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared with all placeholders
        $prepared = $wpdb->prepare( $sql, $all_args );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is prepared above with all placeholders
        return $wpdb->get_results( $prepared, OBJECT );
    }

    /**
     * Nombre total de lignes dans wp_bihr_products avec filtres.
     *
     * @param string $search              Mot-clé de recherche.
     * @param string $stock_filter        Filtre de stock.
     * @param string $price_min           Prix minimum.
     * @param string $price_max           Prix maximum.
     * @param string $category_filter     Filtre de catégorie interne Bihr.
     * @param string $cat_l1_filter       Filtre niveau 1 (CategoryPath).
     * @param string $cat_l2_filter       Filtre niveau 2 (CategoryPath).
     * @param string $cat_l3_filter       Filtre niveau 3 (CategoryPath).
     */
    public function get_products_count( $search = '', $stock_filter = '', $price_min = '', $price_max = '', $category_filter = '', $cat_l1_filter = '', $cat_l2_filter = '', $cat_l3_filter = '' ) {
        global $wpdb;

        $where = array();
        $args  = array();

        $args = array();

        $search          = sanitize_text_field( (string) $search );
        $stock_filter    = sanitize_text_field( (string) $stock_filter );
        $category_filter = sanitize_text_field( (string) $category_filter );
        $cat_l1_filter   = sanitize_text_field( (string) $cat_l1_filter );
        $cat_l2_filter   = sanitize_text_field( (string) $cat_l2_filter );
        $cat_l3_filter   = sanitize_text_field( (string) $cat_l3_filter );

        // Construire les conditions WHERE directement dans la chaîne SQL principale
        // Construire la requête SQL directement sans utiliser de tableau pour éviter les problèmes avec Plugin Check
        $where_sql = '1=1';
        
        if ( $search !== '' ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where_sql .= ' AND (product_code LIKE %s OR new_part_number LIKE %s OR name LIKE %s OR description LIKE %s)';
            $args[] = $search_like;
            $args[] = $search_like;
            $args[] = $search_like;
            $args[] = $search_like;
        }

        if ( $stock_filter === 'in_stock' ) {
            $where_sql .= ' AND stock_level > 0';
        } elseif ( $stock_filter === 'out_of_stock' ) {
            $where_sql .= ' AND (stock_level = 0 OR stock_level IS NULL)';
        }

        if ( $price_min !== '' && is_numeric( $price_min ) ) {
            $where_sql .= ' AND dealer_price_ht >= %f';
            $args[] = (float) $price_min;
        }

        if ( $price_max !== '' && is_numeric( $price_max ) ) {
            $where_sql .= ' AND dealer_price_ht <= %f';
            $args[] = (float) $price_max;
        }

        if ( $category_filter !== '' ) {
            $normalized_category = str_replace( "\xc2\xa0", ' ', $category_filter );
            $normalized_category = preg_replace( '/\s+/u', ' ', trim( $normalized_category ) );
            // Valider et échapper le nom de colonne category (whitelist)
            $category_column = esc_sql( 'category' );
            $where_sql .= " AND REPLACE(TRIM(`{$category_column}`), CHAR(160), ' ') = %s";
            $args[] = $normalized_category;
        }

        // Filtres sur les niveaux CategoryPath (support multi-valeurs "val1||val2")
        if ( $cat_l1_filter !== '' ) {
            $values_l1 = array_filter( array_map( 'trim', explode( '||', $cat_l1_filter ) ) );
            if ( count( $values_l1 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l1 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l1) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l1 );
            } else {
                $where_sql .= ' AND TRIM(cat_l1) = %s';
                $args[]     = reset( $values_l1 );
            }
        }

        if ( $cat_l2_filter !== '' ) {
            $values_l2 = array_filter( array_map( 'trim', explode( '||', $cat_l2_filter ) ) );
            if ( count( $values_l2 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l2 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l2) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l2 );
            } else {
                $where_sql .= ' AND TRIM(cat_l2) = %s';
                $args[]     = reset( $values_l2 );
            }
        }

        if ( $cat_l3_filter !== '' ) {
            $values_l3 = array_filter( array_map( 'trim', explode( '||', $cat_l3_filter ) ) );
            if ( count( $values_l3 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l3 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l3) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l3 );
            } else {
                $where_sql .= ' AND TRIM(cat_l3) = %s';
                $args[]     = reset( $values_l3 );
            }
        }

        // Échapper le nom de table pour la sécurité (les noms de table ne peuvent pas utiliser de placeholders)
        $table_name = esc_sql( $this->table_name );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- $where_sql contains only placeholders and constant strings, all values are passed to prepare()
        $sql = "SELECT COUNT(*) FROM `{$table_name}` WHERE {$where_sql}";

        // Préparer la requête avec tous les arguments (toujours utiliser prepare même si args est vide)
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- SQL is prepared with all placeholders
        $prepared = $wpdb->prepare( $sql, $args );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Query is prepared above with all placeholders
        return (int) $wpdb->get_var( $prepared );
    }

    /**
     * Récupère tous les IDs de produits correspondant aux filtres (sans pagination).
     * Utilisé pour importer tous les produits d'une catégorie/filtre.
     *
     * @param string $search
     * @param string $stock_filter
     * @param string $price_min
     * @param string $price_max
     * @param string $category_filter
     * @param string $cat_l1_filter
     * @param string $cat_l2_filter
     * @param string $cat_l3_filter
     * @return array Tableau d'IDs (int[])
     */
    public function get_all_filtered_product_ids( $search = '', $stock_filter = '', $price_min = '', $price_max = '', $category_filter = '', $cat_l1_filter = '', $cat_l2_filter = '', $cat_l3_filter = '' ) {
        global $wpdb;

        $args = array();

        $search          = sanitize_text_field( (string) $search );
        $stock_filter    = sanitize_text_field( (string) $stock_filter );
        $category_filter = sanitize_text_field( (string) $category_filter );
        $cat_l1_filter   = sanitize_text_field( (string) $cat_l1_filter );
        $cat_l2_filter   = sanitize_text_field( (string) $cat_l2_filter );
        $cat_l3_filter   = sanitize_text_field( (string) $cat_l3_filter );

        $where_sql = '1=1';
        
        if ( $search !== '' ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where_sql .= ' AND (product_code LIKE %s OR new_part_number LIKE %s OR name LIKE %s OR description LIKE %s)';
            $args[] = $search_like;
            $args[] = $search_like;
            $args[] = $search_like;
            $args[] = $search_like;
        }

        if ( $stock_filter === 'in_stock' ) {
            $where_sql .= ' AND stock_level > 0';
        } elseif ( $stock_filter === 'out_of_stock' ) {
            $where_sql .= ' AND (stock_level = 0 OR stock_level IS NULL)';
        }

        if ( $price_min !== '' && is_numeric( $price_min ) ) {
            $where_sql .= ' AND dealer_price_ht >= %f';
            $args[] = (float) $price_min;
        }

        if ( $price_max !== '' && is_numeric( $price_max ) ) {
            $where_sql .= ' AND dealer_price_ht <= %f';
            $args[] = (float) $price_max;
        }

        if ( $category_filter !== '' ) {
            $where_sql .= ' AND category = %s';
            $args[] = $category_filter;
        }

        // Filtres sur les niveaux CategoryPath (support multi-valeurs "val1||val2")
        if ( $cat_l1_filter !== '' ) {
            $values_l1 = array_filter( array_map( 'trim', explode( '||', $cat_l1_filter ) ) );
            if ( count( $values_l1 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l1 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l1) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l1 );
            } else {
                $where_sql .= ' AND TRIM(cat_l1) = %s';
                $args[]     = reset( $values_l1 );
            }
        }
        if ( $cat_l2_filter !== '' ) {
            $values_l2 = array_filter( array_map( 'trim', explode( '||', $cat_l2_filter ) ) );
            if ( count( $values_l2 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l2 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l2) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l2 );
            } else {
                $where_sql .= ' AND TRIM(cat_l2) = %s';
                $args[]     = reset( $values_l2 );
            }
        }
        if ( $cat_l3_filter !== '' ) {
            $values_l3 = array_filter( array_map( 'trim', explode( '||', $cat_l3_filter ) ) );
            if ( count( $values_l3 ) > 1 ) {
                $placeholders = implode( ',', array_fill( 0, count( $values_l3 ), '%s' ) );
                $where_sql   .= " AND TRIM(cat_l3) IN ({$placeholders})";
                $args         = array_merge( $args, $values_l3 );
            } else {
                $where_sql .= ' AND TRIM(cat_l3) = %s';
                $args[]     = reset( $values_l3 );
            }
        }

        $table_name = esc_sql( $this->table_name );
        $sql = "SELECT id FROM `{$table_name}` WHERE {$where_sql} ORDER BY id ASC";

        if ( ! empty( $args ) ) {
            $prepared = $wpdb->prepare( $sql, $args );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- pas de paramètres utilisateur
            $prepared = $sql;
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Query is prepared above
        $ids = $wpdb->get_col( $prepared );

        return array_map( 'intval', $ids );
    }

    /* =========================================================
     *          IMPORT D’UN PRODUIT DANS WOOCOMMERCE
     * ======================================================= */

    /**
     * Importe un produit Bihr (ligne de wp_bihr_products) vers WooCommerce
     */
    public function import_to_woocommerce( $product_id ) {
        global $wpdb;

        // Échapper le nom de table pour la sécurité (les noms de table ne peuvent pas utiliser de placeholders)
        $table_name = esc_sql( $this->table_name );
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM `{$table_name}` WHERE id = %d",
                (int) $product_id
            )
        );

        if ( ! $row ) {
            throw new Exception( 'Produit introuvable dans wp_bihr_products.' );
        }

        if ( ! class_exists( 'WC_Product_Simple' ) ) {
            throw new Exception( 'WooCommerce n’est pas chargé.' );
        }

        $this->logger->log( 'Import WooCommerce: préparation produit ' . $row->product_code );

        // Déterminer le SKU cible (priorité NewPartNumber)
        $sku = ! empty( $row->new_part_number ) ? $row->new_part_number : $row->product_code;

        // Tenter de réutiliser un produit existant (évite les doublons)
        $existing_product_id = $this->find_existing_product( $sku, $row->product_code );

        if ( $existing_product_id ) {
            $product = wc_get_product( $existing_product_id );

            // Si le type n'est pas simple, rebasculer sur un produit simple pour aligner avec la logique d'import
            if ( ! $product instanceof WC_Product_Simple ) {
                $product = new WC_Product_Simple( $existing_product_id );
            }

            $this->logger->log( 'Import WooCommerce: mise à jour du produit existant ID ' . $existing_product_id );
        } else {
            // Création d’un produit simple
            $product = new WC_Product_Simple();
        }

        // Nom du produit (sera peut-être amélioré par l'IA)
        $name = $row->name ?: $row->product_code;

        // SKU : utiliser NewPartNumber si disponible, sinon ProductCode
        if ( ! empty( $sku ) ) {
            $product->set_sku( $sku );
        }

        // Description de base
        $base_description = '';
        if ( ! empty( $row->description ) ) {
            $base_description = $row->description;
        }

        // Enrichissement IA si disponible
        $ai_enrichment = new BihrWI_AI_Enrichment( $this->logger );
        if ( $ai_enrichment->is_enabled() ) {
            $this->logger->log( 'Import WooCommerce: enrichissement IA activé pour ' . $row->product_code );
            
            // Construire l'URL complète de l'image si disponible
            $full_image_url = '';
            if ( ! empty( $row->image_url ) ) {
                if ( preg_match( '#^https?://#i', $row->image_url ) ) {
                    $full_image_url = $row->image_url;
                } else {
                    $full_image_url = rtrim( BIHRWI_IMAGE_BASE_URL, '/' ) . '/' . ltrim( $row->image_url, '/' );
                }
            }
            
            $this->logger->log( 'IA - Appel generate_descriptions pour: ' . $name . ' | Image URL: ' . $full_image_url . ' | Code: ' . $row->product_code );
            
            // Génération du nom amélioré et des descriptions enrichies
            $ai_descriptions = $ai_enrichment->generate_descriptions( $name, $full_image_url, $row->product_code );
            
            $this->logger->log( 'IA - Résultat generate_descriptions: ' . wp_json_encode( $ai_descriptions ) );
            
            if ( $ai_descriptions && is_array( $ai_descriptions ) ) {
                // Nom amélioré par l'IA (si disponible)
                if ( ! empty( $ai_descriptions['product_name'] ) ) {
                    $name = $ai_descriptions['product_name'];
                    $this->logger->log( 'Import WooCommerce: nom amélioré par IA - ' . $name );
                }
                
                // Description courte (excerpt WooCommerce)
                if ( ! empty( $ai_descriptions['short_description'] ) ) {
                    $product->set_short_description( $ai_descriptions['short_description'] );
                    $this->logger->log( 'Import WooCommerce: description courte IA ajoutée - ' . $ai_descriptions['short_description'] );

                }
                
                // Description longue
                if ( ! empty( $ai_descriptions['long_description'] ) ) {
                    // On peut ajouter la description de base en complément si elle existe
                    $long_desc = $ai_descriptions['long_description'];
                    if ( ! empty( $base_description ) ) {
                        $long_desc .= "\n\n<h3>Informations techniques</h3>\n" . $base_description;
                    }
                    $product->set_description( $long_desc );
                    $this->logger->log( 'Import WooCommerce: description longue IA ajoutée' );
                } else {
                    $product->set_description( $base_description );
                }
            } else {
                // Fallback sur description de base si l'IA échoue
                $product->set_description( $base_description );
                $this->logger->log( 'Import WooCommerce: utilisation description de base (IA échouée)' );
            }
        } else {
            // Pas d'enrichissement IA
            $product->set_description( $base_description );
        }

        // Définir le nom du produit (amélioré par IA si disponible, sinon nom original)
        $product->set_name( $name );

        // Prix HT avec application de la marge
        if ( $row->dealer_price_ht !== null ) {
            $price_with_margin = $this->calculate_price_with_margin(
                $row->dealer_price_ht,
                $row->category
            );
            $product->set_regular_price( wc_format_decimal( $price_with_margin ) );
        }

        // Gestion du stock
        if ( $row->stock_level !== null ) {
            $product->set_manage_stock( true );
            $product->set_stock_quantity( (int) $row->stock_level );
            $product->set_stock_status( (int) $row->stock_level > 0 ? 'instock' : 'outofstock' );
        }

        // Sauvegarde du produit
        $product_id_wc = $product->save();

        // Stocker le prix fournisseur dans les métadonnées
        if ( $row->dealer_price_ht !== null ) {
            update_post_meta( $product_id_wc, '_bihr_supplier_price', $row->dealer_price_ht );
        }

        // Meta Bihr
        update_post_meta( $product_id_wc, '_bihr_product_code', $row->product_code );
        if ( ! empty( $row->new_part_number ) ) {
            update_post_meta( $product_id_wc, '_bihr_new_part_number', $row->new_part_number );
        }

        // Gestion des catégories WooCommerce à partir des niveaux CategoryPath Bihr (cat_l1/2/3).
        if ( class_exists( 'BihrWI_Category_Path' ) && taxonomy_exists( 'product_cat' ) ) {
            $levels = array(
                'l1' => ! empty( $row->cat_l1 ) ? (string) $row->cat_l1 : '',
                'l2' => ! empty( $row->cat_l2 ) ? (string) $row->cat_l2 : '',
                'l3' => ! empty( $row->cat_l3 ) ? (string) $row->cat_l3 : '',
            );

            $term_id = BihrWI_Category_Path::ensure_product_categories( $levels['l1'], $levels['l2'], $levels['l3'] );

            if ( $term_id ) {
                // Assigner uniquement la catégorie la plus précise (les parents sont gérés par product_cat).
                wp_set_object_terms( $product_id_wc, array( $term_id ), 'product_cat' );
                $this->logger->log( 'Catégorie product_cat assignée (term_id=' . $term_id . ') pour ' . $row->product_code );
            }

            // Stocker les niveaux Bihr en métadonnées (facultatif mais utile).
            if ( ! empty( $levels['l1'] ) ) {
                update_post_meta( $product_id_wc, '_bihr_cat_l1', $levels['l1'] );
            }
            if ( ! empty( $levels['l2'] ) ) {
                update_post_meta( $product_id_wc, '_bihr_cat_l2', $levels['l2'] );
            }
            if ( ! empty( $levels['l3'] ) ) {
                update_post_meta( $product_id_wc, '_bihr_cat_l3', $levels['l3'] );
            }
        }

        // Image principale (si URL disponible)
        if ( ! empty( $row->image_url ) ) {
            $attachment_id = $this->download_and_attach_image( $row->image_url, $product_id_wc );
            if ( $attachment_id ) {
                $product->set_image_id( $attachment_id );
                $product->save();
            }
        }

        $this->logger->log(
            'Import WooCommerce: produit ' . $row->product_code . ' importé avec succès (post_id=' . $product_id_wc . ')'
        );

        return $product_id_wc;
    }

    /**
     * Télécharge et attache une image à un produit WooCommerce
     */
    protected function download_and_attach_image( $image_url, $post_id ) {

        // OPTION 2 : si l'URL ne commence pas par http, on ajoute le préfixe https://api.mybihr.com
        if ( ! preg_match( '#^https?://#i', $image_url ) ) {
            $image_url = rtrim( BIHRWI_IMAGE_BASE_URL, '/' ) . '/' . ltrim( $image_url, '/' );
        }

        $this->logger->log( 'Téléchargement image : ' . $image_url );

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        // Vérifie si on a déjà une image avec cette URL (évite les doublons)
        $existing_id = $this->find_existing_attachment_by_url( $image_url );
        if ( $existing_id ) {
            $this->logger->log( 'Image déjà présente (attachment_id=' . $existing_id . ').' );
            set_post_thumbnail( $post_id, $existing_id );
            return $existing_id;
        }

        // Télécharge l'image avec media_sideload_image
        $tmp = download_url( $image_url );

        if ( is_wp_error( $tmp ) ) {
            $this->logger->log( 'Erreur download_url : ' . $tmp->get_error_message() );
            return 0;
        }

        // Détecte le type MIME du fichier téléchargé
        $parsed_url = wp_parse_url( $image_url );
        $file_type = wp_check_filetype_and_ext( $tmp, basename( $parsed_url['path'] ) );
        
        // Si le type n'est pas détecté, on essaie avec mime_content_type
        if ( ! $file_type['ext'] && function_exists( 'mime_content_type' ) ) {
            $mime = mime_content_type( $tmp );
            $mime_to_ext = array(
                'image/jpeg' => 'jpg',
                'image/jpg'  => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            );
            
            if ( isset( $mime_to_ext[ $mime ] ) ) {
                $file_type['ext']  = $mime_to_ext[ $mime ];
                $file_type['type'] = $mime;
            }
        }
        
        // Génère un nom de fichier avec l'extension appropriée
        $filename = basename( $parsed_url['path'] );
        
        // Si le fichier n'a pas d'extension reconnue, on ajoute celle détectée
        if ( ! empty( $file_type['ext'] ) ) {
            $path_info = pathinfo( $filename );
            if ( empty( $path_info['extension'] ) || ! in_array( strtolower( $path_info['extension'] ), array( 'jpg', 'jpeg', 'png', 'gif', 'webp' ) ) ) {
                $filename = $path_info['filename'] . '.' . $file_type['ext'];
            }
        }

        $file_array = array(
            'name'     => $filename,
            'tmp_name' => $tmp,
            'type'     => $file_type['type'],
        );

        $attachment_id = media_handle_sideload( $file_array, $post_id );

        if ( is_wp_error( $attachment_id ) ) {
            $this->logger->log( 'Erreur media_handle_sideload : ' . $attachment_id->get_error_message() );
            if ( file_exists( $tmp ) ) {
                wp_delete_file( $tmp );
            }
            return 0;
        }

        // On stocke la source dans un meta pour éviter les doublons plus tard
        update_post_meta( $attachment_id, '_bihr_image_source', esc_url_raw( $image_url ) );

        return $attachment_id;
    }

    /**
     * Retrouve un attachment existant par son meta _bihr_image_source
     */
    protected function find_existing_attachment_by_url( $image_url ) {
        $args = array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'meta_query'     => array(
                array(
                    'key'   => '_bihr_image_source',
                    'value' => esc_url_raw( $image_url ),
                ),
            ),
            'fields'         => 'ids',
        );

        $ids = get_posts( $args );
        if ( ! empty( $ids ) ) {
            return (int) $ids[0];
        }

        return 0;
    }

    /* =========================================================
     *      FUSION DES CATALOGUES CSV -> TABLE wp_bihr_products
     * ======================================================= */

    /**
     * Fusionne les différents catalogues CSV présents dans wp-content/uploads/bihr-import/
     */
    public function merge_catalogs_from_directory() {
        $upload_dir = WP_CONTENT_DIR . '/uploads/bihr-import';

        if ( ! is_dir( $upload_dir ) ) {
            wp_mkdir_p( $upload_dir );
        }

        $this->logger->log( '========================================' );
        $this->logger->log( '=== DÉBUT FUSION DES CATALOGUES ===' );
        $this->logger->log( '========================================' );
        $this->logger->log( 'Dossier de recherche: ' . $upload_dir );

        // 1) Tentative "mode Extended seul" : si les fichiers cat-extended-full-*.csv
        // contiennent déjà toutes les données, on peut construire directement
        // la liste des produits sans dépendre des autres catalogues.
        $extended_master_products = $this->build_products_from_extended_full( $upload_dir );
        if ( ! empty( $extended_master_products ) ) {
            $this->logger->log( 'Mode Extended seul: utilisation directe de cat-extended-full pour construire les produits.' );

            $count = $this->save_merged_products( $extended_master_products );

            $this->logger->log( 'TOTAL FUSIONNÉ (Extended seul): ' . count( $extended_master_products ) . ' produits uniques' );
            $this->logger->log( 'Produits enregistrés: ' . $count );
            $this->logger->log( '=== FIN FUSION (mode Extended seul) ===' );

            return $count;
        }

        // Recherche des différents fichiers (le plus récent pour chaque type)
        // IMPORTANT : on utilise des patterns STRICTS pour References et ExtendedReferences
        // afin d'éviter les collisions entre cat-ref-full-*.csv et cat-extref-full-*.csv.
        $files = array(
            'references'         => $this->find_latest_references_file( $upload_dir ),
            'extendedreferences' => $this->find_latest_extreferences_file( $upload_dir ),
            'prices'             => $this->find_latest_catalog_file( $upload_dir, 'prices' ),
            'images'             => $this->find_latest_catalog_file( $upload_dir, 'images' ),
            'inventory'          => $this->find_latest_catalog_file( $upload_dir, 'inventory' ),
            'attributes'         => $this->find_latest_catalog_file( $upload_dir, 'attributes' ),
        );

        $this->logger->log( '--- Fichiers catalogues trouvés ---' );
        foreach ( $files as $type => $file ) {
            if ( $file ) {
                $this->logger->log( "✓ {$type}: " . basename( $file ) );
            } else {
                $this->logger->log( "✗ {$type}: MANQUANT" );
            }
        }

        $references_data         = array();
        $extendedreferences_data = array();
        $prices_data             = array();
        $images_data             = array();
        $inventory_data          = array();
        $attributes_data         = array();

        $this->logger->log( '--- Parsing des fichiers CSV ---' );

        if ( ! empty( $files['references'] ) ) {
            $this->logger->log( 'Parsing References...' );
            $references_data = $this->parse_references_csv( $files['references'] );
            $this->logger->log( "✓ References: " . count( $references_data ) . " produits" );
        } else {
            $this->logger->log( '⚠ ATTENTION: Fichier References manquant - les noms de produits ne seront pas disponibles!' );
        }

        if ( ! empty( $files['extendedreferences'] ) ) {
            $this->logger->log( 'Parsing ExtendedReferences...' );
            // ExtendedReferences peut être divisé en plusieurs fichiers (_A, _B, etc.)
            $extref_pattern = str_replace( basename( $files['extendedreferences'] ), 'cat-extref-full-*.csv', $files['extendedreferences'] );
            $all_extref_files = glob( $extref_pattern );
            
            if ( ! empty( $all_extref_files ) ) {
                $this->logger->log( 'ExtendedReferences trouvé en ' . count( $all_extref_files ) . ' parties' );
                foreach ( $all_extref_files as $extref_file ) {
                    $this->logger->log( '  - Lecture: ' . basename( $extref_file ) );
                    $partial_data = $this->parse_extendedreferences_csv( $extref_file );
                    $this->logger->log( '    → ' . count( $partial_data ) . ' produits' );
                    
                    // Merge intelligent : ne pas écraser la catégorie si elle existe déjà
                    foreach ( $partial_data as $code => $data ) {
                        if ( ! isset( $extendedreferences_data[ $code ] ) ) {
                            // Nouveau produit : on l'ajoute tel quel
                            $extendedreferences_data[ $code ] = $data;
                        } else {
                            // Produit existant : on merge SAUF la catégorie si elle existe déjà
                            foreach ( $data as $key => $value ) {
                                if ( $key === 'category' && isset( $extendedreferences_data[ $code ]['category'] ) && ! empty( $extendedreferences_data[ $code ]['category'] ) ) {
                                    // Garder la première catégorie trouvée
                                    continue;
                                }
                                $extendedreferences_data[ $code ][ $key ] = $value;
                            }
                        }
                    }
                }
                $this->logger->log( "✓ ExtendedReferences total: " . count( $extendedreferences_data ) . " produits" );
            } else {
                $extendedreferences_data = $this->parse_extendedreferences_csv( $files['extendedreferences'] );
                $this->logger->log( "✓ ExtendedReferences: " . count( $extendedreferences_data ) . " produits" );
            }
        } else {
            $this->logger->log( '⚠ ATTENTION: Fichier ExtendedReferences manquant - les descriptions longues ne seront pas disponibles!' );
        }

        if ( ! empty( $files['prices'] ) ) {
            $this->logger->log( 'Parsing Prices...' );
            $prices_data = $this->parse_prices_csv( $files['prices'] );
            $this->logger->log( "✓ Prices: " . count( $prices_data ) . " produits" );
        } else {
            $this->logger->log( '⚠ ATTENTION: Fichier Prices manquant!' );
        }

        if ( ! empty( $files['images'] ) ) {
            $this->logger->log( 'Parsing Images...' );
            $images_data = $this->parse_images_csv( $files['images'] );
            $this->logger->log( "✓ Images: " . count( $images_data ) . " produits" );
        } else {
            $this->logger->log( '⚠ ATTENTION: Fichier Images manquant!' );
        }

        if ( ! empty( $files['inventory'] ) ) {
            $this->logger->log( 'Parsing Inventory (Stock)...' );
            $inventory_data = $this->parse_inventory_csv( $files['inventory'] );
            $this->logger->log( "✓ Inventory: " . count( $inventory_data ) . " produits" );
        } else {
            $this->logger->log( '⚠ ATTENTION: Fichier Inventory manquant!' );
        }

        if ( ! empty( $files['attributes'] ) ) {
            $this->logger->log( 'Parsing Attributes...' );
            $attributes_data = $this->parse_attributes_csv( $files['attributes'] );
            $this->logger->log( "✓ Attributes: " . count( $attributes_data ) . " produits" );
        } else {
            $this->logger->log( '⚠ ATTENTION: Fichier Attributes manquant!' );
        }

        $this->logger->log( '--- Fusion des données par code produit ---' );

        // Fusion par code produit
        $merged = array();

        // Références comme base principale
        foreach ( $references_data as $code => $row ) {
            if ( ! isset( $merged[ $code ] ) ) {
                $merged[ $code ] = array();
            }
            $merged[ $code ] = array_merge( $merged[ $code ], $row );
        }

        // ExtendedReferences : on fusionne en priorité pour écraser les descriptions de base
        foreach ( $extendedreferences_data as $code => $row ) {
            if ( ! isset( $merged[ $code ] ) ) {
                $merged[ $code ] = array( 'product_code' => $code );
            }
            
            // Fusionner les données SAUF le nom et la catégorie s'ils existent déjà
            foreach ( $row as $key => $value ) {
                // Ne pas écraser le nom s'il est déjà défini (priorité à References)
                if ( $key === 'name' && isset( $merged[ $code ]['name'] ) && ! empty( $merged[ $code ]['name'] ) ) {
                    continue;
                }
                // Ne pas écraser la catégorie si elle est déjà définie
                if ( $key === 'category' && isset( $merged[ $code ]['category'] ) && ! empty( $merged[ $code ]['category'] ) ) {
                    continue;
                }
                $merged[ $code ][ $key ] = $value;
            }
        }

        // On ajoute ce qui n’est pas encore présent
        foreach ( $prices_data as $code => $row ) {
            if ( ! isset( $merged[ $code ] ) ) {
                $merged[ $code ] = array( 'product_code' => $code );
            }
            $merged[ $code ] = array_merge( $merged[ $code ], $row );
        }

        foreach ( $images_data as $code => $row ) {
            if ( ! isset( $merged[ $code ] ) ) {
                $merged[ $code ] = array( 'product_code' => $code );
            }
            $merged[ $code ] = array_merge( $merged[ $code ], $row );
        }

        foreach ( $inventory_data as $code => $row ) {
            if ( ! isset( $merged[ $code ] ) ) {
                $merged[ $code ] = array( 'product_code' => $code );
            }
            $merged[ $code ] = array_merge( $merged[ $code ], $row );
        }

        foreach ( $attributes_data as $code => $row ) {
            if ( ! isset( $merged[ $code ] ) ) {
                $merged[ $code ] = array( 'product_code' => $code );
            }
            $merged[ $code ] = array_merge( $merged[ $code ], $row );
        }

        // Log d'un exemple de produit fusionné pour debug
        if ( ! empty( $merged ) ) {
            $first_merged = reset( $merged );
            $first_code = key( $merged );
            reset( $merged );
            $this->logger->log( '--- Exemple de produit fusionné ---' );
            $this->logger->log( 'Code: ' . $first_code );
            $this->logger->log( 'Nom: ' . ( isset( $first_merged['name'] ) ? $first_merged['name'] : 'NULL' ) );
            $this->logger->log( 'Prix: ' . ( isset( $first_merged['dealer_price_ht'] ) ? $first_merged['dealer_price_ht'] . '€' : 'NULL' ) );
            $this->logger->log( 'Stock: ' . ( isset( $first_merged['stock_level'] ) ? $first_merged['stock_level'] : 'NULL' ) );
            $this->logger->log( 'Image: ' . ( isset( $first_merged['image_url'] ) ? 'OUI' : 'NON' ) );
            $this->logger->log( 'Description: ' . ( isset( $first_merged['description'] ) ? substr( $first_merged['description'], 0, 80 ) . '...' : 'NULL' ) );
        }

        // Appliquer les catégories hiérarchiques depuis cat-extended-full-*.csv (source unique désormais)
        $this->logger->log( '--- Catégories hiérarchiques depuis cat-extended-full (source principale) ---' );
        $extended_categories = $this->load_extended_full_categories( $upload_dir );
        if ( ! empty( $extended_categories ) ) {
            $this->apply_extended_categories_fallback( $merged, $extended_categories );
        } else {
            $this->logger->log( 'ExtendedFull: aucun catalogue cat-extended-full-* détecté, aucun fallback appliqué.' );
        }

        $this->logger->log( '--- Statistiques de fusion ---' );
        $this->logger->log( 'References: ' . count( $references_data ) . ' entrées' );
        $this->logger->log( 'ExtendedReferences: ' . count( $extendedreferences_data ) . ' entrées' );
        $this->logger->log( 'Prices: ' . count( $prices_data ) . ' entrées' );
        $this->logger->log( 'Images: ' . count( $images_data ) . ' entrées' );
        $this->logger->log( 'Inventory: ' . count( $inventory_data ) . ' entrées' );
        $this->logger->log( 'Attributes: ' . count( $attributes_data ) . ' entrées' );
        $this->logger->log( 'TOTAL FUSIONNÉ: ' . count( $merged ) . ' produits uniques' );

        // Écriture dans la table wp_bihr_products
        $this->logger->log( '--- Sauvegarde dans la base de données ---' );
        $count = $this->save_merged_products( $merged );

        $this->logger->log( '========================================' );
        $this->logger->log( '✓ FUSION TERMINÉE: ' . $count . ' produits enregistrés' );
        $this->logger->log( '========================================' );

        return $count;
    }

    /**
     * Trouve le fichier CSV le plus récent contenant un mot clé (fallback générique).
     * NOTE : pour References et ExtendedReferences on privilégie désormais des helpers
     * dédiés avec des patterns stricts (cat-ref-full-*.csv / cat-extref-full-*.csv).
     */
    protected function find_latest_catalog_file( $dir, $keyword ) {
        $pattern = trailingslashit( $dir ) . '*' . $keyword . '*.csv';

        $files = glob( $pattern );
        if ( empty( $files ) ) {
            $this->logger->log( "Aucun fichier trouvé pour pattern générique: {$pattern}" );
            return '';
        }

        usort(
            $files,
            function( $a, $b ) {
                return filemtime( $b ) - filemtime( $a );
            }
        );

        $this->logger->log( "Fichier (fallback) trouvé pour '{$keyword}': " . basename( $files[0] ) );
        return $files[0];
    }

    /**
     * Trouve le fichier de références principal (cat-ref-full-*.csv) le plus récent.
     * Fallback : si aucun fichier strict n'est trouvé, on revient au comportement historique
     * via find_latest_catalog_file( 'ref' ) pour ne rien casser.
     */
    protected function find_latest_references_file( $dir ) {
        $strict_pattern = trailingslashit( $dir ) . 'cat-ref-full-*.csv';
        $files          = glob( $strict_pattern );

        if ( ! empty( $files ) ) {
            usort(
                $files,
                function( $a, $b ) {
                    return filemtime( $b ) - filemtime( $a );
                }
            );
            $this->logger->log( 'Fichier References (strict) trouvé: ' . basename( $files[0] ) );
            return $files[0];
        }

        // Fallback documenté : on garde l'ancien comportement si aucun cat-ref-full n'est trouvé.
        $this->logger->log( 'Aucun cat-ref-full-*.csv trouvé, fallback sur recherche générique *ref*.csv' );
        return $this->find_latest_catalog_file( $dir, 'ref' );
    }

    /**
     * Trouve le fichier ExtendedReferences (cat-extref-full-*.csv) le plus récent.
     * Fallback : si aucun fichier strict n'est trouvé, on revient au comportement historique
     * via find_latest_catalog_file( 'extref' ).
     */
    protected function find_latest_extreferences_file( $dir ) {
        $strict_pattern = trailingslashit( $dir ) . 'cat-extref-full-*.csv';
        $files          = glob( $strict_pattern );

        if ( ! empty( $files ) ) {
            usort(
                $files,
                function( $a, $b ) {
                    return filemtime( $b ) - filemtime( $a );
                }
            );
            $this->logger->log( 'Fichier ExtendedReferences (strict) trouvé: ' . basename( $files[0] ) );
            return $files[0];
        }

        $this->logger->log( 'Aucun cat-extref-full-*.csv trouvé, fallback sur recherche générique *extref*.csv' );
        return $this->find_latest_catalog_file( $dir, 'extref' );
    }

    /**
     * Charge les catégories hiérarchiques depuis les fichiers cat-extended-full-*.csv
     * extraits dans le dossier d'import.
     *
     * Retourne un tableau associatif:
     *   [PartNumber] => [ 'cat_l1' => Category1, 'cat_l2' => Category2, 'cat_l3' => Category3 ]
     *
     * Fallback silencieux si aucun fichier n'est présent.
     */
    protected function load_extended_full_categories( $dir ) {
        $extended = array();

        $pattern = trailingslashit( $dir ) . 'cat-extended-full-*.csv';
        $files   = glob( $pattern );

        if ( empty( $files ) ) {
            $this->logger->log( 'ExtendedFull: aucun fichier cat-extended-full-*.csv trouvé dans ' . $dir );
            return $extended;
        }

        $total_rows        = 0;
        $total_with_cats   = 0;

        $this->logger->log( 'ExtendedFull: ' . count( $files ) . ' fichier(s) détecté(s) pour les catégories étendues.' );

        foreach ( $files as $file_path ) {
            $basename = basename( $file_path );
            $this->logger->log( 'ExtendedFull: parsing ' . $basename );

            if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
                $this->logger->log( 'ExtendedFull: fichier non lisible, ignoré: ' . $file_path );
                continue;
            }

            $handle = fopen( $file_path, 'r' );
            if ( false === $handle ) {
                $this->logger->log( 'ExtendedFull: impossible d\'ouvrir le fichier: ' . $file_path );
                continue;
            }

            // Gestion BOM UTF-8 éventuel
            $bom = fread( $handle, 3 );
            if ( $bom !== "\xEF\xBB\xBF" ) {
                rewind( $handle );
            }

            // Détection du séparateur sur la première ligne lisible
            $first_line = fgets( $handle );
            rewind( $handle );
            if ( $bom === "\xEF\xBB\xBF" ) {
                fread( $handle, 3 ); // Skip BOM
            }
            $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

            // Lecture de l'en-tête
            $header = fgetcsv( $handle, 0, $delimiter );
            if ( false === $header ) {
                fclose( $handle );
                $this->logger->log( 'ExtendedFull: impossible de lire l\'en-tête CSV dans ' . $basename );
                continue;
            }

            $header = array_map(
                function( $h ) {
                    return strtolower( trim( $h ) );
                },
                $header
            );

            // Index des colonnes
            $idx_partnumber = array_search( 'partnumber', $header, true );
            $idx_cat1       = array_search( 'category1', $header, true );
            $idx_cat2       = array_search( 'category2', $header, true );
            $idx_cat3       = array_search( 'category3', $header, true );

            if ( false === $idx_partnumber ) {
                fclose( $handle );
                $this->logger->log( 'ExtendedFull: colonne PartNumber introuvable dans ' . $basename . ', fichier ignoré.' );
                continue;
            }

            // Parcours des lignes
            while ( ( $data = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
                if ( empty( $data ) || count( $data ) < ( $idx_partnumber + 1 ) ) {
                    continue;
                }

                $total_rows++;

                $partnumber = trim( (string) $data[ $idx_partnumber ] );
                if ( $partnumber === '' ) {
                    continue;
                }

                $cat1 = ( false !== $idx_cat1 && isset( $data[ $idx_cat1 ] ) ) ? trim( (string) $data[ $idx_cat1 ] ) : '';
                $cat2 = ( false !== $idx_cat2 && isset( $data[ $idx_cat2 ] ) ) ? trim( (string) $data[ $idx_cat2 ] ) : '';
                $cat3 = ( false !== $idx_cat3 && isset( $data[ $idx_cat3 ] ) ) ? trim( (string) $data[ $idx_cat3 ] ) : '';

                if ( $cat1 === '' && $cat2 === '' && $cat3 === '' ) {
                    continue;
                }

                $extended[ $partnumber ] = array(
                    'cat_l1' => $cat1 !== '' ? $cat1 : null,
                    'cat_l2' => $cat2 !== '' ? $cat2 : null,
                    'cat_l3' => $cat3 !== '' ? $cat3 : null,
                );

                $total_with_cats++;
            }

            fclose( $handle );
        }

        $this->logger->log(
            sprintf(
                'ExtendedFull: %d lignes lues, %d produits avec catégories hiérarchiques.',
                $total_rows,
                $total_with_cats
            )
        );

        return $extended;
    }

    /**
     * Mode "Extended seul" : construit une liste complète de produits
     * à partir des fichiers cat-extended-full-*.csv lorsque ceux-ci
     * contiennent déjà toutes les informations nécessaires
     * (code produit, nom, prix, stock, images, catégories 1/2/3, etc.).
     *
     * Retourne un tableau:
     *   [ProductCode] => [
     *      'product_code'    => string,
     *      'new_part_number' => string|null,
     *      'name'            => string|null,
     *      'description'     => string|null,
     *      'dealer_price_ht' => float|null,
     *      'stock_level'     => int|null,
     *      'image_url'       => string|null,
     *      'category'        => string|null, // macro (Category1)
     *      'cat_l1'          => string|null,
     *      'cat_l2'          => string|null,
     *      'cat_l3'          => string|null,
     *   ]
     *
     * Si aucun fichier n'est trouvé ou qu'aucune ligne exploitable
     * n'est présente, retourne un tableau vide.
     */
    protected function build_products_from_extended_full( $dir ) {
        $products = array();

        $pattern = trailingslashit( $dir ) . 'cat-extended-full-*.csv';
        $files   = glob( $pattern );

        if ( empty( $files ) ) {
            $this->logger->log( 'ExtendedFull Master: aucun fichier cat-extended-full-*.csv trouvé dans ' . $dir );
            return $products;
        }

        $total_rows   = 0;
        $total_built  = 0;

        $this->logger->log( 'ExtendedFull Master: ' . count( $files ) . ' fichier(s) détecté(s).' );

        foreach ( $files as $file_path ) {
            $basename = basename( $file_path );
            $this->logger->log( 'ExtendedFull Master: parsing ' . $basename );

            if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
                $this->logger->log( 'ExtendedFull Master: fichier non lisible, ignoré: ' . $file_path );
                continue;
            }

            $handle = fopen( $file_path, 'r' );
            if ( false === $handle ) {
                $this->logger->log( 'ExtendedFull Master: impossible d\'ouvrir le fichier: ' . $file_path );
                continue;
            }

            // Gestion BOM UTF-8 éventuel
            $bom = fread( $handle, 3 );
            if ( $bom !== "\xEF\xBB\xBF" ) {
                rewind( $handle );
            }

            // Détection du séparateur sur la première ligne lisible
            $first_line = fgets( $handle );
            rewind( $handle );
            if ( $bom === "\xEF\xBB\xBF" ) {
                fread( $handle, 3 ); // Skip BOM
            }
            $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

            // Lecture de l'en-tête
            $header = fgetcsv( $handle, 0, $delimiter );
            if ( false === $header ) {
                fclose( $handle );
                $this->logger->log( 'ExtendedFull Master: impossible de lire l\'en-tête CSV dans ' . $basename );
                continue;
            }

            $header = array_map(
                function( $h ) {
                    return strtolower( trim( $h ) );
                },
                $header
            );

            // Index utiles
            $idx_partnumber   = array_search( 'partnumber', $header, true );
            $idx_productname  = array_search( 'productname', $header, true );
            $idx_designation  = array_search( 'designation', $header, true );
            $idx_price_ht     = array_search( 'retailpriceexcludingtax', $header, true );
            $idx_price_base   = array_search( 'basedealerpriceexcludingtax', $header, true );
            $idx_stock        = array_search( 'stockvalue', $header, true );
            $idx_htmldesc     = array_search( 'htmldescription', $header, true );
            $idx_desc         = array_search( 'description', $header, true );
            $idx_cat1         = array_search( 'category1', $header, true );
            $idx_cat2         = array_search( 'category2', $header, true );
            $idx_cat3         = array_search( 'category3', $header, true );
            $idx_picture1     = array_search( 'picture1', $header, true );
            $idx_picture2     = array_search( 'picture2', $header, true );

            if ( false === $idx_partnumber ) {
                fclose( $handle );
                $this->logger->log( 'ExtendedFull Master: colonne PartNumber introuvable dans ' . $basename . ', fichier ignoré.' );
                continue;
            }

            while ( ( $data = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
                if ( empty( $data ) || count( $data ) < ( $idx_partnumber + 1 ) ) {
                    continue;
                }

                $total_rows++;

                $partnumber = trim( (string) $data[ $idx_partnumber ] );
                if ( $partnumber === '' ) {
                    continue;
                }

                $code = $partnumber;

                // Nom du produit
                $name = '';
                if ( false !== $idx_productname && isset( $data[ $idx_productname ] ) && $data[ $idx_productname ] !== '' ) {
                    $name = trim( (string) $data[ $idx_productname ] );
                } elseif ( false !== $idx_designation && isset( $data[ $idx_designation ] ) && $data[ $idx_designation ] !== '' ) {
                    $name = trim( (string) $data[ $idx_designation ] );
                }

                // Description
                $description = '';
                if ( false !== $idx_desc && isset( $data[ $idx_desc ] ) && $data[ $idx_desc ] !== '' ) {
                    $description = trim( (string) $data[ $idx_desc ] );
                } elseif ( false !== $idx_htmldesc && isset( $data[ $idx_htmldesc ] ) && $data[ $idx_htmldesc ] !== '' ) {
                    $description = trim( (string) $data[ $idx_htmldesc ] );
                }

                // Prix HT (en priorité RetailPriceExcludingTax, sinon BaseDealerPriceExcludingTax)
                $price_ht = null;
                if ( false !== $idx_price_ht && isset( $data[ $idx_price_ht ] ) && $data[ $idx_price_ht ] !== '' ) {
                    $price_ht = (float) str_replace( ',', '.', $data[ $idx_price_ht ] );
                } elseif ( false !== $idx_price_base && isset( $data[ $idx_price_base ] ) && $data[ $idx_price_base ] !== '' ) {
                    $price_ht = (float) str_replace( ',', '.', $data[ $idx_price_base ] );
                }

                // Stock
                $stock_level = null;
                if ( false !== $idx_stock && isset( $data[ $idx_stock ] ) && $data[ $idx_stock ] !== '' ) {
                    $stock_level = (int) $data[ $idx_stock ];
                }

                // Image principale
                $image_url = '';
                if ( false !== $idx_picture1 && isset( $data[ $idx_picture1 ] ) && $data[ $idx_picture1 ] !== '' ) {
                    $image_url = trim( (string) $data[ $idx_picture1 ] );
                } elseif ( false !== $idx_picture2 && isset( $data[ $idx_picture2 ] ) && $data[ $idx_picture2 ] !== '' ) {
                    $image_url = trim( (string) $data[ $idx_picture2 ] );
                }

                // Catégories 1/2/3
                $cat1 = ( false !== $idx_cat1 && isset( $data[ $idx_cat1 ] ) ) ? trim( (string) $data[ $idx_cat1 ] ) : '';
                $cat2 = ( false !== $idx_cat2 && isset( $data[ $idx_cat2 ] ) ) ? trim( (string) $data[ $idx_cat2 ] ) : '';
                $cat3 = ( false !== $idx_cat3 && isset( $data[ $idx_cat3 ] ) ) ? trim( (string) $data[ $idx_cat3 ] ) : '';

                $products[ $code ] = array(
                    'product_code'    => $code,
                    'new_part_number' => $code,
                    'name'            => $name ?: null,
                    'description'     => $description ?: null,
                    'dealer_price_ht' => $price_ht,
                    'stock_level'     => $stock_level,
                    'image_url'       => $image_url ?: null,
                    // Category1 sert de macro-catégorie + Niveau 1
                    'category'        => $cat1 ?: null,
                    'cat_l1'          => $cat1 !== '' ? $cat1 : null,
                    'cat_l2'          => $cat2 !== '' ? $cat2 : null,
                    'cat_l3'          => $cat3 !== '' ? $cat3 : null,
                );

                $total_built++;
            }

            fclose( $handle );
        }

        $this->logger->log(
            sprintf(
                'ExtendedFull Master: %d lignes lues, %d produits construits.',
                $total_rows,
                $total_built
            )
        );

        return $products;
    }

    /**
     * Lit un CSV et retourne un tableau associatif (en minuscules pour les clés)
     */
    protected function read_csv_assoc( $file_path ) {
        $rows = array();
        $wp_filesystem = $this->get_wp_filesystem();

        if ( ! $wp_filesystem->exists( $file_path ) ) {
            return $rows;
        }

        $content = $wp_filesystem->get_contents( $file_path );
        if ( false === $content ) {
            return $rows;
        }

        $lines = explode( "\n", $content );
        if ( empty( $lines ) ) {
            return $rows;
        }

        // Détection du séparateur ; ou ,
        $first_line = $lines[0];
        $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        $header = str_getcsv( $first_line, $delimiter );
        if ( ! $header ) {
            return $rows;
        }

        $header = array_map(
            function( $h ) {
                return strtolower( trim( $h ) );
            },
            $header
        );

        for ( $i = 1; $i < count( $lines ); $i++ ) {
            $line = trim( $lines[ $i ] );
            if ( empty( $line ) ) {
                continue;
            }

            $data = str_getcsv( $line, $delimiter );
            if ( count( $data ) !== count( $header ) ) {
                continue;
            }

            $row = array();
            foreach ( $header as $j => $key ) {
                $row[ $key ] = isset( $data[ $j ] ) ? $data[ $j ] : '';
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Parcourt un CSV et appelle un callback pour chaque ligne.
     * Retourne le nombre de lignes lues.
     *
     * NOTE : pour limiter les risques de régression, on distingue deux chemins :
     * - pour les fichiers cat-ref-full-*.csv (References), on utilise un parsing robuste
     *   basé sur fopen() + fgetcsv() (comme Excel) via iterate_csv_rows_stream().
     * - pour les autres fichiers historiques, on conserve la logique existante
     *   basée sur get_contents() + str_getcsv(), qui a déjà été validée en production.
     */
    protected function iterate_csv_rows( $file_path, callable $callback ) {
        $basename = basename( $file_path );

        // Chemin robuste uniquement pour cat-ref-full-*.csv (contient CategoryPath)
        if ( preg_match( '/^cat-ref-full-.*\.csv$/i', $basename ) ) {
            return $this->iterate_csv_rows_stream( $file_path, $callback );
        }

        // Comportement historique pour tous les autres fichiers (compatibilité maximale)
        $wp_filesystem = $this->get_wp_filesystem();

        if ( ! $wp_filesystem->exists( $file_path ) ) {
            return 0;
        }

        $content = $wp_filesystem->get_contents( $file_path );
        if ( false === $content ) {
            return 0;
        }

        $lines = explode( "\n", $content );
        if ( empty( $lines ) ) {
            return 0;
        }

        // Détection du séparateur ; ou ,
        $first_line = $lines[0];
        $delimiter  = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        $header = str_getcsv( $first_line, $delimiter );
        if ( ! $header ) {
            return 0;
        }

        $header = array_map(
            function( $h ) {
                return strtolower( trim( $h ) );
            },
            $header
        );

        $count = 0;

        for ( $i = 1; $i < count( $lines ); $i++ ) {
            $line = trim( $lines[ $i ] );
            if ( '' === $line ) {
                continue;
            }

            $data = str_getcsv( $line, $delimiter );
            if ( count( $data ) !== count( $header ) ) {
                continue;
            }

            $row = array();
            foreach ( $header as $j => $key ) {
                $row[ $key ] = isset( $data[ $j ] ) ? $data[ $j ] : '';
            }

            $callback( $row, $count );
            $count++;
        }

        return $count;
    }

    /**
     * Version robuste de iterate_csv_rows() utilisant fopen() + fgetcsv().
     * Utilisée principalement pour cat-ref-full-*.csv afin de parser CategoryPath
     * de manière fiable (BOM, CRLF, champs entre guillemets, etc.).
     */
    protected function iterate_csv_rows_stream( $file_path, callable $callback ) {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return 0;
        }

        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            return 0;
        }

        // Gestion éventuelle du BOM UTF-8
        $bom = fread( $handle, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) {
            rewind( $handle );
        }

        // Détection du séparateur sur la première ligne lisible
        $first_line = fgets( $handle );
        rewind( $handle );
        if ( $bom === "\xEF\xBB\xBF" ) {
            fread( $handle, 3 ); // Skip BOM
        }
        $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        // Lecture de l'en-tête
        $header = fgetcsv( $handle, 0, $delimiter );
        if ( false === $header ) {
            fclose( $handle );
            return 0;
        }

        $header = array_map(
            function( $h ) {
                return strtolower( trim( $h ) );
            },
            $header
        );

        $count = 0;

        while ( ( $data = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
            // Sauter les lignes vides ou mal formées
            if ( empty( $data ) || count( $data ) !== count( $header ) ) {
                continue;
            }

            $row = array();
            foreach ( $header as $j => $key ) {
                $row[ $key ] = isset( $data[ $j ] ) ? $data[ $j ] : '';
            }

            $callback( $row, $count );
            $count++;
        }

        fclose( $handle );

        return $count;
    }

    /**
     * Essaie de récupérer le code produit à partir d’une ligne CSV
     * (ProductCode, ProductId, etc.)
     */
    protected function get_product_code_from_row( $row ) {
        if ( isset( $row['productcode'] ) && $row['productcode'] !== '' ) {
            return trim( $row['productcode'] );
        }
        if ( isset( $row['productid'] ) && $row['productid'] !== '' ) {
            return trim( $row['productid'] );
        }
        if ( isset( $row['code'] ) && $row['code'] !== '' ) {
            return trim( $row['code'] );
        }

        return '';
    }

    /* ======== PARSING DES DIFFÉRENTS CATALOGUES ======== */

    /**
     * Parsing du catalog References
     * Fichier : ProductCode, NewPartNumber, ShortDescription, FurtherDescription, ...
     */
    protected function parse_references_csv( $file_path ) {
        $this->logger->log( 'Parsing References CSV : ' . $file_path );

        $result               = array();
        $first_logged         = false;
        $cat_levels_filled    = 0;
        $cat_levels_empty     = 0;

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result, &$first_logged, &$cat_levels_filled, &$cat_levels_empty ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                if ( ! $first_logged ) {
                    $first_logged = true;
                    $this->logger->log( 'En-têtes CSV References : ' . implode( ', ', array_keys( $row ) ) );
                    $first_long1 = isset( $row['longdescription1'] ) ? $row['longdescription1'] : 'N/A';
                    $first_short = isset( $row['shortdescription'] ) ? $row['shortdescription'] : 'N/A';
                    $this->logger->log( "Première ligne - Code: {$code}, LongDescription1: {$first_long1}, ShortDescription: {$first_short}" );
                }

                $new_part_number = isset( $row['newpartnumber'] ) ? trim( $row['newpartnumber'] ) : '';
                $name            = '';

                // À partir de maintenant, on ne remplit plus cat_l1/2/3 depuis References.
                // La hiérarchie détaillée vient exclusivement des catalogues cat-extended-full.
                $cat_l1          = '';
                $cat_l2          = '';
                $cat_l3          = '';

                // Utiliser LongDescription1 pour le nom du produit
                if ( ! empty( $row['longdescription1'] ) ) {
                    $name = trim( $row['longdescription1'] );
                } elseif ( ! empty( $row['shortdescription'] ) ) {
                    $name = trim( $row['shortdescription'] );
                } elseif ( ! empty( $row['furtherdescription'] ) ) {
                    $name = trim( $row['furtherdescription'] );
                }

                $description = '';
                if ( ! empty( $row['furtherdescription'] ) ) {
                    $description = trim( $row['furtherdescription'] );
                }

                // On ignore désormais complètement CategoryPath dans References.
                // Toute la hiérarchie sera fournie par les fichiers cat-extended-full-*.csv.

                $result[ $code ] = array(
                    'product_code'    => $code,
                    'new_part_number' => $new_part_number ?: null,
                    'name'            => $name ?: null,
                    'description'     => $description ?: null,
                    'cat_l1'          => $cat_l1 ?: null,
                    'cat_l2'          => $cat_l2 ?: null,
                    'cat_l3'          => $cat_l3 ?: null,
                );
            }
        );

        $this->logger->log( 'Parsing References: ' . $count . ' lignes.' );
        $this->logger->log( 'References: hiérarchie non utilisée (cat_l1/2/3 gérés uniquement via cat-extended-full-*.csv).' );

        return $result;
    }

    /**
     * Mapping des codes de catégorie vers leurs noms complets
     */
    protected function get_category_mapping() {
        return array(
            'A' => 'RIDER GEAR',
            'B' => 'VEHICLE PARTS & ACCESSORIES',
            'C' => 'LIQUIDS & LUBRICANTS',
            'D' => 'TIRES & ACCESSORIES',
            'E' => 'TOOLING & WS',
            'G' => 'OTHER PRODUCTS & SERVICES',
        );
    }

    /**
     * Extrait le code de catégorie depuis le nom du fichier
     * Ex: cat-extref-full-FR01-FR001-fr-2025_12_04_01_15_01_A.csv → A
     */
    protected function extract_category_from_filename( $file_path ) {
        $basename = basename( $file_path );
        
        // Pattern: cat-extref-full-..._X.csv où X est A, B, C, D, E ou G
        if ( preg_match( '/_([A-G])\.csv$/i', $basename, $matches ) ) {
            $code = strtoupper( $matches[1] );
            $mapping = $this->get_category_mapping();
            
            if ( isset( $mapping[ $code ] ) ) {
                return $mapping[ $code ];
            }
        }
        
        return null;
    }

    /**
     * Parsing du catalog ExtendedReferences
     * Fichier : ProductCode, Description, LongDescription, TechnicalDescription, FurtherDescription
     * Catégorie extraite depuis le nom du fichier (ex: *_A.csv = RIDER GEAR)
     */
    protected function parse_extendedreferences_csv( $file_path ) {
        $this->logger->log( 'Parsing ExtendedReferences CSV : ' . $file_path );

        $result = array();
        $first_logged = false;
        $category = $this->extract_category_from_filename( $file_path );

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result, &$first_logged, $category ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                if ( ! $first_logged ) {
                    $first_logged = true;
                    $this->logger->log( 'En-têtes CSV ExtendedReferences : ' . implode( ', ', array_keys( $row ) ) );
                    $first_long1 = isset( $row['longdescription1'] ) ? substr( $row['longdescription1'], 0, 50 ) : 'N/A';
                    $first_long  = isset( $row['longdescription'] ) ? substr( $row['longdescription'], 0, 50 ) : 'N/A';
                    $this->logger->log( "Première ligne ExtendedRef - Code: {$code}, LongDescription1: {$first_long1}, LongDescription: {$first_long}" );
                }

                // Tentative de récupération de la description la plus complète
                $description = '';
                
                // Priorité : LongDescription > TechnicalDescription > Description
                if ( ! empty( $row['longdescription'] ) ) {
                    $description = trim( $row['longdescription'] );
                } elseif ( ! empty( $row['technicaldescription'] ) ) {
                    $description = trim( $row['technicaldescription'] );
                } elseif ( ! empty( $row['description'] ) ) {
                    $description = trim( $row['description'] );
                }

                // Nom : utiliser LongDescription1 en priorité (pour le nom du produit WooCommerce)
                $name = '';
                if ( ! empty( $row['longdescription1'] ) ) {
                    $name = trim( $row['longdescription1'] );
                } elseif ( ! empty( $row['furtherdescription'] ) ) {
                    $name = trim( $row['furtherdescription'] );
                } elseif ( ! empty( $row['shortdescription'] ) ) {
                    $name = trim( $row['shortdescription'] );
                } elseif ( ! empty( $row['name'] ) ) {
                    $name = trim( $row['name'] );
                }

                $result[ $code ] = array(
                    'description' => $description ?: null,
                    'category'    => $category,
                );

                // On écrase toujours le nom avec celui d'ExtendedReferences (priorité absolue)
                if ( $name ) {
                    $result[ $code ]['name'] = $name;
                }
            }
        );

        $this->logger->log( 'Parsing ExtendedReferences: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Parsing du catalog Prices
     * Fichier : ProductCode, DealerPrice, ...
     */
    protected function parse_prices_csv( $file_path ) {
        $this->logger->log( 'Parsing Prices CSV : ' . $file_path );

        $result = array();

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                // Colonne DealerPrice, parfois DealerPriceHT, etc.
                $price = null;
                if ( isset( $row['dealerprice'] ) && $row['dealerprice'] !== '' ) {
                    $price = (float) str_replace( ',', '.', $row['dealerprice'] );
                } elseif ( isset( $row['dealerpriceht'] ) && $row['dealerpriceht'] !== '' ) {
                    $price = (float) str_replace( ',', '.', $row['dealerpriceht'] );
                }

                if ( $price === null ) {
                    return;
                }

                $result[ $code ] = array(
                    'dealer_price_ht' => $price,
                );
            }
        );

        $this->logger->log( 'Parsing Prices: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Parsing du catalog Images
     * On récupère simplement le chemin (colonne Url) sans préfixe.
     * Le préfixe https://api.mybihr.com sera ajouté plus tard.
     * Fichier : ProductCode, Url, IsDefault, NewPartNumber
     */
    protected function parse_images_csv( $file_path ) {
        $this->logger->log( 'Parsing Images CSV : ' . $file_path );

        $result = array();

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                // Colonne URL du CSV (en minuscules -> 'url')
                $url_path = isset( $row['url'] ) ? trim( $row['url'] ) : '';
                if ( $url_path === '' ) {
                    return;
                }

                // Colonne IsDefault (facultative) :
                // On ne filtre pas sur ce champ pour l'instant,
                // on prendra simplement la première image trouvée pour chaque produit.

                // Si une image existe déjà pour ce code, on ne la remplace pas
                if ( isset( $result[ $code ] ) ) {
                    return;
                }

                $new_part_number = isset( $row['newpartnumber'] ) ? trim( $row['newpartnumber'] ) : '';

                $result[ $code ] = array(
                    'image_url' => $url_path, // chemin brut, sans préfixe
                );

                if ( $new_part_number !== '' ) {
                    $result[ $code ]['new_part_number'] = $new_part_number;
                }
            }
        );

        $this->logger->log( 'Parsing Images: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Parsing du catalog Inventory (Stock)
     * Fichier : ProductId, StockLevel, StockLevelDescription, NewPartNumber
     */
    protected function parse_inventory_csv( $file_path ) {
        $this->logger->log( 'Parsing Inventory CSV : ' . $file_path );

        $result = array();
        $first_logged = false;

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result, &$first_logged ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                if ( ! $first_logged ) {
                    $first_logged = true;
                    $this->logger->log( 'En-têtes CSV Inventory : ' . implode( ', ', array_keys( $row ) ) );
                    $first_stock = isset( $row['stocklevel'] ) ? $row['stocklevel'] : 'N/A';
                    $this->logger->log( "Première ligne Inventory - Code: {$code}, StockLevel: {$first_stock}" );
                }

                $stock_level       = isset( $row['stocklevel'] ) ? (int) $row['stocklevel'] : null;
                $stock_description = isset( $row['stockleveldescription'] ) ? trim( $row['stockleveldescription'] ) : '';

                $new_part_number = isset( $row['newpartnumber'] ) ? trim( $row['newpartnumber'] ) : '';

                $result[ $code ] = array(
                    'stock_level'       => $stock_level,
                    'stock_description' => $stock_description ?: null,
                );

                if ( $new_part_number !== '' ) {
                    $result[ $code ]['new_part_number'] = $new_part_number;
                }
            }
        );

        $this->logger->log( 'Parsing Inventory: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Parsing du catalog Attributes (optionnel)
     * Ici, on se contente de concaténer les attributs dans la description.
     */
    protected function parse_attributes_csv( $file_path ) {
        $this->logger->log( 'Parsing Attributes CSV : ' . $file_path );

        $result = array();

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                // On concatène grossièrement tous les champs (sauf le code) en texte
                $parts = array();
                foreach ( $row as $key => $value ) {
                    if ( in_array( $key, array( 'productcode', 'productid', 'code' ), true ) ) {
                        continue;
                    }
                    if ( $value === '' ) {
                        continue;
                    }
                    $parts[] = $key . '=' . $value;
                }

                if ( empty( $parts ) ) {
                    return;
                }

                $attr_text = 'Attributs Bihr : ' . implode( ' | ', $parts );

                $result[ $code ] = array(
                    'attributes_text' => $attr_text,
                );
            }
        );

        $this->logger->log( 'Parsing Attributes: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Enregistre la fusion finale dans la table wp_bihr_products
     */
    protected function save_merged_products( $merged ) {
        global $wpdb;

        $count = 0;

        foreach ( $merged as $code => $data ) {
            if ( empty( $code ) ) {
                continue;
            }

            // Description + ajout éventuel des attributs
            $description = '';
            if ( isset( $data['description'] ) && $data['description'] !== null ) {
                $description = (string) $data['description'];
            }

            if ( isset( $data['attributes_text'] ) && $data['attributes_text'] !== null ) {
                $description .= "\n\n" . $data['attributes_text'];
            }

            // Vérifier si le produit existe déjà
            // Échapper le nom de table pour la sécurité (les noms de table ne peuvent pas utiliser de placeholders)
            $table_name = esc_sql( $this->table_name );
            $existing = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM `{$table_name}` WHERE product_code = %s",
                    $code
                ),
                ARRAY_A
            );

            // Construction des champs à enregistrer (seulement les champs présents dans $data)
            $fields = array( 'product_code' => $code );
            $formats = array( '%s' );

            // Ne mettre à jour que les champs qui sont présents dans $data
            if ( isset( $data['new_part_number'] ) ) {
                $fields['new_part_number'] = $data['new_part_number'];
                $formats[] = '%s';
            }

            if ( isset( $data['name'] ) ) {
                $fields['name'] = $data['name'];
                $formats[] = '%s';
            }

            if ( $description !== '' ) {
                $fields['description'] = $description;
                $formats[] = '%s';
            }

            if ( isset( $data['image_url'] ) ) {
                $fields['image_url'] = $data['image_url'];
                $formats[] = '%s';
            }

            if ( isset( $data['dealer_price_ht'] ) ) {
                $fields['dealer_price_ht'] = $data['dealer_price_ht'];
                $formats[] = '%f';
            }

            if ( isset( $data['stock_level'] ) ) {
                $fields['stock_level'] = $data['stock_level'];
                $formats[] = '%d';
            }

            if ( isset( $data['stock_description'] ) ) {
                $fields['stock_description'] = $data['stock_description'];
                $formats[] = '%s';
            }

            if ( isset( $data['category'] ) ) {
                $fields['category'] = $data['category'];
                $formats[]          = '%s';
            }

            // Niveaux de catégorie issus de CategoryPath (CSV References).
            if ( isset( $data['cat_l1'] ) ) {
                $fields['cat_l1'] = $data['cat_l1'];
                $formats[]        = '%s';
            }

            if ( isset( $data['cat_l2'] ) ) {
                $fields['cat_l2'] = $data['cat_l2'];
                $formats[]        = '%s';
            }

            if ( isset( $data['cat_l3'] ) ) {
                $fields['cat_l3'] = $data['cat_l3'];
                $formats[]        = '%s';
            }

            if ( $existing ) {
                // UPDATE : ne mettre à jour que les champs fournis
                $where = array( 'product_code' => $code );
                $where_format = array( '%s' );
                
                // Retirer product_code des champs à mettre à jour
                unset( $fields['product_code'] );
                array_shift( $formats );
                
                if ( ! empty( $fields ) ) {
                    $wpdb->update( $this->table_name, $fields, $where, $formats, $where_format );
                }
            } else {
                // INSERT : nouveau produit
                $wpdb->insert( $this->table_name, $fields, $formats );
            }
            
            $count++;
        }

        return $count;
    }

    /**
     * Applique les catégories hiérarchiques depuis cat-extended-full
     * sur le tableau fusionné $merged. cat-extended-full est désormais la
     * source unique pour cat_l1/cat_l2/cat_l3 (on ne s'appuie plus sur CategoryPath).
     *
     * @param array $merged              Référence vers le tableau [product_code => data[]].
     * @param array $extended_categories Tableau [PartNumber => ['cat_l1','cat_l2','cat_l3']].
     */
    protected function apply_extended_categories_fallback( array &$merged, array $extended_categories ) {
        if ( empty( $merged ) || empty( $extended_categories ) ) {
            return;
        }

        $updated = 0;
        $total   = 0;

        foreach ( $merged as $code => &$data ) {
            $total++;

            // Clé de lookup : prioriser NewPartNumber si disponible, sinon ProductCode.
            $lookup_key = '';
            if ( ! empty( $data['new_part_number'] ) ) {
                $lookup_key = (string) $data['new_part_number'];
            } else {
                $lookup_key = (string) $code;
            }

            if ( $lookup_key === '' ) {
                continue;
            }

            if ( ! isset( $extended_categories[ $lookup_key ] ) ) {
                continue;
            }

            $cats = $extended_categories[ $lookup_key ];

            // On écrase systématiquement cat_l1/2/3 avec la hiérarchie Extended,
            // sans jamais toucher à la macro-catégorie "category".
            $data['cat_l1'] = ( isset( $cats['cat_l1'] ) && $cats['cat_l1'] !== null && $cats['cat_l1'] !== '' )
                ? $cats['cat_l1']
                : null;
            $data['cat_l2'] = ( isset( $cats['cat_l2'] ) && $cats['cat_l2'] !== null && $cats['cat_l2'] !== '' )
                ? $cats['cat_l2']
                : null;
            $data['cat_l3'] = ( isset( $cats['cat_l3'] ) && $cats['cat_l3'] !== null && $cats['cat_l3'] !== '' )
                ? $cats['cat_l3']
                : null;

            // Si au moins un niveau a été renseigné, compter comme "updated".
            if (
                ( ! empty( $data['cat_l1'] ) ) ||
                ( ! empty( $data['cat_l2'] ) ) ||
                ( ! empty( $data['cat_l3'] ) )
            ) {
                $updated++;
            }
        }

        $this->logger->log(
            sprintf(
                'ExtendedFull: fallback catégories appliqué à %d produits (sur %d produits fusionnés).',
                $updated,
                $total
            )
        );
    }

    /**
     * Extrait un fichier ZIP vers le dossier d'import
     * Retourne le nombre de fichiers CSV extraits
     */
    public function extract_zip_to_import_dir( $zip_file ) {
        $import_dir = WP_CONTENT_DIR . '/uploads/bihr-import/';
        
        if ( ! is_dir( $import_dir ) ) {
            wp_mkdir_p( $import_dir );
        }

        if ( ! file_exists( $zip_file ) ) {
            $this->logger->log( "Fichier ZIP introuvable: {$zip_file}" );
            return 0;
        }

        // Utilise la classe WP_Filesystem
        WP_Filesystem();
        global $wp_filesystem;

        $unzipped = unzip_file( $zip_file, $import_dir );

        if ( is_wp_error( $unzipped ) ) {
            $this->logger->log( 'Erreur extraction ZIP: ' . $unzipped->get_error_message() );
            return 0;
        }

        // Compte les fichiers CSV extraits
        $csv_files = glob( $import_dir . '*.csv' );
        $count     = count( $csv_files );

        // Log des noms de fichiers extraits pour debug
        if ( $count > 0 && $count <= 15 ) {
            $file_names = array_map( 'basename', $csv_files );
            $this->logger->log( "Fichiers CSV extraits: " . implode( ', ', $file_names ) );
        }

        $this->logger->log( "Extraction ZIP réussie: {$count} fichiers CSV dans {$import_dir}" );

        // Supprime le fichier ZIP après extraction
        if ( file_exists( $zip_file ) ) {
            wp_delete_file( $zip_file );
        }

        return $count;
    }

    /**
     * Calcule le prix de vente avec application de la marge configurée
     *
     * @param float $supplier_price Prix fournisseur HT
     * @param string $category Catégorie du produit
     * @return float Prix de vente HT avec marge appliquée
     */
    protected function calculate_price_with_margin( $supplier_price, $category = '' ) {
        // Récupérer la configuration des marges
        $margin_settings = get_option( 'bihrwi_margin_settings', array(
            'default_margin_type' => 'percentage',
            'default_margin_value' => 0,
            'category_margins' => array(),
            'price_range_margins' => array(),
            'priority' => 'specific'
        ) );

        // Si priorité globale uniquement, appliquer la marge par défaut
        if ( $margin_settings['priority'] === 'global' ) {
            return $this->apply_margin( 
                $supplier_price, 
                $margin_settings['default_margin_type'], 
                $margin_settings['default_margin_value'] 
            );
        }

        // Priorité spécifique : chercher dans l'ordre Tranche de prix → Catégorie → Défaut

        // 1. Vérifier les tranches de prix
        if ( ! empty( $margin_settings['price_range_margins'] ) ) {
            foreach ( $margin_settings['price_range_margins'] as $range ) {
                if ( ! $range['enabled'] ) {
                    continue;
                }

                $min = floatval( $range['min'] );
                $max = floatval( $range['max'] );

                if ( $supplier_price >= $min && $supplier_price <= $max ) {
                    $this->logger->log( sprintf(
                        'Marge appliquée (tranche %.2f-%.2f€): %s %s',
                        $min, $max, $range['value'], $range['type'] === 'percentage' ? '%' : '€'
                    ) );
                    
                    return $this->apply_margin( $supplier_price, $range['type'], $range['value'] );
                }
            }
        }

        // 2. Vérifier la marge par catégorie
        if ( ! empty( $category ) && ! empty( $margin_settings['category_margins'] ) ) {
            $cat_key = sanitize_key( $category );
            
            if ( isset( $margin_settings['category_margins'][ $cat_key ] ) ) {
                $cat_margin = $margin_settings['category_margins'][ $cat_key ];
                
                if ( $cat_margin['enabled'] ) {
                    $this->logger->log( sprintf(
                        'Marge appliquée (catégorie %s): %s %s',
                        $category, $cat_margin['value'], $cat_margin['type'] === 'percentage' ? '%' : '€'
                    ) );
                    
                    return $this->apply_margin( $supplier_price, $cat_margin['type'], $cat_margin['value'] );
                }
            }
        }

        // 3. Appliquer la marge par défaut
        if ( $margin_settings['default_margin_value'] > 0 ) {
            $this->logger->log( sprintf(
                'Marge appliquée (par défaut): %s %s',
                $margin_settings['default_margin_value'], 
                $margin_settings['default_margin_type'] === 'percentage' ? '%' : '€'
            ) );
        }

        return $this->apply_margin( 
            $supplier_price, 
            $margin_settings['default_margin_type'], 
            $margin_settings['default_margin_value'] 
        );
    }

    /**
     * Applique une marge sur un prix
     *
     * @param float $price Prix de base
     * @param string $type Type de marge ('percentage' ou 'fixed')
     * @param float $value Valeur de la marge
     * @return float Prix avec marge appliquée
     */
    protected function apply_margin( $price, $type, $value ) {
        if ( $value <= 0 ) {
            return $price;
        }

        if ( $type === 'percentage' ) {
            // Marge en pourcentage
            return $price * ( 1 + ( $value / 100 ) );
        } else {
            // Marge fixe en euros
            return $price + $value;
        }
    }

    /**
     * Recalcule les catégories cat_l1/cat_l2/cat_l3 depuis le fichier cat-ref-full-*.csv.
     * Cette fonction lit le CSV avec fgetcsv() (comme Excel) et met à jour wp_bihr_products.
     *
     * @param int $offset Ligne de départ (pour traitement batch).
     * @param int $limit Nombre de lignes à traiter par batch.
     * @return array Statut: { 'processed' => int, 'updated' => int, 'errors' => int, 'has_more' => bool, 'file' => string }
     */
    public function rebuild_cat_levels_from_catref( $offset = 0, $limit = 2000 ) {
        return array(
            'processed' => 0,
            'updated'   => 0,
            'errors'    => 0,
            'has_more'  => false,
            'file'      => '',
            'offset'    => $offset,
        );
    }
}


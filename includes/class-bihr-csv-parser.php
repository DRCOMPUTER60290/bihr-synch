<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Parseur CSV pour les catalogues BIHR.
 * Extrait de BihrWI_Product_Sync pour isoler toute la logique de lecture CSV
 * (BOM, délimiteur, streaming fgetcsv) des classes métier.
 */
class BihrWI_CSV_Parser {

    protected BihrWI_Logger $logger;

    public function __construct( BihrWI_Logger $logger ) {
        $this->logger = $logger;
    }

    /**
     * Ouvre un CSV en streaming et retourne handle + délimiteur + en-tête.
     * Gère le BOM UTF-8 et la détection automatique du séparateur (; ou ,).
     *
     * @param string $file_path Chemin absolu vers le fichier CSV.
     * @return array{handle:resource,delimiter:string,header:string[]}|null null si le fichier est illisible.
     */
    public function open_csv_stream( string $file_path ): ?array {
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            return null;
        }

        $handle = fopen( $file_path, 'r' );
        if ( false === $handle ) {
            return null;
        }

        // BOM UTF-8 éventuel
        $bom = fread( $handle, 3 );
        if ( $bom !== "\xEF\xBB\xBF" ) {
            rewind( $handle );
        }

        // Détection du séparateur sur la première ligne
        $first_line = fgets( $handle );
        rewind( $handle );
        if ( $bom === "\xEF\xBB\xBF" ) {
            fread( $handle, 3 ); // reskip BOM
        }
        $delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

        // En-tête
        $header = fgetcsv( $handle, 0, $delimiter );
        if ( false === $header ) {
            fclose( $handle );
            return null;
        }

        $header = array_map(
            function( $h ) {
                return strtolower( trim( $h ) );
            },
            $header
        );

        return array(
            'handle'    => $handle,
            'delimiter' => $delimiter,
            'header'    => $header,
        );
    }

    /**
     * Parcourt un CSV en streaming (fopen/fgetcsv) et appelle un callback par ligne.
     *
     * @param string   $file_path Chemin absolu.
     * @param callable $callback  function( array $row, int $index ).
     * @return int Nombre de lignes lues.
     */
    public function iterate_csv_rows( $file_path, callable $callback ) {
        return $this->iterate_csv_rows_stream( $file_path, $callback );
    }

    /**
     * Implémentation interne du streaming CSV.
     */
    public function iterate_csv_rows_stream( $file_path, callable $callback ) {
        $stream = $this->open_csv_stream( $file_path );
        if ( null === $stream ) {
            return 0;
        }

        [ 'handle' => $handle, 'delimiter' => $delimiter, 'header' => $header ] = $stream;

        $count = 0;

        while ( ( $data = fgetcsv( $handle, 0, $delimiter ) ) !== false ) {
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
     * Essaie de récupérer le code produit à partir d'une ligne CSV
     * (ProductCode, ProductId, etc.)
     */
    public function get_product_code_from_row( $row ) {
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

    /**
     * Mapping des codes de catégorie vers leurs noms complets
     */
    public function get_category_mapping() {
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
    public function extract_category_from_filename( $file_path ) {
        $basename = basename( $file_path );

        if ( preg_match( '/_([A-G])\.csv$/i', $basename, $matches ) ) {
            $code    = strtoupper( $matches[1] );
            $mapping = $this->get_category_mapping();

            if ( isset( $mapping[ $code ] ) ) {
                return $mapping[ $code ];
            }
        }

        return null;
    }

    /**
     * Parsing du catalog References
     * Retourne array [ product_code => [...] ]
     */
    public function parse_references_csv( $file_path ) {
        $this->logger->log( 'Parsing References CSV : ' . $file_path );

        $result       = array();
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
                    $this->logger->log( 'En-têtes CSV References : ' . implode( ', ', array_keys( $row ) ) );
                    $first_long1 = isset( $row['longdescription1'] ) ? $row['longdescription1'] : 'N/A';
                    $first_short = isset( $row['shortdescription'] ) ? $row['shortdescription'] : 'N/A';
                    $this->logger->log( "Première ligne - Code: {$code}, LongDescription1: {$first_long1}, ShortDescription: {$first_short}" );
                }

                $new_part_number = isset( $row['newpartnumber'] ) ? trim( $row['newpartnumber'] ) : '';
                $name            = '';

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

                $result[ $code ] = array(
                    'product_code'    => $code,
                    'new_part_number' => $new_part_number ?: null,
                    'name'            => $name ?: null,
                    'description'     => $description ?: null,
                    'cat_l1'          => null,
                    'cat_l2'          => null,
                    'cat_l3'          => null,
                );
            }
        );

        $this->logger->log( 'Parsing References: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Parsing du catalog ExtendedReferences
     * Catégorie extraite depuis le nom du fichier (ex: *_A.csv = RIDER GEAR)
     * Retourne array [ product_code => [...] ]
     */
    public function parse_extendedreferences_csv( $file_path ) {
        $this->logger->log( 'Parsing ExtendedReferences CSV : ' . $file_path );

        $result       = array();
        $first_logged = false;
        $category     = $this->extract_category_from_filename( $file_path );

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

                $description = '';
                if ( ! empty( $row['longdescription'] ) ) {
                    $description = trim( $row['longdescription'] );
                } elseif ( ! empty( $row['technicaldescription'] ) ) {
                    $description = trim( $row['technicaldescription'] );
                } elseif ( ! empty( $row['description'] ) ) {
                    $description = trim( $row['description'] );
                }

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
     * Retourne array [ product_code => [ 'dealer_price_ht' => float ] ]
     */
    public function parse_prices_csv( $file_path ) {
        $this->logger->log( 'Parsing Prices CSV : ' . $file_path );

        $result = array();

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                $price = null;
                if ( isset( $row['dealerprice'] ) && $row['dealerprice'] !== '' ) {
                    $price = (float) str_replace( ',', '.', $row['dealerprice'] );
                } elseif ( isset( $row['dealerpriceht'] ) && $row['dealerpriceht'] !== '' ) {
                    $price = (float) str_replace( ',', '.', $row['dealerpriceht'] );
                }

                if ( $price === null ) {
                    return;
                }

                $result[ $code ] = array( 'dealer_price_ht' => $price );
            }
        );

        $this->logger->log( 'Parsing Prices: ' . $count . ' lignes.' );

        return $result;
    }

    /**
     * Parsing du catalog Images
     * Retourne array [ product_code => [ 'image_url' => string, ... ] ]
     */
    public function parse_images_csv( $file_path ) {
        $this->logger->log( 'Parsing Images CSV : ' . $file_path );

        $result = array();

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

                $url_path = isset( $row['url'] ) ? trim( $row['url'] ) : '';
                if ( $url_path === '' || isset( $result[ $code ] ) ) {
                    return;
                }

                $new_part_number = isset( $row['newpartnumber'] ) ? trim( $row['newpartnumber'] ) : '';

                $result[ $code ] = array( 'image_url' => $url_path );

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
     * Retourne array [ product_code => [ 'stock_level' => int, ... ] ]
     */
    public function parse_inventory_csv( $file_path ) {
        $this->logger->log( 'Parsing Inventory CSV : ' . $file_path );

        $result       = array();
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
                $new_part_number   = isset( $row['newpartnumber'] ) ? trim( $row['newpartnumber'] ) : '';

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
     * Concatène les attributs dans un texte descriptif
     * Retourne array [ product_code => [ 'attributes_text' => string ] ]
     */
    public function parse_attributes_csv( $file_path ) {
        $this->logger->log( 'Parsing Attributes CSV : ' . $file_path );

        $result = array();

        $count = $this->iterate_csv_rows(
            $file_path,
            function( $row ) use ( &$result ) {
                $code = $this->get_product_code_from_row( $row );
                if ( $code === '' ) {
                    return;
                }

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

                $result[ $code ] = array(
                    'attributes_text' => 'Attributs Bihr : ' . implode( ' | ', $parts ),
                );
            }
        );

        $this->logger->log( 'Parsing Attributes: ' . $count . ' lignes.' );

        return $result;
    }
}

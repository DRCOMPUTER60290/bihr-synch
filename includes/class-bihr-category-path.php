<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Utilitaires pour exploiter le champ CategoryPath de Bihr
 * et le mapper vers la taxonomie WooCommerce product_cat.
 */
class BihrWI_Category_Path {

    /**
     * Valeurs de catégories à ignorer (placeholders).
     *
     * @var string[]
     */
    protected static $invalid_values = array(
        '?',
        '¯\_(ツ)_/¯',
    );

    /**
     * Parse un CategoryPath Bihr du type "Niveau1 -> Niveau2 -> Niveau3".
     *
     * @param string $category_path
     * @return array Tableau associatif: array( 'l1' => '', 'l2' => '', 'l3' => '' ).
     */
    public static function parse_category_path( $category_path ) {
        $result = array(
            'l1' => '',
            'l2' => '',
            'l3' => '',
        );

        if ( ! is_string( $category_path ) || '' === trim( $category_path ) ) {
            return $result;
        }

        $normalized = trim( (string) $category_path );

        // Placeholder complet → rien.
        if ( self::is_invalid_value( $normalized ) ) {
            return $result;
        }

        // Split sur "->".
        $parts = explode( '->', $normalized );

        $clean = array();

        foreach ( $parts as $part ) {
            $label = trim( $part );
            if ( '' === $label ) {
                continue;
            }

            if ( self::is_invalid_value( $label ) ) {
                continue;
            }

            $clean[] = $label;
            if ( count( $clean ) >= 3 ) {
                break;
            }
        }

        if ( isset( $clean[0] ) ) {
            $result['l1'] = $clean[0];
        }
        if ( isset( $clean[1] ) ) {
            $result['l2'] = $clean[1];
        }
        if ( isset( $clean[2] ) ) {
            $result['l3'] = $clean[2];
        }

        return $result;
    }

    /**
     * Crée (si besoin) la hiérarchie de catégories WooCommerce correspondant
     * aux niveaux fournis et retourne l'ID de la catégorie la plus précise.
     *
     * - l1 : catégorie racine
     * - l2 : enfant de l1
     * - l3 : enfant de l2
     *
     * Si tous les niveaux sont vides/invalides, la catégorie "Non classé" est utilisée.
     *
     * @param string $level1
     * @param string $level2
     * @param string $level3
     * @return int term_id de la catégorie finale, ou 0 si échec.
     */
    public static function ensure_product_categories( $level1, $level2, $level3 ) {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
            return 0;
        }

        // Si tous les niveaux sont vides, utiliser "Non classé".
        if ( '' === $level1 && '' === $level2 && '' === $level3 ) {
            return self::ensure_uncategorized_term();
        }

        $parent_id = 0;
        $final_id  = 0;

        // Niveau 1.
        if ( '' !== $level1 && ! self::is_invalid_value( $level1 ) ) {
            $parent_id = self::ensure_term( $level1, 0 );
            $final_id  = $parent_id;
        }

        // Niveau 2.
        if ( $parent_id && '' !== $level2 && ! self::is_invalid_value( $level2 ) ) {
            $term2    = self::ensure_term( $level2, $parent_id );
            $parent_id = $term2;
            $final_id  = $term2;
        }

        // Niveau 3.
        if ( $parent_id && '' !== $level3 && ! self::is_invalid_value( $level3 ) ) {
            $term3   = self::ensure_term( $level3, $parent_id );
            $final_id = $term3;
        }

        if ( ! $final_id ) {
            // Si tout a été filtré comme invalide, fallback sur "Non classé".
            $final_id = self::ensure_uncategorized_term();
        }

        return (int) $final_id;
    }

    /**
     * Vérifie si une valeur de catégorie est invalide / placeholder.
     *
     * @param string $value
     * @return bool
     */
    protected static function is_invalid_value( $value ) {
        $value = trim( (string) $value );

        if ( '' === $value ) {
            return true;
        }

        foreach ( self::$invalid_values as $invalid ) {
            if ( 0 === strcasecmp( $value, $invalid ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Crée ou récupère un term product_cat avec un parent donné.
     *
     * @param string $name
     * @param int    $parent_id
     * @return int term_id ou 0.
     */
    protected static function ensure_term( $name, $parent_id = 0 ) {
        $name      = trim( wp_strip_all_tags( (string) $name ) );
        $parent_id = absint( $parent_id );

        if ( '' === $name ) {
            return 0;
        }

        // Chercher un term avec ce nom et parent donné.
        $existing = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'hide_empty' => false,
                'name'       => $name,
                'parent'     => $parent_id,
                'number'     => 1,
                'fields'     => 'ids',
            )
        );

        if ( ! is_wp_error( $existing ) && ! empty( $existing ) ) {
            return (int) $existing[0];
        }

        $args = array();
        if ( $parent_id > 0 ) {
            $args['parent'] = $parent_id;
        }

        $term = wp_insert_term( $name, 'product_cat', $args );

        if ( is_wp_error( $term ) ) {
            if ( isset( $term->error_data['term_exists'] ) ) {
                return (int) $term->error_data['term_exists'];
            }

            return 0;
        }

        return (int) $term['term_id'];
    }

    /**
     * Crée ou récupère la catégorie "Non classé".
     *
     * @return int term_id
     */
    protected static function ensure_uncategorized_term() {
        $label = __( 'Non classé', 'bihr-synch' );

        $existing = get_term_by( 'name', $label, 'product_cat' );
        if ( $existing && ! is_wp_error( $existing ) ) {
            return (int) $existing->term_id;
        }

        $term = wp_insert_term( $label, 'product_cat' );

        if ( is_wp_error( $term ) ) {
            if ( isset( $term->error_data['term_exists'] ) ) {
                return (int) $term->error_data['term_exists'];
            }

            return 0;
        }

        return (int) $term['term_id'];
    }
}


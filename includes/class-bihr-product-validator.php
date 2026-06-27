<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Product_Validator {

    private const REQUIRED_FIELDS = array( 'product_code', 'name', 'dealer_price_ht' );

    /**
     * Valide une ligne produit importée depuis la DB.
     *
     * @param array $row Ligne associative de wp_bihr_products.
     * @return string[] Liste des erreurs (vide = valide).
     */
    public function validate( array $row ): array {
        $errors = array();

        foreach ( self::REQUIRED_FIELDS as $field ) {
            if ( empty( $row[ $field ] ) && '0' !== (string) ( $row[ $field ] ?? null ) ) {
                $errors[] = "Champ obligatoire manquant : {$field}";
            }
        }

        if ( isset( $row['dealer_price_ht'] ) && ! empty( $row['dealer_price_ht'] ) ) {
            if ( ! is_numeric( $row['dealer_price_ht'] ) || (float) $row['dealer_price_ht'] < 0 ) {
                $errors[] = 'Le prix (dealer_price_ht) doit être un nombre positif.';
            }
        }

        if ( isset( $row['product_code'] ) && ! empty( $row['product_code'] ) ) {
            if ( strlen( $row['product_code'] ) > 100 ) {
                $errors[] = 'Le product_code dépasse 100 caractères.';
            }
        }

        return $errors;
    }
}

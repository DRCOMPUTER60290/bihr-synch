<?php
/**
 * Bootstrap de compatibilité ascendante (class aliases)
 *
 * Crée des alias des anciennes classes BihrWI_* vers les nouvelles
 * classes namespacées Bihr\Synch\* pour assurer la compatibilité
 * avec le code existant et les extensions tierces.
 *
 * À charger dans le fallback autoloader du fichier principal.
 */

// Fonction utilitaire pour créer un alias et reporter les erreurs
function bihrwi_alias( string $old_name, string $new_name ): void {
    if ( ! class_exists( $old_name ) && class_exists( $new_name ) ) {
        class_alias( $new_name, $old_name );
    }
}

// Les classes src/ sont chargées par le PSR-4 (Composer) à la demande.
// On ne fait ici que créer les alias pour la rétrocompatibilité.

// Créer les alias pour la rétrocompatibilité
bihrwi_alias( 'BihrWI_Logger', 'Bihr\\Synch\\Logger' );
bihrwi_alias( 'BihrWI_API_Client', 'Bihr\\Synch\\Api\\Client' );
bihrwi_alias( 'BihrWI_Rate_Limiter', 'Bihr\\Synch\\Rate\\Limiter' );
bihrwi_alias( 'BihrWI_Product_Validator', 'Bihr\\Synch\\Product\\Validator' );
bihrwi_alias( 'BihrWI_Category_Path', 'Bihr\\Synch\\Category\\Path' );
bihrwi_alias( 'BihrWI_AI_Enrichment', 'Bihr\\Synch\\AI\\Enrichment' );
bihrwi_alias( 'BihrWI_Category_Translator', 'Bihr\\Synch\\Category\\Translator' );
bihrwi_alias( 'BihrWI_Product_Sync', 'Bihr\\Synch\\Product\\Sync' );
bihrwi_alias( 'BihrWI_Vehicle_Compatibility', 'Bihr\\Synch\\Vehicle\\Compatibility' );
bihrwi_alias( 'BihrWI_Order_Sync', 'Bihr\\Synch\\Order\\Sync' );
bihrwi_alias( 'BihrWI_Vehicle_Filter', 'Bihr\\Synch\\Vehicle\\Filter' );
bihrwi_alias( 'BihrWI_Product_Filter', 'Bihr\\Synch\\Product\\Filter' );
bihrwi_alias( 'BihrWI_Category_Filters', 'Bihr\\Synch\\Category\\Filters' );
bihrwi_alias( 'BihrWI_CLI_Commands', 'Bihr\\Synch\\CLI\\Commands' );
bihrwi_alias( 'BihrWI_Admin', 'Bihr\\Synch\\Admin' );
bihrwi_alias( 'BihrWI_Tools', 'Bihr\\Synch\\Admin\\Tools' );

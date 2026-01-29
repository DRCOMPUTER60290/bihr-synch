<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_AI_Enrichment {

    protected $logger;
    protected $api_key;

    public function __construct( $logger = null ) {
        $this->logger  = $logger;
        $this->api_key = get_option( 'bihrwi_openai_key', '' );
    }

    /**
     * Vérifie si l'enrichissement IA est disponible
     */
    public function is_enabled() {
        return ! empty( $this->api_key );
    }

    /**
     * Helper pour loguer en toute sécurité
     */
    protected function log( $message ) {
        if ( $this->logger && method_exists( $this->logger, 'log' ) ) {
            $this->logger->log( $message );
        }
    }

    /**
     * Génère un nom amélioré et les descriptions courte et longue via OpenAI GPT-4 Vision
     * @param string $product_name Nom du produit original
     * @param string $image_url URL de l'image du produit
     * @param string $product_code Code produit (pour référence)
     * @return array ['product_name' => string, 'short_description' => string, 'long_description' => string] ou false en cas d'erreur
     */
    public function generate_descriptions( $product_name, $image_url = '', $product_code = '' ) {
        if ( ! $this->is_enabled() ) {
            $this->log( 'AI Enrichment: désactivé (pas de clé OpenAI)' );
            return false;
        }

        $this->log( "AI Enrichment: génération pour {$product_code} - {$product_name}" );

        try {
            // Construction du prompt
            $prompt = $this->build_prompt( $product_name, $product_code );

            // Appel à l'API OpenAI
            $messages = array(
                array(
                    'role'    => 'system',
                    'content' => 'Tu es un expert en rédaction de fiches produits pour un site e-commerce spécialisé dans les pièces et accessoires moto. Tes descriptions sont claires, professionnelles et optimisées pour le SEO.',
                ),
                array(
                    'role'    => 'user',
                    'content' => $prompt,
                ),
            );

            // Essayer d'abord avec vision si image disponible
            $model = 'gpt-4o-mini'; // Par défaut sans vision
            if ( ! empty( $image_url ) && filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
                // Essayer avec vision en premier
                $messages[1]['content'] = array(
                    array(
                        'type' => 'text',
                        'text' => $prompt,
                    ),
                    array(
                        'type'      => 'image_url',
                        'image_url' => array(
                            'url' => $image_url,
                        ),
                    ),
                );
                $model = 'gpt-4o'; // Modèle avec vision
                $this->log( "IA - Tentative avec vision (gpt-4o) et image: {$image_url}" );
                
                $response = $this->call_openai_api( $messages, $model );
                
                // Si l'image cause une erreur, réessayer sans image
                if ( $response === false || $response === 'IMAGE_ERROR' ) {
                    $this->log( 'IA - Échec avec image, tentative sans image (gpt-4o-mini)' );
                    $messages[1]['content'] = $prompt;
                    $model = 'gpt-4o-mini';
                    $response = $this->call_openai_api( $messages, $model );
                }
            } else {
                $this->log( "IA - Pas d'image valide, utilisation de gpt-4o-mini" );
                $response = $this->call_openai_api( $messages, $model );
            }

            if ( $response === false ) {
                return false;
            }

            // Parse la réponse
            $parsed = $this->parse_response( $response );

            if ( $parsed ) {
                $this->log( "AI Enrichment: succès pour {$product_code}" );
                return $parsed;
            }

            return false;

        } catch ( Exception $e ) {
            $this->log( 'AI Enrichment erreur: ' . $e->getMessage() );
            return false;
        }
    }

    /**
     * Construit le prompt pour OpenAI
     */
    protected function build_prompt( $product_name, $product_code ) {
        $prompt = "Génère un nom amélioré et deux descriptions pour ce produit moto :\n\n";
        $prompt .= "**Produit original :** {$product_name}\n";
        
        if ( ! empty( $product_code ) ) {
            $prompt .= "**Référence :** {$product_code}\n";
        }

        $prompt .= "\n**Instructions :**\n";
        $prompt .= "1. **Nom amélioré** (max 80 caractères) : Nom de produit optimisé pour le SEO, clair et accrocheur. Garde les informations techniques importantes mais rends-le plus vendeur.\n";
        $prompt .= "2. **Description courte** (2-3 phrases, max 160 caractères) : Résumé accrocheur pour la liste de produits.\n";
        $prompt .= "3. **Description longue** (4-6 paragraphes) : Détails techniques, avantages, compatibilité, utilisation.\n\n";
        $prompt .= "**Format de réponse (strict) :**\n";
        $prompt .= "[NAME]\n";
        $prompt .= "[Nom amélioré du produit]\n";
        $prompt .= "[/NAME]\n\n";
        $prompt .= "[SHORT]\n";
        $prompt .= "[Contenu de la description courte]\n";
        $prompt .= "[/SHORT]\n\n";
        $prompt .= "[LONG]\n";
        $prompt .= "[Contenu de la description longue]\n";
        $prompt .= "[/LONG]\n";

        return $prompt;
    }

    /**
     * Appelle l'API OpenAI
     */
    protected function call_openai_api( $messages, $model = 'gpt-4o-mini' ) {
        $endpoint = 'https://api.openai.com/v1/chat/completions';

        $body = array(
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => 1500,
            'temperature' => 0.7,
        );

        $args = array(
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body'    => wp_json_encode( $body ),
            'timeout' => 60,
        );

        $response = wp_remote_post( $endpoint, $args );

        if ( is_wp_error( $response ) ) {
            $this->log( 'OpenAI API erreur: ' . $response->get_error_message() );
            return false;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body        = wp_remote_retrieve_body( $response );

        if ( $status_code !== 200 ) {
            // Déterminer si c'est une erreur d'image
            if ( strpos( $body, 'invalid_image_url' ) !== false || strpos( $body, 'Error while downloading' ) !== false ) {
                $this->log( "IA - Erreur d'image détectée (HTTP {$status_code})" );
                return 'IMAGE_ERROR'; // Signal spécial pour erreur d'image
            }
            $this->log( "OpenAI API erreur HTTP {$status_code}: {$body}" );
            return false;
        }

        $data = json_decode( $body, true );

        if ( ! isset( $data['choices'][0]['message']['content'] ) ) {
            $this->log( 'OpenAI API réponse invalide: ' . $body );
            return false;
        }

        return $data['choices'][0]['message']['content'];
    }

    /**
     * Parse la réponse d'OpenAI pour extraire le nom et les descriptions
     */
    protected function parse_response( $response ) {
        $product_name      = '';
        $short_description = '';
        $long_description  = '';

        $this->log( 'IA - Réponse brute à parser: ' . substr( $response, 0, 200 ) . '...' );

        // Extraction du nom amélioré
        if ( preg_match( '/\[NAME\](.*?)\[\/NAME\]/s', $response, $name_matches ) ) {
            $product_name = trim( $name_matches[1] );
            $this->log( 'IA - Nom amélioré trouvé via balises: ' . $product_name );
        }

        // Extraction de la description courte
        if ( preg_match( '/\[SHORT\](.*?)\[\/SHORT\]/s', $response, $short_matches ) ) {
            $short_description = trim( $short_matches[1] );
            $this->log( 'IA - Description courte trouvée via balises' );
        }

        // Extraction de la description longue
        if ( preg_match( '/\[LONG\](.*?)\[\/LONG\]/s', $response, $long_matches ) ) {
            $long_description = trim( $long_matches[1] );
            $this->log( 'IA - Description longue trouvée via balises' );
        }

        // Si les balises ne sont pas trouvées, on essaie de splitter par ligne vide
        if ( empty( $short_description ) || empty( $long_description ) ) {
            $this->log( 'IA - Tentative de parsing sans balises (fallback)' );
            $parts = preg_split( '/\n\s*\n/', trim( $response ), 3 );
            if ( count( $parts ) >= 2 ) {
                // Premier bloc = nom (si pas déjà extrait)
                if ( empty( $product_name ) && ! empty( $parts[0] ) ) {
                    $product_name = trim( $parts[0] );
                }
                // Deuxième bloc = description courte
                if ( empty( $short_description ) && isset( $parts[1] ) ) {
                    $short_description = trim( $parts[1] );
                }
                // Troisième bloc = description longue
                if ( empty( $long_description ) && isset( $parts[2] ) ) {
                    $long_description = trim( $parts[2] );
                } elseif ( empty( $long_description ) && isset( $parts[1] ) ) {
                    $long_description = trim( $parts[1] );
                }
                $this->log( 'IA - Contenu splité en ' . count( $parts ) . ' parties' );
            } else {
                // Fallback: tout dans la description longue
                $long_description = trim( $response );
                $short_description = wp_trim_words( $long_description, 25, '...' );
                $this->log( 'IA - Tout mis en description longue (pas de split possible)' );
            }
        }

        if ( empty( $short_description ) && empty( $long_description ) ) {
            $this->log( 'IA - ERREUR: Aucune description extraite!' );
            return false;
        }

        $this->log( 'IA - Parse réussi | Name: ' . ( $product_name ?: 'non généré' ) . ' | Short: ' . substr( $short_description, 0, 50 ) . '... | Long: ' . substr( $long_description, 0, 50 ) . '...' );

        $result = array(
            'short_description' => $short_description,
            'long_description'  => $long_description,
        );

        // Ajouter le nom amélioré si disponible
        if ( ! empty( $product_name ) ) {
            $result['product_name'] = $product_name;
        }

        return $result;
    }
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BihrWI_Rate_Limiter {

    private const OPENAI_HOURLY_LIMIT   = 200;
    private const OPENAI_TRANSIENT_KEY  = 'bihrwi_openai_call_count';
    private const OPENAI_WINDOW_SECONDS = HOUR_IN_SECONDS;

    /**
     * Vérifie si un appel OpenAI est autorisé (quota horaire non atteint).
     */
    public function can_call_openai(): bool {
        $count = (int) get_transient( self::OPENAI_TRANSIENT_KEY );
        return $count < self::OPENAI_HOURLY_LIMIT;
    }

    /**
     * Enregistre un appel OpenAI (incrémente le compteur horaire).
     */
    public function record_openai_call(): void {
        $count = (int) get_transient( self::OPENAI_TRANSIENT_KEY );
        if ( false === get_transient( self::OPENAI_TRANSIENT_KEY ) ) {
            set_transient( self::OPENAI_TRANSIENT_KEY, 1, self::OPENAI_WINDOW_SECONDS );
        } else {
            set_transient( self::OPENAI_TRANSIENT_KEY, $count + 1, self::OPENAI_WINDOW_SECONDS );
        }
    }

    /**
     * Retourne le nombre d'appels restants dans la fenêtre courante.
     */
    public function remaining_openai_calls(): int {
        $count = (int) get_transient( self::OPENAI_TRANSIENT_KEY );
        return max( 0, self::OPENAI_HOURLY_LIMIT - $count );
    }
}

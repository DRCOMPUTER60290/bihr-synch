<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>
<div class="wrap">
    <h1>Authentification Bihr</h1>

    <?php if ( isset( $_GET['bihrwi_auth_success'] ) ) : ?>
        <div class="notice notice-success"><p>✓ Authentification Bihr réussie. Le token a été récupéré.</p></div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_auth_error'] ) ) : ?>
        <div class="notice notice-error">
            <p><strong>✗ Échec de l'authentification Bihr.</strong></p>
            <?php if ( ! empty( $_GET['bihrwi_msg'] ) ) : ?>
                <p><strong>Détail :</strong> <?php echo esc_html( wp_unslash( $_GET['bihrwi_msg'] ) ); ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_openai_success'] ) ) : ?>
        <div class="notice notice-success">
            <p><strong>✓ Clé OpenAI valide et opérationnelle !</strong></p>
            <p>L'enrichissement automatique des descriptions par IA est activé.</p>
        </div>
    <?php endif; ?>

    <?php if ( isset( $_GET['bihrwi_openai_error'] ) ) : ?>
        <div class="notice notice-warning">
            <p><strong>⚠ Problème avec la clé OpenAI</strong></p>
            <?php if ( ! empty( $_GET['bihrwi_openai_msg'] ) ) : ?>
                <p><strong>Détail :</strong> <?php echo esc_html( wp_unslash( $_GET['bihrwi_openai_msg'] ) ); ?></p>
            <?php endif; ?>
            <p><em>L'import des produits fonctionnera sans enrichissement IA.</em></p>
        </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'bihrwi_authenticate_action', 'bihrwi_authenticate_nonce' ); ?>
        <input type="hidden" name="action" value="bihrwi_authenticate" />

        <table class="form-table">
            <tr>
                <th scope="row"><label for="bihrwi_username">Username Bihr</label></th>
                <td><input name="bihrwi_username" id="bihrwi_username" type="text" class="regular-text" value="<?php echo esc_attr( $username ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="bihrwi_password">Password Bihr</label></th>
                <td><input name="bihrwi_password" id="bihrwi_password" type="password" class="regular-text" value="<?php echo esc_attr( $password ); ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="bihrwi_openai_key">Clé API OpenAI (optionnel)</label></th>
                <td>
                    <input name="bihrwi_openai_key" id="bihrwi_openai_key" type="password" class="regular-text" value="<?php echo esc_attr( $openai_key ); ?>">
                    <button type="button" id="bihr-test-openai-key" class="button" style="margin-left: 10px;">
                        🧪 Tester la clé
                    </button>
                    <span id="bihr-openai-test-result" style="margin-left: 10px;"></span>
                    <p class="description">
                        Si renseignée, l'IA générera automatiquement un nom amélioré, une description courte et une description longue lors de l'import des produits.
                    </p>
                    <p class="description" style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                        <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener noreferrer" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px; color: #2271b1; font-weight: 500;">
                            <span class="dashicons dashicons-admin-links" style="font-size: 16px; width: 16px; height: 16px; color: #2271b1;"></span>
                            Configurer ma clé API OpenAI
                            <span class="dashicons dashicons-external" style="font-size: 14px; width: 14px; height: 14px; opacity: 0.7;"></span>
                        </a>
                        <br>
                        <small style="color: #666; font-style: italic; margin-top: 4px; display: inline-block;">Ouvre la page OpenAI pour créer ou gérer vos clés API</small>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Sauvegarder & Tester l\'authentification' ); ?>
    </form>

    <h2>Token actuel</h2>
    <?php if ( ! empty( $last_token ) ) : ?>
        <p><strong>Token :</strong></p>
        <textarea readonly style="width:100%;height:120px;"><?php echo esc_textarea( $last_token ); ?></textarea>
        <p><em>Le token est valable environ 30 minutes.</em></p>
    <?php else : ?>
        <p>Aucun token en cache. Cliquez sur le bouton ci-dessus pour tester l'authentification et récupérer un token.</p>
    <?php endif; ?>
</div>

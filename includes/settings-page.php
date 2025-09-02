<?php if (!defined('ABSPATH')) exit; ?>
<div class="wrap">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

  <form method="post" action="">
    <?php wp_nonce_field('ace_settings'); ?>
    <input type="hidden" name="ace_settings_submitted" value="1" />

    <table class="form-table" role="presentation">
      <tbody>
        <tr>
          <th scope="row"><label for="ace_openai_api_key">OpenAI API Key</label></th>
          <td>
            <input type="password" id="ace_openai_api_key" name="ace_openai_api_key"
              class="regular-text" value="<?php echo esc_attr($api_key); ?>" autocomplete="off" />
            <p class="description">推奨は <code>wp-config.php</code> の <code>OPENAI_API_KEY</code>（環境変数）です。設定値があればそちらが優先されます。</p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="ace_openai_model">OpenAI Model</label></th>
          <td>
            <select id="ace_openai_model" name="ace_openai_model">
              <?php
              $models = [
                'gpt-4o-mini' => 'gpt-4o-mini（高速・低コスト）',
                'gpt-4o'      => 'gpt-4o',
                'o4-mini'     => 'o4-mini（推論強化・新API移行時向け）',
                'gpt-3.5-turbo' => 'gpt-3.5-turbo（互換用）',
              ];
              foreach ($models as $val => $label) {
                printf('<option value="%s" %s>%s</option>', esc_attr($val), selected($model, $val, false), esc_html($label));
              }
              ?>
            </select>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="ace_system_prompt">System Prompt</label></th>
          <td>
            <textarea id="ace_system_prompt" name="ace_system_prompt" class="large-text code" rows="6"><?php echo esc_textarea($system_prompt); ?></textarea>
            <p class="description">AI の編集方針。文体/構成/禁止事項などを明示。</p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="ace_user_prompt_template">User Prompt テンプレ</label></th>
          <td>
            <textarea id="ace_user_prompt_template" name="ace_user_prompt_template" class="large-text code" rows="6"><?php echo esc_textarea($user_template); ?></textarea>
            <p class="description"><code>{content}</code> が原文に置換されます。</p>
          </td>
        </tr>

        <tr>
          <th scope="row"><label for="ace_max_backups">バックアップ保持数</label></th>
          <td>
            <input type="number" id="ace_max_backups" name="ace_max_backups" min="1" step="1" value="<?php echo esc_attr((int)$max_backups); ?>" />
            <p class="description">直近 N 件まで保存（古いものから削除）。</p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php submit_button(__('Save Changes')); ?>
  </form>
</div>
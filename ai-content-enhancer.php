<?php
/**
 * Plugin Name: AI Content Enhancer
 * Description: AI で本文を加筆し、バックアップ/復元もできる編集支援プラグイン（Classic/Gutenberg対応）
 * Version: 1.0.0
 * Author: Panolabo
 * Text Domain: ai-content-enhancer
 * Requires at least: 6.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('ACE_VERSION', '1.0.0');
define('ACE_PLUGIN_FILE', __FILE__);
define('ACE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ACE_PLUGIN_URL', plugin_dir_url(__FILE__));

class AI_Content_Enhancer {
    const OPTION_API_KEY         = 'ace_openai_api_key';
    const OPTION_MODEL           = 'ace_openai_model';
    const OPTION_SYSTEM_PROMPT   = 'ace_system_prompt';
    const OPTION_USER_TEMPLATE   = 'ace_user_prompt_template';
    const OPTION_MAX_BACKUPS     = 'ace_max_backups';
    const META_BACKUPS           = '_ace_backups'; // array: [ [ts=>int, content=>string], ... ]
    const NONCE_ACTION           = 'ace_nonce';
    const CAPABILITY             = 'edit_posts';

    public function __construct() {
        add_action('admin_menu',           [$this, 'add_settings_page']);
        add_action('admin_enqueue_scripts',[$this, 'enqueue_admin_assets']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_admin_assets']); // Gutenberg

        add_action('add_meta_boxes',       [$this, 'add_metabox']); // Classic/Gutenberg両方で表示される
        add_action('save_post',            [$this, 'maybe_auto_enhance_on_first_save'], 20, 2);

        // AJAX: 加筆/バックアップ一覧/復元
        add_action('wp_ajax_ace_enhance_content',  [$this, 'ajax_enhance_content']);
        add_action('wp_ajax_ace_list_backups',     [$this, 'ajax_list_backups']);
        add_action('wp_ajax_ace_restore_backup',   [$this, 'ajax_restore_backup']);
    }

    /* ========== 設定画面 ========== */
    public function add_settings_page() {
        add_options_page(
            __('AI Content Enhancer', 'ai-content-enhancer'),
            __('AI Content Enhancer', 'ai-content-enhancer'),
            'manage_options',
            'ai-content-enhancer',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        if (!current_user_can('manage_options')) wp_die(__('You do not have permission.', 'ai-content-enhancer'));

        if (isset($_POST['ace_settings_submitted'])) {
            check_admin_referer('ace_settings');

            update_option(self::OPTION_API_KEY,         sanitize_text_field($_POST[self::OPTION_API_KEY] ?? ''));
            update_option(self::OPTION_MODEL,           sanitize_text_field($_POST[self::OPTION_MODEL]   ?? 'gpt-4o-mini'));
            update_option(self::OPTION_SYSTEM_PROMPT,   wp_kses_post($_POST[self::OPTION_SYSTEM_PROMPT]  ?? $this->default_system_prompt()));
            update_option(self::OPTION_USER_TEMPLATE,   wp_kses_post($_POST[self::OPTION_USER_TEMPLATE]  ?? $this->default_user_template()));
            update_option(self::OPTION_MAX_BACKUPS,     max(1, intval($_POST[self::OPTION_MAX_BACKUPS]   ?? 5)));

            echo '<div class="notice notice-success is-dismissible"><p>'.esc_html__('Settings saved.', 'ai-content-enhancer').'</p></div>';
        }

        $api_key         = get_option(self::OPTION_API_KEY, '');
        $model           = get_option(self::OPTION_MODEL, 'gpt-4o-mini');
        $system_prompt   = get_option(self::OPTION_SYSTEM_PROMPT, $this->default_system_prompt());
        $user_template   = get_option(self::OPTION_USER_TEMPLATE, $this->default_user_template());
        $max_backups     = get_option(self::OPTION_MAX_BACKUPS, 5);

        include ACE_PLUGIN_DIR.'includes/settings-page.php';
    }

    private function default_system_prompt(): string {
        return "あなたはプロの編集者です。事実を歪めず、冗長さを抑えつつ、見出し・小見出し・箇条書き等を適切に用い、読みやすく構成します。専門用語は分かりやすく補足し、日本語の自然さと簡潔さを両立してください。";
    }

    private function default_user_template(): string {
        return "次の本文を、意味を変えずに構成/表現を磨いてください。重要点は見出し化し、段落を整理し、必要に応じて箇条書きで簡潔に：\n\n{content}";
    }

    /* ========== メタボックス（UI） ========== */
    public function add_metabox() {
        add_meta_box(
            'ace-metabox',
            '🧠 AI Content Enhancer',
            function($post) {
                echo '<p>'.esc_html__('「AI加筆」で本文を整え、変更前は自動バックアップします。', 'ai-content-enhancer').'</p>';
                echo '<button type="button" class="button button-primary" id="ace-enhance-btn">🧠 AI加筆</button> ';
                echo '<button type="button" class="button" id="ace-backups-btn">📝 バックアップ</button>';
                wp_nonce_field(self::NONCE_ACTION, 'ace_nonce_field');
            },
            null, 'side', 'high'
        );
    }

    /* ========== アセット読み込み ========== */
    public function enqueue_admin_assets($hook='') {
        // 投稿編集画面 or 設定ページのみ
        $screen = get_current_screen();
        $is_editor = isset($screen->base) && in_array($screen->base, ['post','post-new','edit'], true);
        $is_settings = ($hook === 'settings_page_ai-content-enhancer');

        if (!$is_editor && !$is_settings) return;

        wp_enqueue_style('ace-enhancer', ACE_PLUGIN_URL.'assets/css/enhancer.css', [], ACE_VERSION);
        wp_enqueue_script('ace-enhancer', ACE_PLUGIN_URL.'assets/js/enhancer.js', ['jquery'], ACE_VERSION, true);

        wp_localize_script('ace-enhancer', 'aceAjax', [
            'ajaxUrl'   => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce(self::NONCE_ACTION),
            'strings'   => [
                'enhanceButton' => '🧠 AI加筆',
                'enhancing'     => 'AI 加筆中...',
                'backups'       => 'バックアップ',
                'restore'       => '復元',
                'close'         => '閉じる',
                'noBackups'     => 'バックアップはありません',
                'error'         => 'エラーが発生しました',
                'confirmRestore'=> 'このバックアップで本文を置き換えます。よろしいですか？'
            ]
        ]);
    }

    /* ========== 自動加筆（初回保存のみ / 任意） ========== */
    // 必要な場合は条件を拡張（例：本文が空 && original_description がある など）
    public function maybe_auto_enhance_on_first_save($post_id, $post) {
        if (wp_is_post_revision($post_id) || 'auto-draft' === $post->post_status) return;
        // ここでは既定で何もしない（誤動作防止）。要件が固まったらONにしてください。
    }

    /* ========== AJAX: 加筆 ========== */
    public function ajax_enhance_content() {
        $this->ensure_ajax_permissions();

        $post_id = intval($_POST['post_id'] ?? 0);
        $content = wp_kses_post($_POST['content'] ?? '');
        if (!$post_id || $content === '') $this->json_error('Invalid request.');

        // 変更前をバックアップ
        $this->push_backup($post_id, $content);

        $enhanced = $this->call_openai($content);
        if ($enhanced === null) $this->json_error('AI request failed.');

        wp_send_json_success(['enhanced' => $enhanced]);
    }

    /* ========== AJAX: バックアップ一覧 ========== */
    public function ajax_list_backups() {
        $this->ensure_ajax_permissions();
        $post_id = intval($_POST['post_id'] ?? 0);
        if (!$post_id) $this->json_error('Invalid post_id.');

        $backups = get_post_meta($post_id, self::META_BACKUPS, true);
        if (!is_array($backups)) $backups = [];

        wp_send_json_success(['backups' => $backups]);
    }

    /* ========== AJAX: 復元 ========== */
    public function ajax_restore_backup() {
        $this->ensure_ajax_permissions();
        $post_id = intval($_POST['post_id'] ?? 0);
        $index   = intval($_POST['index']   ?? -1);

        $backups = get_post_meta($post_id, self::META_BACKUPS, true);
        if (!is_array($backups) || !isset($backups[$index])) $this->json_error('Invalid backup index.');

        $content = (string)($backups[$index]['content'] ?? '');
        if ($content === '') $this->json_error('Empty backup content.');

        // 本文置換（DB更新はJS側で編集内容として反映させるため、ここではコンテンツ返却に留める手もある）
        wp_send_json_success(['content' => $content]);
    }

    /* ========== 内部：バックアップ管理 ========== */
    private function push_backup(int $post_id, string $content): void {
        $max = max(1, intval(get_option(self::OPTION_MAX_BACKUPS, 5)));
        $backups = get_post_meta($post_id, self::META_BACKUPS, true);
        if (!is_array($backups)) $backups = [];

        array_unshift($backups, [
            'ts'      => time(),
            'content' => $content
        ]);
        if (count($backups) > $max) $backups = array_slice($backups, 0, $max);

        update_post_meta($post_id, self::META_BACKUPS, $backups);
    }

    /* ========== 内部：OpenAI 呼び出し ========== */
    private function call_openai(string $content): ?string {
        // 環境変数優先、なければオプション
        $api_key = getenv('OPENAI_API_KEY');
        if (!$api_key) $api_key = (string)get_option(self::OPTION_API_KEY, '');
        if (!$api_key) {
            $this->log('OPENAI_API_KEY missing.');
            return null;
        }

        $model         = (string)get_option(self::OPTION_MODEL, 'gpt-4o-mini');
        $system_prompt = (string)get_option(self::OPTION_SYSTEM_PROMPT, $this->default_system_prompt());
        $user_template = (string)get_option(self::OPTION_USER_TEMPLATE, $this->default_user_template());
        $user_message  = str_replace('{content}', $content, $user_template);

        $endpoint = 'https://api.openai.com/v1/chat/completions';
        $body = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $system_prompt],
                ['role' => 'user',   'content' => $user_message],
            ],
            'temperature' => 0.5,
            'max_tokens'  => 2048,
        ];

        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer '.$api_key,
                'Content-Type'  => 'application/json',
            ],
            'timeout' => 60,
            'body'    => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            $this->log('OpenAI error: '.$response->get_error_message());
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $json = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || !is_array($json)) {
            $this->log('OpenAI bad response: '.$code.' / '.substr(wp_remote_retrieve_body($response), 0, 500));
            return null;
        }

        $out = $json['choices'][0]['message']['content'] ?? '';
        $out = is_string($out) ? trim($out) : '';
        if ($out === '') return null;

        return $out;
    }

    /* ========== 共通ユーティリティ ========== */
    private function ensure_ajax_permissions(): void {
        if (!current_user_can(self::CAPABILITY)) $this->json_error('Forbidden.', 403);

        $nonce = $_POST['nonce'] ?? ($_POST['ace_nonce'] ?? ($_POST['ace_nonce_field'] ?? ''));
        if (!wp_verify_nonce($nonce, self::NONCE_ACTION)) $this->json_error('Invalid nonce.', 403);
    }

    private function json_error(string $message, int $code = 400): void {
        wp_send_json_error(['message' => $message], $code);
    }

    private function log(string $msg): void {
        if (defined('WP_DEBUG') && WP_DEBUG) error_log('[ACE] '.$msg);
    }
}

new AI_Content_Enhancer();
<?php
/**
 * Plugin Name: AI Content Enhancer
 * Description: AI-powered content enhancement tool for WordPress posts with backup/restore functionality
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: ai-content-enhancer
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ACE_VERSION', '1.0.0');
define('ACE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ACE_PLUGIN_PATH', plugin_dir_path(__FILE__));

class AIContentEnhancer
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        add_action('init', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        load_plugin_textdomain('ai-content-enhancer', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'adminEnqueueScripts'));
            add_action('admin_menu', array($this, 'addAdminMenu'));
            add_action('wp_ajax_ace_enhance_content', array($this, 'handleEnhanceContent'));
            add_action('wp_ajax_ace_restore_content', array($this, 'handleRestoreContent'));
            add_action('wp_ajax_ace_get_backups', array($this, 'handleGetBackups'));
        }
    }

    public function adminEnqueueScripts($hook)
    {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }

        wp_enqueue_script(
            'ace-enhancer',
            ACE_PLUGIN_URL . 'assets/js/enhancer.js',
            array('jquery'),
            ACE_VERSION,
            true
        );

        wp_enqueue_style(
            'ace-enhancer',
            ACE_PLUGIN_URL . 'assets/css/enhancer.css',
            array(),
            ACE_VERSION
        );

        wp_localize_script('ace-enhancer', 'aceAjax', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ace_nonce'),
            'strings' => array(
                'enhanceButton' => __('🧠 AI Enhance', 'ai-content-enhancer'),
                'enhancing' => __('AI Enhancing...', 'ai-content-enhancer'),
                'backupButton' => __('📝 Backups', 'ai-content-enhancer'),
                'restoreButton' => __('↩️ Restore', 'ai-content-enhancer'),
                'confirmEnhance' => __('This will backup your current content and enhance it with AI. Continue?', 'ai-content-enhancer'),
                'confirmRestore' => __('Are you sure you want to restore this backup?', 'ai-content-enhancer'),
                'success' => __('Content enhanced successfully!', 'ai-content-enhancer'),
                'error' => __('Enhancement failed. Please try again.', 'ai-content-enhancer'),
                'noApiKey' => __('OpenAI API key not configured. Please check settings.', 'ai-content-enhancer'),
                'noContent' => __('No content to enhance.', 'ai-content-enhancer')
            )
        ));
    }

    public function addAdminMenu()
    {
        add_options_page(
            __('AI Content Enhancer Settings', 'ai-content-enhancer'),
            __('AI Enhancer', 'ai-content-enhancer'),
            'manage_options',
            'ai-content-enhancer',
            array($this, 'settingsPage')
        );
    }

    public function settingsPage()
    {
        if (isset($_POST['submit'])) {
            check_admin_referer('ace_settings');
            
            update_option('ace_openai_api_key', sanitize_text_field($_POST['ace_openai_api_key']));
            update_option('ace_openai_model', sanitize_text_field($_POST['ace_openai_model']));
            update_option('ace_system_prompt', wp_kses_post($_POST['ace_system_prompt']));
            update_option('ace_user_prompt_template', wp_kses_post($_POST['ace_user_prompt_template']));
            update_option('ace_max_backups', intval($_POST['ace_max_backups']));
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'ai-content-enhancer') . '</p></div>';
        }

        $api_key = get_option('ace_openai_api_key', '');
        $model = get_option('ace_openai_model', 'gpt-3.5-turbo');
        $system_prompt = get_option('ace_system_prompt', 'You are a professional content writer and editor. Help enhance the following content while maintaining its original meaning and style.');
        $user_prompt_template = get_option('ace_user_prompt_template', 'Please enhance this content to make it more engaging, informative, and well-structured:\n\n{content}');
        $max_backups = get_option('ace_max_backups', 5);

        include ACE_PLUGIN_PATH . 'includes/settings-page.php';
    }

    public function handleEnhanceContent()
    {
        check_ajax_referer('ace_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $content = wp_unslash($_POST['content'] ?? '');
        $post_id = intval($_POST['post_id'] ?? 0);

        if (empty($content)) {
            wp_send_json_error(__('No content provided', 'ai-content-enhancer'));
        }

        if ($post_id > 0) {
            $this->createBackup($post_id, $content);
        }

        $enhanced_content = $this->callOpenAI($content);
        
        if (empty($enhanced_content)) {
            wp_send_json_error(__('AI enhancement failed', 'ai-content-enhancer'));
        }

        wp_send_json_success($enhanced_content);
    }

    public function handleRestoreContent()
    {
        check_ajax_referer('ace_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $backup_id = intval($_POST['backup_id'] ?? 0);
        $post_id = intval($_POST['post_id'] ?? 0);

        $backup = $this->getBackup($post_id, $backup_id);
        
        if (!$backup) {
            wp_send_json_error(__('Backup not found', 'ai-content-enhancer'));
        }

        wp_send_json_success($backup['content']);
    }

    public function handleGetBackups()
    {
        check_ajax_referer('ace_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $backups = $this->getBackups($post_id);

        wp_send_json_success($backups);
    }

    private function createBackup($post_id, $content)
    {
        $backups = get_post_meta($post_id, '_ace_content_backups', true);
        if (!is_array($backups)) {
            $backups = array();
        }

        $backup = array(
            'id' => time(),
            'content' => $content,
            'created_at' => current_time('mysql'),
            'user_id' => get_current_user_id()
        );

        array_unshift($backups, $backup);

        $max_backups = get_option('ace_max_backups', 5);
        if (count($backups) > $max_backups) {
            $backups = array_slice($backups, 0, $max_backups);
        }

        update_post_meta($post_id, '_ace_content_backups', $backups);
    }

    private function getBackups($post_id)
    {
        $backups = get_post_meta($post_id, '_ace_content_backups', true);
        return is_array($backups) ? $backups : array();
    }

    private function getBackup($post_id, $backup_id)
    {
        $backups = $this->getBackups($post_id);
        
        foreach ($backups as $backup) {
            if ($backup['id'] == $backup_id) {
                return $backup;
            }
        }
        
        return null;
    }

    private function callOpenAI($content)
    {
        $api_key = get_option('ace_openai_api_key');
        if (empty($api_key)) {
            return '';
        }

        $model = get_option('ace_openai_model', 'gpt-3.5-turbo');
        $system_prompt = get_option('ace_system_prompt', 'You are a professional content writer and editor.');
        $user_prompt_template = get_option('ace_user_prompt_template', 'Please enhance this content:\n\n{content}');
        
        $user_prompt = str_replace('{content}', $content, $user_prompt_template);

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json'
            ),
            'body' => wp_json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_prompt
                    ),
                    array(
                        'role' => 'user',
                        'content' => $user_prompt
                    )
                ),
                'temperature' => 0.7
            )),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            error_log('OpenAI API Error: ' . $response->get_error_message());
            return '';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        }

        error_log('OpenAI API Response: ' . $body);
        return '';
    }

    public function activate()
    {
        if (!get_option('ace_openai_model')) {
            add_option('ace_openai_model', 'gpt-3.5-turbo');
        }
        if (!get_option('ace_system_prompt')) {
            add_option('ace_system_prompt', 'You are a professional content writer and editor. Help enhance the following content while maintaining its original meaning and style.');
        }
        if (!get_option('ace_user_prompt_template')) {
            add_option('ace_user_prompt_template', 'Please enhance this content to make it more engaging, informative, and well-structured:\n\n{content}');
        }
        if (!get_option('ace_max_backups')) {
            add_option('ace_max_backups', 5);
        }
    }

    public function deactivate()
    {
        // Cleanup if needed
    }
}

AIContentEnhancer::getInstance();
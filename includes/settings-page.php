<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('ace_settings'); ?>
        
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="ace_openai_api_key"><?php _e('OpenAI API Key', 'ai-content-enhancer'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="password" 
                            id="ace_openai_api_key" 
                            name="ace_openai_api_key" 
                            value="<?php echo esc_attr($api_key); ?>" 
                            class="regular-text"
                            autocomplete="off"
                        />
                        <p class="description">
                            <?php _e('Get your API key from', 'ai-content-enhancer'); ?> 
                            <a href="https://platform.openai.com/api-keys" target="_blank">OpenAI Platform</a>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ace_openai_model"><?php _e('OpenAI Model', 'ai-content-enhancer'); ?></label>
                    </th>
                    <td>
                        <select id="ace_openai_model" name="ace_openai_model">
                            <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                            <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>>GPT-4</option>
                            <option value="gpt-4-turbo-preview" <?php selected($model, 'gpt-4-turbo-preview'); ?>>GPT-4 Turbo Preview</option>
                        </select>
                        <p class="description">
                            <?php _e('Choose the AI model to use for content enhancement.', 'ai-content-enhancer'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ace_system_prompt"><?php _e('System Prompt', 'ai-content-enhancer'); ?></label>
                    </th>
                    <td>
                        <textarea 
                            id="ace_system_prompt" 
                            name="ace_system_prompt" 
                            rows="4" 
                            cols="50" 
                            class="large-text"
                        ><?php echo esc_textarea($system_prompt); ?></textarea>
                        <p class="description">
                            <?php _e('Define the AI\'s role and behavior. This sets the context for how content should be enhanced.', 'ai-content-enhancer'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ace_user_prompt_template"><?php _e('Enhancement Instructions', 'ai-content-enhancer'); ?></label>
                    </th>
                    <td>
                        <textarea 
                            id="ace_user_prompt_template" 
                            name="ace_user_prompt_template" 
                            rows="6" 
                            cols="50" 
                            class="large-text"
                        ><?php echo esc_textarea($user_prompt_template); ?></textarea>
                        <p class="description">
                            <?php _e('Template for enhancement instructions. Use {content} as a placeholder for the original content.', 'ai-content-enhancer'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="ace_max_backups"><?php _e('Max Backups per Post', 'ai-content-enhancer'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            id="ace_max_backups" 
                            name="ace_max_backups" 
                            value="<?php echo esc_attr($max_backups); ?>" 
                            min="1" 
                            max="20" 
                            class="small-text"
                        />
                        <p class="description">
                            <?php _e('Maximum number of content backups to keep per post (1-20).', 'ai-content-enhancer'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <div class="ace-settings-section">
            <h2><?php _e('Usage Instructions', 'ai-content-enhancer'); ?></h2>
            <ol>
                <li><?php _e('Configure your OpenAI API key above', 'ai-content-enhancer'); ?></li>
                <li><?php _e('Go to any post edit screen', 'ai-content-enhancer'); ?></li>
                <li><?php _e('Click the "🧠 AI Enhance" button next to the title', 'ai-content-enhancer'); ?></li>
                <li><?php _e('Your original content will be automatically backed up', 'ai-content-enhancer'); ?></li>
                <li><?php _e('Use the "📝 Backups" button to view and restore previous versions', 'ai-content-enhancer'); ?></li>
            </ol>
        </div>
        
        <style>
        .ace-settings-section {
            margin-top: 30px;
            padding: 20px;
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .ace-settings-section h2 {
            margin-top: 0;
        }
        </style>
        
        <?php submit_button(); ?>
    </form>
</div>
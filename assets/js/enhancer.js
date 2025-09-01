jQuery(document).ready(function($) {
    'use strict';
    
    let currentBackups = [];
    
    function initializeEnhancer() {
        createEnhancerControls();
        bindEvents();
    }
    
    function createEnhancerControls() {
        const $titleWrap = $('#titlewrap');
        if ($titleWrap.length === 0) return;
        
        const $controlsContainer = $('<div class="ace-controls"></div>');
        
        const $enhanceBtn = $('<button type="button" class="button button-primary ace-enhance-btn">')
            .html('<span class="dashicons dashicons-admin-generic"></span> ' + aceAjax.strings.enhanceButton)
            .attr('title', aceAjax.strings.confirmEnhance);
            
        const $backupsBtn = $('<button type="button" class="button ace-backups-btn">')
            .html('<span class="dashicons dashicons-backup"></span> ' + aceAjax.strings.backupButton)
            .attr('title', 'View content backups');
        
        $controlsContainer.append($enhanceBtn).append($backupsBtn);
        $titleWrap.after($controlsContainer);
        
        createBackupsModal();
        loadBackups();
    }
    
    function createBackupsModal() {
        const modalHtml = `
            <div id="ace-backups-modal" class="ace-modal" style="display: none;">
                <div class="ace-modal-content">
                    <div class="ace-modal-header">
                        <h3>${aceAjax.strings.backupButton}</h3>
                        <span class="ace-modal-close">&times;</span>
                    </div>
                    <div class="ace-modal-body">
                        <div id="ace-backups-list"></div>
                    </div>
                </div>
            </div>
        `;
        
        $('body').append(modalHtml);
    }
    
    function bindEvents() {
        $(document).on('click', '.ace-enhance-btn', handleEnhanceClick);
        $(document).on('click', '.ace-backups-btn', showBackupsModal);
        $(document).on('click', '.ace-modal-close', hideBackupsModal);
        $(document).on('click', '.ace-restore-btn', handleRestoreClick);
        $(document).on('click', function(e) {
            if ($(e.target).is('#ace-backups-modal')) {
                hideBackupsModal();
            }
        });
    }
    
    function handleEnhanceClick(e) {
        e.preventDefault();
        
        if (!confirm(aceAjax.strings.confirmEnhance)) {
            return;
        }
        
        const $btn = $(this);
        const content = getCurrentContent();
        
        if (!content.trim()) {
            alert(aceAjax.strings.noContent);
            return;
        }
        
        const postId = $('#post_ID').val() || 0;
        
        $btn.prop('disabled', true)
            .html('<span class="dashicons dashicons-update ace-spinning"></span> ' + aceAjax.strings.enhancing);
        
        $.post(aceAjax.ajaxUrl, {
            action: 'ace_enhance_content',
            content: content,
            post_id: postId,
            nonce: aceAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                setCurrentContent(response.data);
                showNotice(aceAjax.strings.success, 'success');
                loadBackups(); // Refresh backups list
            } else {
                const errorMsg = response.data || aceAjax.strings.error;
                showNotice(errorMsg, 'error');
                
                if (errorMsg.includes('API key')) {
                    showNotice(aceAjax.strings.noApiKey, 'error');
                }
            }
        })
        .fail(function() {
            showNotice(aceAjax.strings.error, 'error');
        })
        .always(function() {
            $btn.prop('disabled', false)
                .html('<span class="dashicons dashicons-admin-generic"></span> ' + aceAjax.strings.enhanceButton);
        });
    }
    
    function handleRestoreClick(e) {
        e.preventDefault();
        
        if (!confirm(aceAjax.strings.confirmRestore)) {
            return;
        }
        
        const $btn = $(this);
        const backupId = $btn.data('backup-id');
        const postId = $('#post_ID').val() || 0;
        
        $btn.prop('disabled', true);
        
        $.post(aceAjax.ajaxUrl, {
            action: 'ace_restore_content',
            backup_id: backupId,
            post_id: postId,
            nonce: aceAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                setCurrentContent(response.data);
                showNotice('Content restored successfully!', 'success');
                hideBackupsModal();
            } else {
                showNotice('Failed to restore content', 'error');
            }
        })
        .fail(function() {
            showNotice('Failed to restore content', 'error');
        })
        .always(function() {
            $btn.prop('disabled', false);
        });
    }
    
    function showBackupsModal() {
        loadBackups();
        $('#ace-backups-modal').show();
    }
    
    function hideBackupsModal() {
        $('#ace-backups-modal').hide();
    }
    
    function loadBackups() {
        const postId = $('#post_ID').val();
        if (!postId) return;
        
        $.post(aceAjax.ajaxUrl, {
            action: 'ace_get_backups',
            post_id: postId,
            nonce: aceAjax.nonce
        })
        .done(function(response) {
            if (response.success) {
                currentBackups = response.data;
                renderBackupsList();
                updateBackupsButton();
            }
        });
    }
    
    function renderBackupsList() {
        const $list = $('#ace-backups-list');
        
        if (currentBackups.length === 0) {
            $list.html('<p>No backups available.</p>');
            return;
        }
        
        let html = '<div class="ace-backups-container">';
        
        currentBackups.forEach(function(backup, index) {
            const date = new Date(backup.created_at).toLocaleString();
            const preview = backup.content.substring(0, 150) + (backup.content.length > 150 ? '...' : '');
            
            html += `
                <div class="ace-backup-item">
                    <div class="ace-backup-header">
                        <strong>Backup ${index + 1}</strong>
                        <span class="ace-backup-date">${date}</span>
                    </div>
                    <div class="ace-backup-preview">${escapeHtml(preview)}</div>
                    <div class="ace-backup-actions">
                        <button type="button" class="button button-small ace-restore-btn" data-backup-id="${backup.id}">
                            ${aceAjax.strings.restoreButton}
                        </button>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        $list.html(html);
    }
    
    function updateBackupsButton() {
        const $btn = $('.ace-backups-btn');
        const count = currentBackups.length;
        
        if (count > 0) {
            $btn.html('<span class="dashicons dashicons-backup"></span> ' + aceAjax.strings.backupButton + ' (' + count + ')');
        } else {
            $btn.html('<span class="dashicons dashicons-backup"></span> ' + aceAjax.strings.backupButton);
        }
    }
    
    function getCurrentContent() {
        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            return tinyMCE.activeEditor.getContent();
        } else {
            return $('#content').val() || '';
        }
    }
    
    function setCurrentContent(content) {
        if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) {
            tinyMCE.activeEditor.setContent(content);
        } else {
            $('#content').val(content);
        }
    }
    
    function showNotice(message, type) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible ace-notice"><p>' + message + '</p></div>');
        
        $('.wrap h1').first().after($notice);
        
        // Auto-dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $notice.remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.remove();
        });
    }
    
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Initialize when DOM is ready
    initializeEnhancer();
});
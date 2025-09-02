jQuery(function ($) {
  'use strict';

  // Gutenberg/Classic の本文取得・設定
  function getContent() {
    try {
      if (window.wp && wp.data && wp.data.select) {
        const sel = wp.data.select('core/editor');
        if (sel && sel.getEditedPostContent) return sel.getEditedPostContent() || '';
      }
      // Classic
      if (window.tinymce && tinymce.get('content')) return tinymce.get('content').getContent() || '';
      const ta = document.getElementById('content');
      return ta ? ta.value : '';
    } catch (e) { return ''; }
  }

  function setContent(html) {
    try {
      if (window.wp && wp.data && wp.data.dispatch) {
        const disp = wp.data.dispatch('core/editor');
        if (disp && disp.editPost) return disp.editPost({ content: html });
      }
      if (window.tinymce && tinymce.get('content')) return tinymce.get('content').setContent(html);
      const ta = document.getElementById('content');
      if (ta) ta.value = html;
    } catch (e) {}
  }

  function getPostId() {
    if (window.wp && wp.data && wp.data.select) {
      const sel = wp.data.select('core/editor');
      if (sel && sel.getCurrentPostId) return sel.getCurrentPostId();
    }
    const el = document.getElementById('post_ID');
    return el ? parseInt(el.value, 10) : 0;
  }

  // モーダル（シンプル実装）
  function openBackupsModal(list) {
    const html = `
      <div class="ace-modal" id="ace-backups-modal" role="dialog" aria-modal="true">
        <div class="ace-modal__dialog">
          <div class="ace-modal__header">
            <h2>📝 ${aceAjax.strings.backups}</h2>
            <button type="button" class="button-link ace-modal__close" aria-label="${aceAjax.strings.close}">✕</button>
          </div>
          <div class="ace-modal__body">
            ${list.length === 0
              ? `<p>${aceAjax.strings.noBackups}</p>`
              : `<ul class="ace-backup-list">
                  ${list.map((b, i) => {
                    const d = new Date((b.ts || 0) * 1000);
                    const label = isNaN(d.getTime()) ? `#${i}` : d.toLocaleString();
                    return `<li>
                      <div><strong>${label}</strong></div>
                      <div class="ace-backup-actions">
                        <button type="button" class="button ace-restore" data-index="${i}">↩️ ${aceAjax.strings.restore}</button>
                      </div>
                    </li>`
                  }).join('')}
                 </ul>`
            }
          </div>
        </div>
      </div>`;
    $('body').append(html);
  }

  function closeBackupsModal() {
    $('#ace-backups-modal').remove();
  }

  // クリックハンドラ
  $(document).on('click', '#ace-enhance-btn', function () {
    const $btn = $(this);
    const postId = getPostId();
    const content = getContent();

    if (!postId || !content) {
      alert(aceAjax.strings.error + ' (empty post/content)');
      return;
    }

    $btn.prop('disabled', true).text(aceAjax.strings.enhancing);

    $.post(aceAjax.ajaxUrl, {
      action: 'ace_enhance_content',
      nonce: aceAjax.nonce,
      post_id: postId,
      content
    }).done(function (res) {
      if (res && res.success && res.data && res.data.enhanced) {
        setContent(res.data.enhanced);
      } else {
        alert(aceAjax.strings.error);
      }
    }).fail(function () {
      alert(aceAjax.strings.error);
    }).always(function () {
      $btn.prop('disabled', false).text(aceAjax.strings.enhanceButton);
    });
  });

  $(document).on('click', '#ace-backups-btn', function () {
    const postId = getPostId();
    if (!postId) {
      alert(aceAjax.strings.error + ' (invalid post id)');
      return;
    }
    $.post(aceAjax.ajaxUrl, {
      action: 'ace_list_backups',
      nonce: aceAjax.nonce,
      post_id: postId
    }).done(function (res) {
      const list = (res && res.success && res.data && Array.isArray(res.data.backups)) ? res.data.backups : [];
      openBackupsModal(list);
    }).fail(function () {
      alert(aceAjax.strings.error);
    });
  });

  // 復元ボタン
  $(document).on('click', '.ace-restore', function () {
    const postId = getPostId();
    const index = parseInt($(this).data('index'), 10);
    if (!postId || isNaN(index)) return;

    if (!confirm(aceAjax.strings.confirmRestore)) return;

    $.post(aceAjax.ajaxUrl, {
      action: 'ace_restore_backup',
      nonce: aceAjax.nonce,
      post_id: postId,
      index
    }).done(function (res) {
      if (res && res.success && res.data && res.data.content) {
        setContent(res.data.content);
      } else {
        alert(aceAjax.strings.error);
      }
    }).fail(function () {
      alert(aceAjax.strings.error);
    });
  });

  // モーダル閉じる
  $(document).on('click', '.ace-modal__close', function () {
    closeBackupsModal();
  });
  $(document).on('click', '#ace-backups-modal', function (e) {
    if (e.target && e.target.id === 'ace-backups-modal') closeBackupsModal();
  });
});
/**
 * Plugin Updater Admin JavaScript
 * 
 * Handles manual update check via AJAX
 */
jQuery(document).ready(function($) {
    $('.writgoai-check-update').on('click', function(e) {
        e.preventDefault();
        
        var $link = $(this);
        var originalText = $link.text();
        
        // Show loading state
        $link.text('Checking...');
        $link.css('pointer-events', 'none');
        
        // Make AJAX request
        $.ajax({
            url: writgoaiUpdater.ajaxurl,
            type: 'POST',
            data: {
                action: 'writgoai_check_updates',
                nonce: writgoaiUpdater.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    
                    // Reload the page if update is available
                    if (response.data.has_update) {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function() {
                alert('Failed to check for updates. Please try again.');
            },
            complete: function() {
                // Restore original state
                $link.text(originalText);
                $link.css('pointer-events', '');
            }
        });
    });
});

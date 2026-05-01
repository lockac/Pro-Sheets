jQuery(document).ready(function($) {

    // ==========================================
    // 0. INITIALIZE WP COLOR PICKERS
    // ==========================================
    if ($.fn.wpColorPicker) {
        $('.my-color-picker').wpColorPicker();
    }

    // ==========================================
    // 1. HIGHLIGHT TAB: TRANSPARENCY SLIDER
    // ==========================================
    var $slider = $('#protable-highlight-slider');
    var $percent = $('#hi-transparency-display');
    var $bgColorInput = $('#protable-highlight-bg');

    function updateSliderTrack() {
        if ($slider.length && $percent.length && $bgColorInput.length) {
            var val = parseInt($slider.val());
            var hex = $bgColorInput.val();
            if (!hex || hex.length < 4) hex = '#ffff00';

            // Convert Hex to RGB
            var r = parseInt(hex.substr(1,2), 16);
            var g = parseInt(hex.substr(3,2), 16);
            var b = parseInt(hex.substr(5,2), 16);
            
            // Calculate opacity for the RGBA color
            var opacity = val / 100;

            // Update percentage text
            $percent.text(val + '%');

            // Create the RGBA color string
            var rgbaColor = 'rgba(' + r + ',' + g + ',' + b + ',' + opacity + ')';

            // GRADIENT LOGIC:
            // Fills from Left (0%) to Thumb Position (Val%) with Color.
            // Rest of track (Val% to 100%) is Transparent (showing checkerboard).
            // This ensures: Left=Transparent, Right=Full Color.
            var gradient = 'linear-gradient(to right, ' + 
                rgbaColor + ' 0%, ' + 
                rgbaColor + ' ' + val + '%, ' + 
                'rgba(0,0,0,0) ' + val + '%, ' + 
                'rgba(0,0,0,0) 100%)';

            // Apply gradient over checkerboard base
            $slider.css({
                'background': gradient + ', ' +
                    'linear-gradient(45deg, #e5e5e5 25%, transparent 25%), ' + 
                    'linear-gradient(-45deg, #e5e5e5 25%, transparent 25%), ' + 
                    'linear-gradient(45deg, transparent 75%, #e5e5e5 75%), ' + 
                    'linear-gradient(-45deg, transparent 75%, #e5e5e5 75%)',
                'background-size': '100% 100%, 8px 8px, 8px 8px, 8px 8px, 8px 8px',
                'background-position': '0 0, 0 0, 0 4px, 4px -4px, -4px 0px',
                'background-blend-mode': 'normal'
            });
        }
    }

    // Initialize on page load
    updateSliderTrack();

    // Update on slider drag
    $slider.on('input', updateSliderTrack);

    // Update when WP Color Picker changes (with slight delay for stability)
    $(document).on('wpcolorpickerchange', '#protable-highlight-bg', function() {
        setTimeout(updateSliderTrack, 150);
    });

    // Update when manual text input changes
    $('#protable-highlight-bg').on('change keyup', updateSliderTrack);

    // ==========================================
    // 2. FONT STYLE BUTTONS (Checkboxes)
    // ==========================================
    $(document).on('click', '.ps-style-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var $checkbox = $btn.find('input[type="checkbox"]');
        
        if ($checkbox.length) {
            var isCurrentlyChecked = $checkbox.prop('checked');
            $checkbox.prop('checked', !isCurrentlyChecked);
            $btn.toggleClass('active', !isCurrentlyChecked);
        }
    });

    // ==========================================
    // 3. TEXT CASE BUTTONS (Radios with Toggle-Off)
    // ==========================================
    $(document).on('click', '.ps-case-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $btn = $(this);
        var $radio = $btn.find('input[type="radio"]');
        
        if ($radio.length) {
            if ($btn.hasClass('active')) {
                // Clicking active button deselects it
                $radio.prop('checked', false);
                $btn.removeClass('active');
            } else {
                // Clicking inactive button selects it & deselects siblings
                $btn.closest('.ps-case-group').find('.ps-case-btn').removeClass('active');
                $radio.prop('checked', true);
                $btn.addClass('active');
            }
        }
    });

    // ==========================================
    // 4. TAB SWITCHING
    // ==========================================
    $('.ps-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).data('target');
        $('.ps-tab').removeClass('active');
        $('.ps-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + target).addClass('active');
    });

    // ==========================================
    // 5. SECTION TOGGLES (Enable/Disable & Auto-Fill)
    // ==========================================
    function updateSectionStatus() {
        $('.ps-section-toggle').each(function() {
            var isChecked = $(this).is(':checked');
            var $col = $(this).closest('.freeze-section-col');
            if (isChecked) {
                $col.removeClass('section-disabled');
                // Auto-set rows/cols to 1 if empty or 0
                var name = $(this).attr('name');
                if (name) {
                    var prefix = name.replace('_en', '');
                    var suffix = (prefix === 'h' || prefix === 'f') ? '_rows' : '_cols';
                    var $input = $('input[name="' + prefix + suffix + '"]');
                    if ($input.length && (parseInt($input.val()) === 0 || $input.val() === '')) {
                        $input.val(1);
                    }
                }
            } else {
                $col.addClass('section-disabled');
            }
        });
    }
        // =========================================================
        // OVERRIDE COLORS TOGGLE (Freeze Panes Tab)
        // =========================================================
        (function() {
            'use strict';
            
            function applyOverrideState(checkbox) {
                if (!checkbox || !checkbox.dataset.prefix) return;
                const prefix = checkbox.dataset.prefix;
                const shouldDisable = !checkbox.checked;
                
                const bgInput = document.querySelector('input[name="' + prefix + '_bg"]');
                const txtInput = document.querySelector('input[name="' + prefix + '_txt"]');
                
                if (bgInput) {
                    bgInput.disabled = shouldDisable;
                    bgInput.style.opacity = shouldDisable ? '0.5' : '1';
                    bgInput.style.pointerEvents = shouldDisable ? 'none' : 'auto';
                }
                if (txtInput) {
                    txtInput.disabled = shouldDisable;
                    txtInput.style.opacity = shouldDisable ? '0.5' : '1';
                    txtInput.style.pointerEvents = shouldDisable ? 'none' : 'auto';
                }
            }
            
            function initOverrideToggles() {
                const toggles = document.querySelectorAll('.ps-override-toggle');
                if (!toggles.length) return;
                
                toggles.forEach(function(cb) {
                    applyOverrideState(cb);
                    cb.addEventListener('change', function() {
                        applyOverrideState(this);
                    });
                });
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initOverrideToggles);
            } else {
                initOverrideToggles();
            }
        })();

    $(document).on('change', '.ps-section-toggle', updateSectionStatus);
    updateSectionStatus(); // Run on page load
});
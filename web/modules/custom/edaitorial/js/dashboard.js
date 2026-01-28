/**
 * @file
 * JavaScript for edAItorial Dashboard.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.edaitorialDashboard = {
    attach: function (context, settings) {
      // Run Audit Button
      $('#run-audit', context).once('edaitorial-audit').on('click', function(e) {
        e.preventDefault();
        
        $(this).prop('disabled', true).text(Drupal.t('Running audit...'));
        
        // Simulate audit process
        setTimeout(function() {
          $('#run-audit').prop('disabled', false).text(Drupal.t('Run Audit'));
          
          // Show success message
          if (typeof Drupal.Message !== 'undefined') {
            Drupal.Message().add(Drupal.t('Audit completed successfully!'), {type: 'status'});
          }
          
          // Reload page to show updated metrics
          location.reload();
        }, 3000);
      });

      // Animate progress bars on load
      $('.progress-fill', context).once('edaitorial-progress').each(function() {
        var targetWidth = $(this).css('width');
        $(this).css('width', '0');
        
        setTimeout(() => {
          $(this).css('width', targetWidth);
        }, 100);
      });

      // Animate circular gauge on load
      $('.gauge-progress', context).once('edaitorial-gauge').each(function() {
        var strokeDashoffset = $(this).css('stroke-dashoffset');
        $(this).css('stroke-dashoffset', '502');
        
        setTimeout(() => {
          $(this).css('stroke-dashoffset', strokeDashoffset);
        }, 100);
      });

      // Add hover effects to metric cards
      $('.metric-card', context).once('edaitorial-hover').hover(
        function() {
          $(this).css('transform', 'translateY(-4px)');
          $(this).css('box-shadow', '0 4px 12px rgba(0, 0, 0, 0.15)');
        },
        function() {
          $(this).css('transform', 'translateY(0)');
          $(this).css('box-shadow', '0 2px 8px rgba(0, 0, 0, 0.08)');
        }
      );

      // Add smooth transitions
      $('.metric-card, .content-section, .footer-section', context).once('edaitorial-transition').css({
        'transition': 'all 0.3s ease'
      });

      // SEO Overview: Animate score circle
      $('.score-progress', context).once('edaitorial-score').each(function() {
        var strokeDashoffset = $(this).css('stroke-dashoffset');
        $(this).css('stroke-dashoffset', '534');
        
        setTimeout(() => {
          $(this).css('stroke-dashoffset', strokeDashoffset);
        }, 500);
      });

      // SEO Overview: Refresh button
      $('#refresh-seo', context).once('edaitorial-refresh-seo').on('click', function(e) {
        e.preventDefault();
        var $btn = $(this);
        var originalHtml = $btn.html();
        
        $btn.prop('disabled', true).html('<span class="button-icon">⏳</span>' + Drupal.t('Analyzing...'));
        
        // Simulate analysis
        setTimeout(function() {
          $btn.prop('disabled', false).html(originalHtml);
          
          if (typeof Drupal.Message !== 'undefined') {
            Drupal.Message().add(Drupal.t('SEO analysis refreshed!'), {type: 'status'});
          }
          
          location.reload();
        }, 2500);
      });

      // SEO Overview: Animate check cards on scroll
      if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
          entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
              setTimeout(() => {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
              }, index * 100);
              observer.unobserve(entry.target);
            }
          });
        }, {
          threshold: 0.1
        });

        $('.check-card', context).once('edaitorial-check-animate').each(function() {
          $(this).css({
            'opacity': '0',
            'transform': 'translateY(20px)',
            'transition': 'all 0.5s ease'
          });
          observer.observe(this);
        });
      }

      // SEO Overview: Stat boxes animation
      $('.stat-box', context).once('edaitorial-stat-animate').each(function(index) {
        var $statBox = $(this);
        $statBox.css({
          'opacity': '0',
          'transform': 'scale(0.9)'
        });
        
        setTimeout(() => {
          $statBox.css({
            'opacity': '1',
            'transform': 'scale(1)',
            'transition': 'all 0.5s ease'
          });
        }, 300 + (index * 100));
      });
    }
  };

})(jQuery, Drupal);

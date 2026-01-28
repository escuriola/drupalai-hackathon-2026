<?php

/**
 * @file
 * Script to create sample content for testing edAItorial module.
 * 
 * Usage: ddev drush php:script web/modules/custom/edaitorial/create-sample-content.php
 */

use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

// Sample content with various issues for testing
$sample_content = [
  // Good content - no issues
  [
    'title' => 'Complete Guide to Web Accessibility',
    'body' => '<h2>Introduction to Accessibility</h2><p>Web accessibility ensures that websites, tools, and technologies are designed so people with disabilities can use them. This comprehensive guide covers everything you need to know.</p><h3>Why Accessibility Matters</h3><p>Accessibility is essential for developers and organizations that want to create high quality websites and web tools, and not exclude people from using their products and services. Making the web accessible benefits individuals, businesses, and society.</p><h3>Key Principles</h3><p>The WCAG guidelines follow four main principles: Perceivable, Operable, Understandable, and Robust. Each principle contains guidelines that help make content more accessible.</p>',
    'issues' => [],
  ],
  
  // Missing headings
  [
    'title' => 'Our Company History',
    'body' => '<p>We started our company in 2010 with a simple mission. Since then we have grown to serve thousands of customers worldwide. Our team is dedicated to providing excellent service.</p><p>Today we continue to innovate and improve our products. Thank you for being part of our journey.</p>',
    'issues' => ['No heading structure'],
  ],
  
  // Title too short
  [
    'title' => 'News',
    'body' => '<h2>Latest Updates</h2><p>Check out our latest company news and product announcements. We have some exciting developments to share with you this month.</p>',
    'issues' => ['Title too short'],
  ],
  
  // Title too long
  [
    'title' => 'Everything You Need to Know About Our Products, Services, Support, and Customer Success Programs',
    'body' => '<h2>Overview</h2><p>Learn about all our offerings and how we can help your business grow and succeed in the digital age.</p>',
    'issues' => ['Title too long'],
  ],
  
  // Short content
  [
    'title' => 'Quick Tips for Success',
    'body' => '<h2>Tips</h2><p>Be consistent. Work hard. Stay focused.</p>',
    'issues' => ['Content too short'],
  ],
  
  // Bad link text
  [
    'title' => 'Download Our Resources',
    'body' => '<h2>Free Resources</h2><p>We offer many helpful resources for our customers. <a href="/files/guide.pdf">Click here</a> to download our guide. You can also <a href="/contact">read more</a> about our services.</p>',
    'issues' => ['Bad link text'],
  ],
  
  // Mixed issues
  [
    'title' => 'FAQ',
    'body' => '<p>Question: How do I get started?</p><p>Answer: Just sign up and begin. Its that easy!</p><p>Question: What about pricing?</p><p>Answer: Check our pricing page.</p>',
    'issues' => ['Short title', 'No headings', 'Short content'],
  ],
  
  // Good SEO optimized content
  [
    'title' => 'Best Practices for SEO Optimization in 2026',
    'body' => '<h2>Introduction to Modern SEO</h2><p>Search Engine Optimization continues to evolve with new technologies and user behaviors. This guide covers the most important SEO practices for modern websites.</p><h3>Content Quality</h3><p>Quality content is the foundation of good SEO. Your content should be original, valuable, and well-structured. Use proper headings to organize information and make it easy for both users and search engines to understand your content.</p><h3>Technical SEO</h3><p>Technical optimization includes page speed, mobile responsiveness, proper URL structure, and clean HTML. These factors significantly impact your search rankings.</p><h3>User Experience</h3><p>Search engines now prioritize user experience metrics. Ensure your site loads quickly, is easy to navigate, and provides a smooth experience across all devices.</p>',
    'issues' => [],
  ],
  
  // Content with lists
  [
    'title' => 'Top 10 Features of Our Platform',
    'body' => '<h2>Key Features</h2><p>Our platform offers comprehensive solutions for modern businesses. Here are our top features:</p><ul><li>Advanced analytics and reporting</li><li>Real-time collaboration tools</li><li>Secure cloud storage</li><li>Mobile app access</li><li>24/7 customer support</li><li>Customizable dashboards</li><li>API integrations</li><li>Automated workflows</li><li>Team management</li><li>Data export options</li></ul><h3>Why Choose Us</h3><p>We combine powerful features with ease of use, making it simple for teams of any size to get started and scale as they grow.</p>',
    'issues' => [],
  ],
  
  // Service page
  [
    'title' => 'Professional Web Development Services',
    'body' => '<h2>Custom Web Solutions</h2><p>We specialize in creating custom web applications tailored to your business needs. Our experienced team delivers high-quality solutions on time and within budget.</p><h3>Our Services Include</h3><ul><li>Website Design and Development</li><li>E-commerce Solutions</li><li>Content Management Systems</li><li>Progressive Web Apps</li><li>API Development and Integration</li></ul><h3>Our Process</h3><p>We follow an agile development methodology that ensures transparency and regular communication throughout the project lifecycle. From initial consultation to final deployment, we work closely with you to achieve your goals.</p>',
    'issues' => [],
  ],
];

echo "Creating sample content for edAItorial testing...\n\n";

$created = 0;
$errors = 0;

foreach ($sample_content as $content) {
  try {
    $node = Node::create([
      'type' => 'article',
      'title' => $content['title'],
      'body' => [
        'value' => $content['body'],
        'format' => 'full_html',
      ],
      'status' => 1,
      'uid' => 1,
    ]);
    
    $node->save();
    $created++;
    
    $issues_text = empty($content['issues']) ? 'No issues' : implode(', ', $content['issues']);
    echo "✓ Created: {$content['title']} (ID: {$node->id()})\n";
    echo "  Expected issues: {$issues_text}\n\n";
    
  } catch (Exception $e) {
    $errors++;
    echo "✗ Error creating: {$content['title']}\n";
    echo "  Error: {$e->getMessage()}\n\n";
  }
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "Summary:\n";
echo "  Created: {$created} nodes\n";
echo "  Errors: {$errors}\n";

$total = \Drupal::entityQuery('node')
  ->condition('status', 1)
  ->accessCheck(FALSE)
  ->count()
  ->execute();

echo "  Total published nodes: {$total}\n";
echo str_repeat('=', 60) . "\n";

echo "\nNext steps:\n";
echo "1. Clear cache: ddev drush cr\n";
echo "2. Visit: https://drupalai-hackathon-2026.ddev.site/admin/config/content/edaitorial\n";
echo "3. Click 'Run Audit' to analyze all content\n";
echo "4. Check Content Audit: /admin/config/content/edaitorial/content-audit\n";

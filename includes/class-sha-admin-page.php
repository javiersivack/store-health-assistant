<?php

if (!defined('ABSPATH')) {
    exit;
}

class SHA_Admin_Page {

    public static function init() {
        add_action('admin_menu', [self::class, 'register_menu']);
    }

    public static function register_menu() {
        add_menu_page(
            'Store Health Assistant',
            'Store Health',
            'manage_options',
            'store-health-assistant',
            [self::class, 'render'],
            'dashicons-chart-area',
            56
        );
    }

    public static function render() {
        echo '<div class="wrap">';
        echo '<h1>Store Health Assistant</h1>';

        if (!class_exists('WooCommerce')) {
            echo '<div class="notice notice-error"><p><strong>WooCommerce is not active.</strong></p></div>';
            echo '</div>';
            return;
        }

        echo '<div class="notice notice-success"><p><strong>WooCommerce detected.</strong></p></div>';

        self::render_scan_controls();
        self::render_health_score();
        self::render_priority_issues();

        self::render_product_issue_card(
            'products-without-image',
            'Products without image',
            'Products without images may reduce conversions and customer trust.',
            'Good. No published products without image found.',
            'published products without image found.',
            SHA_Product_Scanner::get_products_without_image()
        );

        self::render_product_issue_card(
            'products-without-description',
            'Products without description',
            'Products without descriptions may reduce SEO visibility and customer trust.',
            'Good. No published products without description found.',
            'published products without description found.',
            SHA_Product_Scanner::get_products_without_description()
        );

        self::render_product_issue_card(
            'products-without-price',
            'Products without price',
            'Products without price may prevent customers from completing purchases.',
            'Good. No published products without price found.',
            'published products without price found.',
            SHA_Product_Scanner::get_products_without_price()
        );

        self::render_product_issue_card(
            'out-of-stock-products',
            'Out of stock products',
            'Out of stock products may reduce potential revenue.',
            'Good. No out of stock published products found.',
            'out of stock published products found.',
            SHA_Product_Scanner::get_out_of_stock_products()
        );

        self::render_product_issue_card(
            'low-stock-products',
            'Low stock products',
            'Low stock products may need attention before they run out.',
            'Good. No low stock published products found.',
            'low stock published products found.',
            SHA_Product_Scanner::get_low_stock_products()
        );

        self::render_product_issue_card(
            'products-never-sold',
            'Products never sold',
            'Products with zero sales may need better images, descriptions, pricing, or promotion.',
            'Good. No published products with zero sales found.',
            'published products with zero sales found.',
            SHA_Product_Scanner::get_products_never_sold()
        );

        echo '</div>';
    }

    private static function render_scan_controls() {
        $last_scan = get_transient('sha_last_scan');

        if (!$last_scan) {
            $last_scan = current_time('mysql');
            set_transient('sha_last_scan', $last_scan, DAY_IN_SECONDS);
        }

        if (isset($_GET['sha_run_scan'])) {
            $last_scan = current_time('mysql');
            set_transient('sha_last_scan', $last_scan, DAY_IN_SECONDS);

            echo '<div class="notice notice-success">';
            echo '<p><strong>Store scan completed.</strong></p>';
            echo '</div>';
        }

        self::render_card_start();

        echo '<h2 style="margin-top:0;">Store Scan</h2>';
        echo '<p style="margin-bottom:12px;">Run a store scan to refresh detected WooCommerce issues.</p>';

        $scan_url = admin_url('admin.php?page=store-health-assistant&sha_run_scan=1');

        echo '<p style="margin:0 0 12px 0;">';
        echo '<a href="' . esc_url($scan_url) . '" class="button button-primary">Run Store Scan</a>';
        echo '</p>';

        echo '<p style="color:#666; margin:0;">Last scan: ' . esc_html($last_scan) . '</p>';

        self::render_card_end();
    }

    private static function render_health_score() {
        $health_score = SHA_Product_Scanner::get_store_health_score();
        $total_issues = self::get_total_issue_count();

        $score_color = '#dc3232';

        if ($health_score >= 80) {
            $score_color = '#46b450';
        } elseif ($health_score >= 50) {
            $score_color = '#ffb900';
        }

        self::render_card_start();

        echo '<h2 style="margin-top:0;">Store Health Score</h2>';

        echo '<div style="
            display:flex;
            align-items:flex-end;
            gap:24px;
            flex-wrap:wrap;
        ">';

        echo '<div style="
            font-size:48px;
            font-weight:bold;
            line-height:1;
            color:' . esc_attr($score_color) . ';
        ">';

        echo esc_html($health_score) . '/100';
        echo '</div>';

        echo '<div style="
            font-size:20px;
            font-weight:600;
            color:#1d2327;
            margin-bottom:4px;
        ">';

        echo esc_html($total_issues) . ' issues detected';
        echo '</div>';

        echo '</div>';

        echo '<p style="margin-bottom:0;">Your store health score is based on detected product issues.</p>';

        self::render_card_end();
    }

    private static function render_priority_issues() {
        $priority_issues = SHA_Product_Scanner::get_priority_issues();

        self::render_card_start();

        echo '<h2 style="margin-top:0;">Priority Issues</h2>';

        if (empty($priority_issues)) {
            echo '<p style="margin-bottom:0;">Great. No critical store issues detected.</p>';
        } else {
            echo '<ul style="margin-bottom:0;">';

            foreach ($priority_issues as $issue) {
                $anchor = self::get_issue_anchor($issue['title']);

                echo '<li style="margin-bottom:12px;">';

                echo '<a href="#' . esc_attr($anchor) . '" style="font-weight:600; text-decoration:none;">';
                echo esc_html($issue['title']) . ' (' . esc_html($issue['count']) . ')';
                echo '</a>';

                echo '<br>';
                echo '<span style="color:#50575e;">' . esc_html($issue['message']) . '</span>';

                echo '</li>';
            }

            echo '</ul>';
        }

        self::render_card_end();
    }

    private static function render_product_issue_card($id, $title, $description, $empty_message, $found_message, $products) {
        self::render_card_start($id);

        echo '<h2 style="margin-top:0;">' . esc_html($title) . '</h2>';
        echo '<p>' . esc_html($description) . '</p>';

        if (empty($products)) {
            echo '<p style="margin-bottom:0;">' . esc_html($empty_message) . '</p>';
        } else {
            echo '<p><strong>' . esc_html(count($products)) . '</strong> ' . esc_html($found_message) . '</p>';
            self::render_product_list($products);
        }

        self::render_card_end();
    }

    private static function render_product_list($products) {
        echo '<ul style="margin-bottom:0;">';

        foreach ($products as $product) {
            echo '<li>';
            echo esc_html($product->get_name());
            echo ' — <a href="' . esc_url(get_edit_post_link($product->get_id())) . '">Edit</a>';
            echo '</li>';
        }

        echo '</ul>';
    }

    private static function get_total_issue_count() {
        return
            count(SHA_Product_Scanner::get_products_without_image()) +
            count(SHA_Product_Scanner::get_products_without_description()) +
            count(SHA_Product_Scanner::get_products_without_price()) +
            count(SHA_Product_Scanner::get_out_of_stock_products()) +
            count(SHA_Product_Scanner::get_low_stock_products()) +
            count(SHA_Product_Scanner::get_products_never_sold());
    }

    private static function get_issue_anchor($title) {
        $map = [
            'Products without images' => 'products-without-image',
            'Products without descriptions' => 'products-without-description',
            'Products without price' => 'products-without-price',
            'Out of stock products' => 'out-of-stock-products',
            'Low stock products' => 'low-stock-products',
            'Products never sold' => 'products-never-sold',
        ];

        return isset($map[$title]) ? $map[$title] : '';
    }

    private static function render_card_start($id = '') {
        $id_attribute = $id ? ' id="' . esc_attr($id) . '"' : '';

        echo '<div' . $id_attribute . ' style="
            background:#fff;
            padding:16px 20px;
            margin:16px 0;
            border:1px solid #dcdcde;
            border-radius:8px;
            box-shadow:0 1px 2px rgba(0,0,0,0.04);
        ">';
    }

    private static function render_card_end() {
        echo '</div>';
    }
}
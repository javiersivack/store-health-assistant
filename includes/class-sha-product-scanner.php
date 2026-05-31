<?php

if (!defined('ABSPATH')) {
    exit;
}

class SHA_Product_Scanner {

    private static function get_published_products() {
        return wc_get_products([
            'status' => 'publish',
            'limit'  => -1,
            'return' => 'objects',
        ]);
    }

    public static function get_products_without_image() {
        return array_filter(self::get_published_products(), function ($product) {
            return !$product->get_image_id();
        });
    }

    public static function get_products_without_description() {
        return array_filter(self::get_published_products(), function ($product) {
            $description = trim(wp_strip_all_tags($product->get_description()));
            return empty($description);
        });
    }

    public static function get_products_without_price() {
        return array_filter(self::get_published_products(), function ($product) {
            $price = $product->get_price();
            return $price === '' || $price === null;
        });
    }

    public static function get_out_of_stock_products() {
        return array_filter(self::get_published_products(), function ($product) {
            return $product->get_stock_status() === 'outofstock';
        });
    }

    public static function get_low_stock_products() {
        return array_filter(self::get_published_products(), function ($product) {
            if (!$product->managing_stock()) {
                return false;
            }

            $stock_quantity = $product->get_stock_quantity();

            if ($stock_quantity === null) {
                return false;
            }

            return $stock_quantity > 0 && $stock_quantity <= 5;
        });
    }

    public static function get_products_never_sold() {
        return array_filter(self::get_published_products(), function ($product) {
            return (int) $product->get_total_sales() === 0;
        });
    }

    public static function get_store_health_score() {
        $score = 100;

        $score -= count(self::get_products_without_image()) * 5;
        $score -= count(self::get_products_without_description()) * 3;
        $score -= count(self::get_products_without_price()) * 8;
        $score -= count(self::get_out_of_stock_products()) * 4;
        $score -= count(self::get_low_stock_products()) * 2;
        $score -= count(self::get_products_never_sold()) * 1;

        if ($score < 0) {
            $score = 0;
        }

        return $score;
    }

    public static function get_priority_issues() {
        $issues = [];

        $products_without_image = self::get_products_without_image();

        if (!empty($products_without_image)) {
            $issues[] = [
                'title' => 'Products without images',
                'count' => count($products_without_image),
                'message' => 'Products without images may reduce conversions and customer trust.',
            ];
        }

        $products_without_description = self::get_products_without_description();

        if (!empty($products_without_description)) {
            $issues[] = [
                'title' => 'Products without descriptions',
                'count' => count($products_without_description),
                'message' => 'Products without descriptions may reduce SEO visibility and sales.',
            ];
        }

        $products_without_price = self::get_products_without_price();

        if (!empty($products_without_price)) {
            $issues[] = [
                'title' => 'Products without price',
                'count' => count($products_without_price),
                'message' => 'Products without price may prevent purchases.',
            ];
        }

        $out_of_stock_products = self::get_out_of_stock_products();

        if (!empty($out_of_stock_products)) {
            $issues[] = [
                'title' => 'Out of stock products',
                'count' => count($out_of_stock_products),
                'message' => 'Out of stock products may reduce potential revenue.',
            ];
        }

        $low_stock_products = self::get_low_stock_products();

        if (!empty($low_stock_products)) {
            $issues[] = [
                'title' => 'Low stock products',
                'count' => count($low_stock_products),
                'message' => 'Low stock products may need attention before they run out.',
            ];
        }

        $products_never_sold = self::get_products_never_sold();

        if (!empty($products_never_sold)) {
            $issues[] = [
                'title' => 'Products never sold',
                'count' => count($products_never_sold),
                'message' => 'Products with zero sales may need better images, descriptions, pricing, or promotion.',
            ];
        }

        return $issues;
    }
}
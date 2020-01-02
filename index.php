<?php
/**
 * @package Woocommerce_Price_Range
 * @version 1.0
 */
/*
Plugin Name: Woocommerce Price Range
Plugin URI: 
Description: fill me in
Author: Guy Lyons 
Version: 1.0
Author URI: https://yugsnoyl.com
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// process the post data for saving in the admin
function save_custom_field( $post_id ) {
  
  $custom_field_value = isset( $_POST['price_range'] ) ? $_POST['price_range'] : '';
  $product = wc_get_product( $post_id );
  $product->update_meta_data( 'price_range', $custom_field_value );
  $product->save();
}
add_action( 'woocommerce_process_product_meta', 'save_custom_field' );

// create input field in the product edit admin
function priceRangeAdminInputField() {
  global $post;

  woocommerce_wp_text_input(
    array(
      'id'        => 'price_range',
      'value'     =>  get_post_meta( $post->ID, 'price_range' )[0],
      'label'     => __( 'Price range', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')',
      'data_type' => 'price',
    )
  );

  return;
}
add_action( 'woocommerce_product_options_pricing',  'priceRangeAdminInputField' );

// the html to be used to display the price range to the user
function priceRangeHtml() {
  global $post;
  global $product;
  $price_range = get_post_meta( $post->ID, 'price_range' )[0];

  if ( !empty( $price_range ) ) {

    echo "<p>Sliding scale</p>";
    echo '
    <div>
      <span>Min: ' . $price_range . '</span>
      <input type="range" id="price-range-input" name="price-range-input" 
     	 min="' . $price_range . '" max="' . $product->price . '" value="' . $product->price . '" step="1">
      <span>Max: ' . $product->price . '
      <label id="price-range-label" for="price-range-input">
      </label>
    </div>
      ';
    }
}
add_action( 'woocommerce_single_product_summary', 'priceRangeHtml', 15);

function priceRangeHiddenField() {
  global $post;
  global $product;
  $price_range = get_post_meta( $post->ID, 'price_range' )[0];
  
  echo '
  <input type="hidden" id="priceRangeHidden" name="price-range" value="' . $product->price . '" />';

  echo '
    <script>
    var priceRangeInput = document.getElementById("price-range-input");
    var priceLabel = document.querySelector(".woocommerce-Price-amount");
    var priceRangeHidden = document.getElementById("priceRangeHidden");

    priceRangeInput.addEventListener("input", function(event) {
      var currencySymbol = "'.html_entity_decode(get_woocommerce_currency_symbol()).'";
      priceLabel.innerText = currencySymbol + this.value + ".00"; // TODO: make dynamic
      priceRangeHidden.value = this.value;
    });

    </script>
    ';
}
add_action( 'woocommerce_before_add_to_cart_button', 'priceRangeHiddenField' );

function test($cart_item_data) {
  if ( !empty( $_POST["price-range"] ) ) {
    $cart_item_data["price"] = $_POST["price-range"];

    function alter_price($price) {
      $price = '';
      $cart_item_data["price"] = $_POST["price-range"];
      $price = woocommerce_price($cart_item_data["price"]);
      return $price;
    }
    add_filter( 'woocommerce_get_price_html', 'alter_price', 100, 2 );
  }

  return $cart_item_data;

}
add_action( 'woocommerce_add_cart_item_data', 'test', 10, 3 );

function before_calculate_totals( $cart_obj ) {
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
    return;
  }
  // Iterate through each cart item
  foreach( $cart_obj->get_cart() as $key=>$value ) {
    if( isset( $value['price'] ) ) {
      $price = $value['price'];
      $value['data']->set_price( ( $price ) );
    }
  }
}
add_action( 'woocommerce_before_calculate_totals', 'before_calculate_totals', 10, 1 );
